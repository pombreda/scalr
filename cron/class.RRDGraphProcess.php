<?
	Core::Load("NET/SNMP");
	Core::Load("Data/RRD");
	
	require_once(dirname(__FILE__)."/watchers/class.SNMPWatcher.php");
	require_once(dirname(__FILE__)."/watchers/class.CPUSNMPWatcher.php");
	require_once(dirname(__FILE__)."/watchers/class.LASNMPWatcher.php");
	require_once(dirname(__FILE__)."/watchers/class.MEMSNMPWatcher.php");
	require_once(dirname(__FILE__)."/watchers/class.NETSNMPWatcher.php");
	
	class RRDGraphProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Stats graphics generator";
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
            $db = Core::GetDBInstance();
            
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
        	// Reconfigure observers;
        	Scalr::ReconfigureObservers();
        	
        	$db = Core::GetDBInstance();
            $SNMP = new SNMP();
            
            define("SUB_TRANSACTIONID", posix_getpid());
            define("LOGGER_FARMID", $farminfo["id"]);
            
            $this->Logger->info("[".SUB_TRANSACTIONID."] Creating stats graphics for farm (ID: {$farminfo['id']}, Name: {$farminfo['name']})");
            
            //
            // Check farm status
            //
            if ($db->GetOne("SELECT status FROM farms WHERE id=?", array($farminfo["id"])) != 1)
            {
            	$this->Logger->error("[FarmID: {$farminfo['id']}] Farm terminated by client.");
            	return;
            }
            
            $farm_rrddb_dir = CONFIG::$RRD_DB_DIR."/{$farminfo['id']}";
            
            $farm_amis = $db->GetAll("SELECT ami_roles.name as name FROM farm_amis
            	INNER JOIN ami_roles ON ami_roles.ami_id = farm_amis.ami_id 
            	WHERE farmid=?", array($farminfo["id"])
            );
            
            $farm_amis[] = array("name" => "_FARM");            
            foreach ($farm_amis as $farm_ami)
            {
            	// Get Role name
            	$role_name = $farm_ami["name"];
            	
            	foreach (array_keys($this->Watchers) as $watchername)
            	{
            		$rrddbpath = "{$farm_rrddb_dir}/{$role_name}/{$watchername}/db.rrd";
            		if (file_exists($rrddbpath))
            		{
            			$Reflect = new ReflectionClass("GRAPH_TYPE");
            			$types = $Reflect->getConstants();
            			foreach($types as $type)
            				$this->GenerateGraph($farminfo['id'], $role_name, $rrddbpath, $watchername, $type);
            		}
            		else
            			$this->Logger->info("RRD database for role {$role_name} and watcher {$watchername} not created yet.");
            	}
            }
        }
        
        private function GenerateGraph($farmid, $role_name, $rrddbpath, $watchername, $graph_type)
        {
        	
        	$image_path = APPPATH."/cache/stats/{$farmid}/{$role_name}.{$watchername}.{$graph_type}.gif";
        	@mkdir(dirname($image_path), 0777, true);

        	$graph_info = $this->GetGraphicInfo($graph_type);
        	
        	if (file_exists($image_path))
        	{
        		clearstatcache();
		        $time = filemtime($image_path);
		        
		        if ($time > time()-$graph_info['update_every'])
		        	return;
        	}
        	
            // Plot daily graphic
            try
            {
            	$Reflect = new ReflectionClass("{$watchername}Watcher");
            	$PlotGraphicMethod = $Reflect->getMethod("PlotGraphic");
            	$PlotGraphicMethod->invoke(NULL, $rrddbpath, $image_path, $graph_info);
            }
            catch(Exception $e)
            {
            	$this->Logger->fatal("Cannot plot graphic: {$e->getMessage()}");
            	return;
            }
            
            // Save graphic
            if (CONFIG::$RRD_GRAPH_STORAGE_TYPE == RRD_STORAGE_TYPE::AMAZON_S3)
            {
            	$this->Logger->debug("Store graphic on amazon S3: Bucket: ".CONFIG::$RRD_GRAPH_STORAGE_PATH);
            	
            	// Get S3Client object
            	$S3Client = new AmazonS3(CONFIG::$AWS_ACCESSKEY_ID, CONFIG::$AWS_ACCESSKEY);
            	
            	// Check statistics folder for farm. If not exists - create it
            	try
            	{
            		$S3Client->GetObjectMetaData("{$farmid}_\$folder\$", CONFIG::$RRD_GRAPH_STORAGE_PATH);
            	}
            	catch(Exception $e)
            	{
            		// folder not exists try to create
            		try
            		{
            			$S3Client->CreateFolder($farmid, CONFIG::$RRD_GRAPH_STORAGE_PATH);
            		}
            		catch(Exception $e)
            		{
            			$this->Logger->fatal("Cannot create folder for farm statistics. {$e->getMessage()}");
            			exit;
            		}
            	}
            	
            	try
            	{
            		// Upload graph to S3
            		$S3Client->CreateObject(
            			"{$farmid}/{$role_name}_{$watchername}.{$graph_type}.gif",
            			CONFIG::$RRD_GRAPH_STORAGE_PATH,
            			$image_path,
            			"image/gif" 
            		);
            	}
            	catch (Exception $e)
            	{
            		$this->Logger->fatal("Cannot upload graph. {$e->getMessage()}");
            		exit;
            	}
            }
            else
            {
            	// Copy image from temporary dir to storage
            	$new_path = CONFIG::$RRD_GRAPH_STORAGE_PATH."/{$farmid}/{$role_name}_{$watchername}.{$graph_type}.gif";
            	@mkdir(dirname($new_path));
            	@copy($image_path, $new_path);
            }
        }
        
        private function GetGraphicInfo($type)
        {
        	switch($type)
            {
            	case GRAPH_TYPE::DAILY:
            		$r = array(
            			"start" => "-1d5min", 
            			"end" => "-5min", 
            			"step" => 60, 
            			"update_every" => 300,
            			"x_grid" => "HOUR:1:HOUR:2:HOUR:2:0:%H"
            		);
            		break;
            	case GRAPH_TYPE::WEEKLY:
            		$r = array(
            			"start" => "-1wk5min", 
            			"end" => "-5min", 
            			"step" => 1800, 
            			"update_every" => 7200,
            			"x_grid" => "HOUR:12:HOUR:24:HOUR:24:0:%a"
            		);
            		break;
            	case GRAPH_TYPE::MONTHLY:
            		$r = array(
            			"start" => "-1mon5min", 
            			"end" => "-5min", 
            			"step" => 7200, 
            			"update_every" => 43200,
            			"x_grid" => "DAY:2:WEEK:1:WEEK:1:0:week %V"
            		);
            		break;
            	case GRAPH_TYPE::YEARLY:
            		$r = array(
            			"start" => "-1y", 
            			"end" => false, 
            			"step" => 86400, 
            			"update_every" => 86400,
            			"x_grid" => "MONTH:1:MONTH:1:MONTH:1:0:%b"
            		);
            		break;
            }
            
            return $r;
        }
    }
?>