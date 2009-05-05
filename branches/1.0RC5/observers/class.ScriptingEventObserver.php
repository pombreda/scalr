<?php
	class ScriptingEventObserver extends EventObserver
	{
		public $ObserverName = 'Scripting';
		private $Crypto;
		
		function __construct()
		{
			parent::__construct();			
			$this->Crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
		}

		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->InstanceInfo['isrebootlaunched'] == 1)
				return;
										
			$this->SendExecTrap($event->InstanceInfo, EVENT_TYPE::HOST_DOWN);
		}
		
		public function OnHostUp(HostUpEvent $event)
		{
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
				array($event->InstanceInfo['id'])
			);
						
			$this->SendExecTrap($instanceinfo, EVENT_TYPE::HOST_UP);
		}
		
		public function OnHostInit(HostInitEvent $event)
		{			
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
				array($event->InstanceInfo['id'])
			);
			
			$instanceinfo["external_ip"] = $event->ExternalIP;
			$instanceinfo["internal_ip"] = $event->InternalIP;
			
			$this->SendExecTrap($instanceinfo, EVENT_TYPE::HOST_INIT);
		}
		
		public function OnRebootComplete(RebootCompleteEvent $event)
		{
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
				array($event->InstanceInfo['id'])
			);
			
			$this->SendExecTrap($instanceinfo, EVENT_TYPE::REBOOT_COMPLETE);
		}
		
		public function OnIPAddressChanged(IPAddressChangedEvent $event)
		{
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
				array($event->InstanceInfo['id'])
			);
			
			$this->SendExecTrap($instanceinfo, EVENT_TYPE::INSTANCE_IP_ADDRESS_CHANGED);
		}
		
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event)
		{
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
				array($event->InstanceInfo['id'])
			);
			
			$this->SendExecTrap($instanceinfo, EVENT_TYPE::NEW_MYSQL_MASTER);
		}
		
		public function OnEBSVolumeMounted(EBSVolumeMountedEvent $event)
		{
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
				array($event->InstanceInfo['id'])
			);
			
			$this->SendExecTrap($instanceinfo, EVENT_TYPE::EBS_VOLUME_MOUNTED);
		}
		
		public function OnBeforeInstanceLaunch(BeforeInstanceLaunchEvent $event)
		{			
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
				array($event->InstanceInfo['id'])
			);
				
			$this->SendExecTrap($instanceinfo, EVENT_TYPE::BEFORE_INSTANCE_LAUNCH);
		}
		
		public function OnBeforeHostTerminate(BeforeHostTerminateEvent $event)
		{			
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
				array($event->InstanceInfo['id'])
			);
				
			$this->SendExecTrap($instanceinfo, EVENT_TYPE::BEFORE_HOST_TERMINATE);
		}
		
		private function SendExecTrap($instanceinfo, $event_name)
		{			
			// Try to send trap to all instances
			// , INSTANCE_STATE::RUNNING, INSTANCE_STATE::INIT (AND state IN(?,?))
			// Comment: Scripting mount event before hostInit.
			
			$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=?",
				array($instanceinfo['farmid'])
			);
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			foreach ($instances as $instance)
			{
				// For some events we must sent trap to all instances include instance where ip adress changed.
				// For other events we must exclude instance that fired event from trap list.
				if ($instanceinfo['id'] == $instance['id'])
				{
					if (!in_array($event_name, array(
						EVENT_TYPE::INSTANCE_IP_ADDRESS_CHANGED, 
						EVENT_TYPE::EBS_VOLUME_MOUNTED, 
						EVENT_TYPE::BEFORE_INSTANCE_LAUNCH,
						EVENT_TYPE::BEFORE_HOST_TERMINATE
						))) 
					{
						continue;
					}
				}

				$DBInstance = DBInstance::LoadByID($instance['id']);
				$DBInstance->SendMessage(
					new EventNoticeScalrMessage(
						$instanceinfo['internal_ip'],
						$this->DB->GetOne("SELECT alias FROM ami_roles WHERE ami_id=?", array($instanceinfo['ami_id'])),
						$instanceinfo['role_name'],
						$event_name
					)
				);
			}
		}
	}
?>