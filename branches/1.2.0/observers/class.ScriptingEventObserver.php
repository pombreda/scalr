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
			if ($event->DBInstance->IsRebootLaunched == 1)
				return;
										
			$this->SendExecTrap($event->DBInstance, $event);
		}
		
		public function OnHostUp(HostUpEvent $event)
		{
			$event->DBInstance->ReLoad();
						
			$this->SendExecTrap($event->DBInstance, $event);
		}
		
		public function OnHostInit(HostInitEvent $event)
		{			
			$event->DBInstance->ReLoad();
			
			$event->DBInstance->ExternalIP = $event->ExternalIP;
			$event->DBInstance->InternalIP = $event->InternalIP;
			
			$this->SendExecTrap($event->DBInstance, $event);
		}
		
		public function OnRebootComplete(RebootCompleteEvent $event)
		{
			$event->DBInstance->ReLoad();
			
			$this->SendExecTrap($event->DBInstance, $event);
		}
		
		public function OnIPAddressChanged(IPAddressChangedEvent $event)
		{
			$event->DBInstance->ReLoad();
			
			$this->SendExecTrap($event->DBInstance, $event);
		}
		
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event)
		{
			$event->DBInstance->ReLoad();
			
			$this->SendExecTrap($event->DBInstance, $event);
		}
		
		public function OnEBSVolumeMounted(EBSVolumeMountedEvent $event)
		{
			$event->DBInstance->Reload();
			
			$this->SendExecTrap($event->DBInstance, $event);
		}
		
		public function OnBeforeInstanceLaunch(BeforeInstanceLaunchEvent $event)
		{			
			$event->DBInstance->ReLoad();
				
			$this->SendExecTrap($event->DBInstance, $event);
		}
		
		public function OnBeforeHostTerminate(BeforeHostTerminateEvent $event)
		{			
			$event->DBInstance->ReLoad();
				
			$this->SendExecTrap($event->DBInstance, $event);
		}
		
		public function OnDNSZoneUpdated(DNSZoneUpdatedEvent $event)
		{			
			$this->SendExecTrap(null, $event);
		}
		
		public function OnEBSVolumeAttached(EBSVolumeAttachedEvent $event)
		{			
			$this->SendExecTrap($event->DBInstance, $event);
		}
		
		private function SendExecTrap(DBInstance $DBInstance, Event $Event)
		{			
			// Try to send trap to all instances
			// , INSTANCE_STATE::RUNNING, INSTANCE_STATE::INIT (AND state IN(?,?))
			// Comment: Scripting mount event before hostInit.
			
			$c = $this->DB->GetOne("SELECT COUNT(*) FROM farm_role_scripts WHERE farmid=? AND event_name=?",
				array($Event->GetFarmID(), $Event->GetName())
			);
			if ($c == 0)
			{
				return;
			}
			
			$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state != '".INSTANCE_STATE::TERMINATED."'",
				array($Event->GetFarmID())
			);
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			foreach ($instances as $instance)
			{
				// For some events we must sent trap to all instances include instance where ip adress changed.
				// For other events we must exclude instance that fired event from trap list.
				if ($DBInstance && $DBInstance->ID == $instance['id'])
				{
					if (!in_array($Event->GetName(), array(
						EVENT_TYPE::INSTANCE_IP_ADDRESS_CHANGED, 
						EVENT_TYPE::EBS_VOLUME_MOUNTED, 
						EVENT_TYPE::BEFORE_INSTANCE_LAUNCH,
						EVENT_TYPE::BEFORE_HOST_TERMINATE
						))) 
					{
						continue;
					}
				}

				$TDBInstance = DBInstance::LoadByID($instance['id']);
				$TDBInstance->SendMessage(
					new EventNoticeScalrMessage(
						($DBInstance) ? $DBInstance->InternalIP : '0.0.0.0',
						$Event->GetEventID(),
						($DBInstance) ? $DBInstance->GetDBFarmRoleObject()->GetRoleName() : '',
						$Event->GetName()
					)
				);
			}
		}
	}
?>