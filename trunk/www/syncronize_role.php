<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Synchronize role";
		
    if ($req_iid)
    {
        $instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($req_iid));
        if ($instanceinfo)
        {
            $farminfo = $db->GetRow("SELECT * FROM farms WHERE id='{$instanceinfo['farmid']}'");
            
            $AmazonEC2Client = new AmazonEC2(
                        APPPATH . "/etc/clients_keys/{$farminfo['clientid']}/pk.pem", 
                        APPPATH . "/etc/clients_keys/{$farminfo['clientid']}/cert.pem");
            
            $clientinfo = $db->GetRow("SELECT * FROM clients WHERE id='{$farminfo['clientid']}'");
            
            if ($farminfo["clientid"] != $_SESSION['uid'] && $_SESSION['uid'] != 0)
                UI::Redirect("index.php");
                
            $ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$instanceinfo['ami_id']}'");
            $rolename = $ami_info['name'];
            
            if ($db->GetOne("SELECT id FROM ami_roles WHERE `replace` = '{$ami_info["ami_id"]}' and clientid='{$farminfo['clientid']}'"))
            {
                $errmsg = "This role already being synchronized...";
                UI::Redirect("client_roles_view.php");
            }
            
            if ($ami_info["roletype"] == 'SHARED')
            {
                $i = 0;
                $role = $db->GetOne("SELECT id FROM ami_roles WHERE name=? AND iscompleted='1' AND clientid='{$_SESSION['uid']}'", array($ami_info["name"]));
                if (!$role)
                	$name = "{$ami_info["name"]}1";
                else
                {
	                while ($role)
	                {
	                    $name = $ami_info["name"];
	                    if ($i > 0)
	                        $name .= "{$i}";
	                        
	                    $role = $db->GetOne("SELECT id FROM ami_roles WHERE name=? AND iscompleted='1' AND clientid='{$_SESSION['uid']}'", array($name));                    
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
        $Validator = new Validator();
		
		if (!$Validator->IsAlphaNumeric($post_name))
            $err[] = "Role name must be an alphanumeric string";
		else 
		{
		    if ($post_name != $new_rolename)
		    {
		        if ($db->GetOne("SELECT * FROM ami_roles WHERE name=? AND clientid=? AND iscompleted='1'", array($post_name, $farminfo["clientid"])))
		          	$err[] = "Role {$post_name} already exists. Please use a different name for new role.";
		          
		        if ($db->GetOne("SELECT * FROM ami_roles WHERE name=? AND roletype='SHARED'", array($post_name)))
		        	$err[] = "There is already a shared role {$post_name}. Please use a different name for new role.";
		    }
		}
		
		if (count($err) == 0)
		{
		    // Create security group if needed
		    $security_group_name = CONFIG::$SECGROUP_PREFIX.$post_name;
		    
		    $client_security_groups = $AmazonEC2Client->DescribeSecurityGroups();
            if (!$client_security_groups)
                throw new ApplicationException("Cannot describe security groups for client.");
                
            $client_security_groups = $client_security_groups->securityGroupInfo->item;
            
            $addSecGroup = true;
            // Now we need add missed security groups
            if (is_array($client_security_groups))
            {
                foreach ($client_security_groups as $group)
                {
                    if (strtolower($group->groupName) == strtolower($security_group_name))
                    {
                       $addSecGroup = false;
                       break;
                    }
                }
            }
            elseif (strtolower($client_security_groups->groupName) == strtolower($security_group_name))
               $addSecGroup = false;
                                                                    
            if (count($err) == 0)
            {
                $instance_ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($instanceinfo["ami_id"]));  
            	$alias = $instance_ami_info["alias"];
                $architecture = $instance_ami_info["architecture"];
                $i_type = $instance_ami_info["instance_type"];
                $ami_info = $instance_ami_info;
                                
                $db->BeginTrans();
                
                try
                {
	                $db->Execute("INSERT INTO ami_roles SET name=?, roletype='CUSTOM', clientid=?, prototype_iid=?, iscompleted='0', `replace`=?, `alias`=?, `architecture`=?, `instance_type`=?", array($post_name, $farminfo["clientid"], $instanceinfo['instance_id'], $instanceinfo['ami_id'], $alias, $architecture, $i_type));
	                
	                $newroleid = $db->Insert_ID();
	                
	                if ($addSecGroup)
	                {
                        $res = $AmazonEC2Client->CreateSecurityGroup($security_group_name, $post_name);
                        if (!$res)
                           throw new ApplicationException("Cannot create security group", E_USER_ERROR);	                        
                           
                        $db->Execute("INSERT INTO security_rules (id, roleid, rule) SELECT NULL, '{$newroleid}', rule FROM security_rules WHERE roleid='{$ami_info['id']}'");
                           
                        // Set permissions for group
                        $group_rules = $db->GetAll("SELECT * FROM security_rules WHERE roleid='{$newroleid}'");	                        
                        $IpPermissionSet = new IpPermissionSetType();
                        foreach ($group_rules as $rule)
                        {
                           $group_rule = explode(":", $rule["rule"]);
                           $IpPermissionSet->AddItem($group_rule[0], $group_rule[1], $group_rule[2], null, array($group_rule[3]));
                        }
                        
                        $AmazonEC2Client->AuthorizeSecurityGroupIngress($clientinfo['aws_accountid'], $security_group_name, $IpPermissionSet);
	                }
	                
	                $Shell = ShellFactory::GetShellInstance();
	            	$res = $Shell->QueryRaw(CONFIG::$SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$instanceinfo['external_ip'].' "" SNMPv2-MIB::snmpTrap.12.0 SNMPv2-MIB::sysName.0 s "'.$post_name.'" SNMPv2-MIB::sysLocation.0 s "0" 2>&1', true);
                }
                catch(Exception $e)
                {
                	$db->RollbackTrans();
		    		$Logger->fatal("Exception thrown during role synchronization: {$e->getMessage()}");
		    		$errmsg = "Cannot synchronize role. Please try again later.";
		    		UI::Redirect("farms_view.php");
                }
                
                $db->CommitTrans();
                
	            $Logger->debug("Sending SNMP Trap 12.0 (Start rebundle) complete ({$res})");
                
                $okmsg = "An image for new role {$post_name} is being bundled. It may take up to 10 minutes.";
                UI::Redirect("client_roles_view.php");
            }
		}
	}
                           
    $display["instance_id"] = $instance_id;
    $display["new_rolename"] = $new_rolename;
    $display["rolename"] = $rolename;

	$display["form_action"] = $_SERVER['PHP_SELF'];
	
	require("src/append.inc.php"); 
?>