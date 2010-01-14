<?php
	class BasicScalrMessage extends ScalrMessage
	{
		const SNMP_TRAP = "SNMPv2-MIB::snmpTrap.10.2 SNMPv2-MIB::sysUpTime.0 s \"{MessageID}\" SNMPv2-MIB::sysName.0 s \"{MessageName}\" SNMPv2-MIB::sysLocation.0 s \"{Arg1}\" SNMPv2-MIB::sysDescr.0 s \"{Arg2}\" SNMPv2-MIB::sysContact.0 s \"{Arg3}\"";
		
		public $MessageName;
		public $Arg1;
		public $Arg2;
		public $Arg3;
		
		public function __construct($message_name, $arg1 = "", $arg2 = "", $arg3 = "")
		{
			parent::__construct();
			
			$this->MessageName = $message_name;
			$this->Arg1 = $arg1;
			$this->Arg2 = $arg2;
			$this->Arg3 = $arg3;
		}
	}
?>