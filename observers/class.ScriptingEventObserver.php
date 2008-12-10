<?php
	class ScriptingEventObserver extends EventObserver
	{
		public $ObserverName = 'Scripting';
		private $Crypto;
		private $SNMP;
		
		function __construct()
		{
			parent::__construct();
			
			$this->SNMP = new SNMP();
			
			$this->Crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
		}

		public function OnHostDown(HostDownEvent $event)
		{
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
				array($event->InstanceInfo['id'])
			);
						
			$this->SendExecTrap($instanceinfo, EVENT_TYPE::HOST_DOWN);
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
		
		private function SendExecTrap($instanceinfo, $event_name)
		{			
			$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state IN(?,?)",
				array($instanceinfo['farmid'], INSTANCE_STATE::RUNNING, INSTANCE_STATE::INIT)
			);
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			foreach ($instances as $instance)
			{
				// For INSTANCE_IP_ADDRESS_CHANGED we must sent trap to all instances include instance where ip adress changed.
				// For other events we must exclude instance that fired event from trap list.
				if ($event_name != EVENT_TYPE::INSTANCE_IP_ADDRESS_CHANGED && $instanceinfo['id'] == $instance['id'])
					continue;

				$this->SNMP->Connect($instance['external_ip'], null, $farminfo['hash']);
				$trap = vsprintf(SNMP_TRAP::NOTIFY_EVENT, array(
					$instanceinfo['internal_ip'],
					$this->DB->GetOne("SELECT alias FROM ami_roles WHERE ami_id=?", array($instanceinfo['ami_id'])),
					$instanceinfo['role_name'],
					$event_name
				));
            	$res = $this->SNMP->SendTrap($trap);
            	$this->Logger->info("[FarmID: {$this->FarmID}] Sending SNMP Trap notifyEvent ({$trap}) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})");
			}
		}
	}
?>