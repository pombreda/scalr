<?php
	require_once('../src/prepend.inc.php');
	
	set_time_limit(0);
		
	$sql = $argv[1];
	$path_to_hook = $argv[2]; 
	
	try
	{
		if (!$sql || !$path_to_hook)
		{
			print "Usage: php -q ".__FILE__." SQL_QUERY PATH_TO_HOOK";
			exit();
		}
		
		$instances = $db->Execute($sql);
		while ($instance = $instances->FetchRow())
		{
			$farminfo = $db->GetRow("SELECT * FROM farms WHERE id='{$instance['farmid']}'");
			if ($farminfo["status"] == 0)
				continue;
			
			print "Instance: {$instance['instance_id']} ({$instance['external_ip']}) on farm: {$farminfo['name']}:\n";
			
			try
			{
				$pub_key_file = tempnam("/tmp", "AWSK");
				@file_put_contents($pub_key_file, $farminfo['public_key']);
		
				$priv_key_file = tempnam("/tmp", "AWSK");
				@file_put_contents($priv_key_file, $farminfo["private_key"]);
		
				$SSH2 = new SSH2();
				$SSH2->AddPubkey("root", $pub_key_file, $priv_key_file);
				if ($SSH2->Connect($instance['external_ip'], 22))
				{
					$hook = $path_to_hook;
					$name = basename($path_to_hook);
					print "Executing Hook hook: {$name}<br>";
					$SSH2->SendFile("/tmp/{$name}", $hook, "w+");
					$res = $SSH2->Exec("chmod 0600 /tmp/{$name} && /tmp/{$name}");
					print "{$name} hook execution output: {$res}<br>";
					
					@unlink($pub_key_file);
					@unlink($priv_key_file);
				}
				else
				{
					print "Cannot connect to SSH\n";
					continue;
				}
			}
			catch(Exception $e)
			{
				print "{$e->getMessage()}\n";
				continue;
			}
			
			print "OK\n";
			flush();
		}
	}
	catch(Exception $e)
	{
		die("FATAL ERROR: {$e->getMessage()}");
	}
?>