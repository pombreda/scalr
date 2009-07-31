<? 
	require("src/prepend.inc.php");
	$display['load_extjs'] = true;
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	
	$display["title"] = _("Service log&nbsp;&raquo;&nbsp;Transaction details");
	
    if (!$get_trnid && !$get_strnid)
	   UI::Redirect("logs_view.php");
	   
	$display["grid_query_string"] .= "&trnid={$req_trnid}";
	$display["grid_query_string"] .= "&strnid={$req_strnid}";
	   		
	require("src/append.inc.php");
?>