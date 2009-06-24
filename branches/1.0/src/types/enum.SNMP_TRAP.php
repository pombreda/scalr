<?
	final class SNMP_TRAP
	{		
		// MakeMySQLBackupScalrMessage
		const MYSQL_START_BACKUP 	= "SNMPv2-MIB::snmpTrap.12.2 SNMPv2-MIB::sysName.0 s \"backup\"";
		
		// MakeMySQLDataBundleScalrMessage
		const MYSQL_START_REBUNDLE 	= "SNMPv2-MIB::snmpTrap.12.2 SNMPv2-MIB::sysName.0 s \"bundle\"";
		
		// HostInitScalrMessage
		const HOST_INIT				= "SNMPv2-MIB::snmpTrap.12.1 SNMPv2-MIB::sysName.0 s \"%s\"";

		// HostUpScalrMessage
		const HOST_UP				= "SNMPv2-MIB::snmpTrap.11.1 SNMPv2-MIB::sysName.0 s \"%s\" SNMPv2-MIB::sysLocation.0 s \"%s\" SNMPv2-MIB::sysDescr.0 s \"%s\"";
		
		// StartRebundleScalrMessage
		const START_REBUNDLE		= "SNMPv2-MIB::snmpTrap.12.0 SNMPv2-MIB::sysName.0 s \"%s\" SNMPv2-MIB::sysLocation.0 s \"0\"";
		
		// VhostReconfigureScalrMessage
		const VHOST_RECONFIGURE		= "SNMPv2-MIB::snmpTrap.11.2 SNMPv2-MIB::sysName.0 s \"%s\" SNMPv2-MIB::sysDescr.0 s \"%s\"";
		
		// HostDownScalrMessage
		const HOST_DOWN 			= "SNMPv2-MIB::snmpTrap.11.0 SNMPv2-MIB::sysName.0 s \"%s\" SNMPv2-MIB::sysLocation.0 s \"%s\" SNMPv2-MIB::sysDescr.0 s \"%s\" SNMPv2-MIB::sysContact.0 s \"%s\"";
		
		// NewMySQLMasterUpScalrMessage
		const NEW_MYSQL_MASTER		= "SNMPv2-MIB::snmpTrap.10.1 SNMPv2-MIB::sysName.0 s \"%s\" SNMPv2-MIB::sysLocation.0 s \"%s\" SNMPv2-MIB::sysDescr.0 s \"%s\"";
		

		// ScalarizrUpdateAvailableScalrMessage
		const LAUNCH_APT_UPGRADE	= "SNMPv2-MIB::snmpTrap.10.2";
		
		
		// EventNoticeScalrMessage
		const NOTIFY_EVENT			= "SNMPv2-MIB::snmpTrap.5.1 SNMPv2-MIB::sysName.0 s \"%s\" SNMPv2-MIB::sysLocation.0 s \"%s\" SNMPv2-MIB::sysDescr.0 s \"%s\" SNMPv2-MIB::sysContact.0 s \"%s\"";
		
		// MountPointsReconfigureScalrMessage
		const MOUNT_EBS				= "SNMPv2-MIB::snmpTrap.5.0 SNMPv2-MIB::sysName.0 s \"%s\" SNMPv2-MIB::sysDescr.0 s \"%s\" SNMPv2-MIB::sysLocation.0 s \"%s\"";
		
		// MountPointsReconfigureScalrMessage
		const EBS_RECONFIGURE 		= self::MOUNT_EBS;
	}
?>