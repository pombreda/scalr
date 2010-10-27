<?php
	class Scalr_Helpers_Dns
	{
		public static function farmValidateRoleSettings($settings, $rolename)
		{
			if ($settings[DBFarmRole::SETTING_DNS_EXT_RECORD_ALIAS])
			{
				if (!preg_match("/^[A-Za-z0-9]+[A-Za-z0-9-]*[A-Za-z0-9]+/si", $settings[DBFarmRole::SETTING_DNS_EXT_RECORD_ALIAS]))
					throw new Exception("ext-%rolename% record alias should contain only [A-Za-z0-9-] chars. First and last char should not by hypen.");
			}
			
			if ($settings[DBFarmRole::SETTING_DNS_INT_RECORD_ALIAS])
			{
				if (!preg_match("/^[A-Za-z0-9]+[A-Za-z0-9-]*[A-Za-z0-9]+/si", $settings[DBFarmRole::SETTING_DNS_INT_RECORD_ALIAS]))
					throw new Exception("int-%rolename% record alias should contain only [A-Za-z0-9-] chars. First and last char should not by hypen.");
			}
		}
		
		public static function farmUpdateRoleSettings(DBFarmRole $DBFarmRole, $oldSettings, $newSettings)
		{
			if ($newSettings[DBFarmRole::SETTING_DNS_EXT_RECORD_ALIAS] != $oldSettings[DBFarmRole::SETTING_DNS_EXT_RECORD_ALIAS])
				$update = true;
				
			if ($newSettings[DBFarmRole::SETTING_DNS_INT_RECORD_ALIAS] != $oldSettings[DBFarmRole::SETTING_DNS_INT_RECORD_ALIAS])
				$update = true;
				
			if ($update)
			{
				$zones = DBDNSZone::loadByFarmId($DBFarmRole->FarmID);
				foreach ($zones as $zone)
				{
					$zone->updateSystemRecords();
					$zone->save();
				}
			}
		}
	}

?>