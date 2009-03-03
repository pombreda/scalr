<?php

	class EventNoticeScalrMessage extends ScalrMessage
	{
		const SNMP_TRAP = "SNMPv2-MIB::snmpTrap.5.1 SNMPv2-MIB::sysUpTime.0 s \"{MessageID}\" SNMPv2-MIB::sysName.0 s \"{InternalIP}\" SNMPv2-MIB::sysLocation.0 s \"{RoleAlias}\" SNMPv2-MIB::sysDescr.0 s \"{RoleName}\" SNMPv2-MIB::sysContact.0 s \"{EventName}\"";
		
		public $RoleAlias;
		public $InternalIP;
		public $RoleName;
		public $EventName;
		
		public function __construct($internal_ip, $role_alias, $role_name, $event_name)
		{
			parent::__construct();
			
			$this->RoleAlias = $role_alias;
			$this->InternalIP = $internal_ip;
			$this->RoleName = $role_name;
			$this->EventName = $event_name;
		}
	}
?>