<?php
	class Modules_Platforms_Rds_Observers_Rds extends EventObserver
	{
		public $ObserverName = 'RDS';
		
		function __construct()
		{
			parent::__construct();
			
			$this->Crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
		}

		/**
		 * Return new instance of AmazonRDS object
		 *
		 * @return AmazonRDS
		 */
		private function GetAmazonRDSClientObject($region)
		{
	    	// Get ClientID from database;
			$clientid = $this->DB->GetOne("SELECT clientid FROM farms WHERE id=?", array($this->FarmID));
			
			// Get Client Object
			$Client = Client::Load($clientid);
			
			$RDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
		    $RDSClient->SetRegion($region);
			
			return $RDSClient;
		}
				
		/**
		 * Terminate all running instance on farm
		 *
		 * @param FarmTerminatedEvent $event
		 */
		public function OnFarmTerminated(FarmTerminatedEvent $event)
		{			
			$DBDarm = DBFarm::LoadByID($this->FarmID);
									
			$servers = $DBDarm->GetServersByFilter(array('platform' => SERVER_PLATFORMS::RDS));
			                    
		    if (count($servers) == 0)
		    	return;
		    
		    // TERMINATE RUNNING INSTANCES
		    $RDSClient = $this->GetAmazonRDSClientObject($DBDarm->Region);
            foreach ($servers as $DBServer)
            {                
                if ($this->DB->GetOne("SELECT id FROM bundle_tasks WHERE server_id=? AND status NOT IN ('success','failed')", array($DBServer->serverId)))
                	continue;
                
            	if ($DBServer->status != SERVER_STATUS::PENDING_LAUNCH)
                {
	            	try {    				
	    				$response = $RDSClient->DeleteDBInstance($DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID));
	    					
	    				if ($response instanceof SoapFault)
	    					$this->Logger->warn($response->faultstring);
	    					
	    				if ($DBServer->status != SERVER_STATUS::TERMINATED)
	    				{
		    				$this->DB->Execute("UPDATE servers_history SET
								dtterminated	= NOW(),
								terminate_reason	= ?
								WHERE server_id = ?
							", array(
								sprintf("Farm was terminated"),
								$DBServer->serverId
							));
	    				}
	    			}
	    			catch (Exception $e) {
	    				$this->Logger->error($e->getMessage()); 
	    			}
                }
    			
                if ($DBServer->status == SERVER_STATUS::PENDING || $DBServer->status == SERVER_STATUS::PENDING_LAUNCH)
    			{
    				$DBServer->status = SERVER_STATUS::TERMINATED;
    				$DBServer->Save();
    			}
            }
		}
		
		public function OnBeforeHostTerminate(BeforeHostTerminateEvent $event)
		{
			if ($event->DBServer->platform != SERVER_PLATFORMS::RDS)
				return;
			
			if ($event->ForceTerminate)
			{ 
				$DBFarm = DBFarm::LoadByID($this->FarmID);
				$AmazonRDSClient = $this->GetAmazonRDSClientObject($DBFarm->Region);
				
				$instance_id = $event->DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID);
				
				Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($this->FarmID, "Terminating instance '{$instance_id}' (O)."));
                $AmazonRDSClient->DeleteDBInstance($instance_id);
			}
		}
	}
?>