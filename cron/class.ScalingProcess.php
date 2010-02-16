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
                        
            $farm_roles = $db->GetAll("SELECT ami_id, id, replace_to_ami FROM farm_roles WHERE farmid=? ORDER BY launch_index ASC", array($farminfo['id']));

            $Client = Client::Load($farminfo['clientid']);
            
            // Get AmazonEC2 Object
            $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region'])); 
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
            
			$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($farminfo['id']));
            if ($farminfo['status'] != FARM_STATUS::RUNNING)
            {
            	$this->Logger->warn("[FarmID: {$farminfo['id']}] Farm terminated. There is no need to scale it.");
            	return;
            }
			
            foreach ($farm_roles as $farm_ami)
            {
            	$DBFarmRole = DBFarmRole::LoadByID($farm_ami['id']);
            	
            	if ($DBFarmRole->ReplaceToAMI != '')
            	{
            		$this->Logger->warn("[FarmID: {$farminfo['id']}] Role '{$DBFarmRole->GetRoleName()}' being synchronized. This role will not be scalled.");
            		continue;
            	}
            	
            	// Get polling interval in seconds
            	$polling_interval = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_POLLING_INTERVAL)*60;
            	$dt_last_polling = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_LAST_POLLING_TIME);
            	if ($dt_last_polling && $dt_last_polling+$polling_interval > time())
            	{
            		$this->Logger->info("Polling interval: every {$polling_interval} seconds");
            		continue;
            	}
            	
            	// Set Last polling time
            	$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_LAST_POLLING_TIME, time());

            	// Get current count of running and pending instances.
            	$this->Logger->info(sprintf("Processing role '%s'", $DBFarmRole->GetRoleName()));
            	
            	$RoleScalingManager = new RoleScalingManager($DBFarmRole);            	
            	foreach ($RoleScalingManager->GetEnabledAlgos() as $ScalingAlgo)
            	{            		
            		$this->Logger->info(sprintf("Checking %s scaling algorithm...", get_class($ScalingAlgo)));
            		
            		$res = $ScalingAlgo->MakeDecision($DBFarmRole);
	            	$this->Logger->info(sprintf("%s result: %s", get_class($ScalingAlgo), $res));
	            	
	            	if ($res == ScalingAlgo::STOP_SCALING)
	            	{
	            		exit();
	            	}
	            	if ($res == ScalingAlgo::NOOP)
	            	{
	            		//TODO:
	            	}
	            	elseif ($res == ScalingAlgo::DOWNSCALE)
	            	{	     
						/*
						 Timeout instance's count decrease. Decreases instance�s count after scaling 
						 resolution �the spare instances are running� for selected timeout interval
						 from scaling EditOptions							
						*/    
						
						// We have to check timeout limits before new scaling (downscaling) process will be initiated
						if($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_DOWNSCALE_TIMEOUT_ENABLED))
						{   // if the farm timeout is exceeded
							// checking timeout interval.
							
							$last_down_scale_data_time =  strtotime($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_DOWNSCALE_DATETIME, false)); 							
							$timeout_interval = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_DOWNSCALE_TIMEOUT);
							
							// check the time interval to continue scaling or cancel it...
							if(time() - $last_down_scale_data_time < $timeout_interval*60)
							{
								// if the launch time is too small to terminate smth in this role -> go to the next role in foreach()							
								Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($farminfo['id'], 
											sprintf("Waiting due to downscaling timeout for farm %s, role %s",
												$farminfo['name'],
												$instanceinfo['role_name']
												)
											));
								continue;
							}
						} // end Timeout instance's count decrease         			
            			$sort = ($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_KEEP_OLDEST) == 1) ? 'DESC' : 'ASC';
	            		
	            		$instances = $db->GetAll("SELECT * FROM farm_instances WHERE state = ? AND farm_roleid=? ORDER BY dtadded {$sort}",
		            		array(INSTANCE_STATE::RUNNING, $DBFarmRole->ID)
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
		                        	AND farm_roleid='{$instanceinfo['farm_roleid']}' AND state != '".INSTANCE_STATE::TERMINATED."'");
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

                        			Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($farminfo['id'], sprintf("Farm %s, role %s scaling down (Algo: %s). Instance '%s' will be terminated in %s minutes. Launch time: %s",
                        				$farminfo['name'],
                        				$instanceinfo['role_name'],
                        				get_class($ScalingAlgo),
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
		                            Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($farminfo['id'], 
		                            	sprintf("Scheduled termination due to scaling down (Algo: %s) for instance %s (%s). It will be terminated in 3 minutes.",
		                            		get_class($ScalingAlgo),
		                            		$instanceinfo["instance_id"],
		                            		$instanceinfo["external_ip"]
		                            	)
		                            ));
		                            
						            Scalr::FireEvent($farminfo['id'], new BeforeHostTerminateEvent(DBInstance::LoadByID($instanceinfo['id']), false));
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
                        	$this->Logger->warn(sprintf("Scalr unable to determine what instance it should terminate. Skipping..."));
	                        
						break;
	            	}
            		elseif ($res == ScalingAlgo::UPSCALE)
	            	{
						/*
						Timeout instance's count increase. Increases  instance's count after 
						scaling resolution �need more instances� for selected timeout interval
						from scaling EditOptions						
						*/
						
						if($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_UPSCALE_TIMEOUT_ENABLED))
						{ 
							// if the farm timeout is exceeded
							// checking timeout interval.
							$last_up_scale_data_time =  strtotime($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_UPSCALE_DATETIME, false)); 								
							$timeout_interval = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_UPSCALE_TIMEOUT);						
							
							// check the time interval to continue scaling or cancel it...
							if(time() - $last_up_scale_data_time < $timeout_interval*60)
							{
								// if the launch time is too small to terminate smth in this role -> go to the next role in foreach()							
								Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($farminfo['id'], 
											sprintf("Waiting due to upscaling timeout for farm %s, role %s",
												$farminfo['name'],
												$instanceinfo['role_name']
												)
											));
								continue;									
							}
						}// end Timeout instance's count increase 
						
						$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($farminfo['id']));
			            if ($farminfo['status'] != FARM_STATUS::RUNNING)
			            {
			            	$this->Logger->warn("[FarmID: {$farminfo['id']}] Farm terminated. There is no need to scale it.");
			            	break;
			            }
	            		
	            		$instance_id = Scalr::RunInstance($DBFarmRole, false, false, true);                            
                        if ($instance_id)
							Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($farminfo['id'], sprintf("Starting new instance. InstanceID = %s.", $instance_id)));
            		
	            		break;
	            	}
            	}
            }
        }
    }
?>