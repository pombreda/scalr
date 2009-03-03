<?php
	
	class DBInstance
	{
		public 
			$ID,
			$FarmID,
			$InstanceID,
			$State,
			$AMIID,
			$InternalIP,
			$ExternalIP,
			$IsDBMaster,
			$IncludeInDNS,
			$RoleName,
			$AvailZone,
			$Index,
			$Region,
			$ScalarizrPackageVersion;
		
		private $DB;
		private $Client;
		private $Farm;
		
		private static $FieldPropertyMap = array(
			'id' 			=> 'ID',
			'farmid'		=> 'FarmID',
			'instance_id'	=> 'Email',
			'state'			=> 'State',
			'ami_id' 		=> 'AMIID',
			'internal_ip'	=> 'InternalIP',
			'external_ip'	=> 'ExternalIP',
			'isdbmaster'	=> 'IsDBMaster',
			'isactive'		=> 'IncludeInDNS',
			'role_name'		=> 'RoleName',
			'avail_zone'	=> 'AvailZone',
			'index'			=> 'Index',
			'Region'		=> 'Region',
			'scalarizr_pkg_version'	=> 'ScalarizrPackageVersion'
		);
		
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
		
		/**
		 * Return information about scalarizr version installed on instance
		 * @return array
		 */
		public function GetScalarizrVersion()
		{
			preg_match("/^([0-9]+)\.([0-9]+)-([0-9]+)$/", $this->ScalarizrPackageVersion, $matches);
			return array("major" => $matches[1], "minor" => $matches[2], "revision" => $matches[3]);
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
?>