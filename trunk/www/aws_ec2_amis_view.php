<?php	
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}		
	$display['load_extjs'] = true;		
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon EC2&nbsp;&raquo;&nbsp;Spot requests&nbsp;&raquo;&nbsp;Add new spot instance request");
		
	require("src/append.inc.php"); 	

?>
