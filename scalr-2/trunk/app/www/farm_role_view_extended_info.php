<?
	require_once('src/prepend.inc.php');   
	
	$display['title'] = _('Farm role extended information');
	
	if (!$req_farm_roleid)
		UI::Redirect("index.php");
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
		
	try 
	{
		$DBFarmRole = DBFarmRole::LoadByID($req_farm_roleid);
		$DBFarm = $DBFarmRole->GetFarmObject();
		
		if ($DBFarm->ClientID != $_SESSION['uid'])
			UI::Redirect("index.php");
		
		//$info = PlatformFactory::NewPlatform($DBServer->platform)->GetServerExtendedInformation($DBServer);

		$display['farmrole'] = $DBFarmRole;
		$display['props'] = $DBFarmRole->GetAllSettings();
		//$display['info'] = $info;
		
	}
	catch(Exception $e)
	{
		$err[] = $e->getMessage(); 
    	UI::Redirect("servers_view.php");
	}
	
	require_once ("src/append.inc.php");
?>