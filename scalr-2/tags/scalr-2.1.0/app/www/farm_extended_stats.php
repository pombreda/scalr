<?
    require("src/prepend.inc.php"); 
    
    if (Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND env_id=?", 
        	array($req_farmid, Scalr_Session::getInstance()->getEnvironmentId())
        );

    if (!$farminfo)
        UI::Redirect("farms_view.php");
        
    if ($farminfo["status"] != 1)
    {
    	$errmsg = _("You cannot view statistics for terminated farm");
    	UI::Redirect("farms_view.php");
    }
        
	$display["title"] = _("Farm&nbsp;&raquo;&nbsp;Extended statistics");
	$display["farminfo"] = $farminfo;
	
	$Reflect = new ReflectionClass("GRAPH_TYPE");
    $types = $Reflect->getConstants();
    
    $display['farmid'] = $req_farmid;
    $display['watcher'] = $get_watcher;
    $display['role_name'] = $get_role;
	
	require_once("src/append.inc.php");
?>