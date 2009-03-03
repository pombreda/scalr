<?php
	
	class ScalarizrUpdateAvailableScalrMessage extends ScalrMessage
	{
		const SNMP_TRAP = "SNMPv2-MIB::snmpTrap.10.2 SNMPv2-MIB::sysUpTime.0 s \"{MessageID}\"";
		
		public function __construct()
		{
			parent::__construct();
		}
	}
?>