<?php
	require_once('../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$eips = $db->Execute("SELECT * FROM elastic_ips WHERE state='1' AND role_name IS NULL");
	while ($eip = $eips->FetchRow())
	{
		$instance_info = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($eip['instance_id']));
		if ($instance_info)
		{
			$db->Execute("UPDATE elastic_ips SET role_name=? WHERE id=?", array($instance_info['role_name'], $eip['id']));
		}
	}
?>