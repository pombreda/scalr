<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Scripting log&nbsp;&raquo;&nbsp;View");
	$display['load_extjs'] = true;
    	
	if (!$_SESSION["uid"])
		$farms = $db->GetAll("SELECT * FROM farms");
	else
		$farms = $db->GetAll("SELECT * FROM farms WHERE clientid='{$_SESSION['uid']}'");
		
	$disp_farms = array(array('',''));
	foreach ($farms as $farm)
		$disp_farms[] = array($farm['id'], $farm['name']);
	
	$display['farms'] = json_encode($disp_farms);
	
	
	require("src/append.inc.php"); 	
?>