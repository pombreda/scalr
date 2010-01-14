<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	
	$display["title"] = "Settings&nbsp;&raquo;&nbsp;General";
	if ($_POST) 
	{
		unset($_POST['Submit']);
		unset($_POST['id']);
		unset($_POST['page']);
		unset($_POST['f']);
		
		//Pass
		if ($post_pass != "******")
		{
			
			$db->BeginTrans();
			
			$current_pass = $_SESSION["cpwd"];
			
			if (!is_writable(dirname(__FILE__)."/../etc/.passwd"))
			{
			    $errmsg = dirname(__FILE__)."/../etc/.passwd - not writable. Please check file permissions.";
			    UI::Redirect("settings_core.php");
			}
			
			try 
			{	
				$rows = $db->GetAll("select * from nameservers");
				foreach ($rows as $row)
				{
					$pass = $Crypto->Decrypt($row["password"], $current_pass);
					$encrypted = $Crypto->Encrypt($pass, $post_pass);
					$db->Execute("update nameservers set `password` =? where id=?",
						array($encrypted, $row["id"])
					);
				}
			    
				$clients = $db->GetAll("SELECT * FROM clients");
				foreach ($clients as $client)
				{
					if ($client["aws_accountid"])
					{
						$key = $Crypto->Decrypt($client["aws_accesskey"], $current_pass);
						$keyid = $Crypto->Decrypt($client["aws_accesskeyid"], $current_pass);
						$pkey = $Crypto->Decrypt($client["aws_private_key_enc"], $current_pass);
						$cert = $Crypto->Decrypt($client["aws_certificate_enc"], $current_pass);
						
						$db->Execute("UPDATE clients SET
							aws_accesskey = ?,
							aws_accesskeyid = ?,
							aws_private_key_enc = ?,
							aws_certificate_enc = ?
							WHERE id=?",
							array(
								$Crypto->Encrypt(trim($key), $post_pass),
								$Crypto->Encrypt(trim($keyid), $post_pass),
								$Crypto->Encrypt(trim($pkey), $post_pass),
								$Crypto->Encrypt(trim($cert), $post_pass),
								$client["id"]
							)
						);
					}
				}
				
			    // Save new password into DB
				$result = $db->Execute("REPLACE INTO config SET `value`=?, `key`=?", array($Crypto->Hash($post_pass), "admin_password"));
				
				// Failed to update
				if (!$result)
				{
					// If we cannot update at least one password, rollback all changes
					$db->RollbackTrans();
					$errmsg = "Cannot update password in database.";
					UI::Redirect("settings_core.php");
				}
				
				$enc = $Crypto->Encrypt($post_pass, CONFIG::$CRYPTOKEY);
				$pwd = @fopen(dirname(__FILE__)."/../etc/.passwd", "w+");
				$res = @fwrite($pwd, $enc);
				@fclose($pwd);
				
				if (!$res)
				{
				    $db->RollbackTrans();
					$errmsg = "Failed to write etc/.passwd file";
					UI::Redirect("settings_core.php");
				}
				else 
				{
    				// Commit all changes
    				$db->CommitTrans();			
				}	
			} 
			catch (Exception $e)
			{
				// If we cannot update at least one password, rollback all changes
				$db->RollbackTrans();
				$errmsg = "Failed to rehash passwords. ".$e->getMessage();
				UI::Redirect("settings_core.php");
			}
		}
		
		$_POST["paypal_isdemo"] = (isset($_POST["paypal_isdemo"])) ? 1 : 0;
		
		// Regular keys
		foreach($_POST as $k => $v)
		{
			if (!in_array($k, array('pass', 'admin_password', 'logger_password')))
				$db->Execute("REPLACE INTO config SET `value`=?, `key`=?", array(stripslashes($v), $k));
		}
		
		
		$okmsg = "Settings successfully updated";
		UI::Redirect("settings_core.php");
	}
	
	foreach ($db->GetAll("select * from config") as $rsk)
		$cfg[$rsk["key"]] = $rsk["value"];
	
	$display = array_merge($display, array_map('stripslashes', $cfg));
	
	require("src/append.inc.php"); 
?>