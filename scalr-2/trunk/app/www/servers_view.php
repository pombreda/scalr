<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	try {
		if ($req_farmid)
		{
			$DBFarm = DBFarm::LoadByID($req_farmid);
			if ($_SESSION['uid'] != 0 && $DBFarm->ClientID != $_SESSION['uid'])
				throw new Exception(_("No such farm in database"));
		        
			$clientid = $DBFarm->ClientID;
			
			$display["grid_query_string"] .= "&farmid={$req_farmid}";
		}
		else
		{
			if ($_SESSION["uid"] == 0)
				throw new Exception (_("Requested page cannot be viewed from admin account"));

			$clientid = $_SESSION['uid'];
		}	
	}
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();
		UI::Redirect("farms_view.php");
	}
	
	// Load Client Object
    $Client = Client::Load($clientid);
        
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