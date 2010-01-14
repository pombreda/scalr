<?php

	Core::Load("Data/RRD");
	
	require_once(APPPATH . "/cron/watchers/class.SNMPWatcher.php");
	require_once(APPPATH . "/cron/watchers/class.CPUSNMPWatcher.php");
	require_once(APPPATH . "/cron/watchers/class.LASNMPWatcher.php");
	require_once(APPPATH . "/cron/watchers/class.MEMSNMPWatcher.php");
	require_once(APPPATH . "/cron/watchers/class.NETSNMPWatcher.php");
	
	class Scalr_Cronjob_SNMPStatsPoller extends Scalr_System_Cronjob_MultiProcess_DefaultWorker
    {
    	static function getConfig () {
    		return array(
    			"description" => "SNMP stats poller",
    			"processPool" => array(
					"daemonize" => true,
					"preventParalleling" => true, 
    				"workerMemoryLimit" => 40000,   		
    				"size" => 10,
    				"startupTimeout" => 10000 // 10 seconds
    			),
    			"fileName" => __FILE__,
    			"memoryLimit" => 300000
    		);
    	}
    	
        private $logger;
        private $tmpLogger;
        private $watchers;
        private $snmpWatcher;
        private $db;
        
        public function __construct() {
        	$this->logger = LoggerManager::getLogger(__CLASS__);
        	$this->tmpLogger = LoggerManager::getLogger("Scalr_System");
        	$this->db = Core::GetDBInstance();
        	
        	// key = watcher_name, value = use average value for varm and role
        	$this->watchers = array("CPUSNMP" => true, "MEMSNMP" => true, 
        			"LASNMP" => true, "NETSNMP" => false);
        }
        
        function startForking ($workQueue) {
        	// Reopen DB connection after daemonizing
        	$this->db = Core::GetDBInstance(null, true);
        }
        
        function startChild () {
        	// Reopen DB connection in child
        	$this->db = Core::GetDBInstance(null, true);
        	// Reconfigure observers;
        	Scalr::ReconfigureObservers();
        	
        	$this->snmpWatcher = new SNMPWatcher();
        }        
        
        function enqueueWork ($workQueue) {
            $this->logger->info("Fetching completed farms...");
            
            $rows = $this->db->GetAll("SELECT farms.*, clients.isactive FROM farms 
            	INNER JOIN clients ON clients.id = farms.clientid 
            	WHERE farms.status='1' AND clients.isactive='1'");
            $this->logger->info("Found ".count($rows)." farms");            
            
            foreach ($rows as $row) {
            	$workQueue->put($row["id"]);
            }
        }
        
        function handleWork ($farmId) {
        	$farminfo = $this->db->GetRow("SELECT hash, name FROM farms WHERE id=?", array($farmId));
            
            $GLOBALS["SUB_TRANSACTIONID"] = abs(crc32(posix_getpid().$farmId));
            $GLOBALS["LOGGER_FARMID"] = $farmId;
            
            $this->logger->info("[{$GLOBALS["SUB_TRANSACTIONID"]}] Begin polling farm (ID: {$farmId}, Name: {$farminfo['name']})");
            
            //
            // Check farm status
            //
            if ($this->db->GetOne("SELECT status FROM farms WHERE id=?", array($farmId)) != 1)
            {
            	$this->logger->warn("[FarmID: {$farmId}] Farm terminated by client.");
            	return;
            }
            
            //
            // Collect information from database
            //			
                        
            // Check data folder for farm
			$farm_rrddb_dir = CONFIG::$RRD_DB_DIR."/{$farmId}";
            
            if (!file_exists($farm_rrddb_dir))
            {
            	mkdir($farm_rrddb_dir, 0777);
            	chmod($farm_rrddb_dir, 0777);
            }
            	
           	// SNMP Watcher config
            $this->snmpWatcher->SetConfig($farminfo["hash"], $farm_rrddb_dir);

            $farm_data = array();
            
            // Get all farm roles
            $farm_roles = $this->db->GetAll("SELECT id, ami_id FROM farm_roles WHERE farmid=?", array($farmId));
            foreach ($farm_roles as $farm_ami)
            {
            	$ami_data = array();
            	$ami_icnt = 0;
            	
            	// Get Role name
            	$farm_ami["role_name"] = $this->db->GetOne("SELECT name FROM roles WHERE ami_id=?",
            		array($farm_ami["ami_id"])
            	);
            	
            	// Get instances
            	$ami_instances = $this->db->GetAll("SELECT state, external_ip FROM farm_instances WHERE farm_roleid=?", 
            		array($farm_ami["id"])
            	);
            	
            	// Watch SNMP values fro each instance
            	foreach ($ami_instances as $ami_instance)
            	{
            		if ($ami_instance['state'] == INSTANCE_STATE::PENDING_TERMINATE || $ami_instance['state'] == INSTANCE_STATE::TERMINATED)
            			continue;
            		
            		// Connect to SNMP
            		$this->snmpWatcher->Connect($ami_instance['external_ip']);
            		
            		foreach (array_keys($this->watchers) as $watcher_name)
            		{            			
            			// Get data
            			$data = $this->snmpWatcher->RetreiveData($watcher_name);
            			
            			$this->logger->info("Retrieved data from {$ami_instance['external_ip']} ($watcher_name): ".implode(", ", $data));
            			
            			 
            			if ($data[0] == '')
            			{
            				break 2;
            			}
            			
            			// Collect data
            			foreach($data as $k=>$v)
            			{
            				$ami_data[$watcher_name][$k] += $v;
            				$farm_data[$watcher_name][$k] += $v;
            			}
            		}
            		
            		$ami_icnt++;
            		$farm_icnt++;
            	}
            	
            	//Update data and build graphic for role
            	foreach ($ami_data as $watcher_name => $data)
            	{
            		// if true count average value value
            		if ($this->watchers[$watcher_name])
            		{
            			foreach ($data as &$ditem)
            				$ditem = round($ditem/$ami_icnt, 2);
            		}
            		
            		$this->logger->info("Data for role {$farm_ami["role_name"]} ($watcher_name): ".implode(", ", $data));
            			
            		 if ($data[0] === '' || $data[0] === false || $data[0] === null)
            			break 1;
            		
            		try
            		{
	            		// Update RRD database for role
	            		$this->snmpWatcher->UpdateRRDDatabase($watcher_name, $data, $farm_ami["role_name"]);
            		}
            		catch(Exception $e)
            		{
            			$this->logger->error("RRD Update for {$watcher_name} on role {$farm_ami["role_name"]} failed. {$e->getMessage()}");
            		}
            	}
            }
            
            // Update data and build graphic for farm
        	foreach ($farm_data as $watcher_name => $data)
            {
            	// if true count average value value
            	if ($this->watchers[$watcher_name])
            	{
            		foreach ($data as &$ditem)
            			$ditem = round($ditem/$farm_icnt, 2);
            	}
            	
            	if ($farmId == 2758)
            	{
            		$this->tmpLogger->info("FarmUsage(2758, {$watcher_name}): ".implode(", ", $data));
            	}
            	
            	if ($data[0] === '' || $data[0] === false || $data[0] === null)
            	{
            		$this->tmpLogger->info("continue...");
            		continue;
            	}
            	
            	$this->logger->info("Data for farm ($watcher_name): ".implode(", ", $data));
            	
            	try
            	{
	            	// Update farm RRD database
	            	$this->snmpWatcher->UpdateRRDDatabase($watcher_name, $data, "_FARM");
            	}
            	catch(Exception $e)
            	{
            		$this->logger->error("RRD Update for farm failed. {$e->getMessage()}");
            	}
            }
        }
    }
