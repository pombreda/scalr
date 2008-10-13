<? 
	require("src/prepend.inc.php"); 
	$display["title"] = "Event log&nbsp;&raquo;&nbsp;View";
    
	if ($_SESSION["uid"] != 0)
		$auth_sql = " AND (SELECT clientid FROM farms WHERE id = logentries.farmid) = '{$_SESSION["uid"]}'";
	
	$sql = "SELECT * from logentries WHERE id > 0 {$auth_sql}";

	$paging = new SQLPaging();
	
	if ($get_iid)
	{
		$iid = preg_replace("/[^A-Za-z0-9-]+/si", "", $get_iid);
		
		$paging->AddURLFilter("iid", $iid);
		$display["iid"] = $iid;
		$sql  .= " AND serverid = '{$iid}'";
	}
	
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
		$sql  .= " AND (message LIKE {$search} OR source LIKE {$search} OR serverid LIKE {$search})";
	}
	
	if ($req_severity)
	{
		foreach ($req_severity as &$s)
		{
			$s = (int)$s;
			
			$paging->AddURLFilter("severity[]", $s);
			$display["checked_severities"][$s] = true;
		}
			
		$severities = implode(",", array_values($req_severity));
		$sql  .= " AND severity IN ($severities)";
	}
	else
	{
		$display["checked_severities"] = array(0 => false, 2 => true, 3 => true, 4 => true, 5 => true);
		$sql  .= " AND severity IN (2,3,4,5)";
	}
	
	//
	//Paging
	//
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = "ORDER BY time DESC";
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = "";
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");

	$severities = array(0 => "DEBUG", 2 => "INFO", 3 => "WARN", 4 => "ERROR", 5 => "FATAL");
	
	$display["severities"] = $severities;
		
	
	//
	// Rows
	//
	$display["rows"] = $db->GetAll($paging->SQL);
	foreach ($display["rows"] as &$row)
	{
		$row["time"] = date("d-m-Y H:i:s", $row["time"]);
		$row["servername"] = $row["serverid"];
		$row["severity"] = $severities[$row["severity"]];
	}
	
	if (!$_SESSION["uid"])
		$display["farms"] = $db->GetAll("SELECT * FROM farms");
	else
		$display["farms"] = $db->GetAll("SELECT * FROM farms WHERE clientid='{$_SESSION['uid']}'");
	
	$display["page_data_options_add"] = false;
	
	require("src/append.inc.php"); 
	
?>