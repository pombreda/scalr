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
				
		public function OnNewMysqlMasterUp($instanceinfo, $snapurl)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=?", array($this->FarmID));
			foreach ((array)$instances as $instance)
			{
				$this->SNMP->Connect($instance['external_ip'], null, $farminfo['hash']);
                $trap = vsprintf(SNMP_TRAP::NEW_MYSQL_MASTER, array($instanceinfo['internal_ip'], $snapurl));
                $res = $this->SNMP->SendTrap($trap);
                $this->Logger->info("[FarmID: {$this->FarmID}] Sending SNMP Trap newMysqlMaster ({$trap}) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})");
			}
		}
		
		public function OnHostInit($instanceinfo, $local_ip, $remote_ip, $public_key)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			$clientinfo = $this->DB->GetRow("SELECT * FROM clients WHERE id=?", array($farminfo["clientid"]));
			
			$this->SNMP->Connect($remote_ip, null, $farminfo['hash']);
			$trap = vsprintf(SNMP_TRAP::HOST_INIT, array($clientinfo['aws_accountid']));
            $res = $this->SNMP->SendTrap($trap);
            $this->Logger->info("[FarmID: {$this->FarmID}] Sending SNMP Trap hostInit ({$trap}) to '{$instanceinfo['instance_id']}' ('{$remote_ip}') complete ({$res})");
		}
		
		public function OnHostCrash($instanceinfo)
		{
			$this->OnHostDown($instanceinfo);
		}
						
		public function OnHostUp($instanceinfo)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			$alias = $this->DB->GetOne("SELECT alias FROM ami_roles WHERE name='{$instanceinfo["role_name"]}' AND iscompleted='1'");
			
			$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state='Running'", 
				array($farminfo["id"])
			);
			
			foreach ((array)$instances as $instance)
			{
				$this->SNMP->Connect($instance['external_ip'], null, $farminfo['hash']);
				
				$trap = vsprintf(SNMP_TRAP::HOST_UP, array(
					$alias, 
					$instanceinfo['internal_ip'], 
					$instanceinfo["role_name"])
				);
				
				$res = $this->SNMP->SendTrap($trap);
				$this->Logger->info("[FarmID: {$this->FarmID}] Sending SNMP Trap hostUp ({$trap}) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})");
			}
		}
		
		public function OnHostDown($instanceinfo)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			// Get list of all instances for farm
			$farm_instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid='{$farminfo['id']}' ORDER BY id DESC");
			
			// Get alias of role
			$alias = $this->DB->GetOne("SELECT alias FROM ami_roles WHERE ami_id='{$instanceinfo['ami_id']}'");
			
			$first_in_role_handled = false;
			foreach ($farm_instances as $farm_instance_snmp)
			{
				if ($farm_instance_snmp["state"] != INSTANCE_STATE::RUNNING || !$farm_instance_snmp["external_ip"])
					continue;

				if ($farm_instance_snmp["id"] == $instanceinfo["id"])
					continue;

				$farm_ami_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE ami_id=?", 
					array($instanceinfo['ami_id'])
				);
					
				$this->Logger->debug("Processing instance: {$farm_instance_snmp['instance_id']} ({$farm_instance_snmp["ami_id"]})");
				$this->Logger->debug("Farm ami: {$farm_ami_info['ami_id']}, {$farm_ami_info['replace_to_ami']}");
				
				$isfirstinrole = '0';
				
				if ($instanceinfo['isdbmaster'] == 1 && !$first_in_role_handled)
				{
					$alias = $this->DB->GetOne("SELECT alias FROM ami_roles WHERE ami_id=?", array($farm_instance_snmp['ami_id']));
					if ($alias == ROLE_ALIAS::MYSQL)
					{
						$first_in_role_handled = true;
						$isfirstinrole = '1';
					}	
				}
								
				if ($instanceinfo['internal_ip'])
				{
					$this->SNMP->Connect($farm_instance_snmp['external_ip'], null, $farminfo['hash']);
	                $trap = vsprintf(SNMP_TRAP::HOST_DOWN, array(
	                	$alias, 
	                	$instanceinfo['internal_ip'], 
	                	$isfirstinrole,
	                	$instanceinfo["role_name"])
	                );
	                $res = $this->SNMP->SendTrap($trap);
	                $this->Logger->info("[FarmID: {$this->FarmID}] Sending SNMP Trap hostDown ({$trap}) to '{$farm_instance_snmp['instance_id']}' ('{$farm_instance_snmp['external_ip']}') complete ({$res})");
				}
			}
		}
	}
?>