<? 
	require("src/prepend.inc.php");
	
	$display["title"] = "Custom role&nbsp;&raquo;&nbsp;Create failure details";
	
    if (!$get_id)
	   UI::Redirect("client_coles_view.php");

	if ($_SESSION["uid"] != 0)
		$info = $db->GetRow("SELECT * FROM ami_roles WHERE id=? AND clientid=?", array($get_id, $_SESSION["uid"]));
	else
		$info = $db->GetRow("SELECT * FROM ami_roles WHERE id=?", array($get_id));
		
	if (!$info["fail_details"])
	{
		$errmsg = "There are no details found for selected role";
		UI::Redirect("client_roles_view.php");
	}

	$display["details"] = $info["fail_details"];
	
	require("src/append.inc.php");
?>