<?php
	
	class DBFarm
	{
		const SETTING_AWS_PRIVATE_KEY 			= 'aws.ssh_private_key';
		const SETTING_AWS_PUBLIC_KEY 			= 'aws.ssh_public_key';
		const SETTING_AWS_KEYPAIR_NAME 			= 'aws.keypair_name';
		const SETTING_AWS_S3_BUCKET_NAME 		= 'aws.s3_bucket_name';
		
		const SETTING_MYSQL_BCP_ENABLED 		= 'mysql.enable_bcp';
		const SETTING_MYSQL_BCP_EVERY 			= 'mysql.bcp_every';
		const SETTING_MYSQL_BUNDLE_ENABLED 		= 'mysql.enable_bundle';
		const SETTING_MYSQL_BUNDLE_EVERY 		= 'mysql.bundle_every';
		const SETTING_MYSQL_LAST_BCP_TS 		= 'mysql.dt_last_bcp';
		const SETTING_MYSQL_LAST_BUNDLE_TS 		= 'mysql.dt_last_bundle';
		const SETTING_MYSQL_IS_BCP_RUNNING 		= 'mysql.isbcprunning';
		const SETTING_MYSQL_IS_BUNDLE_RUNNING 	= 'mysql.isbundlerunning';
		const SETTING_MYSQL_BCP_INSTANCE_ID 	= 'mysql.bcp_instance_id';
		const SETTING_MYSQL_BUNDLE_INSTANCE_ID 	= 'mysql.bundle_instance_id';
		
		const SETTING_MYSQL_DATA_STORAGE_ENGINE = 'mysql.data_storage_engine';
		const SETTING_MYSQL_MASTER_EBS_VOLUME_ID = 'mysql.master_ebs_volume_id';
		const SETTING_MYSQL_EBS_VOLUME_SIZE 	= 'mysql.ebs_volume_size';
		
		
		public 
			$ID,
			$ClientID,
			$Name,
			$Hash,
			$Status,
			$ScalarizrCertificate,
			$Region;
		
		private $DB;
		
		private $SettingsCache = array();
		
		private static $FieldPropertyMap = array(
			'id' 			=> 'ID',
			'clientid'		=> 'ClientID',
			'name'			=> 'Name',
			'hash'			=> 'Hash',
			'status'		=> 'Status',
			'region'		=> 'Region',
			'scalarizr_cert'=> 'ScalarizrCertificate'
		);
		
		/**
		 * Constructor
		 * @param $instance_id
		 * @return void
		 */
		public function __construct($id)
		{
			$this->ID = $id;
			$this->DB = Core::GetDBInstance();
			
			$this->Logger = Logger::getLogger(__CLASS__);
		}
		
		public function __sleep()
		{
			return array("ID", "ClientID", "Name", "Region");		
		}
		
		public function __wakeup()
		{
			$this->DB = Core::GetDBInstance();
			$this->Logger = Logger::getLogger(__CLASS__);
		}
		
		public function GetInstancesByFilter($filter_args = array())
		{
			$sql = "SELECT id FROM farm_instances WHERE `farmid`=?";
			$args = array($this->ID);
			foreach ((array)$filter_args as $k=>$v)
			{
				$sql .= " AND `{$k}`=?";
				array_push($args, $v);
			}
			
			$res = $this->DB->GetAll($sql, $args);
			$retval = array();
			foreach ((array)$res as $i)
			{
				if ($i['id'])
					$retval[] = DBInstance::LoadByID($i['id']);
			}
			
			return $retval;
		}
		
		/**
		 * Returns all farm settings
		 * @return unknown_type
		 */
		public function GetAllSettings()
		{
			$settings = $this->DB->GetAll("SELECT * FROM farm_settings WHERE farmid=?", array($this->ID));
			$retval = array();
			foreach ($settings as $setting)
				$retval[$setting['name']] = $setting['value']; 
			
			$this->SettingsCache = array_merge($this->SettingsCache, $retval);
				
			return $retval;
		}
		
		/**
		 * Set farm setting
		 * @param string $name
		 * @param mixed $value
		 * @return void
		 */
		public function SetSetting($name, $value)
		{
			$Reflect = new ReflectionClass($this);
			$consts = array_values($Reflect->getConstants());
			if (in_array($name, $consts))
			{
				$this->DB->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
					array($this->ID, $name, $value)
				);
				
				$this->SettingsCache[$name] = $value;
				
				return true;
			}			
			else
				throw new Exception("Unknown farm setting '{$name}'");
		}
		
		/**
		 * Get Farm setting by name
		 * @param string $name
		 * @return mixed
		 */
		public function GetSetting($name)
		{
			if (!$this->SettingsCache[$name])
			{
				$this->SettingsCache[$name] = $this->DB->GetOne("SELECT `value` FROM `farm_settings` WHERE `farmid`=? AND `name` = ?",
				array(
					$this->ID,
					$name
				));
			}
			
			return $this->SettingsCache[$name];
		}
		
		/**
		 * Load DBInstance by database id
		 * @param $id
		 * @return DBFarm
		 */
		static public function LoadByID($id)
		{
			$db = Core::GetDBInstance();
			
			$farm_info = $db->GetRow("SELECT * FROM farms WHERE id=?", array($id));
			
			if (!$farm_info)
				throw new Exception(sprintf(_("Farm ID#%s not found in database"), $id));
				
			$DBFarm = new DBFarm($id);

			foreach(self::$FieldPropertyMap as $k=>$v)
			{
				if ($farm_info[$k])
					$DBFarm->{$v} = $farm_info[$k];
			}
			
			return $DBFarm;
		}
	}
?>