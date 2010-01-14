<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	else
		$clientid = $_SESSION['uid'];
	
	// Load Client Object
    $Client = Client::Load($clientid);
    
    if ($post_cancel)
		UI::Redirect(basename(__FILE__)."?farmid={$farminfo['id']}");
    
        
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon RDS&nbsp;&raquo;&nbsp;DB Instances");
			
		
	$display["grid_query_string"] = "&farmid={$farminfo["id"]}";
		
	require("src/append.inc.php");
?>