<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	try
	{
		$DBServer = DBServer::LoadByID($req_server_id);
		
		if ($_SESSION['uid'] != 0 && $DBServer->clientId != $_SESSION['uid'])
			CoreUtils::Redirect("/servers_view.php");
	} 
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();
		CoreUtils::Redirect("/servers_view.php");
	}
    
    $display["title"] = sprintf(_("Running processes on instance %s (%s)"), $DBServer->serverId, $DBServer->remoteIp);
    
    $display["server_id"] = $DBServer->serverId;
    
	require("src/append.inc.php"); 
?>