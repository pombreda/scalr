<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	try {
		if ($req_farmid)
		{
			$DBFarm = DBFarm::LoadByID($req_farmid);
			if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($DBFarm->EnvID))
				throw new Exception(_("No such farm in database"));
			
			$display["grid_query_string"] .= "&farmid={$req_farmid}";
		}
		else
		{
			if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
				throw new Exception (_("You have no permissions for viewing this page"));
		}	
	}
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();
		UI::Redirect("farms_view.php");
	}
        
	$display["title"] = _("Servers&nbsp;&raquo;&nbsp;View");
	
	if (isset($req_farm_roleid))
	{
		$req_farm_roleid = (int)$req_farm_roleid;
		$display["grid_query_string"] .= "&farm_roleid={$req_farm_roleid}";
	}
	
	if (isset($req_server_id))
	{
		$display["grid_query_string"] .= "&server_id={$req_server_id}";
	}
	
	require("src/append.inc.php"); 
	
?>