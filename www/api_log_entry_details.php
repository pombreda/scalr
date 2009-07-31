<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	
	$display["title"] = _("API log entry details");
	
	$display['entry'] = $db->GetRow("SELECT * FROM api_log WHERE transaction_id=? AND clientid=?",
		array(
			$req_trans_id,
			$_SESSION['uid']
		)
	);
	
	if (!$display['entry'])
	{
		$errmsg = _("Unknown transaction");
		UI::Redirect("index.php");
	}
	
	$display['entry']['dtadded'] = date("M j, Y H:i:s", $display['entry']['dtadded']);
	
	require("src/append.inc.php"); 
?>