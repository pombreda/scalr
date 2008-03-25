<?
    require("src/prepend.inc.php"); 
    
    if ($_SESSION['uid'] == 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));

    if (!$farminfo)
        CoreUtils::Redirect("farms_view.php");
     
    if ($post_cbtn_2)
    {
           
        if ($req_action == "Launch")
        {
            $AmazonEC2Client = new AmazonEC2(
                        APPPATH . "/etc/clients_keys/{$farminfo['clientid']}/pk.pem", 
                        APPPATH . "/etc/clients_keys/{$farminfo['clientid']}/cert.pem");
                        
            $amis = $db->GetAll("SELECT * FROM farm_amis WHERE farmid='{$farminfo['id']}'");
            
            foreach ($amis as $ami)
            {
                $roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($ami["ami_id"]));
                if ($roleinfo)
                {
                    for($i = 1; $i <= $ami["min_count"];$i++)
                    {		    
        			    $role = $roleinfo["name"];    
                        $res = RunInstance($AmazonEC2Client, CF_SECGROUP_PREFIX.$role, $farminfo['id'], $role, $farminfo['hash'], $ami["ami_id"]);                        
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
                $ZoneControler->Update($zone["id"]);
            
            $db->Execute("UPDATE zones SET isdeleted='0' WHERE farmid='{$farminfo['id']}'");
            
            if (!$err)
            {
                $okmsg = "Farm {$farminfo['name']} is now launching. It will take few minutes to start all instances.";
                CoreUtils::Redirect("farms_view.php");
            }
            else 
            {
                $errmsg = "Farm launched with {$i} instances with following errors:";
                CoreUtils::Redirect("farms_view.php");
            }
        }
        elseif ($req_action == "Terminate")
        {
           
            $instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid='{$farminfo['id']}'");
            $AmazonEC2Client = new AmazonEC2(
                        APPPATH . "/etc/clients_keys/{$farminfo['clientid']}/pk.pem", 
                        APPPATH . "/etc/clients_keys/{$farminfo['clientid']}/cert.pem");
                        
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
        
        				$db->Execute("DELETE FROM farm_instances WHERE farmid='{$farminfo['id']}'");
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
    	        
    	        $db->Execute("UPDATE zones SET isdeleted='1' WHERE farmid='{$farminfo['id']}'");
    	    }
    	    
    	    $db->Execute("UPDATE farms SET status='0' WHERE id='{$farminfo['id']}'");
    	    
    		if (!$errmsg)
    		{
    		    $okmsg = "Farm successfully terminated";
    		    CoreUtils::Redirect("farms_view.php");
    	    } 
        }
    }
    elseif ($post_cbtn_3)
    {
        CoreUtils::Redirect("farms_view.php");
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
    }
    
	$display["title"] = "Farms&nbsp;&raquo;&nbsp;{$display["action"]}";
	$display["new"] = ($req_new) ? "1" : "0";
	$display["farminfo"] = $farminfo;

	require_once("src/append.inc.php");
?>