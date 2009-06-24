<?php
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new UpdateToRC6();
	$ScalrUpdate->Run();
	
	class UpdateToRC6
	{
		function Run()
		{
			$this->UpdateFarmRoles();
		}
		
		function UpdateFarmRoles()
		{
			global $db;
			
			$db->BeginTrans();
			
			try
			{
				$farm_roles = $db->GetAll("SELECT id, min_count, max_count, min_LA, max_LA FROM farm_amis");
				foreach ($farm_roles as $farm_role)
				{
					$DBFarmRole = DBFarmRole::LoadByID($farm_role['id']);
					
					$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_POLLING_INTERVAL, 1);
					$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES, $farm_role['max_count']);
					$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES, $farm_role['min_count']);
					
					$DBFarmRole->SetSetting(LAScalingAlgo::PROPERTY_MIN_LA, $farm_role['min_LA']);
					$DBFarmRole->SetSetting(LAScalingAlgo::PROPERTY_MAX_LA, $farm_role['max_LA']);
					$DBFarmRole->SetSetting("scaling.la.enabled", 1);
				}
				
				$db->Execute("alter table `farm_amis` drop column `min_count`, drop column `max_count`, drop column `min_LA`, drop column `max_LA`");
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				die("ERROR: {$e->getMessage()}");
			}
			
			$db->CommitTrans();
		}
	}
?>