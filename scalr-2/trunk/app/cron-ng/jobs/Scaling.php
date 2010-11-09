<?php

	class Scalr_Cronjob_Scaling extends Scalr_System_Cronjob_MultiProcess_DefaultWorker
	{
		static function getConfig () {
	        return array(
	        	"description" => "Roles scaling",        	
	        	"processPool" => array(
					"daemonize" => false,
	        		"workerMemoryLimit" => 40000,   // 40Mb       	
	        		"startupTimeout" => 10000, 		// 10 seconds
	        		"workTimeout" => 120000,		// 120 seconds
	        		"size" => 10					// 10 workers
	        	),
	    		"waitPrevComplete" => true,        		
				"fileName" => __FILE__,
	        );
		}
	    
		private $logger;
	        
		private $db;
	        
	    function __construct() {
	        $this->logger = Logger::getLogger(__CLASS__);
	        $this->db = Core::GetDBInstance();
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
		}        
	        
		function enqueueWork ($workQueue) {
			$this->logger->info("Fetching completed farms...");
	            
			$rows = $this->db->GetAll("SELECT farms.id FROM farms 
	            INNER JOIN clients ON clients.id = farms.clientid WHERE clients.isactive='1' AND farms.status=?",
	            array(FARM_STATUS::RUNNING)
			);
			foreach ($rows as $row) {
				$workQueue->put($row['id']);
			}
	                      
			$this->logger->info(sprintf("Found %d farms.", count($rows)));
		}
		
		function handleWork ($farmId) {
        	$dbFarm = DBFarm::LoadByID($farmId);
        	
            $GLOBALS["SUB_TRANSACTIONID"] = abs(crc32(posix_getpid().$farmId));
            $GLOBALS["LOGGER_FARMID"] = $farmId;
                        
                        
            if ($dbFarm->Status != FARM_STATUS::RUNNING)
            {
            	$this->logger->warn("[FarmID: {$dbFarm->ID}] Farm terminated. There is no need to scale it.");
            	return;
            }
			
            foreach ($dbFarm->GetFarmRoles() as $dbFarmRole)
            {            	
            	if ($dbFarmRole->NewRoleID != '')
            	{
            		$this->logger->warn("[FarmID: {$dbFarm->ID}] Role '{$dbFarmRole->GetRoleObject()->name}' being synchronized. This role will not be scalled.");
            		continue;
            	}
            	
            	// Get polling interval in seconds
            	$polling_interval = $dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_POLLING_INTERVAL)*60;
            	$dt_last_polling = $dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_LAST_POLLING_TIME);
            	if ($dt_last_polling && $dt_last_polling+$polling_interval > time())
            	{
            		$this->logger->info("Polling interval: every {$polling_interval} seconds");
            		//continue;
            	}
            	
            	// Set Last polling time
            	$dbFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_LAST_POLLING_TIME, time());

            	// Get current count of running and pending instances.
            	$this->logger->info(sprintf("Processing role '%s'", $dbFarmRole->GetRoleObject()->name));
            	
            	
            	$scalingManager = new Scalr_Scaling_Manager($dbFarmRole);
            	$scalingDecision = $scalingManager->makeScalingDecition();
	            	
            	if ($scalingDecision == Scalr_Scaling_Decision::STOP_SCALING)
            	{
            		return;
            	}
            	if ($scalingDecision == Scalr_Scaling_Decision::NOOP)
            	{
            		//TODO:
            	}
            	elseif ($scalingDecision == Scalr_Scaling_Decision::DOWNSCALE)
            	{	     
					/*
					 Timeout instance's count decrease. Decreases instance�s count after scaling 
					 resolution the spare instances are running�g for selected timeout interval
					 from scaling EditOptions							
					*/    
						
					// We have to check timeout limits before new scaling (downscaling) process will be initiated
					if($dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_DOWNSCALE_TIMEOUT_ENABLED))
					{   // if the farm timeout is exceeded
						// checking timeout interval.
						
						$last_down_scale_data_time =  strtotime($dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_DOWNSCALE_DATETIME, false)); 							
						$timeout_interval = $dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_DOWNSCALE_TIMEOUT);
						
						// check the time interval to continue scaling or cancel it...
						if(time() - $last_down_scale_data_time < $timeout_interval*60)
						{
							// if the launch time is too small to terminate smth in this role -> go to the next role in foreach()							
							Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($dbFarm->ID, 
										sprintf("Waiting due to downscaling timeout for farm %s, role %s",
											$dbFarm->Name,
											$dbFarmRole->GetRoleObject()->name
											)
										));
							continue;
						}
					} // end Timeout instance's count decrease         			
            		$sort = ($dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_KEEP_OLDEST) == 1) ? 'DESC' : 'ASC';
	            		
	            	$servers = $this->db->GetAll("SELECT server_id FROM servers WHERE status = ? AND farm_roleid=? ORDER BY dtadded {$sort}",
		            	array(SERVER_STATUS::RUNNING, $dbFarmRole->ID)
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
	                    $dbServer = DBServer::LoadByID($item['server_id']);
                        
                        // Exclude db master
                        if ($dbServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) != 1)
                        {
                        	/* 
                        	 * We do not want to delete the most recently synced instance. Because of LA fluctuation. 
                        	 * I.e. LA may skyrocket during sync and drop dramatically after sync.
                        	 */

                        	if ($dbServer->dateLastSync != 0)
                        	{
                        		$chk_sync_time = $this->db->GetOne("SELECT server_id FROM servers 
                        		WHERE dtlastsync > {$dbServer->dateLastSync} 
	                        	AND farm_roleid='{$dbServer->farmRoleId}' AND status != '".SERVER_STATUS::TERMINATED."'");
                        		if ($chk_sync_time)
                        			$got_valid_instance = true;
                        	}
                        	else
                        		$got_valid_instance = true;
	                    }
					}
                        
                    if ($dbServer && $got_valid_instance)
                    {
						$this->logger->info(sprintf("Server '%s' selected for termination...", $dbServer->serverId));
                       	$allow_terminate = false;

                       	if ($dbServer->platform == SERVER_PLATFORMS::EC2)
                       	{
	                        $ec2Client = Scalr_Service_Cloud_Aws::newEc2(
								$dbServer->GetProperty(EC2_SERVER_PROPERTIES::REGION),
								$dbServer->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
								$dbServer->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
							);
								
                        	// Shutdown an instance just before a full hour running 
		                    $response = $ec2Client->DescribeInstances($dbServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID));
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

                        			Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($farmId, sprintf("Farm %s, role %s scaling down (Algo: %s, Sensor value: %s). Server '%s' will be terminated in %s minutes. Launch time: %s",
                        				$dbFarm->Name,
                        				$dbServer->GetFarmRoleObject()->GetRoleObject()->name,
                        				get_class($ScalingAlgo),
                        				is_array($ScalingAlgo->lastSensorValue) ? serialize($ScalingAlgo->lastSensorValue) : $ScalingAlgo->lastSensorValue,
                        				$dbServer->serverId,
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
						    	Scalr::FireEvent($dbFarm->ID, new BeforeHostTerminateEvent($dbServer, false));
						            
						        Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($farmId, sprintf("Farm %s, role %s scaling down. Server '%s' marked as 'Pending terminate' and will be fully terminated in 3 minutes.",
                        			$dbFarm->Name,
                        			$dbServer->GetFarmRoleObject()->GetRoleObject()->name,
                        			$dbServer->serverId
                        		)));
							}
	                        catch (Exception $e)
	                        {
	                            $this->logger->fatal(sprintf("Cannot terminate %s: %s",
	                            	$dbFarm->ID,
	                            	$dbServer->serverId,
	                            	$e->getMessage()
	                            ));
	                        }
                        }
					}
                    else
						$this->logger->warn(sprintf("Scalr unable to determine what instance it should terminate. Skipping..."));
	                        
					break;
	            }
            	elseif ($scalingDecision == Scalr_Scaling_Decision::UPSCALE)
	            {
					/*
					Timeout instance's count increase. Increases  instance's count after 
					scaling resolution �need more instances� for selected timeout interval
					from scaling EditOptions						
					*/	            		
					if($dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_UPSCALE_TIMEOUT_ENABLED))
					{ 
						// if the farm timeout is exceeded
						// checking timeout interval.
						$last_up_scale_data_time =  strtotime($dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_UPSCALE_DATETIME, false)); 								
						$timeout_interval = $dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_UPSCALE_TIMEOUT);						
						
						// check the time interval to continue scaling or cancel it...
						if(time() - $last_up_scale_data_time < $timeout_interval*60)
						{
							// if the launch time is too small to terminate smth in this role -> go to the next role in foreach()							
							Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($dbFarm->ID, 
										sprintf("Waiting due to upscaling timeout for farm %s, role %s",
											$dbFarm->Name,
											$dbFarmRole->GetRoleObject()->name
											)
										));
							continue;									
						}
					}// end Timeout instance's count increase 
						
					$fstatus = $this->db->GetOne("SELECT status FROM farms WHERE id=?", array($dbFarm->ID));
		            if ($fstatus != FARM_STATUS::RUNNING)
		            {
		            	$this->logger->warn("[FarmID: {$dbFarm->ID}] Farm terminated. There is no need to scale it.");
		            	break;
		            }
	      			            
		            $serverCreInfo = new ServerCreateInfo($dbFarmRole->Platform, $dbFarmRole);
					try {
						$dbServer = Scalr::LaunchServer($serverCreInfo);
													
						Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($dbFarm->ID, sprintf("Farm %s, role %s scaling up. Starting new instance. ServerID = %s.", 
							$dbFarm->Name,
                        	$dbServer->GetFarmRoleObject()->GetRoleObject()->name,
                        	$dbServer->serverId
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
