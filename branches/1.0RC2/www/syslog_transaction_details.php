<? 
	require("src/prepend.inc.php");
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	
	$display["title"] = "Service log&nbsp;&raquo;&nbsp;Transaction details";
	
    if (!$get_trnid && !$get_strnid)
	   UI::Redirect("logs_view.php");
	   
	if ($get_trnid && !$get_strnid)
		$display["rows"] = $db->GetAll("
			SELECT * FROM syslog WHERE transactionid=? AND transactionid != sub_transactionid GROUP BY sub_transactionid 
			UNION SELECT * FROM syslog WHERE transactionid=? AND transactionid = sub_transactionid ORDER BY dtadded_time ASC, id ASC
			", array($get_trnid, $get_trnid));
	else
		$display["rows"] = $db->GetAll("SELECT *, transactionid as sub_transactionid FROM syslog WHERE sub_transactionid=? AND transactionid=? ORDER BY dtadded_time ASC, id ASC", array($get_strnid, $get_trnid));
	
	foreach ($display["rows"] as &$row)
	{
		$row["message"] = nl2br(preg_replace("/[\n]+/", "\n", htmlentities($row["message"], ENT_QUOTES, "UTF-8")));
	}
		
	require("src/append.inc.php");
?>