<?php
	class SNMPInformer extends EventObserver 
	{
		public $ObserverName = 'SNMPInformer';
		
		function __construct()
		{
			parent::__construct();
		}

		public function OnRoleOptionChanged(RoleOptionChangedEvent $event) 
		{	
			switch($event->OptionName)
			{
				case RESERVED_ROLE_OPTIONS::APACHE_HTTPS_VHOST_TEMPLATE:
				case RESERVED_ROLE_OPTIONS::APACHE_HTTP_VHOST_TEMPLATE:
				case RESERVED_ROLE_OPTIONS::NGINX_HTTPS_VHOST_TEMPLATE:
					
					$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state IN (?,?)", 
						array($this->FarmID, INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING)
					);
					foreach ((array)$instances as $instance)
					{
						$DBInstance = DBInstance::LoadByID($instance['id']);
						
						if ($DBInstance->GetDBFarmRoleObject()->GetRoleOrigin() == ROLE_ALIAS::APP || $DBInstance->GetDBFarmRoleObject()->GetRoleOrigin() == ROLE_ALIAS::WWW)
						{
							$DBInstance->SendMessage(new VhostReconfigureScalrMessage());
						}
					}
					
					break;
			}
		}
		
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event)
		{
			$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state IN (?,?)", 
				array($this->FarmID, INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING)
			);
			foreach ((array)$instances as $instance)
			{
				$DBInstance = DBInstance::LoadByID($instance['id']);
				$DBInstance->SendMessage(new NewMySQLMasterUpScalrMessage(
					$event->DBInstance->InternalIP, 
                	$event->SnapURL,
                	$event->DBInstance->GetDBFarmRoleObject()->GetRoleName()
				));
			}
		}
		
		public function OnHostInit(HostInitEvent $event)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$Client = Client::Load($farminfo["clientid"]);
			
			$event->DBInstance->SendMessage(new HostInitScalrMessage(
				$Client->AWSAccountID
			));
		}
						
		public function OnHostUp(HostUpEvent $event)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state IN(?,?)", 
				array($farminfo["id"], INSTANCE_STATE::RUNNING, INSTANCE_STATE::INIT)
			);
			
			foreach ((array)$instances as $instance)
			{
				$DBInstance = DBInstance::LoadByID($instance['id']);
				$DBInstance->SendMessage(new HostUpScalrMessage(
					$event->DBInstance->GetDBFarmRoleObject()->GetRoleAlias(), 
					$event->DBInstance->InternalIP, 
					$event->DBInstance->GetDBFarmRoleObject()->GetRoleName()
				));
			}
		}
		
		public function OnBeforeHostTerminate(BeforeHostTerminateEvent $event)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			if ($event->DBInstance->GetDBFarmRoleObject()->GetRoleAlias() != ROLE_ALIAS::APP)
				return true;
			
			// Get list of all instances for farm
			$farm_instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state IN (?,?)", 
				array($farminfo['id'], INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING)
			);
			
			foreach ($farm_instances as $farm_instance_snmp)
			{
				if (!$farm_instance_snmp["external_ip"])
					continue;

				if ($farm_instance_snmp["id"] == $event->DBInstance->ID)
					continue;

				$DBFarmRole = $event->DBInstance->GetDBFarmRoleObject();
				$DBFarmRoleISNMP = DBFarmRole::LoadByID($farm_instance_snmp['farm_roleid']);	
				
				if ($DBFarmRoleISNMP->GetRoleAlias() != ROLE_ALIAS::WWW)
					continue;
												
				if ($event->DBInstance->InternalIP)
				{
					$DBInstance = DBInstance::LoadByID($farm_instance_snmp['id']);
					$DBInstance->SendMessage(new HostDownScalrMessage(
						$event->DBInstance->GetDBFarmRoleObject()->GetRoleAlias(), 
	                	$event->DBInstance->InternalIP, 
	                	"0",
	                	$DBFarmRole->GetRoleName()
					));
				}
			}
		}
		
		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->DBInstance->IsRebootLaunched == 1)
				return;
			
			// Get list of all instances for farm
			$farm_instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state IN (?,?)", 
				array($this->FarmID, INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING)
			);
			
			try
			{
				$DBFarmRole = $event->DBInstance->GetDBFarmRoleObject();
				$is_synchronize = ($DBFarmRole->ReplaceToAMI) ? true : false;
				$alias = $DBFarmRole->GetRoleAlias();
			}
			catch(Exception $e)
			{
				$is_synchronize = false;
				$alias = $this->DB->GetOne("SELECT alias FROM roles WHERE ami_id=?", array($event->DBInstance->AMIID));
			}

			$first_in_role_handled = false;
			foreach ($farm_instances as $farm_instance_snmp)
			{
				if (!$farm_instance_snmp["external_ip"])
					continue;

				if ($farm_instance_snmp["id"] == $event->DBInstance->ID)
					continue;

				$this->Logger->debug("Processing instance: {$farm_instance_snmp['instance_id']} ({$farm_instance_snmp["ami_id"]})");
								
				$isfirstinrole = '0';
				$calias = $this->DB->GetOne("SELECT alias FROM roles WHERE ami_id=?", array($farm_instance_snmp['ami_id']));
				
				if ($event->DBInstance->IsDBMaster == 1 && !$first_in_role_handled)
				{
					if (!$is_synchronize || $farm_instance_snmp['farm_roleid'] != $event->DBInstance->FarmRoleID)
					{
						if ($calias == ROLE_ALIAS::MYSQL)
						{
							$first_in_role_handled = true;
							$isfirstinrole = '1';
						}
					}	
				}
								
				if ($event->DBInstance->InternalIP)
				{
					$DBInstance = DBInstance::LoadByID($farm_instance_snmp['id']);
					$DBInstance->SendMessage(new HostDownScalrMessage(
						$alias, 
	                	$event->DBInstance->InternalIP, 
	                	$isfirstinrole,
	                	$event->DBInstance->RoleName
					));
				}
			}
		}
	}
?>