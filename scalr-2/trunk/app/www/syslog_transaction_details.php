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

    $severities = array(
		array('hideLabel' => true, 'boxLabel'=> 'Fatal error', 'name' => 'severity[]', 'inputValue' => 'FATAL', 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Error', 'name' => 'severity[]','inputValue'=> 'ERROR', 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Warning', 'name' => 'severity[]', 'inputValue'=> 'WARN', 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Information','name' => 'severity[]', 'inputValue'=> 'INFO', 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Debug', 'name' => 'severity[]', 'inputValue'=> 'DEBUG', 'checked'=> false)
	);
	$display["severities"] = json_encode($severities);
	
	require("src/append.inc.php");
?>