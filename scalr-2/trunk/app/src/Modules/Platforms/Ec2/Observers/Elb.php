<?php
	class Modules_Platforms_Ec2_Observers_Elb extends EventObserver
	{
		public $ObserverName = 'Elastic Load Balancing';
		private $Crypto;
		
		function __construct()
		{
			parent::__construct();			
		}
	
		private function DeregisterInstanceFromLB(DBServer $DBServer)
		{
			try
			{
				$DBFarmRole = $DBServer->GetFarmRoleObject();
				if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB) == 1)
				{
					$Client = $DBServer->GetClient();
					
					$AmazonELBClient = AmazonELB::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
					$AmazonELBClient->SetRegion($DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION));
					
					$AmazonELBClient->DeregisterInstancesFromLoadBalancer(
						$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME),
						array($DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID))
					);
					
					Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($this->FarmID, 
						sprintf(_("Instance '%s' deregistered from '%s' load balancer"),
							$DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID),
							$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME)
						)
					));
				}
			}
			catch(Exception $e)
			{
				Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($this->FarmID,
					sprintf(_("Cannot deregister instance from the load balancer: %s"), $e->getMessage())
				));
			}
		}
		
		public function OnHostDown(HostDownEvent $event)
		{
			$this->DeregisterInstanceFromLB($event->DBServer);
		}
		
		public function OnBeforeHostTerminate(BeforeHostTerminateEvent $event)
		{					
			$this->DeregisterInstanceFromLB($event->DBServer);
		}
		
		public function OnHostUp(HostUpEvent $event)
		{
			try
			{
				$DBFarmRole = $event->DBServer->GetFarmRoleObject();				
				if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB) == 1)
				{
					$Client = $event->DBServer->GetClient();
					
					$AmazonELBClient = AmazonELB::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
					$AmazonELBClient->SetRegion($event->DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION));
					
					$AmazonELBClient->RegisterInstancesWithLoadBalancer(
						$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME),
						array($event->DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID))
					);
					
					Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($this->FarmID, 
						sprintf(_("Instance '%s' registered on '%s' load balancer"),
							$event->DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID),
							$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME)
						)
					));
				}
			}
			catch(Exception $e)
			{
				//TODO:
				$this->Logger->fatal(sprintf(_("Cannot register instance with the load balancer: %s (%s)"), $e->getMessage(), serialize($e)));
			}
		}
		
		public function OnHostInit(HostInitEvent $event)
		{			
			//
		}
	}
?>