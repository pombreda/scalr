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
			if ($event->InstanceInfo['isrebootlaunched'] == 1)
				return;
										
			try
			{
				$DBFarmRole = DBFarmRole::Load($event->InstanceInfo['farmid'], $event->InstanceInfo['ami_id']);
				if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB) == 1)
				{
					$farminfo = $this->DB->GetRow("SELECT clientid FROM farms WHERE id=?", array($this->FarmID));
					$Client = Client::Load($farminfo['clientid']);
					
					$AmazonELBClient = AmazonELB::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
					
					$AmazonELBClient->DeregisterInstancesFromLoadBalancer(
						$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME),
						array($event->InstanceInfo['instance_id'])
					);
					
					$this->Logger->info(new FarmLogMessage($this->FarmID, 
						sprintf(_("Instance '%s' deregistered from '%s' load balancer"),
							$event->InstanceInfo['instance_id'],
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
				$DBInstance = DBInstance::LoadByID($event->InstanceInfo['id']); 
				$DBFarmRole = $DBInstance->GetDBFarmRoleObject();
				if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB) == 1)
				{
					$farminfo = $this->DB->GetRow("SELECT clientid FROM farms WHERE id=?", array($this->FarmID));
					$Client = Client::Load($farminfo['clientid']);
					
					$AmazonELBClient = AmazonELB::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
					
					$AmazonELBClient->RegisterInstancesWithLoadBalancer(
						$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME),
						array($DBInstance->InstanceID)
					);
					
					$this->Logger->info(new FarmLogMessage($this->FarmID, 
						sprintf(_("Instance '%s' registered on '%s' load balancer"),
							$DBInstance->InstanceID,
							$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME)
						)
					));
				}
			}
			catch(Exception $e)
			{
				$this->Logger->fatal(sprintf(_("Cannot register instance with the load balancer: %s"), $e->getMessage()));
			}
		}
		
		public function OnHostInit(HostInitEvent $event)
		{			
			//
		}
	}
?>