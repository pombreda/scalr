<?php
	
	class MakeMySQLBackupScalrMessage extends ScalrMessage
	{
		const SNMP_TRAP = "SNMPv2-MIB::snmpTrap.12.2 SNMPv2-MIB::sysUpTime.0 s \"{MessageID}\" SNMPv2-MIB::sysName.0 s \"backup\"";
		
		public function __construct()
		{
			parent::__construct();
		}
	}
?>