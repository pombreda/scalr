<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	$display["title"] = "Manage Reserved Instances";
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	
	if ($req_code == 1)
		$okmsg = _("Reserved instances offering successfully purchased.");
	
	require("src/append.inc.php");
?>