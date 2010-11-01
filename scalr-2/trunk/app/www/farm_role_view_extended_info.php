<?
	require_once('src/prepend.inc.php');   
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	$display['title'] = _('Farm role extended information');
	
	if (!$req_farm_roleid)
		UI::Redirect("index.php");
		
	try 
	{
		$DBFarmRole = DBFarmRole::LoadByID($req_farm_roleid);
		$DBFarm = $DBFarmRole->GetFarmObject();
		
		if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($DBFarm->EnvID))
			UI::Redirect("index.php");
		
		$display['farmrole'] = $DBFarmRole;
		$display['props'] = $DBFarmRole->GetAllSettings();
		
		$scalingManager = new Scalr_Scaling_Manager($DBFarmRole);
		$scaling_algos = array();
        foreach ($scalingManager->getFarmRoleMetrics() as $farmRoleMetric)
        	$scaling_algos[] = array(
        		'name' => $farmRoleMetric->getMetric()->name, 
        		'last_value' => $farmRoleMetric->lastValue,
        		'date'		=> date("Y-m-d H:i:s", $farmRoleMetric->dtLastPolled)
        	);
        	
        $display['metrics'] = $scaling_algos;
	}
	catch(Exception $e)
	{
		$err[] = $e->getMessage(); 
    	UI::Redirect("servers_view.php");
	}
	
	require_once ("src/append.inc.php");
?>