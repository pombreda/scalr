<?
	final class SNMP_TRAP
	{
		const MYSQL_START_BACKUP 	= "SNMPv2-MIB::snmpTrap.12.2 SNMPv2-MIB::sysName.0 s \"backup\"";
		const MYSQL_START_REBUNDLE 	= "SNMPv2-MIB::snmpTrap.12.2 SNMPv2-MIB::sysName.0 s \"bundle\"";
		const HOST_INIT				= "SNMPv2-MIB::snmpTrap.12.1 SNMPv2-MIB::sysName.0 s \"%s\"";
		const START_REBUNDLE		= "SNMPv2-MIB::snmpTrap.12.0 SNMPv2-MIB::sysName.0 s \"%s\" SNMPv2-MIB::sysLocation.0 s \"0\"";
		const VHOST_RECONFIGURE		= "SNMPv2-MIB::snmpTrap.11.2 SNMPv2-MIB::sysName.0 s \"%s\" SNMPv2-MIB::sysLocation.0 s \"%s\"";
		const HOST_UP				= "SNMPv2-MIB::snmpTrap.11.1 SNMPv2-MIB::sysName.0 s \"%s\" SNMPv2-MIB::sysLocation.0 s \"%s\" SNMPv2-MIB::sysDescr.0 s \"%s\"";
		const HOST_DOWN 			= "SNMPv2-MIB::snmpTrap.11.0 SNMPv2-MIB::sysName.0 s \"%s\" SNMPv2-MIB::sysLocation.0 s \"%s\" SNMPv2-MIB::sysDescr.0 s \"%s\" SNMPv2-MIB::sysContact.0 s \"%s\"";
		const LAUNCH_APT_UPGRADE	= "SNMPv2-MIB::snmpTrap.10.2";
		const NEW_MYSQL_MASTER		= "SNMPv2-MIB::snmpTrap.10.1 SNMPv2-MIB::sysName.0 s \"%s\" SNMPv2-MIB::sysLocation.0 s \"%s\"";
		
	}
?>