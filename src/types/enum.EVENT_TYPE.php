<?
	final class EVENT_TYPE
	{
		const HOST_UP 	= "HostUp";
		const HOST_DOWN	= "HostDown";
		const HOST_CRASH	= "HostCrash";
		const HOST_INIT 	= "HostInit";
		
		const LA_OVER_MAXIMUM	= "LAOverMaximum";
		const LA_UNDER_MINIMUM	= "LAUnderMinimum";
		
		const REBUNDLE_COMPLETE	= "RebundleComplete";
		const REBUNDLE_FAILED	= "RebundleFailed";
		
		const REBOOT_BEGIN	= "RebootBegin";
		const REBOOT_COMPLETE	= "RebootComplete";
		
		const FARM_TERMINATED = "FarmTerminated";
		const FARM_LAUNCHED = "FarmLaunched";
		
		const INSTANCE_IP_ADDRESS_CHANGED = "IPAddressChanged";
		
		const NEW_MYSQL_MASTER = "NewMysqlMasterUp";
		const MYSQL_BACKUP_COMPLETE = "MysqlBackupComplete";
		const MYSQL_BACKUP_FAIL = "MysqlBackupFail";
		
		const MYSQL_REPLICATION_FAIL = "MySQLReplicationFail";
		const MYSQL_REPLICATION_RECOVERED = "MySQLReplicationRecovered";
		
		public static function GetEventDescription($event_type)
		{
			$descriptions = array(
				self::HOST_UP 			=> "Instance started and configured.",
				self::HOST_DOWN 		=> "Instance terminated.",
				self::HOST_CRASH 		=> "Instance crashed inexpectedly.",
				self::LA_OVER_MAXIMUM 	=> "Cumulative load average for a role is higher than maxLA setting.",
				self::LA_UNDER_MINIMUM 	=> "Cumulative LA for a role is lower than minLA setting.",
				self::REBUNDLE_COMPLETE => "\"Synchronize to all\" or custom role creation competed succesfully.",
				self::REBUNDLE_FAILED 	=> "\"Synchronize to all\" or custom role creation failed.",
				self::REBOOT_BEGIN 		=> "Instance being rebooted.",
				self::REBOOT_COMPLETE 	=> "Instance came up after reboot.",
				self::FARM_LAUNCHED 	=> "Farm has been launched.",
				self::FARM_TERMINATED 	=> "Farm has been terminated.",
				self::HOST_INIT			=> "Instance sucessfully configured after boot up.",
				self::NEW_MYSQL_MASTER	=> "One of MySQL instances promoted as master on boot up, or one of mySQL slaves promoted as master.", // due to master failure.",
				self::MYSQL_BACKUP_COMPLETE => "MySQL backup completed succesfully.",
				self::MYSQL_BACKUP_FAIL => "MySQL backup failed.",
				self::INSTANCE_IP_ADDRESS_CHANGED => "Public IP address of the instance was changed upon reboot or within Elastic IP assignments.",
				self::MYSQL_REPLICATION_FAIL => "MySQL replication failure",
				self::MYSQL_REPLICATION_RECOVERED => "MySQL replication recovered after failure"
			);
			
			return $descriptions[$event_type];
		}
	}
?>