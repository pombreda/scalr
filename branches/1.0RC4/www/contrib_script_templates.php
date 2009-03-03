<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION['uid'] != 0)
		UI::Redirect("script_templates.php");
	
	$display["title"] = _("Contrinuted script templates");
	if (isset($post_cancel))
		UI::Redirect("script_templates.php");
	
	$paging = new SQLPaging();
	
	$sql = "select *, script_revisions.id as id, script_revisions.dtcreated as dtcreated, script_revisions.approval_state as approval_state 
		FROM script_revisions 
		INNER JOIN scripts ON scripts.id = script_revisions.scriptid 
		WHERE 1=1 AND scripts.origin='".SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED."'
	";

    if (isset($req_approval_state))
    {
    	$approval_state = preg_replace("/[^A-Za-z0-9-]+/", "", $req_approval_state);
    	$sql .= " AND script_revisions.approval_state='{$approval_state}'";
    	$paging->AddURLFilter("approval_state", $approval_state);
    }
    
	$paging->SetSQLQuery($sql);
	$paging->ApplyFilter($_POST["filter_q"], array("name", "description"));
	$paging->AdditionalSQL = "ORDER BY dtcreated DESC, revision DESC";	
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");

	// Rows
	$display["rows"] = $db->GetAll($paging->SQL);	
	foreach ($display["rows"] as &$row)
	{
	    //
	}
	
	//$display["page_data_options"] = array();
	//$display["page_data_options_add"] = true;
		
	require("src/append.inc.php");
?>