<?
	require_once('src/prepend.inc.php');   
	
	$display['title'] = _('Server extended information');
	
	if (!$req_server_id)
		UI::Redirect("index.php");
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
		
	try 
	{
		$DBServer = DBServer::LoadByID($req_server_id);
		
		if ($DBServer->clientId != $_SESSION['uid'])
			UI::Redirect("index.php");
		
		$info = PlatformFactory::NewPlatform($DBServer->platform)->GetServerExtendedInformation($DBServer);

		$display['server'] = $DBServer;
		$display['props'] = $DBServer->GetAllProperties();
		$display['info'] = $info;
		
	}
	catch(Exception $e)
	{
		$err[] = $e->getMessage(); 
    	UI::Redirect("servers_view.php");
	}
	
	require_once ("src/append.inc.php");
?>