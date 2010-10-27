<? 
	require("src/prepend.inc.php"); 
		
	$DBServer = DBServer::LoadByID($req_server_id);
	$DBFarm = $DBServer->GetFarmObject();
	
	if ($DBServer->clientId == $_SESSION['uid'] || $_SESSION['uid'] == 0)
	{
		if ($DBServer->remoteIp)
		{
			$ssh_port = $db->GetOne("SELECT default_ssh_port FROM roles WHERE id=?", array($DBServer->roleId));
			if (!$ssh_port)
				$ssh_port = 22;
			
			$Smarty->assign(
				array(
					"DBServer" => $DBServer, 
					"host" => $DBServer->remoteIp, 
					"port" => $ssh_port, 
					"key" => base64_encode($DBFarm->GetSetting(DBFarm::SETTING_AWS_PRIVATE_KEY))
				)
			);
			$Smarty->display("ssh_applet.tpl");
			exit();
		}
		else
			$errmsg = _("Server not initialized yet.");
	}
	
	
	UI::Redirect("/server_view.php");
	
	require("src/append.inc.php"); 
	
?>