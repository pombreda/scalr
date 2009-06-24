<?php
	
	class DBFarmRole
	{
		const SETTING_EXCLUDE_FROM_DNS = 'dns.exclude_role';
		const SETTING_SCALING_MIN_INSTANCES = 'scaling.min_instances';
		const SETTING_SCALING_MAX_INSTANCES = 'scaling.max_instances';
		const SETTING_SCALING_POLLING_INTERVAL = 'scaling.polling_interval';
		const SETTING_SCALING_LAST_POLLING_TIME = 'scaling.last_polling_time';
		
		public 
			$ID,
			$FarmID,
			$AMIID,
			$IsAutoEIPEnabled,
			$IsAutoEBSEnabled,
			$LaunchIndex;
		
		private $DB,
				$RoleName;
		
		private static $FieldPropertyMap = array(
			'id' 			=> 'ID',
			'farmid'		=> 'FarmID',
			'ami_id'		=> 'AMIID',
			'use_ebs'		=> 'IsAutoEBSEnabled',
			'use_elastic_ips' => 'IsAutoEIPEnabled',
			'launch_index'	=> 'LaunchIndex'
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
		
		/**
		 * 
		 * Enter description here...
		 * @param $id
		 * @return DBFarmRole
		 */
		static public function LoadByID($id)
		{
			$db = Core::GetDBInstance();
			
			$farm_role_info = $db->GetRow("SELECT * FROM farm_amis WHERE id=?", array($id));
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
			
			$farm_role_info = $db->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND (ami_id=? OR replace_to_ami=?)", array($farmid, $amiid, $amiid));
			if (!$farm_role_info)
				throw new Exception(sprintf(_("AMIID %s is not assigned to farm #%s"), $amiid, $farmid));
				
			$DBFarmRole = new DBFarmRole($farm_role_info['id']);
			foreach (self::$FieldPropertyMap as $k=>$v)
				$DBFarmRole->{$v} = $farm_role_info[$k];
				
			return $DBFarmRole;
		}
		
		/**
		 * Returns Role name
		 * @return string
		 */
		public function GetRoleName()
		{
			if (!$this->RoleName)
				$this->RoleName = $this->DB->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($this->AMIID));
				
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
			$this->DB->Execute("DELETE FROM farm_amis WHERE id=?", array($this->ID));
                           
            // Clear farm role options & scripts
			$this->DB->Execute("DELETE FROM farm_role_options WHERE farmid=? AND ami_id=?", array($this->FarmID, $this->AMIID));
			$this->DB->Execute("DELETE FROM farm_role_scripts WHERE farmid=? AND ami_id=?", array($this->FarmID, $this->AMIID));
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
		}
		
		/**
		 * Get Role setting by name
		 * @param string $name
		 * @return mixed
		 */
		public function GetSetting($name)
		{
			return $this->DB->GetOne("SELECT value FROM farm_role_settings WHERE name=? AND farm_roleid=?",
				array($name, $this->ID)
			);
		}
	}
?>