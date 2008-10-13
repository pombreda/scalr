<?
    require("src/prepend.inc.php"); 
    
    if ($_SESSION['uid'] == 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_id));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_id, $_SESSION['uid']));

    if (!$farminfo || $post_cancel)
        UI::Redirect("farms_view.php");
        
    if ($farminfo["status"] == 1)
    {
    	$errmsg = "Cannot delete a running farm. Please terminate a farm before deleting it.";
    	UI::Redirect("farms_view.php");
    }
        
    if ($req_action == "delete")
    {
    	if ($_SESSION['uid'] != 0)
			$info = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($farminfo['id'], $_SESSION['uid']));
		else 
			$info = $db->GetRow("SELECT * FROM farms WHERE id=?", array($farminfo['id']));
		
	    if ($info)
	    {
    	    $db->BeginTrans();
    		
	    	try
	    	{
		    	$db->Execute("DELETE FROM farms WHERE id=?", array($farminfo['id']));	
	    		$db->Execute("DELETE FROM farm_amis WHERE farmid=?", array($farminfo['id']));
	    		$db->Execute("DELETE FROM farm_instances WHERE farmid=?", array($farminfo['id']));
	    		$db->Execute("DELETE FROM records WHERE zoneid IN (SELECT id FROM zones WHERE farmid=?)", array($farminfo['id']));
	    		$db->Execute("DELETE FROM zones WHERE farmid=?", array($farminfo['id']));
	    		$db->Execute("DELETE FROM logentries WHERE farmid=?", array($farminfo['id']));
	    	}
	    	catch(Exception $e)
	    	{
	    		$db->RollbackTrans();
	    		$Logger->fatal("Exception thrown during farm deletion: {$e->getMessage()}");
	    		$errmsg = "Cannot delete farm at the moment. Please try again later.";
	    		UI::Redirect("farms_view.php");
	    	}
    		
	    	$db->CommitTrans();
    		
    		$Logger->info("Farm #{$farminfo['id']} removed from database!");
	    }
	    
	    $okmess = "Farm successfully deleted";
		UI::Redirect("farms_view.php");
    }

    
	$display["title"] = "Farms&nbsp;&raquo;&nbsp;Delete";
	$display["farminfo"] = $farminfo;
	$display["app_count"] = $db->GetOne("SELECT COUNT(*) FROM zones WHERE farmid='{$farminfo['id']}'");

	require_once("src/append.inc.php");
?>