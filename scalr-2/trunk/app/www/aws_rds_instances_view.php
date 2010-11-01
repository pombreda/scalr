<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
    
    if ($post_cancel)
		UI::Redirect(basename(__FILE__)."?farmid={$farminfo['id']}");    
        
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon RDS&nbsp;&raquo;&nbsp;DB Instances");					
	$display["grid_query_string"] = "&farmid={$farminfo["id"]}";
		
	require("src/append.inc.php");
?>