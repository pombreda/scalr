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
    	$errmsg = _("Cannot delete a running farm. Please terminate a farm before deleting it.");
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
	    		$db->Execute("DELETE FROM logentries WHERE farmid=?", array($farminfo['id']));
	    		$db->Execute("DELETE FROM elastic_ips WHERE farmid=?", array($farminfo['id']));
	    		$db->Execute("DELETE FROM events WHERE farmid=?", array($farminfo['id']));
	    		
	    		$db->Execute("DELETE FROM farm_role_options WHERE farmid=?", array($farminfo['id']));
	    		$db->Execute("DELETE FROM farm_role_scripts WHERE farmid=?", array($farminfo['id']));
	    		
	    		// Clean observers
	    		$observers = $db->Execute("SELECT * FROM farm_event_observers WHERE farmid=?", array($farminfo['id']));
	    		while ($observer = $observers->FetchRow())
	    		{
	    			$db->Execute("DELETE FROM farm_event_observers WHERE id=?", array($observer['id']));
	    			$db->Execute("DELETE FROM farm_event_observers_config WHERE observerid=?", array($observer['id']));
	    		}
	    		
	    		// Remove DNS zones
	    		$DNSZoneControler = new DNSZoneControler();
	    		$zones = $db->GetAll("SELECT * FROM zones WHERE farmid=?", array($farminfo['id']));
	    		foreach ($zones as $zone)
	    		{
	    			$DNSZoneControler->Delete($zone['id']);
	    		}
	    	}
	    	catch(Exception $e)
	    	{
	    		$db->RollbackTrans();
	    		$Logger->fatal("Exception thrown during farm deletion: {$e->getMessage()}");
	    		$errmsg = _("Cannot delete farm at the moment. Please try again later.");
	    		UI::Redirect("farms_view.php");
	    	}
    		
	    	$db->CommitTrans();
    		
    		$Logger->info("Farm #{$farminfo['id']} removed from database!");
	    }
	    
	    $okmess = _("Farm successfully deleted");
		UI::Redirect("farms_view.php");
    }

    
	$display["title"] = _("Farms&nbsp;&raquo;&nbsp;Delete");
	$display["farminfo"] = $farminfo;
	$display["app_count"] = $db->GetOne("SELECT COUNT(*) FROM zones WHERE farmid='{$farminfo['id']}'");

	require_once("src/append.inc.php");
?>