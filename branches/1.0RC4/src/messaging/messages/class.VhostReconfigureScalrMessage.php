<?php
	
	class VhostReconfigureScalrMessage extends ScalrMessage
	{
		const SNMP_TRAP = "SNMPv2-MIB::snmpTrap.11.2 SNMPv2-MIB::sysUpTime.0 s \"{MessageID}\" SNMPv2-MIB::sysName.0 s \"{VhostName}\" SNMPv2-MIB::sysDescr.0 s \"{IsSSLVhost}\"";
		
		public $VhostName;
		public $IsSSLVhost;
		
		public function __construct($vhost_name, $is_ssl_vhost)
		{
			parent::__construct();
			
			$this->VhostName = $vhost_name;
			$this->IsSSLVhost = $is_ssl_vhost;
		}
	}
?>