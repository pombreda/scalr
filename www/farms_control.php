<?
    require("src/prepend.inc.php"); 
    
    if ($_SESSION['uid'] == 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));

    if (!$farminfo || $post_cancel)
        UI::Redirect("farms_view.php");
                
    if ($req_action == "Launch")
    {    	
    	if ($post_cbtn_3)
    		UI::Redirect("farms_add.php?id={$farminfo['id']}");
    	
    	Scalr::FireEvent($farminfo['id'], EVENT_TYPE::FARM_LAUNCHED, $req_mark_active);
        
        $okmsg = "Farm {$farminfo['name']} is now launching. It will take few minutes to start all instances.";
        UI::Redirect("farms_view.php");
    }
    elseif ($req_action == "Terminate")
    {			        	
    	if ($req_term_step == 2 && $farminfo['status'] == FARM_STATUS::RUNNING)
	    {
	    	$_SESSION['term_post'] = $_POST;
	    	$_SESSION['issync'] = isset($_POST['cbtn_2']) ? true : false;
	    	$display["term_step"] = 2;
	    }
    	else
    	{
    		$Logger->info("Terminating farm ID {$farminfo["id"]}");
    		
    		if ($_SESSION['issync'])
		    {
		    	$db->BeginTrans();
		    	$SNMP = new SNMP();
		    	
		    	$Logger->debug("Checking and execute sync routines...");
		    	
		    	$sync_instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? 
	    			AND instance_id IN (SELECT prototype_iid FROM ami_roles WHERE iscompleted = '0')", 
	    			array($farminfo['id'])
	    		);
	    		foreach ($sync_instances as $sync_instance)
	    		{
	    			$db->Execute("UPDATE farm_instances SET state=? WHERE id=?",
		    			array(INSTANCE_STATE::PENDING_TERMINATE, $sync_instance['id'])
		    		);
		    		
		    		$Logger->debug("Synchronize for role with ami {$sync_instance['ami_id']} already running on instance: {$sync_instance['instance_id']}");
		    		
		    		continue;
	    		}
		    	
		    	foreach ($_SESSION['term_post']["sync"] as $ami_id)
		    	{		    		
		    		if (!$_SESSION['term_post']["sync_i"][$ami_id])
		    			continue;
		    		
		    		$Logger->debug("Synchronize for role with ami {$ami_id} will be executed on {$_SESSION['term_post']["sync_i"][$ami_id]}");
		    		
		    		$instance = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", 
		    			array($_SESSION['term_post']["sync_i"][$ami_id])
		    		);
		    				 		    		
		    		if (!$instance)
		    		{
		    			$Logger->debug("Instance {$_SESSION['term_post']["sync_i"][$ami_id]} not found in database.");
		    			
		    			$err[] = "Instance {$_SESSION['term_post']["sync_i"][$ami_id]} not found in database.";
		    			continue;
		    		}
	
		    		$roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?",
		    			array($instance['ami_id'])
		    		);
		    		
		    		// Select role name
		    		if ($roleinfo["roletype"] == ROLE_TYPE::SHARED)
		    		{
			    		$i = 1;
		                $name = "{$roleinfo["name"]}-".date("Ymd")."01";
		                $role = $db->GetOne("SELECT id FROM ami_roles WHERE name=? AND iscompleted='1' AND clientid='{$farminfo['clientid']}'", array($name));
		                if ($role)
		                {
			                while ($role)
			                {
			                    $name = $roleinfo["name"];
			                    if ($i > 0)
			                    {
			                        $istring = ($i < 10) ? "0{$i}" : $i;
			                    	$name .= "-".date("Ymd")."{$istring}";
			                    }
			                        
			                    $role = $db->GetOne("SELECT id FROM ami_roles WHERE name=? AND iscompleted='1' AND clientid='{$farminfo['clientid']}'", array($name));                    
			                    $i++;
			                }
		                }
		                
		                $rolename = $name;
		    		}
		    		else
		    			$rolename = $roleinfo["name"];
		    			
		    		$Logger->debug("New role name {$rolename}");
		    			
		    		try
		    		{
			    		// Add record for new ami to database
		    			$db->Execute("INSERT INTO 
			    			ami_roles (
			    				`ami_id`, `name`, `roletype`, `clientid`, `prototype_iid`, `iscompleted`, 
			    				`replace`, `default_minLA`, `default_maxLA`, `alias`, `instance_type`, 
			    				`architecture`, `dtbuildstarted`, `rebundle_trap_received`) 
			    			SELECT 
			    				'', ?, ?, ?, ?, '0', ?, default_minLA, default_maxLA, 
			    				alias, instance_type, architecture, NOW(), '0' FROM ami_roles WHERE ami_id=?", 
			    			array($rolename, ROLE_TYPE::CUSTOM, $farminfo['clientid'], $instance['instance_id'], $ami_id, $ami_id)
			    		);
			    		
			    		// Set instance state = PENDING_TEMINATE
			    		$db->Execute("UPDATE farm_instances SET state=? WHERE id=?",
			    			array(INSTANCE_STATE::PENDING_TERMINATE, $instance['id'])
			    		);
			    		
			    		// Send rebundle trap
			    		$SNMP->Connect($instance['external_ip'], null, $farminfo['hash']);
						$trap = vsprintf(SNMP_TRAP::START_REBUNDLE, array($rolename));
						$res = $SNMP->SendTrap($trap);
						$Logger->info("[FarmID: {$farminfo['id']}] Sending SNMP Trap startRebundle ({$trap}) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})");
		    		}
		    		catch(Exception $e)
		    		{
		    			$db->RollbackTrans();
						$Logger->fatal(new FarmLogMessage($farminfo["id"], "Exception thrown during role synchronization: {$e->getMessage()}"));
		    		}
		    	}
		    	
		    	if (count($err) == 0)
		    		$db->CommitTrans();
		    }
		    
		    if (count($err) == 0)
		    {
			    $remove_zone_from_DNS = ($post_deleteDNS) ? 1 : 0;
		
			    $term_on_sync_fail = ($_SESSION['term_post']["untermonfail"]) ? 0 : 1;
			    
			    Scalr::FireEvent($farminfo['id'], EVENT_TYPE::FARM_TERMINATED, $remove_zone_from_DNS, $post_keep_elastic_ips, $term_on_sync_fail);
				
				$okmsg = "Farm successfully terminated";
			    UI::Redirect("farms_view.php");
		    }
    	}
    }
    
    if ($farminfo["status"] == 0)
    {
        $display["action"] = "Launch";
        $display["show_dns"] = $db->GetOne("SELECT COUNT(*) FROM zones WHERE farmid=?", $farminfo['id']);
        $display["num"] = $db->GetOne("SELECT COUNT(*) FROM farm_amis WHERE farmid=?", $farminfo['id']);
    }
    else
    { 
		if (!$display["term_step"])
    		$display["term_step"] = 1;
    		
    	$display["action"] = "Terminate";
        $display["num"] = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE farmid=?", $farminfo['id']);
        
        $display["sync_count"] = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE farmid=? AND instance_id IN (SELECT prototype_iid FROM ami_roles WHERE iscompleted = '0')", array($farminfo['id']));
        
        $display["elastic_ips"] = $db->GetOne("SELECT COUNT(*) FROM elastic_ips WHERE farmid=?", array($farminfo['id']));
        
        //
        // Synchronize before termination
        //
        $farm_launch_time = strtotime($farminfo['dtlaunched']);        
        $outdated_farm_amis = $db->GetAll("SELECT * FROM farm_amis WHERE (UNIX_TIMESTAMP(dtlastsync) < ? OR dtlastsync IS NULL) AND farmid=?",
        	array($farm_launch_time, $farminfo['id'])
        );
        foreach ($outdated_farm_amis as &$farm_ami)
        {
        	$farm_ami['name'] = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id=?",
        		array($farm_ami['ami_id'])
        	);
        	
        	if ($farm_ami['dtlastsync'])
        		$farm_ami['dtlastsync'] = Formater::FuzzyTimeString(strtotime($farm_ami['dtlastsync']), false);
        	else
        		$farm_ami['dtlastsync'] = false;
        		
        	$farm_ami['instances'] = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND ami_id=? AND state=?",
        		array($farminfo['id'], $farm_ami['ami_id'], INSTANCE_STATE::RUNNING)
        	);
        	
        	$farm_ami['running'] = $db->GetRow("SELECT * FROM farm_instances WHERE farmid=? AND ami_id=? 
    			AND instance_id IN (SELECT prototype_iid FROM ami_roles WHERE iscompleted = '0')", 
    			array($farminfo['id'], $farm_ami['ami_id'])
    		);
        }
        
        $display['outdated_farm_amis'] = $outdated_farm_amis;
    }
    
	$display["title"] = "Farms&nbsp;&raquo;&nbsp;{$display["action"]}";
	$display["new"] = ($req_new) ? "1" : "0";
	$display["iswiz"] = ($req_iswiz) ? "1" : "0";
	$display["farminfo"] = $farminfo;

	require_once("src/append.inc.php");
?>