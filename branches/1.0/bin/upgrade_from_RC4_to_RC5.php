<?php
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new UpdateToRC5();
	$ScalrUpdate->Run();
	
	class UpdateToRC5
	{
		function Run()
		{
			$this->UpdateEIPs();
		}
		
		function UpdateEIPs()
		{
			global $db;
			
			$db->Execute("UPDATE elastic_ips SET instance_index = '0'");
			
			$eips = $db->GetAll("SELECT * FROM elastic_ips ORDER BY id ASC");
			foreach ($eips as $eip)
			{
				$index = $db->GetOne("SELECT MAX(instance_index) FROM elastic_ips WHERE role_name=? AND farmid=?", array($eip['role_name'], $eip['farmid']));
				$index = $index+1;
				$db->Execute("UPDATE elastic_ips SET instance_index = '{$index}' WHERE id='{$eip['id']}'");
			}
		}
	}
?>