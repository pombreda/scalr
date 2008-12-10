<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Role rebundle log");
    
	if (!$get_id)
		UI::Redirect("client_roles_view.php");
		
	$roleid = (int)$get_id;
	
	if ($_SESSION["uid"] != 0)
	{
		if (!$db->GetOne("SELECT id FROM ami_roles WHERE clientid=? AND id=?", array($_SESSION["uid"], $roleid)))
			UI::Redirect("client_roles_view.php");
	}
	
	$sql = "SELECT * from rebundle_log WHERE roleid = '{$roleid}'";

	$paging = new SQLPaging(null, null, 9999);
				
	//
	//Paging
	//
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = "ORDER BY dtadded ASC";
	//$paging->ApplyFilter($_POST["search"], array("message", "serverid"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = "";
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");


	//
	// Rows
	//
	$display["rows"] = $db->GetAll($paging->SQL);
	foreach ($display["rows"] as &$row)
	{
		
	}
		
	$display["page_data_options_add"] = false;
	
	require("src/append.inc.php"); 
	
?>