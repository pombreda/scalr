<? 
	require("src/prepend.inc.php"); 
	$display["title"] = "Event log&nbsp;&raquo;&nbsp;View";
    
	if ($_GET["iid"])
	{
		$iid = preg_replace("/[^A-Za-z0-9-]+/si", "", $get_iid);
		$filter_sql  = " AND serverid = '{$iid}'";
	}
	elseif ($_REQUEST["farmid"])
	{
		$farmid = (int)$_REQUEST["farmid"];
		$filter_sql  = " AND farmid = '{$farmid}'";
	}
		
	if ($_SESSION["uid"] != 0)
	{
		$auth_sql = " AND (SELECT clientid FROM farms WHERE id = logentries.farmid) = '{$_SESSION["uid"]}'";
	}
	    
	$sql = "SELECT * from logentries WHERE id > 0 {$filter_sql} {$auth_sql}";

	$paging = new SQLPaging();
			
	if ($get_iid)
	{
		$paging->AddURLFilter("iid", $iid);
		$display["iid"] = $iid;
	}
	
	if ($get_farmid)
	{
		$paging->AddURLFilter("farmid", $farmid);
		$display["farmid"] = $farmid;
	}
	
	//
	//Paging
	//
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = "ORDER BY time DESC";
	$paging->ApplyFilter($_POST["search"], array("message", "serverid"));
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
		$row["time"] = date("d-m-Y H:i:s", $row["time"]);
		$row["servername"] = $row["serverid"];
	}
	
	if (!$_SESSION["uid"])
		$display["farms"] = $db->GetAll("SELECT * FROM farms");
	else
		$display["farms"] = $db->GetAll("SELECT * FROM farms WHERE clientid='{$_SESSION['uid']}'");
	
	$display["page_data_options_add"] = false;
	
	require("src/append.inc.php"); 
	
?>