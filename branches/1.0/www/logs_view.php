<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Event log&nbsp;&raquo;&nbsp;View");
	$display['load_extjs'] = true;
    
	if ($req_farmid)
	{
		$farmid = (int)$_REQUEST["farmid"];
		$display["grid_query_string"] = "&farmid={$farmid}";
	}
	
	$display["table_title_text"] = sprintf(_("Current time: %s"), date("M j, Y H:i:s"));
	
	$severities = array(
		array('hideLabel' => true, 'boxLabel'=> 'Fatal error', 'name' => 'severity[]', 'inputValue' => 5, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Error', 'name' => 'severity[]','inputValue'=> 4, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Warning', 'name' => 'severity[]', 'inputValue'=> 3, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Information','name' => 'severity[]', 'inputValue'=> 2, 'checked'=> true),
		array('hideLabel' => true, 'boxLabel'=> 'Debug', 'name' => 'severity[]', 'inputValue'=> 0, 'checked'=> false)
	);
	$display["severities"] = json_encode($severities);
	
	$severities = array(0 => "DEBUG", 2 => "INFO", 3 => "WARN", 4 => "ERROR", 5 => "FATAL");
		
	if (!$_SESSION["uid"])
		$farms = $db->GetAll("SELECT id, name FROM farms");
	else
		$farms = $db->GetAll("SELECT id, name FROM farms WHERE clientid='{$_SESSION['uid']}'");
	
	$disp_farms = array(array('',''));
	foreach ($farms as $farm)
	{
		$disp_farms[] = array($farm['id'], $farm['name']);
	}
		
	$display['farms'] = json_encode($disp_farms);
	
	require("src/append.inc.php"); 
	
?>