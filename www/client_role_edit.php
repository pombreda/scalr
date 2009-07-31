<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	
	if ($req_id)
	{
		$display["id"] = $req_id;
		
		$info = $db->GetRow("SELECT * FROM ami_roles WHERE id=? AND clientid=?", array($req_id, $_SESSION['uid']));
		
		if ($_SESSION['uid'] != $info['clientid'])
			UI::Redirect("client_roles_view.php");
		
		if ($info)
		{
		    $display = array_merge($display, $info);
		    $display["arch"] = $info["architecture"];
		    $display["alias"] = ROLE_ALIAS::GetTypeByAlias($display["alias"]);
		    		    
		    $options = $db->GetAll("SELECT * FROM role_options WHERE ami_id=?", array($info['ami_id']));
		    
		    $role_options = array();
		    if (count($options) > 0)
		    {
		    	foreach ($options as $opt)
		    	{
		    		$option = new stdClass();
		    		$option->name = $opt['name'];
		    		$option->type = $opt['type'];
		    		$option->required = (bool)$opt['isrequired'];
		    		$option->defval = $opt['defval'];
		    		$option->allow_multiple_choise = (bool)$opt['allow_multiple_choice'];
		    		$option->options = json_decode($opt['options']);
		    		
		    		$role_options[] = $option; 
		    	}

		    	$display['role_options_dataform'] = json_encode($role_options);
		    }
		    
		    if ($req_task == 'share')
		    {
			    $Client = Client::Load($_SESSION['uid']);
		
				try
		    	{
			   		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($info['region'])); 
					$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
			   		
			   		$DescribeImagesType = new DescribeImagesType();
			   		$DescribeImagesType->imagesSet->item[] = array("imageId" => $info['ami_id']);
			   		$amazon_ami_info = $AmazonEC2Client->DescribeImages($DescribeImagesType);
			   		
			   		$display['is_role_public'] = $amazon_ami_info->imagesSet->item->isPublic;
		    	}
		    	catch(Exception $e)
		    	{
		    		$errmsg = $e->getMessage();
		    		UI::Redirect("client_roles_view.php");
		    	}
		    	
		    	//TODO: Check Role availability on EC2 (Manifest, AMI)
		    	
		    	
		    	$display["title"] = _("Client role&nbsp;&raquo;&nbsp;Share");
		    	
		    	$template_name = 'share_client_role.tpl';
		    }
		    else
		    {
		    	$display["title"] = _("Client role&nbsp;&raquo;&nbsp;Edit");
		    }
		}
	}
	else
	   UI::Redirect("client_roles_view.php");
		
	$reflect = new ReflectionClass("ROLE_ALIAS");
	$display["aliases"] = array_values($reflect->getConstants());
	
	if ($_POST)
	{	    
		$display = array_merge($display, $_POST);
		
		$Validator = new Validator();
	    
		if ($post_default_minLA)
	    	if (!$Validator->IsNumeric($post_default_minLA) || $post_default_minLA < 1)
	       		$err[] = _("Invalid value for minimum LA");
	    
	    if ($post_default_maxLA)
	    	if (!$Validator->IsNumeric($post_default_maxLA) || $post_default_maxLA > 99)
	       		$err[] = _("Invalid value for maximum LA");
	       
		if ($req_task == 'share')
	    {
	    	if (!$post_no_keys_on_ami || (!$post_make_role_public && !$display['is_role_public']))
	    		UI::Redirect("client_roles_view.php");
	    }
	    
		if ($post_name != $info['name'] && $req_task == 'share')
		{
			if (!preg_match("/^[A-Za-z0-9-]+$/", $post_name))
            	$err[] = _("Allowed chars for role name is [A-Za-z0-9-]");
            	
            $chk = $db->GetOne("SELECT id FROM ami_roles WHERE name=? AND iscompleted != '2' AND ami_id != ?", 
            	array($post_name, $info['ami_id'])
            );
            if ($chk)
            	$err[] = _("Name is already used by an existing role. Please choose another name.");
		}
	    
	    if (count($err) == 0)
	    {    		
	        $db->BeginTrans();	    	
	        $info = $db->GetRow("SELECT * FROM ami_roles WHERE id=?", array($req_id));
	        
	        $HTMLPurifier_Config = HTMLPurifier_Config::createDefault();
		    $HTMLPurifier_Config->set('HTML', 'Allowed', 'a[href|title],b,i,p,div,span');
		    $HTMLPurifier_Config->set('Cache', 'DefinitionImpl', null);	    
			$HTMLPurifier_Config->set('Core', 'CollectErrors', true);
			    	
			$purifier = new HTMLPurifier($HTMLPurifier_Config);
			$description = $purifier->purify($post_description);	        
			
			if ($post_name != $info['name'] && $req_task == 'share')
			{
				$db->Execute("UPDATE elastic_ips SET role_name=? WHERE role_name=? farmid IN (SELECT id FROM farms WHERE clientid=? AND region=?)",
                	array($post_name, $info['name'], $_SESSION['uid'], $info['region'])
                );
                
                $db->Execute("UPDATE zones SET role_name=? WHERE role_name=? AND farmid IN (SELECT id FROM farms WHERE clientid=? AND region=?)",
                	array($post_name, $info['name'], $_SESSION['uid'], $info['region'])
                );
                
                $db->Execute("UPDATE farm_instances SET role_name=? WHERE role_name=? AND farmid IN (SELECT id FROM farms WHERE clientid=? AND region=?)",
                	array($post_name, $info['name'], $_SESSION['uid'], $info['region'])
                );
                
                $db->Execute("UPDATE farm_ebs SET role_name=? WHERE role_name=? AND farmid IN (SELECT id FROM farms WHERE clientid=? AND region=?)",
					array($post_name, $info['name'], $_SESSION['uid'], $info['region'])
				);

				$db->Execute("UPDATE ebs_arrays SET role_name=? WHERE role_name=? AND farmid IN (SELECT id FROM farms WHERE clientid=? AND region=?)",
					array($post_name, $info['name'], $_SESSION['uid'], $info['region'])
				);
				
				$db->Execute("UPDATE vhosts SET role_name=? WHERE role_name=? AND farmid IN (SELECT id FROM farms WHERE clientid=? AND region=?)",
					array($post_name, $info['name'], $_SESSION['uid'], $info['region'])
				);
				
				$db->Execute("UPDATE ami_roles SET name=? WHERE id=?", 
	           		array($post_name, $req_id)
	            );
	            
	            $info['name'] = $post_name;
			}
						
	        try
	        {
	           // Add ne role to database
	           $db->Execute("UPDATE ami_roles SET default_minLA=?, default_maxLA=?, description=? WHERE id=?", 
	           		array($post_default_minLA, $post_default_maxLA, $description, $req_id)
	           );
	        
    	       $roleid = $req_id;
	        }
	        catch (Exception $e)
	        {
	            $db->RollbackTrans();
	        	throw new ApplicationException($e->getMessage(), E_ERROR);
	        }
	        
	        $okmsg = _("Role successfully updated");
		    		    
		    try
		    {
			    // Save role options
			    $options = json_decode($post_role_options_dataform, true);
			    $opt_names = array("''"); 
			    if (count($options) > 0)
			    {
			    	foreach ($options as $option)
			    	{
				    	if (!$option['name'] || !$option['type'])
				    		continue;
			    		
				    	if ($option['type'] == FORM_FIELD_TYPE::SELECT)
				    	{
				    		$option['defval'] = array();
				    		foreach ($option['options'] as $opt)
				    		{
				    			if ($opt[3])
				    				array_push($option['defval'], $opt[0]);
				    		}
				    		
				    		$option['defval'] = implode(",", $option['defval']);
				    	}
				    		
				    	$db->Execute("INSERT INTO role_options SET 
				    		name=?, type=?, isrequired=?, defval=?, allow_multiple_choice=?, options=?, ami_id=?, hash=?
				    		ON DUPLICATE KEY UPDATE type=?, isrequired=?, defval=?, allow_multiple_choice=?, options=?
				    	", array(
				    		// For insertion
				    		$option['name'],
				    		$option['type'],
				    		$option['required'],
				    		$option['defval'],
				    		(int)$option['allow_multiple_choise'],
				    		json_encode($option['options']),
				    		$info['ami_id'],
				    		preg_replace("/[^A-Za-z0-9]+/", "_", strtolower($option['name'])),
				    		
				    		// For update
				    		$option['type'],
				    		$option['required'],
				    		$option['defval'],
				    		(int)$option['allow_multiple_choise'],
				    		json_encode($option['options'])
				    	));
				    					    	
				    	array_push($opt_names, "'{$option['name']}'");
			    	}
			    }
			    
			    // Remove removed options from database
			    $db->Execute("DELETE FROM role_options WHERE ami_id=? AND name NOT IN (".implode(",", $opt_names).")", 
			    	array($info['ami_id'])
			    );
		    }
		    catch(Exception $e)
		    {
		    	$db->RollbackTrans();
		        throw new ApplicationException($e->getMessage(), E_ERROR);
		    }

		    if ($req_task == 'share')
	    	{
				if (!$Validator->IsNotEmpty($post_description))
	       			$err[] = _("Description required");
	    		else
	    		{
		    		if (!$display['is_role_public'])
					{
						// make role public
						try
						{
							$AmazonEC2Client->ModifyImageAttribute($info['ami_id'], 'add', array('group' => 'all'));
						}
						catch(Exception $e)
						{
							$db->RollbackTrans();
							$errmsg = $e->getMessage();	
						}
					}
					
					//
					// Load and save security-group settings
					//
	    			$client_security_groups = $AmazonEC2Client->DescribeSecurityGroups();
		        	if (!$client_security_groups)
						$err[] = _("Cannot describe security groups.");
		                
		        	$client_security_groups = $client_security_groups->securityGroupInfo->item;
		        	if ($client_security_groups instanceof stdClass)
		        		$client_security_groups = array($client_security_groups);  
		        
		        	$sec_group = "";
		        		
			        // Check security groups
			        foreach ($client_security_groups as $group)
			        {
			            // Group exist. No need to add new
			            if (strtolower($group->groupName) == strtolower(CONFIG::$SECGROUP_PREFIX."{$info['name']}"))
			        	    $group_info = $group;
			        }
					
			        if ($group_info->ipPermissions && $group_info->ipPermissions->item)
			        {
			        	if (!is_array($group_info->ipPermissions->item))
			        		$group_info->ipPermissions->item = array($group_info->ipPermissions->item);
			        }
			        
			        if ($group_info)
			        {
			        	foreach ($group_info->ipPermissions->item as $perm)
			        	{
			        		if ($perm->ipRanges->item->cidrIp)
			        		{
			        			$db->Execute("INSERT INTO security_rules SET roleid=?, rule=?",
			        				array($info['id'], "{$perm->ipProtocol}:{$perm->fromPort}:{$perm->toPort}:{$perm->ipRanges->item->cidrIp}")
			        			);
			        		}
			        	}
			        }
					
					if (!$errmsg && count($err) == 0)
					{
						$db->Execute("UPDATE ami_roles SET approval_state=?, roletype=? WHERE id=?",
							array(APPROVAL_STATE::PENDING, ROLE_TYPE::SHARED, $info['id'])
						);
						
						if (strlen($post_sharing_comments) > 0)
						{
							$db->Execute("INSERT INTO comments SET 
								clientid		= ?,
								object_owner	= ?,
								dtcreated		= NOW(),
								object_type		= ?,
								comment			= ?,
								objectid		= ?,
								isprivate		= '1'
							", array(
								$_SESSION['uid'],
								$_SESSION['uid'],
								COMMENTS_OBJECT_TYPE::ROLE,
								htmlspecialchars($post_sharing_comments),
								$info['id']
							));
						}
						
						//
						// Send mail to admins
						//		
						$cnt = (int)$db->GetOne("SELECT COUNT(*) FROM ami_roles WHERE iscompleted='1' AND roletype=? AND approval_state=? AND clientid != 0", array(ROLE_TYPE::SHARED, APPROVAL_STATE::PENDING));
						$lnk = "http://{$_SERVER['HTTP_HOST']}/client_roles_view.php?type=SHARED&approval_state=Pending";
						
						$emails = explode("\n", CONFIG::$TEAM_EMAILS);
						if (count($emails) > 0)
						{
							foreach ($emails as $email)
							{
								$email = trim($email);
								
								$Mailer->ClearAddresses();
								$res = $Mailer->Send("emails/contributed_ami.eml", 
									array("Client" => $Client, "role" => $info, "comments" => $post_sharing_comments, "count" => $cnt, "link" => $lnk), 
									$email, 
									""
								);
								
								$Logger->info("Sending 'emails/contributed_ami.eml' email to '{$email}'. Result: {$res}");
								if (!$res)
									$Logger->error($Mailer->ErrorInfo);
							}
						}
					}
	    		}
	    	}
	    	
	    	if (!$errmsg && count($err) == 0)
	    	{
			    // Commit transaction and redirect to shared roles view page
			    $db->CommitTrans();
			    UI::Redirect("client_roles_view.php");
	    	}
	    }
	}
		
	$display["selected_tab"] = "general";
	
	$display["tabs_list"] = array(
		"general"  => _("Role information"),
		"options"  => _("Options")
	);
	
	require("src/append.inc.php"); 
?>