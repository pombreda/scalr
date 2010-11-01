<? 
	require("src/prepend.inc.php"); 
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_ADMIN))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	$display["title"] = _("API log entry details");
	
	$display['entry'] = $db->GetRow("SELECT * FROM api_log WHERE transaction_id=? AND clientid=?",
		array(
			$req_trans_id,
			Scalr_Session::getInstance()->getClientId()
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