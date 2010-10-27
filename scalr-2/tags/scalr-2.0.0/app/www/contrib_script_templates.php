<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION['uid'] != 0)
		UI::Redirect("script_templates.php");
	
	$display["title"] = _("Contrinuted script templates");
	if (isset($post_cancel))
		UI::Redirect("script_templates.php");
	
	$display['grid_query_string'] = "&approval_state={$req_approval_state}";
		
	require("src/append.inc.php");
?>