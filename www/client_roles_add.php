<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Custom roles&nbsp;&raquo;&nbsp;Add";
		
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
	
	$AmazonEC2Client = new AmazonEC2($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);

	$SNMP = new SNMP();
	
    if ($_POST) 
	{
        $Validator = new Validator();
		
		if (!preg_match("/^[A-Za-z0-9-]+$/", $post_name))
			$err[] = "Allowed chars for role name is [A-Za-z0-9-]";
		elseif ($db->GetOne("SELECT * FROM ami_roles WHERE name=? AND (clientid='0' OR clientid='{$_SESSION['uid']}') AND id != ? AND iscompleted!=2", array($post_name, $post_id)))
			$err[] = "Role with name {$post_name} already exists";
		
	    if (!$Validator->IsNumeric($post_default_minLA) || $post_default_minLA < 0)
			$err[] = "Invalid value for minimum LA";
	    
	    if (!$Validator->IsNumeric($post_default_maxLA) || $post_default_maxLA > 99)
			$err[] = "Invalid value for maximum LA";
	       
		if (!$post_id)
		{
            $instance_info = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($post_instance_id));
            if (!$instance_info)
                $err[] = "Instance not found";
                
            $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($instance_info["farmid"], $_SESSION['uid']));
            if (!$farminfo)
                $err[] = "Instance not found";
                
            if (count($err) == 0)
            {                
			    $security_group_name = CONFIG::$SECGROUP_PREFIX.$post_name;
            	
                if (!$errmsg)
                {
	            	$db->BeginTrans();
	            	try
	                {
		            	$instance_ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($instance_info["ami_id"]));  
		            	$alias = $instance_ami_info["alias"];
		                $architecture = $instance_ami_info["architecture"];
		                $instance_type = $instance_ami_info["instance_type"];
		                $ami_info = $instance_ami_info;
	                	
	                	$old_roleid = $db->GetOne("SELECT id FROM ami_roles WHERE ami_id=?", array($instance_info["ami_id"]));
		                $i_type = $db->GetOne("SELECT instance_type FROM ami_roles WHERE ami_id=?", array($instance_info["ami_id"]));
		                
		                // Update last synchronization date
                		$db->Execute("UPDATE farm_instances SET dtlastsync=? WHERE id=?", array(time(), $instance_info['id']));
		                
		                $db->Execute("INSERT INTO ami_roles SET name=?, roletype=?, clientid=?, prototype_iid=?, 
		                	iscompleted='0', default_minLA=?, default_maxLA=?, alias=?, architecture=?, 
		                	instance_type=?, dtbuildstarted=NOW()", 
		                	array($post_name, ROLE_TYPE::CUSTOM, $_SESSION['uid'], $instance_info['instance_id'], 
		                		$post_default_minLA, $post_default_maxLA, $alias, $architecture, $instance_type)
		                );
	                    $roleid = $db->Insert_ID();
		                
		                if ($addSecGroup)
		                {
	                        $res = $AmazonEC2Client->CreateSecurityGroup($security_group_name, $post_name);
	                        if (!$res)
	                           throw new Exception("Cannot create security group", E_USER_ERROR);	                        
	                           
	                        $db->Execute("INSERT INTO security_rules (id, roleid, rule) SELECT NULL, '{$roleid}', rule FROM security_rules WHERE roleid='{$ami_info['id']}'");
	                           
	                        // Set permissions for group
	                        $group_rules = $db->GetAll("SELECT * FROM security_rules WHERE roleid='{$ami_info['id']}'");	                        
	                        $IpPermissionSet = new IpPermissionSetType();
	                        foreach ($group_rules as $rule)
	                        {
	                           $group_rule = explode(":", $rule["rule"]);
	                           $IpPermissionSet->AddItem($group_rule[0], $group_rule[1], $group_rule[2], null, array($group_rule[3]));
	                        }
	                        
	                        $AmazonEC2Client->AuthorizeSecurityGroupIngress($_SESSION['aws_accountid'], $security_group_name, $IpPermissionSet);
		                }
		                
		                $SNMP->Connect($instance_info['external_ip'], null, $farminfo['hash']);
		                $trap = vsprintf(SNMP_TRAP::START_REBUNDLE, array($post_name));
		                $res = $SNMP->SendTrap($trap);
		                $Logger->info("[FarmID: {$farminfo['id']}] Sending SNMP Trap startRebundle ({$trap}) to '{$instance_info['instance_id']}' ('{$instance_info['external_ip']}') complete ({$res})");
	                }
	                catch(Exception $e)
	                {
	                	$db->RollbackTrans();
			    		$Logger->fatal("Exception thrown client role creation: {$e->getMessage()}");
			    		$errmsg = "Cannot create custom role. Please try again later.";
	                }
                }
                
                if (!$errmsg)
                {
                	$db->CommitTrans();
                	$okmsg = "An image for new role {$post_name} is being bundled. It can take up to 10 minutes.";
	            	UI::Redirect("client_roles_view.php");
                }
            }
		}
		else 
		{
            //
		}
	}
                           
    $response = $AmazonEC2Client->DescribeInstances();
	
	$rowz = $response->reservationSet->item;

	if ($rowz instanceof stdClass)
		$rowz = array($rowz);
	
	// Custom properties
	foreach ($rowz as $pk=>$pv)
	{
	    if ($pv->instancesSet->item->instanceState->name == 'running')
	    {
	       $instance_info = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($rowz[$pk]->instancesSet->item->instanceId));
	       if ($instance_info)
	       {
				$farm_role_info = $db->GetRow("SELECT * FROM farm_amis WHERE ami_id=? AND farmid=?", 
					array($rowz[$pk]->instancesSet->item->imageId, $instance_info['farmid'])
				);
				
				if ($farm_role_info)
					$rowz[$pk]->LA = array('min' => $farm_role_info['min_LA'], 'max' => $farm_role_info['max_LA']);
	       }
	    	
	       $rowz[$pk]->Role = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($rowz[$pk]->instancesSet->item->imageId));
	       
	       if ($rowz[$pk]->Role && !$db->GetOne("SELECT id FROM ami_roles WHERE iscompleted='0' AND prototype_iid='{$rowz[$pk]->instancesSet->item->instanceId}'"))
	           $display["rows"][] = $rowz[$pk];
	    }
	}
			
	if (count($display["rows"]) == 0)
	{
		$errmsg = "You must have at least one running instance";
		UI::Redirect("client_roles_view.php");
	}
	
	if ($get_id)
	{
		UI::Redirect("client_roles_view.php");
	}
	else
		$display = array_merge($display, $_POST);

	$display["form_action"] = $_SERVER['PHP_SELF'];
	
	require("src/append.inc.php"); 
?>