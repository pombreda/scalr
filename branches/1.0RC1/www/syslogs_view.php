<? 
	require("src/prepend.inc.php");
		
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	
	Core::Load("Data/Formater");
	
	$display["title"] = _("Logs");
	$display["help"] = _("Almost all Scalr activity being logged. You should check logs in case of any issues.");
	
    $display["load_calendar"] = 1;

    $paging = new SQLPaging();
	$sql = "SELECT *, min(id) FROM syslog WHERE 1=1";

	if ($req_search)
	{
	    $search = str_replace("'", "\'", $req_search);
	    $sql .= " AND (message LIKE '%{$search}%' OR transactionid LIKE '%{$search}%' OR id = '{$search}')";
	    $display["search"] = $req_search;
	    
	    $paging->AddURLFilter("search", $search);
	}
	
	if ($req_farmid)
	{
		$farmid = (int)$req_farmid;
		$sql .= " AND farmid='{$farmid}'";
		$display["farmid"] = $req_farmid;
		
		$paging->AddURLFilter("farmid", $req_farmid);
	}
	
	if ($req_severity || $req_severities)
	{
		$severities = ($req_severity) ? $req_severity : explode(",", $req_severities);
					
		foreach($severities as $severity)
		{
			$_sql[] = "severity = '{$severity}'";
			$display["checked_severities"][$severity] = true;
		}
						
		if (count($_sql) > 0)
			$sql .= " AND (".implode(" OR ", $_sql).")";
					
		$paging->AddURLFilter("severities", implode(",", $severities));
	}
	
	if ($req_dt)
	{
		$date = strtotime($req_dt);
		$sql .= " AND TO_DAYS(dtadded) = TO_DAYS(FROM_UNIXTIME('{$date}'))";
		$paging->AddURLFilter("dt", $req_dt);
		$display["dt"] = $req_dt;
	}
	
	//Paging
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = "GROUP BY transactionid ORDER BY dtadded_time DESC";
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");

	// Rows
	$rows = $db->Execute($paging->SQL);
	
    $added = array();  	
	while ($row = $rows->FetchRow())
	{
        $row = $db->GetRow("SELECT * FROM syslog WHERE transactionid='{$row['transactionid']}' ORDER BY dtadded_time ASC");
        
        $row["ipaddr"] = long2ip($row["ipaddr"]);
        
        $meta = $db->GetRow("SELECT * FROM syslog_metadata WHERE transactionid=?", array($row['transactionid']));
        
        $row["warns"] = $meta["warnings"] ? $meta["warnings"] : 0;
        $row["errors"] = $meta["errors"] ? $meta["errors"] : 0;
        
        $row["dtadded"] = Formater::FuzzyTimeString(strtotime($row["dtadded"]));
        $row["action"] = stripslashes($row["message"]);
        $row["action"] = htmlentities($row["action"], ENT_QUOTES, "UTF-8");
        
        $display["rows"][] = $row;
	}
	
	$display["page_data_options"] = array();
	
	if (!$display["checked_severities"])
	{
		$display["checked_severities"]['FATAL'] = 1;
		$display["checked_severities"]['ERROR'] = 1;
		$display["checked_severities"]['WARN'] = 1;
		$display["checked_severities"]['INFO'] = 1;
		$display["checked_severities"]['DEBUG'] = 0;
	}
	
	$display["farms"] = $db->GetAll("SELECT id, name FROM farms");
	
	$display["severities"] = array (
               'FATAL'          => 'Fatal error',
               'ERROR'     		=> 'Error',
               'WARN'     		=> 'Warning',
               'INFO'        	=> 'Information',                              
               'DEBUG'   		=> 'Debug'
               );
	
	require("src/append.inc.php");
?>