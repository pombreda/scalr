<?php
	class SNMPInformer extends EventObserver 
	{
		public $ObserverName = 'SNMPInformer';
		private $SNMP;
		
		function __construct()
		{
			parent::__construct();
			
			$this->SNMP = new SNMP();
		}
				
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state IN (?,?)", 
				array($this->FarmID, INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING)
			);
			foreach ((array)$instances as $instance)
			{
				$this->SNMP->Connect($instance['external_ip'], null, $farminfo['hash']);
                $trap = vsprintf(SNMP_TRAP::NEW_MYSQL_MASTER, 
                	array(
                		$event->InstanceInfo['internal_ip'], 
                		$event->SnapURL,
                		$event->InstanceInfo['role_name']
                	)
                );
                $res = $this->SNMP->SendTrap($trap);
                $this->Logger->info("[FarmID: {$this->FarmID}] Sending SNMP Trap newMysqlMaster ({$trap}) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})");
			}
		}
		
		public function OnHostInit(HostInitEvent $event)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$Client = Client::Load($farminfo["clientid"]);
			
			$this->SNMP->Connect($event->ExternalIP, null, $farminfo['hash']);
			$trap = vsprintf(SNMP_TRAP::HOST_INIT, array($Client->AWSAccountID));
            $res = $this->SNMP->SendTrap($trap);
            $this->Logger->info("[FarmID: {$this->FarmID}] Sending SNMP Trap hostInit ({$trap}) to '{$event->InstanceInfo['instance_id']}' ('{$event->ExternalIP}') complete ({$res})");
		}
						
		public function OnHostUp(HostUpEvent $event)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			$alias = $this->DB->GetOne("SELECT alias FROM ami_roles WHERE name='{$event->InstanceInfo["role_name"]}' AND iscompleted='1'");
			
			$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state IN(?,?)", 
				array($farminfo["id"], INSTANCE_STATE::RUNNING, INSTANCE_STATE::INIT)
			);
			
			foreach ((array)$instances as $instance)
			{
				$this->SNMP->Connect($instance['external_ip'], null, $farminfo['hash']);
				
				$trap = vsprintf(SNMP_TRAP::HOST_UP, array(
					$alias, 
					$event->InstanceInfo['internal_ip'], 
					$event->InstanceInfo["role_name"])
				);
				
				$res = $this->SNMP->SendTrap($trap);
				$this->Logger->info("[FarmID: {$this->FarmID}] Sending SNMP Trap hostUp ({$trap}) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})");
			}
		}
		
		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->InstanceInfo['isrebootlaunched'] == 1)
				return;
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			// Get list of all instances for farm
			$farm_instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state IN (?,?)", 
				array($this->FarmID, INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING)
			);
			
			// Get alias of role
			$alias = $this->DB->GetOne("SELECT alias FROM ami_roles WHERE ami_id='{$event->InstanceInfo['ami_id']}'");
			
			$first_in_role_handled = false;
			foreach ($farm_instances as $farm_instance_snmp)
			{
				if ($farm_instance_snmp["state"] != INSTANCE_STATE::RUNNING || !$farm_instance_snmp["external_ip"])
					continue;

				if ($farm_instance_snmp["id"] == $event->InstanceInfo["id"])
					continue;

				$farm_ami_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE ami_id=?", 
					array($event->InstanceInfo['ami_id'])
				);
					
				$this->Logger->debug("Processing instance: {$farm_instance_snmp['instance_id']} ({$farm_instance_snmp["ami_id"]})");
				$this->Logger->debug("Farm ami: {$farm_ami_info['ami_id']}, {$farm_ami_info['replace_to_ami']}");
				
				$isfirstinrole = '0';
				
				if ($event->InstanceInfo['isdbmaster'] == 1 && !$first_in_role_handled)
				{
					$calias = $this->DB->GetOne("SELECT alias FROM ami_roles WHERE ami_id=?", array($farm_instance_snmp['ami_id']));
					if ($calias == ROLE_ALIAS::MYSQL)
					{
						$first_in_role_handled = true;
						$isfirstinrole = '1';
					}	
				}
								
				if ($event->InstanceInfo['internal_ip'])
				{
					$this->SNMP->Connect($farm_instance_snmp['external_ip'], null, $farminfo['hash']);
	                $trap = vsprintf(SNMP_TRAP::HOST_DOWN, array(
	                	$alias, 
	                	$event->InstanceInfo['internal_ip'], 
	                	$isfirstinrole,
	                	$event->InstanceInfo["role_name"])
	                );
	                $res = $this->SNMP->SendTrap($trap);
	                $this->Logger->info("[FarmID: {$this->FarmID}] Sending SNMP Trap hostDown ({$trap}) to '{$farm_instance_snmp['instance_id']}' ('{$farm_instance_snmp['external_ip']}') complete ({$res})");
				}
			}
		}
	}
?>