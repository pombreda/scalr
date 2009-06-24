<?
    require("src/prepend.inc.php"); 
    $display['load_extjs'] = true;
    
	if ($_SESSION['uid'] == 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));
        
	if (!$farminfo)
        UI::Redirect("farms_view.php");
        
    $display["grid_query_string"] = "&farmid={$farminfo['id']}";
        
	$display["farminfo"] = $farminfo;
	$display["title"] = sprintf(_("Events for farm %s"), $farminfo['name']);

	require_once("src/append.inc.php");
?>