<?
    require("src/prepend.inc.php"); 
    
    if ($_SESSION['uid'] == 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_id));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_id, $_SESSION['uid']));

    if (!$farminfo || $post_cancel)
        UI::Redirect("farms_view.php");
        
    if ($farminfo["status"] != FARM_STATUS::TERMINATED)
    {
    	$errmsg = _("Cannot delete a running farm. Please terminate a farm before deleting it.");
    	UI::Redirect("farms_view.php");
    }

    $servers = $db->GetOne("SELECT COUNT(*) FROM servers WHERE farm_id=? AND status!=?", array($farminfo['id'], SERVER_STATUS::TERMINATED));
    if ($servers != 0)
    {
    	$errmsg = _("Cannot delete a running farm. {$servers} server are still running on this farm.");
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
		    	$db->Execute("DELETE FROM farm_role_settings WHERE farm_roleid IN (SELECT id FROM farm_roles WHERE farmid=?)", array($farminfo['id']));
	    		$db->Execute("DELETE FROM farm_roles WHERE farmid=?", array($farminfo['id']));
	    		$db->Execute("DELETE FROM logentries WHERE farmid=?", array($farminfo['id']));
	    		$db->Execute("DELETE FROM elastic_ips WHERE farmid=?", array($farminfo['id']));
	    		$db->Execute("DELETE FROM events WHERE farmid=?", array($farminfo['id']));
	    		$db->Execute("DELETE FROM ec2_ebs WHERE farm_id=?", array($farminfo['id']));
	    		$db->Execute("DELETE FROM apache_vhosts WHERE farm_id=?", array($farminfo['id']));
	    		
	    		$db->Execute("DELETE FROM farm_role_options WHERE farmid=?", array($farminfo['id']));
	    		$db->Execute("DELETE FROM farm_role_scripts WHERE farmid=?", array($farminfo['id']));
	    		
	    		//TODO: Remove servers
	    		$db->Execute("DELETE FROM servers WHERE farm_id=?", array($farminfo['id']));
	    		
	    		// Clean observers
	    		$observers = $db->Execute("SELECT * FROM farm_event_observers WHERE farmid=?", array($farminfo['id']));
	    		while ($observer = $observers->FetchRow())
	    		{
	    			$db->Execute("DELETE FROM farm_event_observers WHERE id=?", array($observer['id']));
	    			$db->Execute("DELETE FROM farm_event_observers_config WHERE observerid=?", array($observer['id']));
	    		}
	    		
	    		$db->Execute("UPDATE dns_zones SET farm_id='', farm_roleid='' WHERE farm_id=?", array($farminfo['id']));
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
	    
	    $okmsg = _("Farm successfully deleted");
		UI::Redirect("farms_view.php");
    }

    $display['farm_name'] = $farminfo['name'];
    $display['farm_id'] = $farminfo['id'];
	$display["title"] = _("Farms&nbsp;&raquo;&nbsp;Delete");
	$display["farminfo"] = $farminfo;
	$display["app_count"] = $db->GetOne("SELECT COUNT(*) FROM zones WHERE farmid='{$farminfo['id']}'");

	require_once("src/append.inc.php");
?>