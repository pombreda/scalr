<?
    require("src/prepend.inc.php"); 
    $display['load_extjs'] = true;
    
	if (Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND env_id=?", array($req_farmid, Scalr_Session::getInstance()->getEnvironmentId()));
        
	if (!$farminfo)
        UI::Redirect("farms_view.php");
        
    $display["grid_query_string"] = "&farmid={$farminfo['id']}";
        
	$display["farminfo"] = $farminfo;
	$display["title"] = sprintf(_("Events for farm %s"), $farminfo['name']);
	$display["table_title_text"] = sprintf(_("Current time: %s"), date("M j, Y H:i:s"));
	
	require_once("src/append.inc.php");
?>