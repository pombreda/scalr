<?php

	class MountPointsReconfigureScalrMessage extends ScalrMessage
	{
		const SNMP_TRAP = "SNMPv2-MIB::snmpTrap.5.0 SNMPv2-MIB::sysUpTime.0 s \"{MessageID}\" SNMPv2-MIB::sysName.0 s \"{Device}\" SNMPv2-MIB::sysDescr.0 s \"{MountPoint}\" SNMPv2-MIB::sysLocation.0 s \"{CreateFS}\"";
		
		public $Device;
		public $MountPoint;
		public $CreateFS;
		
		/**
		 * Argumets are required for old versions of scalarizr (Before Jan 2009 release)
		 */
		public function __construct($device = null, $mountpoint = null, $createfs = null)
		{
			parent::__construct();
			
			$this->Device = $device;
			$this->MountPoint = $mountpoint;
			$this->CreateFS = $createfs;
		}
	}
?>