<?
	class SERVER_PROPERTIES
	{
		/** SCALARIZR PROPERTIES */
		const SZR_KEY			= 'scalarizr.key';
		// permanent, one-time
		const SZR_KEY_TYPE		= 'scalarizr.key_type';
		
		const SZR_ONETIME_KEY_EXPIRED = 'scalarizr.onetime_key_expired';
		
		// 0.5 or 0.2-139
		const SZR_VESION		= 'scalarizr.version';
		
		const SZR_IMPORTING_ROLE_NAME = 'scalarizr.import.role_name';
		
		const SZR_IMPORTING_BEHAVIOUR = 'scalarizr.import.behaviour';
		
		const SZR_SNMP_PORT = 'scalarizr.snmp_port';
		
		/** DATABASE PROPERTIES */
		const DB_MYSQL_MASTER	= 'db.mysql.master';
		const DB_MYSQL_REPLICATION_STATUS = 'db.mysql.replication_status';
		
		/** DNS PROPERTIES */
		const EXCLUDE_FROM_DNS	= 'dns.exclude_instance';
		
		const ARCHITECTURE = "system.architecture";
		
		
		const STATISTICS_BW_IN 	= "statistics.bw.in";
		const STATISTICS_BW_OUT	= "statistics.bw.out";
		
		const REBOOTING = "system.rebooting";
	}
?>