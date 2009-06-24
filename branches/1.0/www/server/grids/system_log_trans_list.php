<?
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{ 
		$enable_json = true;
		include("../../src/prepend.inc.php");
			
		if ($_SESSION["uid"] != 0)
		   UI::Redirect("index.php");
		
		if (!$get_trnid && !$get_strnid)
		   exit();
		   
		if ($get_trnid && !$get_strnid)
		{
			$trn_id = $db->qstr($get_trnid);
			
			$sql = "SELECT * FROM syslog WHERE transactionid={$trn_id} AND transactionid != sub_transactionid GROUP BY sub_transactionid
				UNION SELECT * FROM syslog WHERE transactionid={$trn_id} AND transactionid = sub_transactionid ORDER BY dtadded_time ASC, id ASC";
		}
		else
		{
			$trn_id = $db->qstr($get_trnid);
			$strn_id = $db->qstr($get_strnid);
			
			$sql = "SELECT *, transactionid as sub_transactionid FROM syslog WHERE sub_transactionid={$strn_id} AND transactionid={$trn_id} ORDER BY dtadded_time ASC, id ASC";
		}
		
		$t = $db->Execute($sql);
		$response["total"] = $t->RecordCount();
			
		$response['success'] = '';
		$response['error'] = '';
		$response['data'] = array();
		
		while ($row = $t->FetchRow())
		{
	        $row["message"] = nl2br(preg_replace("/[\n]+/", "\n", htmlentities($row["message"], ENT_QUOTES, "UTF-8")));
	        
	   	 	$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>