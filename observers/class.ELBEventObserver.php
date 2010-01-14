<?php
	class ELBEventObserver extends EventObserver
	{
		public $ObserverName = 'Elastic Load Balancing';
		private $Crypto;
		
		function __construct()
		{
			parent::__construct();			
		}

		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->DBInstance->IsRebootLaunched == 1)
				return;
										
			try
			{
				$DBFarmRole = $event->DBInstance->GetDBFarmRoleObject();
				if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB) == 1)
				{
					$farminfo = $this->DB->GetRow("SELECT clientid, region FROM farms WHERE id=?", array($this->FarmID));
					$Client = Client::Load($farminfo['clientid']);
					
					$AmazonELBClient = AmazonELB::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
					$AmazonELBClient->SetRegion($farminfo['region']);
					
					$AmazonELBClient->DeregisterInstancesFromLoadBalancer(
						$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME),
						array($event->DBInstance->InstanceID)
					);
					
					$this->Logger->info(new FarmLogMessage($this->FarmID, 
						sprintf(_("Instance '%s' deregistered from '%s' load balancer"),
							$event->DBInstance->InstanceID,
							$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME)
						)
					));
				}
			}
			catch(Exception $e)
			{
				$this->Logger->fatal(sprintf(_("Cannot deregister instance from the load balancer: %s"), $e->getMessage()));
			}
		}
		
		public function OnHostUp(HostUpEvent $event)
		{
			try
			{
				$DBFarmRole = $event->DBInstance->GetDBFarmRoleObject();
				if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB) == 1)
				{
					$farminfo = $this->DB->GetRow("SELECT clientid, region FROM farms WHERE id=?", array($this->FarmID));
					$Client = Client::Load($farminfo['clientid']);
					
					$AmazonELBClient = AmazonELB::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
					$AmazonELBClient->SetRegion($farminfo['region']);
					
					$AmazonELBClient->RegisterInstancesWithLoadBalancer(
						$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME),
						array($event->DBInstance->InstanceID)
					);
					
					$this->Logger->info(new FarmLogMessage($this->FarmID, 
						sprintf(_("Instance '%s' registered on '%s' load balancer"),
							$event->DBInstance->InstanceID,
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