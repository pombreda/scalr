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
            	$errmsg = _("Instance not found");
            	CoreUtils::Redirect("farms.view.php");
            }
            
            if ($instanceinfo["isdbmaster"] == 1)
            {
            	if ($db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE ami_id=?", array($instanceinfo['ami_id'])) > 1)
            		$display["show_dbmaster_warning"] = true;
            }
            
            if ($farminfo["clientid"] != $_SESSION['uid'] && $_SESSION['uid'] != 0)
                UI::Redirect("index.php");
                
            $ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$instanceinfo['ami_id']}'");
            $rolename = $ami_info['name'];
            
            if ($ami_info['alias'] == ROLE_ALIAS::MYSQL)
            	$display["warnmsg"] = _("You are about to synchronize MySQL instance. The bundle will not include MySQL data. <a href='farm_mysql_info.php?farmid={$instanceinfo['farmid']}'>Click here if you wish to bundle and save MySQL data</a>.");
            
            if (!$ami_info)
            	$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE name=? AND roletype=?", array($instanceinfo['role_name'], ROLE_TYPE::SHARED));
            
            if ($db->GetOne("SELECT id FROM ami_roles WHERE `replace` = '{$ami_info["ami_id"]}' and clientid='{$farminfo['clientid']}'"))
            {
                $errmsg = _("This role already synchonizing&#x2026;");
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
            $err[] = _("Allowed chars for role name is [A-Za-z0-9-]");
		else 
		{
		    if ($post_name != $new_rolename)
		    {
		        if ($db->GetOne("SELECT * FROM ami_roles WHERE name=? AND clientid=? AND iscompleted='1'", array($post_name, $farminfo["clientid"])))
		          	$err[] = sprintf(_("Role %s already exists. Please use a different name for new role."), $post_name);
		          
		        if ($db->GetOne("SELECT * FROM ami_roles WHERE name=? AND roletype=?", array($post_name, ROLE_TYPE::SHARED)))
		        	$err[] = sprintf(_("There is already a shared role %s. Please use a different name for new role."), $post_name);
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
				$errmsg = sprintf(_("Cannot synchronize role. Role with AMI %s not found in database."), $instance_ami_info['ami_id']);
	    		UI::Redirect("farms_view.php");	
			}
				
            $alias = $instance_ami_info["alias"];
			$architecture = $instance_ami_info["architecture"];
			
			$farm_ami_info = $db->GetRow("SELECT * FROM farm_amis WHERE ami_id=? AND farmid=?", 
				array($instanceinfo["ami_id"], $instanceinfo['farmid'])
			);
			
			if ($farm_ami_info)
				$i_type = $farm_ami_info["instance_type"];
			
			if (!$i_type)
				$i_type = $instance_ami_info["instance_type"];
			
				
			
			
			$ami_info = $instance_ami_info;
                                
			$db->BeginTrans();
                
			try
			{
				// Update last synchronization date
				$db->Execute("UPDATE farm_instances SET dtlastsync=? WHERE id=?", array(time(), $instanceinfo['id']));
                
				$db->Execute("INSERT INTO ami_roles SET name=?, roletype=?, clientid=?, prototype_iid=?, iscompleted='0', `replace`=?, `alias`=?, `architecture`=?, `instance_type`=?, dtbuildstarted=NOW()", 
					array($post_name, ROLE_TYPE::CUSTOM, $farminfo["clientid"], $instanceinfo['instance_id'], $instance_ami_info['ami_id'], $alias, $architecture, $i_type)
				);
                
				$SNMP->Connect($instanceinfo['external_ip'], null, $farminfo['hash']);
				$trap = vsprintf(SNMP_TRAP::START_REBUNDLE, array($post_name));
				$res = $SNMP->SendTrap($trap);
				$Logger->info("[FarmID: {$farminfo['id']}] Sending SNMP Trap startRebundle ({$trap}) to '{$instanceinfo['instance_id']}' ('{$instanceinfo['external_ip']}') complete ({$res})");
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				$Logger->fatal("Exception thrown during role synchronization: {$e->getMessage()}");
				$errmsg = _("Cannot synchronize role. Please try again later.");
				UI::Redirect("farms_view.php");
			}
			
			$db->CommitTrans();
			
			$okmsg = sprintf(_("An image for new role %s is being bundled. It can take up to 10 minutes."), $post_name);
			UI::Redirect("client_roles_view.php");
		}
	}
                           
    $display["instance_id"] = $instance_id;
    $display["new_rolename"] = $new_rolename;
    $display["rolename"] = $rolename;
    
	$display["form_action"] = $_SERVER['PHP_SELF'];
	
	require("src/append.inc.php"); 
?>