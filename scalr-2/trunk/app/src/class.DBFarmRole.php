<?php
	
	class DBFarmRole
	{
		const SETTING_EXCLUDE_FROM_DNS					= 	'dns.exclude_role';
		const SETTING_DNS_INT_RECORD_ALIAS				= 	'dns.int_record_alias';
		const SETTING_DNS_EXT_RECORD_ALIAS				= 	'dns.ext_record_alias';
		
		const SETTING_TERMINATE_IF_SNMP_FAILS			=	'health.terminate_if_snmp_fails';
		const SETTING_TERMINATE_ACTION_IF_SNMP_FAILS	= 	'health.terminate_action_if_snmp_fails'; // helps to reboot or terminate nonresponsable farm
				
		const SETTING_SCALING_MIN_INSTANCES				= 	'scaling.min_instances';
		const SETTING_SCALING_MAX_INSTANCES				= 	'scaling.max_instances';
		const SETTING_SCALING_POLLING_INTERVAL			= 	'scaling.polling_interval';
		const SETTING_SCALING_LAST_POLLING_TIME			= 	'scaling.last_polling_time';
		const SETTING_SCALING_KEEP_OLDEST				= 	'scaling.keep_oldest';
		
			 //advanced timeout limits for scaling
		const SETTING_SCALING_UPSCALE_TIMEOUT			=	'scaling.upscale.timeout';
		const SETTING_SCALING_DOWNSCALE_TIMEOUT			=   'scaling.downscale.timeout';
		const SETTING_SCALING_UPSCALE_TIMEOUT_ENABLED	=	'scaling.upscale.timeout_enabled';
		const SETTING_SCALING_DOWNSCALE_TIMEOUT_ENABLED =	'scaling.downscale.timeout_enabled';
		const SETTING_SCALING_UPSCALE_DATETIME			=	'scaling.upscale.datetime';
		const SETTING_SCALING_DOWNSCALE_DATETIME		=	'scaling.downscale.datetime';
		
		const SETTING_BALANCING_USE_ELB 		= 		'lb.use_elb';
		const SETTING_BALANCING_HOSTNAME 		= 		'lb.hostname';
		const SETTING_BALANCING_NAME 			= 		'lb.name';
		const SETTING_BALANCING_HC_TIMEOUT 		= 		'lb.healthcheck.timeout';
		const SETTING_BALANCING_HC_TARGET 		= 		'lb.healthcheck.target';
		const SETTING_BALANCING_HC_INTERVAL		= 		'lb.healthcheck.interval';
		const SETTING_BALANCING_HC_UTH 			= 		'lb.healthcheck.unhealthythreshold';
		const SETTING_BALANCING_HC_HTH 			= 		'lb.healthcheck.healthythreshold';
		const SETTING_BALANCING_HC_HASH 		= 		'lb.healthcheck.hash';
		const SETTING_BALANCING_AZ_HASH 		= 		'lb.avail_zones.hash';		
		
		/** AWS RDS Settings **/
		const SETTING_RDS_INSTANCE_CLASS 		= 		'rds.instance_class';
		const SETTING_RDS_AVAIL_ZONE			= 		'rds.availability_zone';
		const SETTING_RDS_STORAGE				=       'rds.storage';
		const SETTING_RDS_INSTANCE_ENGINE		= 		'rds.engine';
		const SETTING_RDS_MASTER_USER			= 		'rds.master-user';
		const SETTING_RDS_MASTER_PASS			= 		'rds.master-pass';
		const SETTING_RDS_MULTI_AZ				= 		'rds.multi-az';
		const SETTING_RDS_PORT					= 		'rds.port';
		
		/** AWS EC2 Settings **/
		const SETTING_AWS_INSTANCE_TYPE 		= 		'aws.instance_type';
		const SETTING_AWS_AVAIL_ZONE			= 		'aws.availability_zone';
		const SETTING_AWS_USE_ELASIC_IPS		= 		'aws.use_elastic_ips';
		const SETTING_AWS_USE_EBS				=		'aws.use_ebs';
		const SETTING_AWS_EBS_SIZE				=		'aws.ebs_size';
		const SETTING_AWS_EBS_SNAPID			=		'aws.ebs_snapid';
		const SETTING_AWS_EBS_MOUNT				=		'aws.ebs_mount';
		const SETTING_AWS_EBS_MOUNTPOINT		=		'aws.ebs_mountpoint';
		const SETTING_AWS_AKI_ID				= 		'aws.aki_id';
		const SETTING_AWS_ARI_ID				= 		'aws.ari_id';
		const SETTING_AWS_ENABLE_CW_MONITORING	= 		'aws.enable_cw_monitoring';
		const SETTING_AWS_SECURITY_GROUPS_LIST  = 		'aws.security_groups.list';
				
		/** MySQL options **/
		const SETTING_MYSQL_PMA_USER			=		'mysql.pma.username';
		const SETTING_MYSQL_PMA_PASS			=		'mysql.pma.password';
		const SETTING_MYSQL_PMA_REQUEST_TIME	=		'mysql.pma.request_time';
		const SETTING_MYSQL_PMA_REQUEST_ERROR	=		'mysql.pma.request_error';
		
		const SETTING_MYSQL_BUNDLE_WINDOW_START = 		'mysql.bundle_window.start';
		const SETTING_MYSQL_BUNDLE_WINDOW_END	= 		'mysql.bundle_window.end';
		const SETTING_MYSQL_EBS_SNAPS_ROTATE	= 		'mysql.ebs.rotate';
		const SETTING_MYSQL_EBS_SNAPS_ROTATION_ENABLED	= 'mysql.ebs.snaps_rotation_enabled';
		
		const SETTING_MYSQL_BCP_ENABLED 		= 'mysql.enable_bcp';
		const SETTING_MYSQL_BCP_EVERY 			= 'mysql.bcp_every';
		const SETTING_MYSQL_BUNDLE_ENABLED 		= 'mysql.enable_bundle';
		const SETTING_MYSQL_BUNDLE_EVERY 		= 'mysql.bundle_every';
		const SETTING_MYSQL_LAST_BCP_TS 		= 'mysql.dt_last_bcp';
		const SETTING_MYSQL_LAST_BUNDLE_TS 		= 'mysql.dt_last_bundle';
		const SETTING_MYSQL_IS_BCP_RUNNING 		= 'mysql.isbcprunning';
		const SETTING_MYSQL_IS_BUNDLE_RUNNING 	= 'mysql.isbundlerunning';
				
		const SETTING_MYSQL_BCP_SERVER_ID 		= 'mysql.bcp_server_id';
		const SETTING_MYSQL_BUNDLE_SERVER_ID 	= 'mysql.bundle_server_id';
		
		const SETTING_MYSQL_DATA_STORAGE_ENGINE = 'mysql.data_storage_engine';
		const SETTING_MYSQL_MASTER_EBS_VOLUME_ID= 'mysql.master_ebs_volume_id';
		const SETTING_MYSQL_EBS_VOLUME_SIZE 	= 'mysql.ebs_volume_size';
		const SETTING_MYSQL_SLAVE_TO_MASTER 	= 'mysql.slave_to_master';
		
		const SETTING_MYSQL_BCP_MASTER_IF_NO_SLAVES = 'mysql.bcp_master_if_no_slaves';
		/////////////////////////////////////////////////
		
		/* MySQL users credentials */
		const SETTING_MYSQL_ROOT_PASSWORD				= 'mysql.root_password';
		const SETTING_MYSQL_REPL_PASSWORD				= 'mysql.repl_password';		
		const SETTING_MYSQL_STAT_PASSWORD				= 'mysql.stat_password';
		const SETTING_MYSQL_SNAPSHOT_ID					= 'mysql.snapshot_id';
		const SETTING_MYSQL_LOG_FILE					= 'mysql.log_file';		
		const SETTING_MYSQL_LOG_POS						= 'mysql.log_pos';
		
		const SETTING_MTA_PROXY_GMAIL			=		'mta.proxy.gmail'; // settings for mail transfer on Google mail
		const SETTING_MTA_GMAIL_LOGIN			=		'mta.gmail.login';
		const SETTING_MTA_GMAIL_PASSWORD		=		'mta.gmail.password';
		
		
		
		
		public 
			$ID,
			$FarmID,
			$LaunchIndex,
			$RebootTimeout,
			$LaunchTimeout,
			$RoleID,
			$NewRoleID,
			$Platform;
		
		private $DB,
				$RoleName,
				$RoleOrigin,
				$RoleAlias,
				$ImageID,
				$RolePrototype,
				$SettingsCache;
		
		private static $FieldPropertyMap = array(
			'id' 			=> 'ID',
			'farmid'		=> 'FarmID',
			'role_id'		=> 'RoleID',
			'new_role_id'	=> 'NewRoleID',
			'launch_index'	=> 'LaunchIndex',
			'reboot_timeout'=> 'RebootTimeout',
			'launch_timeout'=> 'LaunchTimeout',
			'platform'		=> 'Platform'
		);
		
		/**
		 * Constructor
		 * @param $instance_id
		 * @return void
		 */
		public function __construct($farm_roleid)
		{
			$this->DB = Core::GetDBInstance();
			
			$this->ID = $farm_roleid;
			
			$this->Logger = Logger::getLogger(__CLASS__);
		}
		
		public function __sleep()
		{
			$this->GetRoleName();
			$this->GetRoleAlias();
			
			$retval = array("ID", "FarmID", "RoleID");
			array_push($retval, "RoleAlias");
			array_push($retval, "RoleName");
			
			return $retval;
		}
		
		public function __wakeup()
		{
			$this->DB = Core::GetDBInstance();
			$this->Logger = Logger::getLogger(__CLASS__);
		}
		
		/**
		 * 
		 * Returns DBFarmRole object by id
		 * @param $id
		 * @return DBFarmRole
		 */
		static public function LoadByID($id)
		{
			$db = Core::GetDBInstance();
			
			$farm_role_info = $db->GetRow("SELECT * FROM farm_roles WHERE id=?", array($id));
			if (!$farm_role_info)
				throw new Exception(sprintf(_("Farm Role ID #%s not found"), $id));
				
			$DBFarmRole = new DBFarmRole($farm_role_info['id']);
			foreach (self::$FieldPropertyMap as $k=>$v)
				$DBFarmRole->{$v} = $farm_role_info[$k];
				
			return $DBFarmRole;
		}
		
		/**
		 * Load DBInstance by database id
		 * @param $id
		 * @return DBFarmRole
		 */
		static public function Load($farmid, $roleid)
		{
			$db = Core::GetDBInstance();
			
			$farm_role_info = $db->GetRow("SELECT * FROM farm_roles WHERE farmid=? AND (role_id=? OR new_role_id=?)", array($farmid, $roleid, $roleid));
			if (!$farm_role_info)
				throw new Exception(sprintf(_("Role #%s is not assigned to farm #%s"), $roleid, $farmid));
				
			$DBFarmRole = new DBFarmRole($farm_role_info['id']);
			foreach (self::$FieldPropertyMap as $k=>$v)
				$DBFarmRole->{$v} = $farm_role_info[$k];
				
			return $DBFarmRole;
		}
		
		/**
		 * Returns DBFarm Object
		 * @return DBFarm
		 */
		public function GetFarmObject()
		{
			if (!$this->DBFarm)
				$this->DBFarm = DBFarm::LoadByID($this->FarmID);
				
			return $this->DBFarm;
		}
		
		/**
		 * Returns role alias
		 * @return string
		 */
		public function GetRoleAlias()
		{
			if (!$this->RoleAlias)
				$this->RoleAlias = $this->DB->GetOne("SELECT alias FROM roles WHERE id=?", array($this->RoleID));
				
			return $this->RoleAlias;
		}
		
		/**
		 * Returns Role origin //Shared, Custom, Contributed
		 * @return string
		 */
		public function GetRoleOrigin()
		{
			if (!$this->RoleOrigin)
				$this->RoleOrigin = $this->DB->GetOne("SELECT roletype FROM roles WHERE id=?", array($this->RoleID));
				
			return $this->RoleOrigin;
		}
		
		
		/**
		 * Returns Role name
		 * @return string
		 */
		public function GetRoleName()
		{
			if (!$this->RoleName)
				$this->RoleName = $this->DB->GetOne("SELECT name FROM roles WHERE id=?", array($this->RoleID));
				
			return $this->RoleName;
		}
		
		public function GetImageId()
		{
			if (!$this->ImageID)
				$this->ImageID = $this->DB->GetOne("SELECT ami_id FROM roles WHERE id=?", array($this->RoleID));
				
			return $this->ImageID;
		}
		
		/**
		 * Returns role prototype
		 * @return string
		 */
		public function GetRolePrototype()
		{
			if (!$this->RolePrototype)
				$this->RolePrototype = $this->DB->GetOne("SELECT prototype_role FROM roles WHERE id=?", array($this->RoleID));
				
			return $this->RolePrototype;
		}
		
		/**
		 * Returns role prototype
		 * @return string
		 */
		public function GetRoleID()
		{
			return $this->RoleID;
		}
		
		/**
		 * Delete role from farm
		 * @return void
		 */
		public function Delete()
		{
			// Delete settings
			$this->DB->Execute("DELETE FROM farm_role_settings WHERE farm_roleid=?", array($this->ID));
			
			//
			$this->DB->Execute("DELETE FROM farm_roles WHERE id=?", array($this->ID));
                           
            // Clear farm role options & scripts
			$this->DB->Execute("DELETE FROM farm_role_options WHERE farm_roleid=?", array($this->ID));
			$this->DB->Execute("DELETE FROM farm_role_scripts WHERE farm_roleid=?", array($this->ID));
			
			// Clear apache vhosts and update DNS zones
			$this->DB->Execute("DELETE FROM apache_vhosts WHERE farm_roleid=?", array($this->ID));
			$this->DB->Execute("UPDATE dns_zones SET farm_roleid='0' WHERE farm_roleid=?", array($this->ID));
		}
		
		public function GetPendingInstancesCount()
		{
			return $this->DB->GetOne("SELECT COUNT(*) FROM servers WHERE status IN(?,?,?) AND farm_roleid=?",
            	array(SERVER_STATUS::INIT, SERVER_STATUS::PENDING, SERVER_STATUS::PENDING_LAUNCH, $this->ID)
            );
		}
		
		public function GetRunningInstancesCount()
		{
			return $this->DB->GetOne("SELECT COUNT(*) FROM servers WHERE status = ? AND farm_roleid=?",
            	array(SERVER_STATUS::RUNNING, $this->ID)
            );
		}
		
		public function GetServersByFilter($filter_args = array(), $ufilter_args = array())
		{
			$sql = "SELECT server_id FROM servers WHERE `farm_roleid`=?";
			$args = array($this->ID);
			foreach ((array)$filter_args as $k=>$v)
			{
				if (is_array($v))
				{	
					foreach ($v as $vv)
						array_push($args, $vv);
					
					$sql .= " AND `{$k}` IN (".implode(",", array_fill(0, count($v), "?")).")";
				}
				else
				{
					$sql .= " AND `{$k}`=?";
					array_push($args, $v);
				}
			}
			
			foreach ((array)$ufilter_args as $k=>$v)
			{
				if (is_array($v))
				{	
					foreach ($v as $vv)
						array_push($args, $vv);
					
					$sql .= " AND `{$k}` NOT IN (".implode(",", array_fill(0, count($v), "?")).")";	
				}
				else
				{
					$sql .= " AND `{$k}`!=?";
					array_push($args, $v);
				}
			}
			
			$res = $this->DB->GetAll($sql, $args);
			
			$retval = array();
			foreach ((array)$res as $i)
			{
				if ($i['server_id'])
					$retval[] = DBServer::LoadByID($i['server_id']);
			}
			
			return $retval;
		}
		
		/**
		 * Returns all role settings
		 * @return unknown_type
		 */
		public function GetAllSettings()
		{
			$settings = $this->DB->GetAll("SELECT * FROM farm_role_settings WHERE farm_roleid=?", array($this->ID));
			$retval = array();
			foreach ($settings as $setting)
				$retval[$setting['name']] = $setting['value']; 
			
			$this->SettingsCache = array_merge($this->SettingsCache, $retval);
				
			return $retval;
		}
		
		/**
		 * Set farm role setting
		 * @param string $name
		 * @param mixed $value
		 * @return void
		 */
		public function SetSetting($name, $value)
		{
			if ($value === "" || $value === null)
			{
				$this->DB->Execute("DELETE FROM farm_role_settings WHERE name=? AND farm_roleid=?", array(
					$name, $this->ID
				));
			}
			else
			{
				$this->DB->Execute("INSERT INTO farm_role_settings SET name=?, value=?, farm_roleid=? ON DUPLICATE KEY UPDATE value=?",
					array($name, $value, $this->ID, $value)
				);
			}
			
			$this->SettingsCache[$name] = $value;
			
			return true;
		}
		
		/**
		 * Get Role setting by name
		 * @param string $name
		 * @return mixed
		 */
		public function GetSetting($name)
		{
			if (!$this->SettingsCache[$name])
			{
				$this->SettingsCache[$name] = $this->DB->GetOne("SELECT value FROM farm_role_settings WHERE name=? AND farm_roleid=?",
					array($name, $this->ID)
				);
			}
			
			return $this->SettingsCache[$name];
		}
		
		public function ClearSettings($filter = "")
		{
			$this->DB->Execute("DELETE FROM farm_role_settings WHERE name LIKE '%{$filter}%' AND farm_roleid=?",
				array($this->ID)
			);
			
			$this->SettingsCache = array();
		}
		
		private function Unbind () {
			$row = array();
			foreach (self::$FieldPropertyMap as $field => $property) {
				$row[$field] = $this->{$property};
			}
			
			return $row;		
		}
		
		function Save () {
				
			$row = $this->Unbind();
			unset($row['id']);
			
			// Prepare SQL statement
			$set = array();
			$bind = array();
			foreach ($row as $field => $value) {
				$set[] = "`$field` = ?";
				$bind[] = $value;
			}
			$set = join(', ', $set);
	
			try	{
				// Perform Update
				$bind[] = $this->ID;
				$this->DB->Execute("UPDATE farm_roles SET $set WHERE id = ?", $bind);
				
			} catch (Exception $e) {
				throw new Exception ("Cannot save farm role. Error: " . $e->getMessage(), $e->getCode());			
			}
		}
	}
?>