<?
	require_once('src/prepend.inc.php');
    $display['load_extjs'] = true;
	    
	if ($get_task == "download_private_key")
	{
	    try
	    {
	    	$DBFarm = DBFarm::LoadByID($get_id);
	    	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($DBFarm->EnvID))
	    		throw new Exception("Farm not found");
	    }
	    catch(Exception $e)
	    {
	    	$errmsg = _("Farm not found");
	        UI::Redirect("farms_view.php");
	    }
	    
	    $errmsg = _("Please download private key for farm role.");
	    UI::Redirect("/farm_roles_view.php?farmid={$get_id}");
	}
		
	if (!$_POST && !$get_task && $get_code)
	{
		if ($get_code == 1)
			$okmsg = _("Farm successfully updated");
	}
	
	if ($req_farmid || $req_id)
	{
	    $id = ($req_farmid) ? (int)$req_farmid : (int)$req_id;
	    $display['grid_query_string'] .= "&farmid={$id}";
	}

	if ($req_clientid)
	{
	    $id = (int)$req_clientid;
	    $display['grid_query_string'] .= "&clientid={$id}";
	}
	
	if (isset($req_status))
	{
	    $status = (int)$req_status;
	    $display['grid_query_string'] .= "&status={$status}";
	}
	
	$display["title"] = _("Server Farms > View");	
	$display["help"] = _("This is a list of all your Server Farms. A Server Farm is a logical group of EC2 machines that serve your application. It can include load balancers, databases, web servers, and other custom servers. Servers in these farms can be redundant, self curing, and auto-scaling.");
	
	require_once ("src/append.inc.php");
?>