<?php
	
	class DBInstance
	{
		const PROPERTY_SCALARIZR_PACKAGE_VERSION = 'scalarizr_pkg_version';
		
		public $ID;
		public $FarmID;
		public $ClientID;
		public $InstanceID;
		public $State;
		public $AMIID;
		public $InternalIP;
		public $ExternalIP;
		public $IsDBMaster;
		public $IncludeInDNS;
		public $RoleName;
		public $AvailZone;
		public $Index;
		public $Region;
		public $FarmRoleID;
		public $Uptime;
		public $IsRebootLaunched;
		public $ReplaceIID;
		public $ScalarizrPackageVersion;
		
		private $DB;
		private $Client;
		private $DBFarm;
		private $DBFarmRole;
		private $SettingsCache = array();
		
		private static $FieldPropertyMap = array(
			'id' 			=> 'ID',
			'farmid'		=> 'FarmID',
			'instance_id'	=> 'InstanceID',
			'state'			=> 'State',
			'ami_id' 		=> 'AMIID',
			'internal_ip'	=> 'InternalIP',
			'external_ip'	=> 'ExternalIP',
			'isdbmaster'	=> 'IsDBMaster',
			'isactive'		=> 'IncludeInDNS',
			'role_name'		=> 'RoleName',
			'avail_zone'	=> 'AvailZone',
			'index'			=> 'Index',
			'region'		=> 'Region',
			'farm_roleid'	=> 'FarmRoleID',
			'uptime'		=> 'Uptime',
			'replace_iid'	=> 'ReplaceIID',
			'isrebootlaunched'	=> 'IsRebootLaunched',
			self::PROPERTY_SCALARIZR_PACKAGE_VERSION => 'ScalarizrPackageVersion'
		);
		
		private $Logger;
		
		/**
		 * Constructor
		 * @param $instance_id
		 * @return void
		 */
		public function __construct($instance_id)
		{
			$this->InstanceID = $instance_id;
			$this->DB = Core::GetDBInstance();
			
			$this->Logger = Logger::getLogger(__CLASS__);
		}
		
		public function __sleep()
		{
			// Init objects
			$this->GetDBFarmObject();
			$this->GetDBFarmRoleObject();
			
			$retval = array("ID", "FarmID", "InstanceID", "AMIID", "InternalIP", "ExternalIP", "AvailZone", "Index", "Region", "FarmRoleID", "RoleName");
			array_push($retval, "DBFarmRole");
			array_push($retval, "DBFarm");
			
			return $retval;
		}
		
		public function __wakeup()
		{
			$this->DB = Core::GetDBInstance();
			$this->Logger = Logger::getLogger(__CLASS__);
		}
		
		/**
		 * 
		 * @param int $farm_roleid
		 * @param int $index
		 * @return DBInstance
		 */
		static public function LoadByFarmRoleIDAndIndex($farm_roleid, $index)
		{
			$db = Core::GetDBInstance();
			
			$id = $db->GetRow("SELECT id FROM farm_instances WHERE farm_roleid = ? AND `index` = ?", 
				array($farm_roleid, $index)
			);
			
			if (!$id)
			{
				throw new Exception(sprintf(
					_("Instance with FarmRoleID #%s and index #%s not found in database"), 
					$farm_roleid,
					$index
				));
			}
			
			return self::LoadByID($id);		
		}
		
		/**
		 * Load DBInstance by database id
		 * @param $id
		 * @return DBInstance
		 */
		static public function LoadByID($id)
		{
			$db = Core::GetDBInstance();
			
			$instance_info = $db->GetRow("SELECT * FROM farm_instances WHERE id=?", array($id));
			
			if (!$instance_info)
				throw new Exception(sprintf(_("Instance ID#%s not found in database"), $id));
				
			$DBInstance = new DBInstance($instance_info['instance_id']);
			
			foreach(self::$FieldPropertyMap as $k=>$v)
			{
				if ($instance_info[$k])
					$DBInstance->{$v} = $instance_info[$k];
			}
			
			return $DBInstance;
		}
		
		/**
		 * Load DBInstance by Amazon Instance ID
		 * @param $iid
		 * @return DBInstance
		 */
		static public function LoadByIID($iid)
		{
			$db = Core::GetDBInstance();
			
			$id = $db->GetRow("SELECT id FROM farm_instances WHERE instance_id=?", array($iid));
			
			if (!$id)
				throw new Exception(sprintf(_("Instance Amazon ID#%s not found in database"), $iid));
			
			return self::LoadByID($id);
		}
		
		public function ReLoad()
		{
			$instance_info = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?", array($this->ID));
			
			if (!$instance_info)
				throw new Exception(sprintf(_("Cannot reload DBInstance object. Instance ID#%s not found in database"), $this->ID));
				
			foreach(self::$FieldPropertyMap as $k=>$v)
			{
				if ($instance_info[$k])
					$this->{$v} = $instance_info[$k];
			}
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
		 * 
		 * Returns DBFarmRole object
		 * @return DBFarmRole
		 */
		public function GetDBFarmRoleObject()
		{
			if (!$this->DBFarmRole)
				$this->DBFarmRole = DBFarmRole::LoadByID($this->FarmRoleID);
				
			return $this->DBFarmRole;
		}
		
		/**
		 * returns DBFarm object
		 * @return DBFarm
		 */
		public function GetDBFarmObject()
		{
			if (!$this->DBFarm)
				$this->DBFarm = DBFarm::LoadByID($this->FarmID);
				
			return $this->DBFarm;
		}
		
		/**
		 * Returns all instance settings
		 * @return unknown_type
		 */
		public function GetAllSettings()
		{
			$settings = $this->DB->GetAll("SELECT * FROM farm_instance_settings WHERE farm_instanceid=?", array($this->ID));
			$retval = array();
			foreach ($settings as $setting)
				$retval[$setting['name']] = $setting['value']; 
			
			$this->SettingsCache = array_merge($this->SettingsCache, $retval);
				
			return $retval;
		}
		
		/**
		 * Set Instance setting
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
				$this->DB->Execute("REPLACE INTO farm_instance_settings SET `farm_instanceid`=?, `name`=?, `value`=?",
					array($this->ID, $name, $value)
				);
				
				$this->SettingsCache[$name] = $value;
				
				return true;
			}			
			else
				throw new Exception("Unknown instance setting setting '{$name}'");
		}
		
		/**
		 * Get Instance setting by name
		 * @param string $name
		 * @return mixed
		 */
		public function GetSetting($name)
		{
			if (!$this->SettingsCache[$name])
			{
				$this->SettingsCache[$name] = $this->DB->GetOne("SELECT `value` FROM `farm_instance_settings` WHERE `farm_instanceid`=? AND `name` = ?",
				array(
					$this->ID,
					$name
				));
			}
			
			return $this->SettingsCache[$name];
		}
		
		/**
		 * Update specified property
		 * @param string $prop
		 * @param string $value
		 * @return bool
		 */
		public function UpdateProperty($prop, $value)
		{
			if (!self::$FieldPropertyMap[$prop])
				throw new Exception(sprintf(_("Invalid property name: %s"), $prop));
				
			return $this->DB->Execute("UPDATE farm_instances SET `{$prop}`=? WHERE id=?", array($value, $this->ID));
		}
		
		/**
		 * Return information about scalarizr version installed on instance
		 * @return array
		 */
		public function GetScalarizrVersion()
		{
			preg_match("/^([0-9]+)\.([0-9]+)-([0-9]+)$/", $this->ScalarizrPackageVersion, $matches);
			return array("major" => $matches[1], "minor" => $matches[2], "revision" => $matches[3]);
		}
		
		public function IsSupported($v)
		{
			preg_match("/^([0-9]+)\.([0-9]+)\-([0-9]+)$/si", $v, $matches);
			
			$version = $this->GetScalarizrVersion();
			
			if ($version['major'] > $matches[1])
				return true;
			elseif ($version['major'] == $matches[1] && $version['minor'] > $matches[2])
				return true;
			elseif ($version['major'] == $matches[1] && $version['minor'] == $matches[2] && $version['revision'] >= $matches[3])
				return true;
				
			return false;
		}
		
		public function GetScalarizrConfig()
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			$Client = Client::Load($farminfo['clientid']);
			
			$config = "<config>
			  <aws>
			   <account-id>{$Client->AWSAccountID}</account-id>
			   <access>
			     <key>{$Client->AWSAccessKey}</key>
			     <key-id>{$Client->AWSAccessKeyID}</key-id>
			   </access>
			   <keypair>
			     <cert>{$Client->AWSCertificate}</cert>
			     <pkey>{$Client->AWSPrivateKey}</pkey>
			   </keypair>
			 </aws>
			 <scalr>
			   <access>
			     <key>{$Client->GetScalrAPIKey()}</key>
			     <key-id>{$Client->ScalrKeyID}</key-id>
			   </access>
			   <callback-service-url>".CONFIG::$HTTP_PROTO."://".CONFIG::$EVENTHANDLER_URL."/cb_service.php</callback-service-url>
			 </scalr>
			</config>";
		}
		
		/**
		 * Send message to instance
		 * @param ScalrMessage $message
		 * @return bool
		 */
		public function SendMessage(ScalrMessage $message)
		{
			//TODO: Create MessagingTransport object that implements IMessagingTransport.
			//TODO: SNMPMessagingTransport & AmazonSQSMessagingTransport
			
			// Add message to database
			$this->DB->Execute("INSERT INTO messages SET
				messageid	= ?,
				instance_id	= ?,
				message		= ?,
				dtlastdeliveryattempt = NOW()
			ON DUPLICATE KEY UPDATE delivery_attempts = delivery_attempts+1, dtlastdeliveryattempt = NOW()  
			", array(
				$message->MessageID,
				$this->InstanceID,
				XMLMessageSerializer::Serialize($message) 
			));
		
			
			if ($this->IsSupported("0.5-1"))
			{
				if (!$this->ClientID)
					$this->ClientID = $this->DB->GetOne("SELECT clientid FROM farms WHERE id=?", array($this->FarmID));

				$Client = Client::Load($this->ClientID);
				
				$AmazonSQS = AmazonSQS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
				
				try
				{
					$AmazonSQS->CreateQueue("queue-{$this->InstanceID}", 30);
				}
				catch(Exception $e)
				{
					$this->Logger->warn("Cannot create queue: {$e->getMessage()}");	
				}
				
				$messageID = $AmazonSQS->SendMessage("queue-{$this->InstanceID}", XMLMessageSerializer::Serialize($message));
				
				$this->Logger->info("SQSMessage sent. MessageID: {$messageID}");
			}
			else
			{
				if ($this->ExternalIP)
				{
					$community = $this->DB->GetOne("SELECT hash FROM farms WHERE id=?", array($this->FarmID));
					
					$SNMP = new SNMP();
					$SNMP->Connect($this->ExternalIP, null, $community, null, null, true);
					
					$trap = $message->GetSNMPTrap();
					$res = $SNMP->SendTrap($trap);
					
					$this->Logger->info("[FarmID: {$this->FarmID}] Sending message ".get_class($message)." via SNMP ({$trap}) to '{$this->InstanceID}' ('{$this->ExternalIP}') complete ({$res})");
				}
			}
		}
	}
?>