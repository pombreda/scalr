<?php
	
	class DBFarmRole
	{
		const SETTING_EXCLUDE_FROM_DNS					= 	'dns.exclude_role';
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
		
		const SETTING_MYSQL_EBS_SNAPS_ROTATE	= 		'mysql.ebs.rotate';
		const SETTING_MYSQL_EBS_SNAPS_ROTATION_ENABLED	= 'mysql.ebs.snaps_rotation_enabled';
		
		const SETTING_AWS_INSTANCE_TYPE 		= 		'aws.instance_type';
		const SETTING_AWS_AVAIL_ZONE			= 		'aws.availability_zone';
		const SETTING_AWS_USE_ELASIC_IPS		= 		'aws.use_elastic_ips';
		
		/** MySQL PMA Credentials **/
		const SETTING_MYSQL_PMA_USER			=		'mysql.pma.username';
		const SETTING_MYSQL_PMA_PASS			=		'mysql.pma.password';
		const SETTING_MYSQL_PMA_REQUEST_TIME	=		'mysql.pma.request_time';
		const SETTING_MYSQL_PMA_REQUEST_ERROR	=		'mysql.pma.request_error';
		
		const SETTING_AWS_USE_EBS				=		'aws.use_ebs';
		const SETTING_AWS_EBS_SIZE				=		'aws.ebs_size';
		const SETTING_AWS_EBS_SNAPID			=		'aws.ebs_snapid';
		const SETTING_AWS_EBS_MOUNT				=		'aws.ebs_mount';
		const SETTING_AWS_EBS_MOUNTPOINT		=		'aws.ebs_mountpoint';
		
		const SETTING_AWS_AKI_ID				= 		'aws.aki_id';
		const SETTING_AWS_ARI_ID				= 		'aws.ari_id';
		
		const SETTING_AWS_ENABLE_CW_MONITORING	= 'aws.enable_cw_monitoring';
		
		const SETTING_MTA_PROXY_GMAIL			=		'mta.proxy.gmail'; // settings for mail transfer on Google mail
		const SETTING_MTA_GMAIL_LOGIN			=		'mta.gmail.login';
		const SETTING_MTA_GMAIL_PASSWORD		=		'mta.gmail.password';
		
		
		
		
		public 
			$ID,
			$FarmID,
			$AMIID,
			$LaunchIndex,
			$ReplaceToAMI;
		
		private $DB,
				$RoleName,
				$RoleOrigin,
				$RoleAlias,
				$SettingsCache;
		
		private static $FieldPropertyMap = array(
			'id' 			=> 'ID',
			'farmid'		=> 'FarmID',
			'ami_id'		=> 'AMIID',
			'launch_index'	=> 'LaunchIndex',
			'replace_to_ami'=> 'ReplaceToAMI'
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
			
			$retval = array("ID", "FarmID", "AMIID");
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
		static public function Load($farmid, $amiid)
		{
			$db = Core::GetDBInstance();
			
			$farm_role_info = $db->GetRow("SELECT * FROM farm_roles WHERE farmid=? AND (ami_id=? OR replace_to_ami=?)", array($farmid, $amiid, $amiid));
			if (!$farm_role_info)
				throw new Exception(sprintf(_("AMIID %s is not assigned to farm #%s"), $amiid, $farmid));
				
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
				$this->RoleAlias = $this->DB->GetOne("SELECT alias FROM roles WHERE ami_id=?", array($this->AMIID));
				
			return $this->RoleAlias;
		}
		
		/**
		 * Returns Role origin //Shared, Custom, Contributed
		 * @return string
		 */
		public function GetRoleOrigin()
		{
			if (!$this->RoleOrigin)
				$this->RoleOrigin = $this->DB->GetOne("SELECT roletype FROM roles WHERE ami_id=?", array($this->AMIID));
				
			return $this->RoleOrigin;
		}
		
		
		/**
		 * Returns Role name
		 * @return string
		 */
		public function GetRoleName()
		{
			if (!$this->RoleName)
				$this->RoleName = $this->DB->GetOne("SELECT name FROM roles WHERE ami_id=?", array($this->AMIID));
				
			return $this->RoleName;
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
		}
		
		public function GetPendingInstancesCount()
		{
			return $this->DB->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state IN(?,?) AND farmid=? AND farm_roleid=?",
            	array(INSTANCE_STATE::INIT, INSTANCE_STATE::PENDING, $this->FarmID, $this->ID)
            );
		}
		
		public function GetRunningInstancesCount()
		{
			return $this->DB->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state = ? AND farmid=? AND farm_roleid=?",
            	array(INSTANCE_STATE::RUNNING, $this->FarmID, $this->ID)
            );
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
			$this->DB->Execute("REPLACE INTO farm_role_settings SET name=?, value=?, farm_roleid=?",
				array($name, $value, $this->ID)
			);
			
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
	}
?>