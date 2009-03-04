<?php
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$instances = $db->Execute("SELECT * FROM farm_instances WHERE state IN (?,?)", array(INSTANCE_STATE::RUNNING, INSTANCE_STATE::INIT));
	while ($instance = $instances->FetchRow())
	{		
		print "Sending trap to instance: {$instance['instance_id']} ({$instance['external_ip']}) on farm '{$farminfo['name']}': ";
		
		try
		{
			$DBInstance = DBInstance::LoadByID($instance['id']);
			$DBInstance->SendMessage(new ScalarizrUpdateAvailableScalrMessage());
		}
		catch(Exception $e)
		{
			print "Failed\n";
			continue;
		}
		
		print "Success\n";
		flush();
	}
?>