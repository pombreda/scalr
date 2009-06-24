<?
	class ScalingProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Scaling process";
        public $Logger;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = LoggerManager::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
            
            $this->Logger->info("Fetching completed farms...");
            
            $this->ThreadArgs = $db->GetAll("SELECT farms.*, clients.isactive FROM farms 
            	INNER JOIN clients ON clients.id = farms.clientid WHERE clients.isactive='1' AND farms.status=?",
            	array(FARM_STATUS::RUNNING)
            );
                        
            $this->Logger->info("Found ".count($this->ThreadArgs)." farms.");
        }
        
        public function OnEndForking()
        {
			//$db = Core::GetDBInstance(null, true);
        }
        
        public function StartThread($farminfo)
        {
            // Reconfigure observers;
        	Scalr::ReconfigureObservers();
        	
        	$db = Core::GetDBInstance();
                        
            define("SUB_TRANSACTIONID", posix_getpid());
            define("LOGGER_FARMID", $farminfo["id"]);
            
            $this->Logger->info("Begin polling farm (ID: {$farminfo['id']}, Name: {$farminfo['name']}, Status: {$farminfo['status']})");
                        
            $farm_amis = $db->GetAll("SELECT ami_id, id, replace_to_ami FROM farm_amis WHERE farmid=?", array($farminfo['id']));

            $Client = Client::Load($farminfo['clientid']);
            
            // Get AmazonEC2 Object
            $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region'])); 
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
            
            foreach ($farm_amis as $farm_ami)
            {
            	$DBFarmRole = DBFarmRole::LoadByID($farm_ami['id']);
            	
            	if ($farm_ami['replace_to_ami'] != '')
            	{
            		$this->Logger->warn("[FarmID: {$farminfo['id']}] Role '{$DBFarmRole->GetRoleName()}' being synchronized. This role will not be scalled.");
            		continue;
            	}
            	
            	            	
            	// Get polling interval in seconds
            	$polling_interval = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_POLLING_INTERVAL)*60;
            	$dt_last_polling = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_LAST_POLLING_TIME);
            	if ($dt_last_polling && $dt_last_polling+$polling_interval > time())
            		continue;
            	
            	$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_LAST_POLLING_TIME, time());
            		
            	$this->Logger->info(sprintf("Processing role '%s'", $DBFarmRole->GetRoleName()));
            	
            	$running_instanses = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state = ? AND farmid=? AND (ami_id = ? OR ami_id=?)",
            		array(INSTANCE_STATE::RUNNING, $farminfo['id'], $farm_ami['ami_id'], $farm_ami['replace_to_ami'])
            	);
            	
            	$pending_instances = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state != ? AND farmid=? AND (ami_id = ? OR ami_id=?)",
            		array(INSTANCE_STATE::RUNNING, $farminfo['id'], $farm_ami['ami_id'], $farm_ami['replace_to_ami'])
            	);
            	
            	$RoleScalingManager = new RoleScalingManager($DBFarmRole);            	
            	foreach ($RoleScalingManager->GetEnabledAlgos() as $ScalingAlgo)
            	{            		
            		$this->Logger->info(sprintf("Checking %s scaling algorithm...", get_class($ScalingAlgo)));
            		
            		$res = $ScalingAlgo->MakeDecision($DBFarmRole);
	            	$this->Logger->info(sprintf("%s result: %s", get_class($ScalingAlgo), $res));
	            	
	            	if ($res == ScalingAlgo::NOOP)
	            	{
	            		//TODO:
	            	}
	            	elseif ($res == ScalingAlgo::DOWNSCALE)
	            	{
	            		if ($running_instanses > $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES))
	            		{
		            		//TODO: Fire some event.
	            			
	            			$instances = $db->GetAll("SELECT * FROM farm_instances WHERE state = ? AND farmid=? AND (ami_id = ? OR ami_id=?) ORDER BY dtadded ASC",
			            		array(INSTANCE_STATE::RUNNING, $farminfo['id'], $farm_ami['ami_id'], $farm_ami['replace_to_ami'])
			            	);
			            	
			            	$got_valid_instance = false;
			            	
	                    	// Select instance that will be terminated
	                        //
	                        // * Instances ordered by uptime (oldest wil be choosen)
	                        // * Instance cannot be mysql master
	                        // * Choose the one that was rebundled recently
	                    	while (!$got_valid_instance && count($instances) > 0)
	                        {
	                    		$item = array_shift($instances);
		                        $instanceinfo = $item;
		                        
		                        // Exclude db master
		                        if ($instanceinfo["isdbmaster"] != 1)
		                        {
		                        	/* 
		                        	 * We do not want to delete the most recently synced instance. Because of LA fluctuation. 
		                        	 * I.e. LA may skyrocket during sync and drop dramatically after sync.
		                        	 */
	
		                        	if ($instanceinfo["dtlastsync"] != 0)
		                        	{
		                        		$chk_sync_time = $db->GetOne("SELECT id FROM farm_instances 
		                        		WHERE dtlastsync > {$instanceinfo['dtlastsync']} 
			                        		AND farmid='{$instanceinfo['farmid']}' 
			                        		AND ami_id='{$instanceinfo['ami_id']}'");
		                        		if ($chk_sync_time)
		                        			$got_valid_instance = true;
		                        	}
		                        	else
		                        		$got_valid_instance = true;
		                        }
	                        }
	                        
	                        if ($instanceinfo && $got_valid_instance)
	                        {
								$this->Logger->info(sprintf("Instance '%s' selected for termination...", $instanceinfo['instance_id']));
	                        	$allow_terminate = false;
	                        	
	                        	
	                        	
	                        	// Shutdown an instance just before a full hour running 
		                        $response = $AmazonEC2Client->DescribeInstances($instanceinfo['instance_id']);
		                        if ($response && $response->reservationSet->item)
		                        {
	                        		$launch_time = strtotime($response->reservationSet->item->instancesSet->item->launchTime);
	                        		$time = 3600 - (time() - $launch_time) % 3600;
	                        		
	                        		// Terminate instance in < 10 minutes for full hour. 
	                        		if ($time <= 600)
	                        			$allow_terminate = true;
	                        		else
	                        		{
	                        			$timeout = round(($time - 600) / 60, 1);
	                        			//
	                        			
	                        			$cur_date = date("Y-m-d H:i:s");
	                        			
	                        			$this->Logger->info(new FarmLogMessage($farminfo['id'], sprintf("Farm %s, role %s scaling down. Instance '%s' will be terminated in %s minutes. Launch time: %s",
	                        				$farminfo['name'],
	                        				$instanceinfo['role_name'],
	                        				$instanceinfo['instance_id'],
	                        				$timeout,
	                        				$response->reservationSet->item->instancesSet->item->launchTime
	                        			)));
	                        		}
		                        }
		                        //
	                        	
	                        	if ($allow_terminate)
	                        	{                       
			                        try
			                        {
			                            $this->Logger->info(new FarmLogMessage($farminfo['id'], 
			                            	sprintf("Scheduled termination for instance %s (%s). It will be terminated in 3 minutes.",
			                            		$instanceinfo["instance_id"],
			                            		$instanceinfo["external_ip"]
			                            	)
			                            ));
			                            
							            Scalr::FireEvent($farminfo['id'], new BeforeHostTerminateEvent($instanceinfo));
			                        }
			                        catch (Exception $e)
			                        {
			                            $this->Logger->fatal(sprintf("Cannot terminate %s: %s",
			                            	$farminfo['id'],
			                            	$instanceinfo['instance_id'],
			                            	$e->getMessage()
			                            ));
			                        }
	                        	}
	                        }
	                        else
	                        {
	                        	$this->Logger->warn(sprintf("Cannot determine what instance we should terminate. Skipping..."));
	                        }
	                        
	                        break;
	            		}
	            		/*
	            		else
	            		{
	            			// Add entry to farm log
                        	if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES) > 0)
                        	{
                    			$this->Logger->debug(new FarmLogMessage($farminfo['id'], 
                    				sprintf("Role %s is idle, but needs at least %s instance(s), currently running: %s instance(s).",
                    					$DBFarmRole->GetRoleName(),
                    					$DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES),
                    					$running_instanses
                    				)
                    			));
                        	}
	            		}
	            		*/
	            	}
            		elseif ($res == ScalingAlgo::UPSCALE)
	            	{
	            		if($running_instanses < $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES))
	            		{
		            		if ($pending_instances > 0)
	                        {
	                            // Add entry to farm log
	                    		$this->Logger->info(new FarmLogMessage($farminfo['id'], "{$pending_instances} instances in pending state. We don't need more instances at this time."));
	                        }
	                        else 
	                        {
	                            $instance_id = Scalr::RunInstance(CONFIG::$SECGROUP_PREFIX.$DBFarmRole->GetRoleName(), $farminfo["id"], $DBFarmRole->GetRoleName(), $farminfo["hash"], $DBFarmRole->AMIID, false, true);
	                            
	                            if ($instance_id)
	                                $this->Logger->info(new FarmLogMessage($farminfo['id'], sprintf("Starting new instance. InstanceID = %s.", $instance_id)));
	                        }
	            		}
	            		
	            		/*
		            	else
	                    {
	                        // Add entry to farm log
	                    	$this->Logger->debug(new FarmLogMessage($farminfo['id'], sprintf("Role %s is full. MaxInstances (%s) = Instances count (%s). Pending: %s instances",
	                    		$DBFarmRole->GetRoleName(),
	                    		$DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES),
	                    		$running_instanses,
	                    		$pending_instances
	                    	)));
	                    }
	            		*/
	            		
	            		break;
	            	}
            	}
            }
        }
    }
?>