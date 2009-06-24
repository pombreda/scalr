<?php
	
	class NewMySQLMasterUpScalrMessage extends ScalrMessage
	{
		const SNMP_TRAP = "SNMPv2-MIB::snmpTrap.10.1 SNMPv2-MIB::sysUpTime.0 s \"{MessageID}\" SNMPv2-MIB::sysName.0 s \"{InternalIP}\" SNMPv2-MIB::sysLocation.0 s \"{SnapshotURL}\" SNMPv2-MIB::sysDescr.0 s \"{RoleName}\"";
		
		public $InternalIP;
		public $SnapshotURL;
		public $RoleName;
		
		public function __construct($internal_ip, $snapshot_url, $role_name)
		{
			parent::__construct();
			
			$this->SnapshotURL = $snapshot_url;
			$this->InternalIP = $internal_ip;
			$this->RoleName = $role_name;
		}
	}
?>