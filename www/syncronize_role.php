<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Synchronize role";
		
    if ($req_iid)
    {
        $instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($req_iid));
        if ($instanceinfo)
        {
            $farminfo = $db->GetRow("SELECT * FROM farms WHERE id='{$instanceinfo['farmid']}'");
            
            if ($farminfo["clientid"] != $_SESSION["uid"] && $_SESSION["uid"] != 0)
            {
            	$errmsg = "Instance not found";
            	CoreUtils::Redirect("farms.view.php");
            }
            
            if ($instanceinfo["isdbmaster"] == 1)
            {
            	if ($db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE ami_id=?", array($instanceinfo['ami_id'])) > 1)
            		$display["show_dbmaster_warning"] = true;
            }
            
            $clientinfo = $db->GetRow("SELECT * FROM clients WHERE id='{$farminfo['clientid']}'");
            
            if ($farminfo["clientid"] != $_SESSION['uid'] && $_SESSION['uid'] != 0)
                UI::Redirect("index.php");
                
            $ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$instanceinfo['ami_id']}'");
            $rolename = $ami_info['name'];
            
            if (!$ami_info)
            	$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE name=? AND roletype=?", array($instanceinfo['role_name'], ROLE_TYPE::SHARED));
            
            if ($db->GetOne("SELECT id FROM ami_roles WHERE `replace` = '{$ami_info["ami_id"]}' and clientid='{$farminfo['clientid']}'"))
            {
                $errmsg = "This role already bsynchonizing&#x2026;";
                UI::Redirect("client_roles_view.php");
            }
            
            if ($ami_info["roletype"] == ROLE_TYPE::SHARED)
            {
                $i = 1;
                $name = "{$ami_info["name"]}-".date("Ymd")."01";
                $role = $db->GetOne("SELECT id FROM ami_roles WHERE name=? AND iscompleted='1' AND clientid='{$farminfo['clientid']}'", array($name));
                if ($role)
                {
	                while ($role)
	                {
	               		$name = $ami_info["name"];
	                    if ($i > 0)
	                    {
	                        $istring = ($i < 10) ? "0{$i}" : $i;
	                    	$name .= "-".date("Ymd")."{$istring}";
	                    }
	                        
	                    $role = $db->GetOne("SELECT id FROM ami_roles WHERE name=? AND iscompleted='1' AND clientid='{$farminfo['clientid']}'", array($name));                    
	                    $i++;
	                }
                }
                
                $new_rolename = $name;
            }
            else 
                $new_rolename = $ami_info['name'];
                
            $instance_id = $instanceinfo["instance_id"];
        }
        else 
            UI::Redirect("index.php");
    }
    else 
        UI::Redirect("index.php");
                               
                        
    if ($_POST) 
	{
        if ($post_cancel)
        	UI::Redirect("instances_view.php?farmid={$farminfo['id']}");
		
		$Validator = new Validator();
		$SNMP = new SNMP();
        
		if (!preg_match("/^[A-Za-z0-9-]+$/", $post_name))
            $err[] = "Allowed chars for role name is [A-Za-z0-9-]";
		else 
		{
		    if ($post_name != $new_rolename)
		    {
		        if ($db->GetOne("SELECT * FROM ami_roles WHERE name=? AND clientid=? AND iscompleted='1'", array($post_name, $farminfo["clientid"])))
		          	$err[] = "Role {$post_name} already exists. Please use a different name for new role.";
		          
		        if ($db->GetOne("SELECT * FROM ami_roles WHERE name=? AND roletype=?", array($post_name, ROLE_TYPE::SHARED)))
		        	$err[] = "There is already a shared role {$post_name}. Please use a different name for new role.";
		    }
		}
		
		if (count($err) == 0)
		{
			$instance_ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($instanceinfo["ami_id"]));
			if (!$instance_ami_info)
			{
				$instance_ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE name=? AND roletype=?", array($instanceinfo["role_name"], ROLE_TYPE::SHARED));
				$db->Execute("UPDATE farm_instances SET ami_id=? WHERE farmid=? AND ami_id=?", array($instance_ami_info["ami_id"], $instanceinfo["farmid"], $instanceinfo['ami_id']));
			}
                
			if (!$instance_ami_info)
			{
				$errmsg = "Cannot synchronize role. Role with AMI {$instance_ami_info['ami_id']} not found in database.";
	    		UI::Redirect("farms_view.php");	
			}
				
            $alias = $instance_ami_info["alias"];
			$architecture = $instance_ami_info["architecture"];
			$i_type = $instance_ami_info["instance_type"];
			$ami_info = $instance_ami_info;
                                
			$db->BeginTrans();
                
			try
			{
				// Update last synchronization date
				$db->Execute("UPDATE farm_instances SET dtlastsync=? WHERE id=?", array(time(), $instanceinfo['id']));
                
				$db->Execute("INSERT INTO ami_roles SET name=?, roletype=?, clientid=?, prototype_iid=?, iscompleted='0', `replace`=?, `alias`=?, `architecture`=?, `instance_type`=?, dtbuildstarted=NOW()", array($post_name, ROLE_TYPE::CUSTOM, $farminfo["clientid"], $instanceinfo['instance_id'], $instance_ami_info['ami_id'], $alias, $architecture, $i_type));
                
				$SNMP->Connect($instanceinfo['external_ip'], null, $farminfo['hash']);
				$trap = vsprintf(SNMP_TRAP::START_REBUNDLE, array($post_name));
				$res = $SNMP->SendTrap($trap);
				$Logger->info("[FarmID: {$farminfo['id']}] Sending SNMP Trap startRebundle ({$trap}) to '{$instanceinfo['instance_id']}' ('{$instanceinfo['external_ip']}') complete ({$res})");
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				$Logger->fatal("Exception thrown during role synchronization: {$e->getMessage()}");
				$errmsg = "Cannot synchronize role. Please try again later.";
				UI::Redirect("farms_view.php");
			}
			
			$db->CommitTrans();
			
			$okmsg = "An image for new role {$post_name} is being bundled. It can take up to 10 minutes.";
			UI::Redirect("client_roles_view.php");
		}
	}
                           
    $display["instance_id"] = $instance_id;
    $display["new_rolename"] = $new_rolename;
    $display["rolename"] = $rolename;

	$display["form_action"] = $_SERVER['PHP_SELF'];
	
	require("src/append.inc.php"); 
?>