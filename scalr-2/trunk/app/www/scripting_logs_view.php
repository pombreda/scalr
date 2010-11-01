<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Scripting log&nbsp;&raquo;&nbsp;View");
	$display['load_extjs'] = true;
    	
	if (Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
		$farms = $db->GetAll("SELECT * FROM farms");
	else
		$farms = $db->GetAll("SELECT * FROM farms WHERE env_id=?", array(Scalr_Session::getInstance()->getEnvironmentId()));
		
	$disp_farms = array(array('',''));
	foreach ($farms as $farm)
		$disp_farms[] = array($farm['id'], $farm['name']);
	
	$display['farms'] = json_encode($disp_farms);
	
	$display["table_title_text"] = sprintf(_("Current time: %s"), date("M j, Y H:i:s"));
	
	require("src/append.inc.php"); 	
?>