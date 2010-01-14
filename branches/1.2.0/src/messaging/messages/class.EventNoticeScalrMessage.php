<?php

	class EventNoticeScalrMessage extends ScalrMessage
	{
		const SNMP_TRAP = "SNMPv2-MIB::snmpTrap.5.1 SNMPv2-MIB::sysUpTime.0 s \"{MessageID}\" SNMPv2-MIB::sysName.0 s \"{InternalIP}\" SNMPv2-MIB::sysLocation.0 s \"{EventID}\" SNMPv2-MIB::sysDescr.0 s \"{RoleName}\" SNMPv2-MIB::sysContact.0 s \"{EventName}\"";
		
		public $EventID;
		public $InternalIP;
		public $RoleName;
		public $EventName;
		
		public function __construct($internal_ip, $event_id, $role_name, $event_name)
		{
			parent::__construct();
			
			$this->EventID = $event_id;
			$this->InternalIP = ($internal_ip) ? $internal_ip : "0.0.0.0";
			$this->RoleName = $role_name;
			$this->EventName = $event_name;
		}
	}
?>