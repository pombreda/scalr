<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Custom roles&nbsp;&raquo;&nbsp;Add / Edit";
		
	$AmazonEC2Client = new AmazonEC2(
                        APPPATH . "/etc/clients_keys/{$_SESSION['uid']}/pk.pem", 
                        APPPATH . "/etc/clients_keys/{$_SESSION['uid']}/cert.pem");

    if ($_POST) 
	{
        $Validator = new Validator();
		
		if (!$Validator->IsAlphaNumeric($post_name))
		  $err[] = "Role name must be an alphanumeric string";
		elseif ($db->GetOne("SELECT * FROM ami_roles WHERE name=? AND (clientid='0' OR clientid='{$_SESSION['uid']}') AND id != ? AND iscompleted!=2", array($post_name, $post_id)))
		  $err[] = "Role with same name already exists";
		
	    if (!$Validator->IsNumeric($post_default_minLA) || $post_default_minLA < 1)
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
                $db->BeginTrans();
            	try
                {
	            	$alias = $db->GetOne("SELECT alias FROM ami_roles WHERE ami_id=?", array($instance_info["ami_id"]));
	                $architecture = $db->GetOne("SELECT architecture FROM ami_roles WHERE ami_id=?", array($instance_info["ami_id"]));
	                $instance_type = $db->GetOne("SELECT instance_type FROM ami_roles WHERE ami_id=?", array($instance_info["ami_id"]));
	                
	                $old_roleid = $db->GetOne("SELECT id FROM ami_roles WHERE ami_id=?", array($instance_info["ami_id"]));
	                $i_type = $db->GetOne("SELECT instance_type FROM ami_roles WHERE ami_id=?", array($instance_info["ami_id"]));
	                
	                $Shell = ShellFactory::GetShellInstance();
	                $res = $Shell->QueryRaw(CONFIG::$SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$instance_info['external_ip'].' "" SNMPv2-MIB::snmpTrap.12.0 SNMPv2-MIB::sysName.0 s "'.$post_name.'" SNMPv2-MIB::sysLocation.0 s "0" 2>&1', true);
	                
	                $Logger->debug("Sending SNMP Trap 12.0 (Start rebundle) complete ({$res})");
	                                                            
	                $db->Execute("INSERT INTO ami_roles SET name=?, roletype='CUSTOM', clientid=?, prototype_iid=?, iscompleted='0', default_minLA=?, default_maxLA=?, alias=?, architecture=?, instance_type=?", array($post_name, $_SESSION['uid'], $instance_info['instance_id'], $post_default_minLA, $post_default_maxLA, $alias, $architecture, $instance_type));
                    $roleid = $db->Insert_ID();
                    $db->Execute("INSERT INTO security_rules (id, roleid, rule) SELECT NULL, '{$roleid}', rule FROM security_rules WHERE roleid='{$old_roleid}'");
                }
                catch(Exception $e)
                {
                	$db->RollbackTrans();
		    		$Logger->fatal("Exception thrown client role creation: {$e->getMessage()}");
		    		$errmsg = "Cannot delete farm at the moment. Please try again later.";
                }
                
                if (!$errmsg)
                {
                	$db->CommitTrans();
                	$okmsg = "An image for new role {$post_name} is being bundled. It may take up to 10 minutes.";
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
		
	// Custom properties
	foreach ($rowz as $pk=>$pv)
	{
	    if ($pv->instancesSet->item->instanceState->name == 'running')
	    {
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