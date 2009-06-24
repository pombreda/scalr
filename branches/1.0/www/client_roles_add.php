<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = _("Custom roles&nbsp;&raquo;&nbsp;Add");
		
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	
	if (!$req_region)
    {			
		$Smarty->assign($display);
		$Smarty->display("region_information_step.tpl");
		exit();
    }
    else
    	$region = $req_region;
    	
    $display['region'] = $region;
	
	$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region));
	$AmazonEC2Client->SetAuthKeys($_SESSION['aws_private_key'], $_SESSION['aws_certificate']);
	
    if ($_POST && $post_step == 2) 
	{
        $Validator = new Validator();
		
        if (!preg_match("/^[A-Za-z0-9-]+$/", $post_name))
			$err[] = _("Allowed chars for role name is [A-Za-z0-9-]");
		
		if ($db->GetOne("SELECT id FROM ami_roles WHERE name=? AND (clientid='0' OR clientid='{$_SESSION['uid']}') AND id != ? AND iscompleted != 2", array($post_name, (int)$post_id)))
			$err[] = sprintf(_("Role with name %s already exists"), $post_name);
		
	    if (!$Validator->IsNumeric($post_default_minLA) || $post_default_minLA < 0)
			$err[] = _("Invalid value for minimum LA");
	    
	    if (!$Validator->IsNumeric($post_default_maxLA) || $post_default_maxLA > 99)
			$err[] = _("Invalid value for maximum LA");
			
		if (!$post_id)
		{
            $instance_info = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($post_instance_id));
            if (!$instance_info)
                $err[] = _("Instance not found");
                
            $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($instance_info["farmid"], $_SESSION['uid']));
            if (!$farminfo)
                $err[] = _("Instance not found");
                
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
		                
		                $farm_ami_info = $db->GetRow("SELECT * FROM farm_amis WHERE ami_id=? AND farmid=?", 
							array($instance_info["ami_id"], $instance_info['farmid'])
						);
						
						if ($farm_ami_info)
							$instance_type = $farm_ami_info["instance_type"];
						
						if (!$instance_type)
							$instance_type = $db->GetOne("SELECT instance_type FROM ami_roles WHERE ami_id=?", array($instance_info["ami_id"]));
		                
		                // Update last synchronization date
                		$db->Execute("UPDATE farm_instances SET dtlastsync=? WHERE id=?", array(time(), $instance_info['id']));
		                
		                $db->Execute("INSERT INTO ami_roles SET name=?, roletype=?, clientid=?, prototype_iid=?, 
		                	iscompleted='0', default_minLA=?, default_maxLA=?, alias=?, architecture=?, 
		                	instance_type=?, dtbuildstarted=NOW(), region=?", 
		                	array($post_name, ROLE_TYPE::CUSTOM, $_SESSION['uid'], $instance_info['instance_id'], 
		                		$post_default_minLA, $post_default_maxLA, $alias, $architecture, $instance_type,
		                		$instance_info['region'])
		                );
	                    $roleid = $db->Insert_ID();

	                    $DBInstance = DBInstance::LoadByID($instance_info['id']);
	                    $DBInstance->SendMessage(new StartRebundleScalrMessage(
	                    	$post_name
	                    ));
	                }
	                catch(Exception $e)
	                {
	                	$db->RollbackTrans();
			    		$Logger->fatal(sprintf(_("Exception thrown client role creation: %s"), $e->getMessage()));
			    		$errmsg = _("Cannot create custom role. Please try again later.");
	                }
                }
                
                if (!$errmsg)
                {
                	$db->CommitTrans();
                	$okmsg = sprintf(_("An image for new role %s is being bundled. It can take up to 10 minutes."), $post_name);
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
					$rowz[$pk]->RoleInfo = array('min' => $farm_role_info['min_LA'], 'max' => $farm_role_info['max_LA']);
	       }
	    	
	       $rowz[$pk]->Role = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($rowz[$pk]->instancesSet->item->imageId));
	       
	       if ($rowz[$pk]->Role && !$db->GetOne("SELECT id FROM ami_roles WHERE iscompleted='0' AND prototype_iid='{$rowz[$pk]->instancesSet->item->instanceId}'"))
	           $display["rows"][] = $rowz[$pk];
	    }
	}
			
	if (count($display["rows"]) == 0)
	{
		$errmsg = _("You must have at least one running instance in specified region");
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