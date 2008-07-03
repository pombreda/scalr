<? 
	require("src/prepend.inc.php"); 
	$display["title"] = "Event log&nbsp;&raquo;&nbsp;View";
    
	if ($_SESSION["uid"] != 0)
	{
	    if (!$get_iid)
	    {
	       $sql_query = " AND serverid IN (SELECT instance_id FROM farm_instances INNER JOIN farms ON farms.id = farm_instances.farmid WHERE farms.clientid='{$_SESSION["uid"]}')";
	    }
	    else 
	    {
	        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=(SELECT farmid FROM farm_instances WHERE instance_id=?)", array($get_iid));
	        if ($farminfo["clientid"] != $_SESSION["uid"])
	           CoreUtils::Redirect("index.php");
	    }
	}
	    
	$sql = "SELECT * from logentries WHERE id > 0 {$sql_query}";
	
	$paging = new SQLPaging();
			
	if ($get_iid)
	{
		$iid = preg_replace("/[^A-Za-z0-9-]+/si", "", $get_iid);
	    $sql .= " AND serverid='{$iid}'";
		$paging->AddURLFilter("iid", $iid);
	}
	
	//
	//Paging
	//
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = "ORDER BY time DESC";
	$paging->ApplyFilter($_POST["filter_q"], array("message", "serverid"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");


	//
	// Rows
	//
	$display["rows"] = $db->GetAll($paging->SQL);
	foreach ($display["rows"] as &$row)
	{
		$row["time"] = date("d-m-Y H:i:s", $row["time"]);
		$row["servername"] = $row["serverid"];
		$row["farmid"] = $db->GetOne("SELECT farmid FROM farm_instances WHERE instance_id=?", $row["serverid"]);
	}
	
	$display["page_data_options_add"] = false;
	
	require("src/append.inc.php"); 
	
?>