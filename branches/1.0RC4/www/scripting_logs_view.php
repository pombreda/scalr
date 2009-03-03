<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Scripting log&nbsp;&raquo;&nbsp;View");
    
	if ($_SESSION["uid"] != 0)
		$auth_sql = " AND (SELECT clientid FROM farms WHERE id = scripting_log.farmid) = '{$_SESSION["uid"]}'";
	
	$sql = "SELECT * from scripting_log WHERE id > 0 {$auth_sql}";

	$paging = new SQLPaging();
		
	if ($req_farmid)
	{
		$farmid = (int)$_REQUEST["farmid"];
		$paging->AddURLFilter("farmid", $farmid);
		$display["farmid"] = $farmid;
		$sql  .= " AND farmid = '{$farmid}'";
	}
	
	if ($req_search)
	{
		$search = $db->qstr("%{$_REQUEST["search"]}%");
		$paging->AddURLFilter("search", $req_search);
		$display["search"] = $req_search;
		$sql  .= " AND (message LIKE {$search} OR instance LIKE {$search})";
	}
	
	$display["table_title_text"] = sprintf(_("Current time: %s"), date("d-m-Y H:i:s"));
	
	//
	//Paging
	//
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = "ORDER BY id DESC";
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("inc/table_title.tpl", $display);
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");

	//
	// Rows
	//
	$display["rows"] = $db->GetAll($paging->SQL);
	foreach ($display["rows"] as &$row)
	{
		$row["farm"] = $db->GetRow("SELECT * FROM farms WHERE id=?", array($row['farmid']));
	}
	
	if (!$_SESSION["uid"])
		$display["farms"] = $db->GetAll("SELECT * FROM farms");
	else
		$display["farms"] = $db->GetAll("SELECT * FROM farms WHERE clientid='{$_SESSION['uid']}'");
	
	$display["page_data_options_add"] = false;
	
	require("src/append.inc.php"); 
	
?>