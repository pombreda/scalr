<? 
	require("src/prepend.inc.php");

	$time = date("d M, Y h:i a");
	
	$display["title"] = "System log&nbsp;&raquo;&nbsp;View (Current time: {$time})";
	
    $display["load_calendar"] = 1;

    $paging = new SQLPaging();
	$sql = "SELECT * FROM syslog WHERE dtadded_time > 0";
	
	if (isset($req_severity))
	{
	    if ($req_severity > 0)
	    {
    	    $id = (int)$req_severity;
    	    $paging->AddURLFilter("severity", $id);
    	    $sql .= " AND severity='{$id}'";
    	    
    	    $display["severity"] = $id;
	    }
	}
	
	//Paging
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = "ORDER BY dtadded_time DESC, id DESC";
	$paging->ApplyFilter($_POST["filter_q"], array("message"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");

	
	// Rows
	$rows = $db->Execute($paging->SQL);
	
	$display["severities"] = array (
               E_ERROR          => 'SYSTEM ERROR',
               E_WARNING        => 'SYSTEM WARNING',
               E_NOTICE         => 'SYSTEM NOTICE',
               E_CORE_ERROR     => 'APP ERROR',
               E_CORE_WARNING   => 'APP WARNING',
               E_USER_ERROR     => 'ERROR',
               E_USER_WARNING   => 'WARNING',
               E_USER_NOTICE    => 'NOTICE'
               );
	
	$errorType = array (
               E_ERROR          => '<span style="color:red;font-weight:bold;">SYSTEM ERROR</span>',
               E_WARNING        => '<span style="color:red;font-weight:bold;">SYSTEM WARNING</span>',
               E_PARSE          => 'PARSING ERROR',
               E_NOTICE         => '<span style="color:gray;">SYSTEM NOTICE</span>',
               E_CORE_ERROR     => '<span style="color:purple">APP ERROR</span>',
               E_CORE_WARNING   => '<span style="color:purple">APP WARNING</span>',
               E_COMPILE_ERROR  => 'COMPILE ERROR',
               E_COMPILE_WARNING => 'COMPILE WARNING',
               E_USER_ERROR     => '<span style="color:red;">ERROR</span>',
               E_USER_WARNING   => '<span style="color:red;">WARNING</span>',
               E_USER_NOTICE    => '<span style="color:gray;">NOTICE</span>',
               E_STRICT         => 'STRICT NOTICE',
               E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
               );
	    	
	while ($row = $rows->FetchRow())
	{
	    $row["dtadded"] = Formater::FuzzyTimeString(strtotime($row["dtadded"]));
	    $row["message"] = strip_tags(stripslashes($row["message"]));
	    $row["severity"] = $errorType[$row["severity"]];
	    
	    $display["rows"][] = $row;
	}
		
	require("src/append.inc.php");
?>