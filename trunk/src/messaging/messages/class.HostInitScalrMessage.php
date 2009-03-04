<?php
	
	class HostInitScalrMessage extends ScalrMessage
	{
		const SNMP_TRAP = "SNMPv2-MIB::snmpTrap.12.1 SNMPv2-MIB::sysUpTime.0 s \"{MessageID}\" SNMPv2-MIB::sysName.0 s \"{AWSAccountID}\"";
		
		public $AWSAccountID;
		
		public function __construct($aws_accountid)
		{
			parent::__construct();
			
			$this->AWSAccountID = $aws_accountid;
		}
	}
?>