<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	
	if ($req_ami_id)
	    $display["title"] = _("Shared roles&nbsp;&raquo;&nbsp;Edit");
	else
		$display["title"] = _("Shared roles&nbsp;&raquo;&nbsp;Add new");
	
	$reflect = new ReflectionClass("ROLE_ALIAS");
	$display["aliases"] = array_values($reflect->getConstants());
	
	if ($_POST)
	{	    
		$display = array_merge($display, $_POST);
		
		$Validator = new Validator();
	    
	    if (!preg_match("/[A-Za-z0-9-]+/", $post_name))
            $err[] = _("Allowed chars for role name is [A-Za-z0-9-]");
	       
	    if (!$Validator->IsNumeric($post_default_minLA) || $post_default_minLA < 1)
	       $err[] = _("Invalid value for minimum LA");
	    
	    if (!$Validator->IsNumeric($post_default_maxLA) || $post_default_maxLA > 99)
	       $err[] = _("Invalid value for maximum LA");

	    if ($post_ami_id)
	    {
	    	$info = $db->GetRow("SELECT * FROM roles WHERE ami_id=? AND roletype=?", array($post_ami_id, ROLE_TYPE::SHARED));
	    	if (!$info)
	    	{
	    		$AmazonEC2 = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($post_region)); 
				$AmazonEC2->SetAuthKeys(
					APPPATH . "/etc/pk-".CONFIG::$AWS_KEYNAME.".pem", 
					APPPATH . "/etc/cert-".CONFIG::$AWS_KEYNAME.".pem", 
					true
				);
				
				// Generate DescribeImagesType object
				$DescribeImagesType = new DescribeImagesType();
				$DescribeImagesType->imagesSet->item = array("imageId" => $post_ami_id);
							
				// get information about shared AMIs
				try
				{
					$response = $AmazonEC2->DescribeImages($DescribeImagesType);
					
					if ($response && $response->imagesSet && $response->imagesSet->item)
						$post_arch = $response->imagesSet->item->architecture;
					else
					{
						$post_ami_id = false;
						$req_ami_id = false;
						$err[] = _("Cannot get information about AMI from Amazon");
						$display = array_merge($display, $_POST);
					}
				}
				catch(Exception $e)
				{
					$err[] = $e->getMessage();
					$display['ami_id'] = false;
				}
	    	}
	    }
	    else
	    {
	    	$err[] = _("AMI required");
	    	$display['ami_id'] = false;
	    }
	       
	    $isstable = ($post_isstable == 1) ? '1' : '0';
	    if (count($err) == 0)
	    {    		
		    if (!$info)
		    {
		        $db->BeginTrans();
		    	try
		        {
		           // Add ne role to database
		           $db->Execute("INSERT INTO roles SET 
		           		ami_id=?, 
		           		roletype=?, 
		           		clientid='0', 
		           		name=?, 
		           		default_minLA=?,
		           		default_maxLA=?, 
		           		alias=?, 
		           		architecture=?,
		           		isstable=?,
		           		description=?,
		           		region=?", 
		           array(
		           		$post_ami_id,
		           		ROLE_TYPE::SHARED, 
		           		$post_name, 
		           		$post_default_minLA, 
		           		$post_default_maxLA, 
		           		$post_alias, 
		           		$post_arch,
		           		$isstable,
		           		$post_description,
		           		$post_region
		           		)
		           	);
		        
	    	       $roleid = $db->Insert_ID();
	    	        
	    	       // Add security rules
	    	       foreach ($post_rules as $rule)
	                    $db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($roleid, $rule));
		        }
		        catch (Exception $e)
		        {
		            $db->RollbackTrans();
		        	throw new ApplicationException($e->getMessage(), E_ERROR);
		        }
	            
		        $okmsg = _("Role successfully assigned to AMI");
		        
		    }
		    else 
		    {
		        $db->BeginTrans();
		    	try
		        {
		           // Add ne role to database
		           $db->Execute("UPDATE roles SET isstable=?, description=?, name=?, default_minLA=?, default_maxLA=?, alias=? WHERE ami_id=?", 
		           		array($isstable, $post_description, $post_name, $post_default_minLA, $post_default_maxLA, $post_alias, $post_ami_id)
		           );
		        
	    	       // Add security rules
	    	       $roleid = $db->GetOne("SELECT id FROM roles WHERE ami_id=?", $post_ami_id);
	    	       $db->Execute("DELETE FROM security_rules WHERE roleid=?", $roleid);
	    	       foreach ($post_rules as $rule)
	                    $db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($roleid, $rule));
		        }
		        catch (Exception $e)
		        {
		            $db->RollbackTrans();
		        	throw new ApplicationException($e->getMessage(), E_ERROR);
		        }
		        
		        $okmsg = _("Role successfully updated");
		    }
		    
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
				    		$post_ami_id,
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
			    	array($post_ami_id)
			    );
		    }
		    catch(Exception $e)
		    {
		    	$db->RollbackTrans();
		        throw new ApplicationException($e->getMessage(), E_ERROR);
		    }
		
		    // Commit transaction and redirect to shared roles view page
		    $db->CommitTrans();
		    UI::Redirect("shared_roles.php");
	    }
	}
	
	if ($req_ami_id)
	{		
		$info = $db->GetRow("SELECT * FROM roles WHERE ami_id=? AND roletype=?", array($req_ami_id, ROLE_TYPE::SHARED));
		if ($info)
		{
		    $display["ami_id"] = $req_ami_id;
		    
			$display = array_merge($display, $info);
		    $display["arch"] = $info["architecture"];
		    
		    $rules = $db->GetAll("SELECT * FROM security_rules WHERE roleid=?", array($info['id']));
		    $display["rules"] = array();
		    foreach ($rules as $rule)
		    {
		        $chunks = explode(":", $rule["rule"]);
		        $display["rules"][] = array(   
		        							   "rule" => $rule["rule"], 
	                	                       "id" => $rule["id"], 
	                	                       "protocol" => $chunks[0],
	                	                       "portfrom" => $chunks[1],
	                	                       "portto" => $chunks[2],
	                	                       "ipranges" => $chunks[3]
	                	                   );
		    }
		    
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
		}
	}
	else
	{
		$display["rules"] = array(
			array(   
        	   	"rule" => "udp:161:162:0.0.0.0/0", 
				"id" => rand(0, 1000000), 
				"protocol" => "udp",
				"portfrom" => 161,
				"portto" => 162,
				"ipranges" => "0.0.0.0/0"
            ),
            array(   
        	   	"rule" => "tcp:22:22:0.0.0.0/0", 
				"id" => rand(0, 1000000), 
				"protocol" => "tcp",
				"portfrom" => 22,
				"portto" => 22,
				"ipranges" => "0.0.0.0/0"
            ),
            array(   
        	   	"rule" => "icmp:-1:-1:0.0.0.0/0", 
				"id" => rand(0, 1000000), 
				"protocol" => "icmp",
				"portfrom" => -1,
				"portto" => -1,
				"ipranges" => "0.0.0.0/0"
            )
		);
	}
	
	$display["selected_tab"] = "general";
	
	$display["tabs_list"] = array(
		"general"  => _("Role information"),
		"security" => _("Security settings"),
		"options"  => _("Options")
	);
	
	require("src/append.inc.php"); 
?>