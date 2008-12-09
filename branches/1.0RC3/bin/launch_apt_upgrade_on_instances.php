<?php
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$SNMP = new SNMP();
	
	$instances = $db->Execute("SELECT * FROM farm_instances WHERE state='Running'");
	while ($instance = $instances->FetchRow())
	{
		$farminfo = $db->GetRow("SELECT * FROM farms WHERE id='{$instance['farmid']}'");
		if ($farminfo["status"] == 0)
			continue;
		
		print "Sending trap to instance: {$instance['instance_id']} ({$instance['external_ip']}) on farm '{$farminfo['name']}': ";
		
		try
		{
			$SNMP->Connect($instance['external_ip'], null, $farminfo['hash']);
            $SNMP->SendTrap(SNMP_TRAP::LAUNCH_APT_UPGRADE);		
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