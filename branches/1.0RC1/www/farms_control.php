<?
    require("src/prepend.inc.php"); 
    
    if ($_SESSION['uid'] == 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));

    if (!$farminfo)
        UI::Redirect("farms_view.php");
        
    if ($_SESSION['uid'] == 0)
    {
    	$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($farminfo['clientid']));
	
		// Decrypt client prvate key and certificate
    	$private_key = $Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
    	$certificate = $Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
    }
    else
    {
    	$private_key = $_SESSION["aws_private_key"];
    	$certificate = $_SESSION["aws_certificate"];
    }
	
	$AmazonEC2Client = new AmazonEC2($private_key, $certificate);
        
    if ($req_action == "Launch")
    {
        $amis = $db->GetAll("SELECT * FROM farm_amis WHERE farmid='{$farminfo['id']}'");
        
        foreach ($amis as $ami)
        {
            $roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($ami["ami_id"]));
            if ($roleinfo)
            {
                for($i = 1; $i <= $ami["min_count"];$i++)
                {		    
    			    $role = $roleinfo["name"];  
    			    
    			    $isactive = ($req_mark_active == 1) ? true : false;
    			      
                    $res = RunInstance($AmazonEC2Client, CONFIG::$SECGROUP_PREFIX.$role, $farminfo['id'], $role, $farminfo['hash'], $ami["ami_id"], false, $isactive);                        
                    if (!$res)
                        $err[] = "Cannot run instance. See system log for details!";
                    else
                        $i++;
                }
            }
        }
        
        $db->Execute("UPDATE farms SET status='1' WHERE id='{$farminfo['id']}'");
        
        //
        // Reanimate DNS zones
        //
        $ZoneControler = new DNSZoneControler();
        $zones = $db->GetAll("SELECT * FROM zones WHERE farmid='{$farminfo['id']}'");
        foreach ((array)$zones as $zone)
        {
            $ZoneControler->Update($zone["id"]);
            if ($zone["status"] != ZONE_STATUS::PENDING)
            	$db->Execute("UPDATE zones SET status=? WHERE id='{$zone['id']}'", array(ZONE_STATUS::ACTIVE));
        }
        
        if (!$err)
        {
            Scalr::StoreEvent($farminfo['id'], EVENT_TYPE::FARM_LAUNCHED);
        	
        	$okmsg = "Farm {$farminfo['name']} is now launching. It will take few minutes to start all instances.";
            UI::Redirect("farms_view.php");
        }
        else 
        {
            $errmsg = "Farm launched with {$i} instances with following errors:";
            UI::Redirect("farms_view.php");
        }
    }
    elseif ($req_action == "Terminate")
    {
		$db->Execute("UPDATE farms SET status='0' WHERE id='{$farminfo['id']}'");
		       
        $instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid='{$farminfo['id']}'");
                    
	    if (count($instances) > 0)
	    {
            foreach ($instances as $instance)
            {                
                try 
    			{    				
    				$response = $AmazonEC2Client->TerminateInstances(array($instance["instance_id"]));
    					
    				if ($response instanceof SoapFault)
    				{
    					$err[] = $response->faultstring;
    				}
    			}
    			catch (Exception $e)
    			{
    				$err[] = $e->getMessage(); 
    			}
            }
	    }

	    if ($post_deleteDNS)
	    {
	        $ZoneControler = new DNSZoneControler();
	        
	        $zones = $db->GetAll("SELECT * FROM zones WHERE farmid='{$farminfo['id']}'");
	        foreach ((array)$zones as $zone)
	        {
	            $db->Execute("DELETE FROM records WHERE rtype='A' AND issystem='1' AND zoneid='{$zone['id']}'");
	            $ZoneControler->Delete($zone["id"]);
	        }
	        
	        $db->Execute("UPDATE zones SET status=? WHERE farmid='{$farminfo['id']}'", array(ZONE_STATUS::INACTIVE));
	    }

	    $db->Execute("DELETE FROM farm_instances WHERE farmid=? AND state='Pending'", array($farminfo['id']));
	    
		if (!$errmsg)
		{
		    Scalr::StoreEvent($farminfo['id'], EVENT_TYPE::FARM_TERMINATED);
			
			$okmsg = "Farm successfully terminated";
		    UI::Redirect("farms_view.php");
	    } 
    }
    
    if ($farminfo["status"] == 0)
    {
        $display["action"] = "Launch";
        $display["num"] = $db->GetOne("SELECT SUM(min_count) FROM farm_amis WHERE farmid=?", $farminfo['id']);
    }
    else
    { 
        $display["action"] = "Terminate";
        $display["num"] = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE farmid=?", $farminfo['id']);
        
        $display["sync_count"] = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE farmid=? AND instance_id IN (SELECT prototype_iid FROM ami_roles WHERE iscompleted = '0')", array($farminfo['id']));
    }
    
	$display["title"] = "Farms&nbsp;&raquo;&nbsp;{$display["action"]}";
	$display["new"] = ($req_new) ? "1" : "0";
	$display["farminfo"] = $farminfo;

	require_once("src/append.inc.php");
?>