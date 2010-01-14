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
        
        
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon RDS&nbsp;&raquo;&nbsp;Events log");
			
		
	$display["grid_query_string"] = "&name={$$req_name}&type={$req_type}";
		
	require("src/append.inc.php");
?>