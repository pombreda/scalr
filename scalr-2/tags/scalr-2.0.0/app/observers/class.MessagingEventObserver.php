<?php
	class MessagingEventObserver extends EventObserver 
	{
		public $ObserverName = 'Messaging';
		
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
					
					$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
					foreach ((array)$servers as $DBServer)
					{
						if ($DBServer->GetFarmRoleObject()->GetRoleOrigin() == ROLE_ALIAS::APP || $DBServer->GetFarmRoleObject()->GetRoleOrigin() == ROLE_ALIAS::WWW)
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
					$event->DBServer->GetFarmRoleObject()->GetRoleAlias(),
					$event->DBServer->GetFarmRoleObject()->GetRoleName(),
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
					$msg->awsAccountId = $event->DBServer->GetClient()->AWSAccountID;
				}
			}
			if ($dbFarmRole->GetRoleAlias() == ROLE_ALIAS::MYSQL)
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
						$event->DBServer->GetFarmRoleObject()->GetRoleAlias(),
						$event->DBServer->GetFarmRoleObject()->GetRoleName(),
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
					$event->DBServer->GetFarmRoleObject()->GetRoleAlias(),
					$event->DBServer->GetFarmRoleObject()->GetRoleName(),
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
					$event->DBServer->GetFarmRoleObject()->GetRoleAlias(),
					$event->DBServer->GetFarmRoleObject()->GetRoleName(),
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
			$servers = $dbFarm->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			try
			{
				$DBFarmRole = $event->DBServer->GetFarmRoleObject();
				$is_synchronize = ($DBFarmRole->NewRoleID) ? true : false;
				$alias = $DBFarmRole->GetRoleAlias();
			}
			catch(Exception $e)
			{
				$is_synchronize = false;
				$alias = $this->DB->GetOne("SELECT alias FROM roles WHERE id=?", array($event->DBServer->roleId));
			}


			$first_in_role_handled = false;
			$first_in_role_server = null;
			foreach ($servers as $DBServer)
			{
				if (!($DBServer instanceof DBServer))
					continue;
				
				$isfirstinrole = '0';
				$calias = $this->DB->GetOne("SELECT alias FROM roles WHERE id=?", array($DBServer->roleId));
				
				if ($event->DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) && !$first_in_role_handled)
				{
					if (!$is_synchronize || $DBServer->farmRoleId != $event->DBServer->farmRoleId)
					{
						if ($calias == ROLE_ALIAS::MYSQL)
						{
							$first_in_role_handled = true;
							$first_in_role_server = $DBServer;
							$isfirstinrole = '1';
						}
					}	
				}

				if ($DBFarmRole)
				{
					$role_name = $DBFarmRole->GetRoleName();
					$role_alias = $DBFarmRole->GetRoleAlias();
				}
				else
				{
					$role_name = '*Unknown*';
					$role_alias = '*Unknown*';
				}
				
				$msg = new Scalr_Messaging_Msg_HostDown(
					$role_alias,
					$role_name,
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
				
				foreach ($server as $server) {
					if ($server->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE) == $availZone) {
						$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_SLAVE_TO_MASTER, 1);
						$server->SendMessage($msg);
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