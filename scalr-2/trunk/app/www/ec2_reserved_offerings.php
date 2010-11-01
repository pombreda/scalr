<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	$display["title"] = "Reserved Instances Offerings";
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	require("src/append.inc.php");
?>