<?
	class ScalingProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Scaling process";
        public $Logger;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = Logger::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
            
            $this->Logger->info("Fetching completed farms...");
            
            $this->ThreadArgs = $db->GetAll("SELECT farms.id FROM farms 
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
        	
        	$DBFarm = DBFarm::LoadByID($farminfo['id']);
        	
            define("SUB_TRANSACTIONID", posix_getpid());
            define("LOGGER_FARMID", $DBFarm->ID);
                        
                        
            if ($DBFarm->Status != FARM_STATUS::RUNNING)
            {
            	$this->Logger->warn("[FarmID: {$DBFarm->ID}] Farm terminated. There is no need to scale it.");
            	return;
            }
			
            foreach ($DBFarm->GetFarmRoles() as $DBFarmRole)
            {            	
            	if ($DBFarmRole->NewRoleID != '')
            	{
            		$this->Logger->warn("[FarmID: {$DBFarm->ID}] Role '{$DBFarmRole->GetRoleObject()->name}' being synchronized. This role will not be scalled.");
            		continue;
            	}
            	
            	// Get polling interval in seconds
            	$polling_interval = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_POLLING_INTERVAL)*60;
            	$dt_last_polling = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_LAST_POLLING_TIME);
            	if ($dt_last_polling && $dt_last_polling+$polling_interval > time())
            	{
            		$this->Logger->info("Polling interval: every {$polling_interval} seconds");
            		//continue;
            	}
            	
            	// Set Last polling time
            	$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_LAST_POLLING_TIME, time());

            	// Get current count of running and pending instances.
            	$this->Logger->info(sprintf("Processing role '%s'", $DBFarmRole->GetRoleObject()->name));
            	
            	
            	$scalingManager = new Scalr_Scaling_Manager($DBFarmRole);
            	$scalingDecision = $scalingManager->makeScalingDecition();
	            	
            	if ($scalingDecision == Scalr_Scaling_Decision::STOP_SCALING)
            	{
            		exit();
            	}
            	if ($scalingDecision == Scalr_Scaling_Decision::NOOP)
            	{
            		//TODO:
            	}
            	elseif ($scalingDecision == Scalr_Scaling_Decision::DOWNSCALE)
            	{	     
					/*
					 Timeout instance's count decrease. Decreases instanceï¿½s count after scaling 
					 resolution the spare instances are runningïg for selected timeout interval
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
							Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($DBFarm->ID, 
										sprintf("Waiting due to downscaling timeout for farm %s, role %s",
											$DBFarm->Name,
											$DBFarmRole->GetRoleObject()->name
											)
										));
							continue;
						}
					} // end Timeout instance's count decrease         			
            		$sort = ($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_KEEP_OLDEST) == 1) ? 'DESC' : 'ASC';
	            		
	            	$servers = $db->GetAll("SELECT server_id FROM servers WHERE status = ? AND farm_roleid=? ORDER BY dtadded {$sort}",
		            	array(SERVER_STATUS::RUNNING, $DBFarmRole->ID)
		            );
		            	
	            	$got_valid_instance = false;
		            	
                    // Select instance that will be terminated
                    //
                    // * Instances ordered by uptime (oldest wil be choosen)
                    // * Instance cannot be mysql master
                    // * Choose the one that was rebundled recently
                    while (!$got_valid_instance && count($servers) > 0)
                    {
                    	$item = array_shift($servers);
	                    $DBServer = DBServer::LoadByID($item['server_id']);
                        
                        // Exclude db master
                        if ($DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) != 1)
                        {
                        	/* 
                        	 * We do not want to delete the most recently synced instance. Because of LA fluctuation. 
                        	 * I.e. LA may skyrocket during sync and drop dramatically after sync.
                        	 */

                        	if ($DBServer->dateLastSync != 0)
                        	{
                        		$chk_sync_time = $db->GetOne("SELECT server_id FROM servers 
                        		WHERE dtlastsync > {$DBServer->dateLastSync} 
	                        	AND farm_roleid='{$DBServer->farmRoleId}' AND status != '".SERVER_STATUS::TERMINATED."'");
                        		if ($chk_sync_time)
                        			$got_valid_instance = true;
                        	}
                        	else
                        		$got_valid_instance = true;
	                    }
					}
                        
                    if ($DBServer && $got_valid_instance)
                    {
						$this->Logger->info(sprintf("Server '%s' selected for termination...", $DBServer->serverId));
                       	$allow_terminate = false;

                       	if ($DBServer->platform == SERVER_PLATFORMS::EC2)
                       	{
	                        $AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
								$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION),
								$DBServer->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
								$DBServer->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
							);
								
                        	// Shutdown an instance just before a full hour running 
		                    $response = $AmazonEC2Client->DescribeInstances($DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID));
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

                        			Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($farminfo['id'], sprintf("Farm %s, role %s scaling down (Algo: %s, Sensor value: %s). Server '%s' will be terminated in %s minutes. Launch time: %s",
                        				$DBFarm->Name,
                        				$DBServer->GetFarmRoleObject()->GetRoleObject()->name,
                        				get_class($ScalingAlgo),
                        				is_array($ScalingAlgo->lastSensorValue) ? serialize($ScalingAlgo->lastSensorValue) : $ScalingAlgo->lastSensorValue,
                        				$DBServer->serverId,
                        				$timeout,
                        				$response->reservationSet->item->instancesSet->item->launchTime
                        			)));
                        		}
	                        }
		                        //
                        }
                        else
                        	$allow_terminate = true;
                        	
                        if ($allow_terminate)
                        {                       
	                        try
	                        {		                            
						    	Scalr::FireEvent($DBFarm->ID, new BeforeHostTerminateEvent($DBServer, false));
						            
						        Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($farminfo['id'], sprintf("Farm %s, role %s scaling down. Server '%s' marked as 'Pending terminate' and will be fully terminated in 3 minutes.",
                        			$DBFarm->Name,
                        			$DBServer->GetFarmRoleObject()->GetRoleObject()->name,
                        			$DBServer->serverId
                        		)));
							}
	                        catch (Exception $e)
	                        {
	                            $this->Logger->fatal(sprintf("Cannot terminate %s: %s",
	                            	$DBFarm->ID,
	                            	$DBServer->serverId,
	                            	$e->getMessage()
	                            ));
	                        }
                        }
					}
                    else
						$this->Logger->warn(sprintf("Scalr unable to determine what instance it should terminate. Skipping..."));
	                        
					break;
	            }
            	elseif ($scalingDecision == Scalr_Scaling_Decision::UPSCALE)
	            {
					/*
					Timeout instance's count increase. Increases  instance's count after 
					scaling resolution ï¿½need more instancesï¿½ for selected timeout interval
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
							Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($DBFarm->ID, 
										sprintf("Waiting due to upscaling timeout for farm %s, role %s",
											$DBFarm->Name,
											$DBFarmRole->GetRoleObject()->name
											)
										));
							continue;									
						}
					}// end Timeout instance's count increase 
						
					$fstatus = $db->GetOne("SELECT status FROM farms WHERE id=?", array($DBFarm->ID));
		            if ($fstatus != FARM_STATUS::RUNNING)
		            {
		            	$this->Logger->warn("[FarmID: {$DBFarm->ID}] Farm terminated. There is no need to scale it.");
		            	break;
		            }
	      			            
		            $ServerCreateInfo = new ServerCreateInfo($DBFarmRole->Platform, $DBFarmRole);
					try {
						$DBServer = Scalr::LaunchServer($ServerCreateInfo);
													
						Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($DBFarm->ID, sprintf("Farm %s, role %s scaling up. Starting new instance. ServerID = %s.", 
							$DBFarm->Name,
                        	$DBServer->GetFarmRoleObject()->GetRoleObject()->name,
                        	$DBServer->serverId
						)));
					}
					catch(Exception $e){
						Logger::getLogger(LOG_CATEGORY::SCALING)->error($e->getMessage());
					}
			                                        
            		break;
            	}
            }
        }
    }
?>