<?
	Core::Load("NET/SNMP");
	Core::Load("Data/RRD");
	
	require_once(dirname(__FILE__)."/watchers/class.SNMPWatcher.php");
	require_once(dirname(__FILE__)."/watchers/class.CPUSNMPWatcher.php");
	require_once(dirname(__FILE__)."/watchers/class.LASNMPWatcher.php");
	require_once(dirname(__FILE__)."/watchers/class.MEMSNMPWatcher.php");
	require_once(dirname(__FILE__)."/watchers/class.NETSNMPWatcher.php");
	
	class SNMPStatsPollerProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "SNMP stats poller";
        public $Logger;
        private $Watchers;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = LoggerManager::getLogger(__CLASS__);
        	
        	// Active watchers
        	// key = watcher_name, value = use average value for varm and role
        	$this->Watchers = array("CPUSNMP" => true, "MEMSNMP" => true, "LASNMP" => true, "NETSNMP" => false);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance(null, true);
            
            $this->Logger->info("Fetching completed farms...");
            
            $this->ThreadArgs = $db->GetAll("SELECT farms.*, clients.isactive FROM farms 
            	INNER JOIN clients ON clients.id = farms.clientid 
            	WHERE farms.status='1' AND clients.isactive='1'");
                        
            $this->Logger->info("Found ".count($this->ThreadArgs)." farms.");
        }
        
        public function OnEndForking()
        {
			
        }
        
        public function StartThread($farminfo)
        {
            $db = Core::GetDBInstance(null, true);
            $SNMP = new SNMP();
            
            define("SUB_TRANSACTIONID", posix_getpid());
            define("LOGGER_FARMID", $farminfo["id"]);
            
            $this->Logger->info("[".SUB_TRANSACTIONID."] Begin polling farm (ID: {$farminfo['id']}, Name: {$farminfo['name']})");
            
            //
            // Check farm status
            //
            if ($db->GetOne("SELECT status FROM farms WHERE id=?", array($farminfo["id"])) != 1)
            {
            	$this->Logger->error("[FarmID: {$farminfo['id']}] Farm terminated by client.");
            	return;
            }
            
            //
            // Collect information from database
            //
            $this->Logger->info("[FarmID: {$farminfo['id']}] Begin polling...");
			
            $SNMP_community = $farminfo["hash"];
            
            // Check data folder for farm            
            if (!file_exists(APPPATH."/data/{$farminfo['id']}"))
            	mkdir(APPPATH."/data/{$farminfo['id']}", 0777);
            	
           	// SNMP Watcher instance
            $Watcher = new SNMPWatcher($SNMP_community, $farminfo['id']);

            $farm_data = array();
            
            // Get all farm amis
            $farm_amis = $db->GetAll("SELECT * FROM farm_amis WHERE farmid=?", array($farminfo["id"]));
            foreach ($farm_amis as $farm_ami)
            {
            	$ami_data = array();
            	$ami_icnt = 0;
            	
            	// Get Role name
            	$farm_ami["role_name"] = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id=?",
            		array($farm_ami["ami_id"])
            	);
            	
            	// Get instances
            	$ami_instances = $db->GetAll("SELECT * FROM farm_instances 
            		WHERE ami_id = ? AND farmid=?", 
            		array($farm_ami["ami_id"], $farminfo["id"])
            	);
            	
            	// Watch SNMP values fro each instance
            	foreach ($ami_instances as $ami_instance)
            	{
            		// Connect to SNMP
            		$Watcher->Connect($ami_instance['external_ip']);
            		
            		foreach (array_keys($this->Watchers) as $watcher_name)
            		{            			
            			// Get data
            			$data = $Watcher->RetreiveData($watcher_name);
            			
            			$this->Logger->info("Retrieved data from {$ami_instance['external_ip']} ($watcher_name): ".implode(", ", $data));
            			
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
            		if ($this->Watchers[$watcher_name])
            		{
            			foreach ($data as &$ditem)
            				$ditem = round($ditem/$ami_icnt, 2);
            		}
            		
            		$this->Logger->info("Data for role {$farm_ami["role_name"]} ($watcher_name): ".implode(", ", $data));
            			
            		// Update RRD database for role
            		$Watcher->UpdateRRDDatabase($watcher_name, $data, $farm_ami["role_name"]);
            		
            		// Plot graphic for role
            		$Watcher->PlotGraphic($watcher_name, $farm_ami["role_name"]);
            	}
            }
            
            // Update data and build graphic for farm
        	foreach ($farm_data as $watcher_name => $data)
            {
            	// if true count average value value
            	if ($this->Watchers[$watcher_name])
            	{
            		foreach ($data as &$ditem)
            			$ditem = round($ditem/$farm_icnt, 2);
            	}
            	
            	$this->Logger->info("Data for farm ($watcher_name): ".implode(", ", $data));
            		
            	// Update farm RRD database
            	$Watcher->UpdateRRDDatabase($watcher_name, $data, "_FARM");
            	
            	// Plot graphic for FARM
            	$Watcher->PlotGraphic($watcher_name, "_FARM");
            }
        }
    }
?>