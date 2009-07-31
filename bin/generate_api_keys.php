<?php
	require_once('../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$clients = $db->GetAll("SELECT id FROM clients");
	foreach ($clients as $client)
	{
		$keys = Client::GenerateScalrAPIKeys();
		
		$db->Execute("UPDATE clients SET scalr_api_keyid=?, scalr_api_key =? WHERE id=?",
			array($keys['id'], $keys['key'], $client['id'])
		);
	}
?>