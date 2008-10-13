<?
	require_once 'src/prepend.inc.php';
	
	$clients = $db->GetAll("SELECT * FROM clients");
	
	$cpwd = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
	
	foreach ($clients as $client)
	{
		if ($client["aws_accesskeyid"] && !$client["aws_private_key_enc"])
		{
			$db->Execute("update clients SET
				aws_accesskeyid = ?,
				aws_accesskey = ?
				WHERE id = ?
			", array($Crypto->Encrypt($client["aws_accesskeyid"], $cpwd), 
				$Crypto->Encrypt($client["aws_accesskey"], $cpwd),
				$client["id"]));
		}
		
		$cert_path = APPPATH."/etc/clients_keys/{$client['id']}/cert.pem";
		$pk_path = APPPATH."/etc/clients_keys/{$client['id']}/pk.pem";
		
		if (file_exists($cert_path))
		{
			$contents = file_get_contents($cert_path);
			
			if (stristr($contents, "----"))
				$enc_contents = $Crypto->Encrypt($contents, $cpwd);
			else
				$enc_contents = $contents;
			
			$db->Execute("UPDATE clients SET aws_certificate_enc = ? WHERE id=?", array($enc_contents, $client['id']));
		}
		
		if (file_exists($pk_path))
		{
			$contents = file_get_contents($pk_path);
			
			if (stristr($contents, "----"))
				$enc_contents = $Crypto->Encrypt($contents, $cpwd);
			else
				$enc_contents = $contents;
						
			$db->Execute("UPDATE clients SET aws_private_key_enc = ? WHERE id=?", array($enc_contents, $client['id']));
		}
		
		if ($client["isactive"] == 1)
		{
			$db->Execute("REPLACE INTO client_settings SET clientid=?, `key`=?, `value`=?",
				array($client['id'], 'rss_login', $client['email'])
			);
			
			$db->Execute("REPLACE INTO client_settings SET clientid=?, `key`=?, `value`=?",
				array($client['id'], 'rss_password', $Crypto->Sault(12))
			);
		}
	}
?>