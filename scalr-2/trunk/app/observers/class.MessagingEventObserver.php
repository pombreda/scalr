<?php
	class MessagingEventObserver extends EventObserver 
	{
		public $ObserverName = 'Messaging';
		
		function __construct()
		{
			parent::__construct();
		}

		public function OnServiceConfigurationPresetChanged(ServiceConfigurationPresetChangedEvent $event)
		{
			$farmRolesPresetInfo = $this->DB->GetAll("SELECT * FROM farm_role_service_config_presets WHERE
				preset_id = ? AND behavior = ?
			", array($event->ServiceConfiguration->id, $event->ServiceConfiguration->roleBehavior));
			if (count($farmRolesPresetInfo) > 0)
			{
				$msg = new Scalr_Messaging_Msg_UpdateServiceConfiguration(
					$event->ServiceConfiguration->roleBehavior,
					$event->ResetToDefaults,
					$farmRolesPresetInfo['restart_service']
				);
				
				foreach ($farmRolesPresetInfo as $farmRole)
				{
					try
					{
						$dbFarmRole = DBFarmRole::LoadByID($farmRole['farm_roleid']);
						
						foreach ($dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING)) as $dbServer)
						{
							if ($dbServer->IsSupported("0.6"))
								$dbServer->SendMessage($msg);
						}
					}
					catch(Exception $e){}
				}
			}
		}
		
		public function OnRoleOptionChanged(RoleOptionChangedEvent $event) 
		{	
			switch($event->OptionName)
			{
				case RESERVED_ROLE_OPTIONS::NGINX_HTTPS_VHOST_TEMPLATE:
					
					$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
					foreach ((array)$servers as $DBServer)
					{
						if ($DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::APACHE) || $DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::NGINX))
							$DBServer->SendMessage(new Scalr_Messaging_Msg_VhostReconfigure());
					}
					
					break;
			}
		}
		
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event)
		{
			$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			// TODO: Add scalarizr stuff
			
			foreach ((array)$servers as $DBServer)
			{
				$msg = new Scalr_Messaging_Msg_Mysql_NewMasterUp(
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(),
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name,
					$event->DBServer->localIp,
					$event->DBServer->remoteIp,
					$event->SnapURL
				);
				$farmRole = $DBServer->GetFarmRoleObject();
				$msg->replPassword = $farmRole->GetSetting(DbFarmRole::SETTING_MYSQL_REPL_PASSWORD);
				$msg->rootPassword = $farmRole->GetSetting(DbFarmRole::SETTING_MYSQL_ROOT_PASSWORD);
				$DBServer->SendMessage($msg);
			}
		}
		
		public function OnHostInit(HostInitEvent $event)
		{
			$msg = new Scalr_Messaging_Msg_HostInitResponse(
				$event->DBServer->GetFarmObject()->GetSetting(DBFarm::SETTING_CRYPTO_KEY)
			);
			
			$dbServer = $event->DBServer;
			$dbFarmRole = $dbServer->GetFarmRoleObject();
			if ($event->DBServer->GetScalarizrVersion() < array(0,5,0))
			{
				if ($event->DBServer->platform == SERVER_PLATFORMS::EC2)
				{
					$msg->awsAccountId = $event->DBServer->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCOUNT_ID);
				}
			}
			if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL))
			{
				$isMaster = (int)$dbServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER);

				$msg->mysql = (object)array(
					"replicationMaster" => $isMaster,
					"volumeId" => $isMaster ? 
							$dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_MASTER_EBS_VOLUME_ID) :
							$dbServer->GetProperty(EC2_SERVER_PROPERTIES::MYSQL_SLAVE_EBS_VOLUME_ID),
					"snapshotId" => $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_SNAPSHOT_ID),
					"rootPassword" => $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_ROOT_PASSWORD),
					"replPassword" => $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_REPL_PASSWORD),
					"statPassword" => $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_STAT_PASSWORD),
					"logFile" => $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_LOG_FILE),
					"logPos" => $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_LOG_POS)
				);
			}			
			
			$event->DBServer->SendMessage($msg);
			
			$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			foreach ((array)$servers as $DBServer)
			{
				if ($DBServer->serverId != $event->DBServer->serverId)
				{
					$msg = new Scalr_Messaging_Msg_HostInit(
						$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(),
						$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name,
						$event->DBServer->localIp,
						$event->DBServer->remoteIp
					);
					$DBServer->SendMessage($msg);
				}
			}
		}
						
		public function OnHostUp(HostUpEvent $event)
		{
			$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			foreach ((array)$servers as $DBServer)
			{
				$msg = new Scalr_Messaging_Msg_HostUp(
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(),
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name,
					$event->DBServer->localIp,
					$event->DBServer->remoteIp
				);
				$DBServer->SendMessage($msg);
			}
		}
		
		public function OnBeforeHostTerminate(BeforeHostTerminateEvent $event)
		{
			$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));		
			foreach ($servers as $DBServer)
			{									
				$msg = new Scalr_Messaging_Msg_BeforeHostTerminate(
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(),
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name,
					$event->DBServer->localIp,
					$event->DBServer->remoteIp
				);
				$DBServer->SendMessage($msg);
			}
		}
		
		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->DBServer->IsRebooting() == 1)
				return;
			
			$dbFarm = DBFarm::LoadByID($this->FarmID);
			$servers = $dbFarm->GetServersByFilter(array('status' => array(SERVER_STATUS::RUNNING)));
			try
			{
				$DBFarmRole = $event->DBServer->GetFarmRoleObject();
				$is_synchronize = ($DBFarmRole->NewRoleID) ? true : false;
			}
			catch(Exception $e)
			{
				$is_synchronize = false;
			}

			try
			{
				$DBRole = DBRole::loadById($event->DBServer->roleId);
			}
			catch(Exception $e){}

			$first_in_role_handled = false;
			$first_in_role_server = null;
			foreach ($servers as $DBServer)
			{
				if (!($DBServer instanceof DBServer))
					continue;
				
				$isfirstinrole = '0';
				if ($event->DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) && !$first_in_role_handled)
				{
					if (!$is_synchronize || $DBServer->farmRoleId != $event->DBServer->farmRoleId)
					{
						if (DBRole::loadById($DBServer->roleId)->hasBehavior(ROLE_BEHAVIORS::MYSQL))
						{
							$first_in_role_handled = true;
							$first_in_role_server = $DBServer;
							$isfirstinrole = '1';
						}
					}	
				}
				
				$msg = new Scalr_Messaging_Msg_HostDown(
					($DBRole) ? $DBRole->getBehaviors() : '*Unknown*',
					($DBRole) ? $DBRole->name : '*Unknown*',
					$event->DBServer->localIp,
					$event->DBServer->remoteIp
				);
				$msg->isFirstInRole = $isfirstinrole;
				
				$DBServer->SendMessage($msg);
			}
				
			// If EC2 master down			
			if ($event->DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) &&
				$event->DBServer->platform == SERVER_PLATFORMS::EC2 &&
				$event->DBServer->IsSupported("0.5") &&
				$DBFarmRole)
			{
				// Send Mysql_PromoteToMaster to the first server in the same avail zone as old master (if exists)
				// Otherwise send to first in role
				$availZone = $event->DBServer->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE);
				$msg = new Scalr_Messaging_Msg_Mysql_PromoteToMaster(
					$DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_ROOT_PASSWORD),
					$DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_REPL_PASSWORD),
					$DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_STAT_PASSWORD),
					$DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_MASTER_EBS_VOLUME_ID)				
				);
				
				foreach ($servers as $DBServer) {
					if ($DBServer->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE) == $availZone) {
						$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_SLAVE_TO_MASTER, 1);
						$DBServer->SendMessage($msg);
						return;
					}
				}
				
				if ($first_in_role_server) {
					$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_SLAVE_TO_MASTER, 1);
					$first_in_role_server->SendMessage($msg);
				}
			}
		}
	}
?>