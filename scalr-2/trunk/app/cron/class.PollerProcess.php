<?
	class PollerProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Main poller";
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
            	INNER JOIN clients ON clients.id = farms.clientid WHERE clients.isactive='1'"
            );
                        
            $this->Logger->info("Found ".count($this->ThreadArgs)." farms.");
        }
        
        public function OnEndForking()
        {
			$db = Core::GetDBInstance(null, true);
        	
			// Reconfigure observers;
        	Scalr::ReconfigureObservers();
			
			$trap_wait_timeout = 240; // 120 seconds
			
			try
			{	            
	            $terminated_servers = $db->GetAll("SELECT server_id FROM servers WHERE status=? AND (UNIX_TIMESTAMP(dtshutdownscheduled)+3600 < UNIX_TIMESTAMP(NOW()) OR dtshutdownscheduled IS NULL)", 
	            	array(SERVER_STATUS::TERMINATED)
	            );
	            foreach ($terminated_servers as $ts)
	            	DBServer::LoadByID($ts['server_id'])->Remove();
	            	
	            $importing_servers = $db->GetAll("SELECT server_id FROM servers WHERE status=? AND UNIX_TIMESTAMP(dtadded)+86400 < UNIX_TIMESTAMP(NOW())", 
	            	array(SERVER_STATUS::IMPORTING)
	            );	
	            foreach ($importing_servers as $ts)
	            	DBServer::LoadByID($ts['server_id'])->Remove();
	            
	            $pending_launch_servers = $db->GetAll("SELECT server_id FROM servers WHERE status=?", array(SERVER_STATUS::PENDING_LAUNCH));
	            try
				{
		            foreach ($pending_launch_servers as $ts)
		            {
						$DBServer = DBServer::LoadByID($ts['server_id']);
						Scalr::LaunchServer(null, $DBServer);
		            }
		        }
				catch(Exception $e)
				{
					Logger::getLogger(LOG_CATEGORY::FARM)->error(sprintf("Can't load server with ID #'%s'", 
	                	$ts['server_id'],
	                	$e->getMessage()
	                ));
				}
		            
			}
			catch (Exception $e)
			{
				$this->Logger->fatal("Poller::OnEndForking failed: {$e->getMessage()}");
			}
        }
        
        public function StartThread($farminfo)
        {
            // Reconfigure observers;
        	Scalr::ReconfigureObservers();
        	
        	$db = Core::GetDBInstance();
            
            $DBFarm = DBFarm::LoadByID($farminfo['id']);
            
            define("SUB_TRANSACTIONID", posix_getpid());
            define("LOGGER_FARMID", $DBFarm->ID);
            
            $this->Logger->info("[".SUB_TRANSACTIONID."] Begin polling farm (ID: {$DBFarm->ID}, Name: {$DBFarm->Name}, Status: {$DBFarm->Status})");
            
            //
            // Collect information from database
            //
            $Client = Client::Load($DBFarm->ClientID);

            $servers_count = $db->GetOne("SELECT COUNT(*) FROM servers WHERE farm_id = ? AND status != ?", 
            	array($DBFarm->ID, SERVER_STATUS::TERMINATED)
            );
            $this->Logger->info("[FarmID: {$DBFarm->ID}] Found {$servers_count} farm instances in database");

            if ($DBFarm->Status == FARM_STATUS::TERMINATED && $servers_count == 0)
            	exit();
  
            foreach ($DBFarm->GetServersByFilter(array(), array('status' => SERVER_STATUS::PENDING_LAUNCH)) as $DBServer)
            {
            	try {
	            	if (!PlatformFactory::NewPlatform($DBServer->platform)->IsServerExists($DBServer))
	                {
	                	if ($DBServer->status != SERVER_STATUS::TERMINATED && $DBServer->status != SERVER_STATUS::PENDING_TERMINATE)
	                	{
		                	$DBServer->SetProperty(SERVER_PROPERTIES::REBOOTING, 0);
	                		
	                		// Add entry to farm log
		                    Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($DBFarm->ID, 
		                    	sprintf("Server '%s' found in database but not found on {$DBServer->platform}. Crashed.", $DBServer->serverId)
		                    ));
		                	Scalr::FireEvent($DBFarm->ID, new HostCrashEvent($DBServer));
		                	continue;
	                	}
	                }
            	}
            	catch(Exception $e)
            	{
            		if (stristr($e->getMessage(), "AWS was not able to validate the provided access credentials"))
            			exit();
            			
            		throw $e;
            	}
                
                if ($DBServer->GetRealStatus()->isTerminated() && $DBServer->status != SERVER_STATUS::TERMINATED)
                {
                    Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($DBFarm->ID, 
                    	sprintf("Server '%s' (Platform: %s) not running (Real state: %s).", $DBServer->serverId, $DBServer->platform, $DBServer->GetRealStatus()->getName())
                    ));
                	
                    $DBServer->SetProperty(SERVER_PROPERTIES::REBOOTING, 0);
                    
                	Scalr::FireEvent($DBFarm->ID, new HostDownEvent($DBServer));
                	continue;
                }
                elseif ($DBServer->GetRealStatus()->IsRunning() && $DBServer->status != SERVER_STATUS::RUNNING)
                {
                	if ($DBServer->status != SERVER_STATUS::TERMINATED)
                	{
	                	if ($DBServer->platform == SERVER_PLATFORMS::RDS)
	                	{
	                		//TODO: timeouts
	                		
	                		if ($DBServer->status == SERVER_STATUS::PENDING)
	                		{
	                			$info = PlatformFactory::NewPlatform($DBServer->platform)->GetServerIPAddresses($DBServer);
	                			$event = new HostInitEvent(
									$DBServer, 
									$info['localIp'],
									$info['remoteIp'],
									''
								);	
	                		}
	                		elseif ($DBServer->status == SERVER_STATUS::INIT)
	                		{
	                			$event = new HostUpEvent($DBServer, ""); // TODO: add mysql replication password
	                		}
	                		
	                		if ($event)
	                			Scalr::FireEvent($DBServer->farmId, $event);
	                		else
	                		{
	                			//TODO: Log
	                		}
	                	}
	                	else {
		                	$dtadded = strtotime($DBServer->dateAdded);
		                	$DBFarmRole = $DBServer->GetFarmRoleObject();
							$launch_timeout = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SYSTEM_LAUNCH_TIMEOUT) > 0 ? $DBFarmRole->GetSetting(DBFarmRole::SETTING_SYSTEM_LAUNCH_TIMEOUT) : CONFIG::$LAUNCH_TIMEOUT;
		                            
							if ($DBServer->status == SERVER_STATUS::PENDING) {
								$event = "hostInit";
								$scripting_event = EVENT_TYPE::HOST_INIT;
							}
							elseif ($DBServer->status == SERVER_STATUS::INIT) { 
								$event = "hostUp";
								$scripting_event = EVENT_TYPE::HOST_UP;
							}
								
							if ($scripting_event) {
								$scripting_timeout = (int)$db->GetOne("SELECT sum(timeout) FROM farm_role_scripts  
									WHERE event_name=? AND 
									farm_roleid=? AND issync='1'",
									array($scripting_event, $DBServer->farmRoleId)
								);
									
								if ($scripting_timeout)
									$launch_timeout = $launch_timeout+$scripting_timeout;
									
								if ($dtadded+$launch_timeout < time()) {
		                            //Add entry to farm log
		                    		Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($DBFarm->ID, "Server '{$DBServer->serverId}' did not send '{$event}' event in {$launch_timeout} seconds after launch (Try increasing timeouts in role settings). Considering it broken. Terminating instance."));
		                                
		                            try {
		                            	Scalr::FireEvent($DBFarm->ID, new BeforeHostTerminateEvent($DBServer));
		                            }
		                            catch (Exception $err) {
										$this->Logger->fatal($err->getMessage());
		                            }
								}
		                    }
	                	}
                	}
                }
                elseif ($DBServer->GetRealStatus()->isRunning() && $DBServer->status == SERVER_STATUS::RUNNING)
                {
                	if (!$DBServer->IsRebooting()) 
					{
						$ipaddresses = PlatformFactory::NewPlatform($DBServer->platform)->GetServerIPAddresses($DBServer);
						
						if ($ipaddresses['remoteIp'] && $DBServer->remoteIp != $ipaddresses['remoteIp'])
						{
							Scalr::FireEvent(
                            	$DBServer->farmId,
                                new IPAddressChangedEvent($DBServer, $ipaddresses['remoteIp']) 
                            );
						}
						
						//TODO: Check health:
					}
					else
					{
						//TODO: Check reboot timeout
					}
                }
                
                if ($DBServer->status == SERVER_STATUS::PENDING_TERMINATE || $DBServer->status == SERVER_STATUS::TERMINATED)
                {
                	if ($DBServer->status == SERVER_STATUS::TERMINATED || ($DBServer->dateShutdownScheduled && strtotime($DBServer->dateShutdownScheduled)+60*3 < time()))
                	{                		
                		if (!$DBServer->GetRealStatus()->isTerminated())
						{
                			Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($DBFarm->ID, sprintf("Terminating server '%s' (Platform: %s) (Poller).", 
                				$DBServer->serverId, $DBServer->platform
                			)));
                			
                			PlatformFactory::NewPlatform($DBServer->platform)->TerminateServer($DBServer);
                			
                			$db->Execute("UPDATE servers_history SET
								dtterminated	= NOW(),
								terminate_reason	= ?
								WHERE server_id = ?
							", array(
								sprintf("Server is running on Amazon but has terminated status on Scalr"),
								$DBServer->serverId
							));
						}
                	}
                }
            }
        }
    }
?>