<?php
	
	class StartRebundleScalrMessage extends ScalrMessage
	{
		const SNMP_TRAP = "SNMPv2-MIB::snmpTrap.12.0 SNMPv2-MIB::sysUpTime.0 s \"{MessageID}\" SNMPv2-MIB::sysName.0 s \"{RoleName}\" SNMPv2-MIB::sysLocation.0 s \"0\"";
		
		public $RoleName;
		
		public function __construct($role_name)
		{
			parent::__construct();
			
			$this->RoleName = $role_name;
		}
	}
?>