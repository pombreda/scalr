<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	
	if ($req_ami_id)
	{
	    $display["title"] = "Shared roles&nbsp;&raquo;&nbsp;Edit";
	}
	else
	{
		$display["title"] = "Shared roles&nbsp;&raquo;&nbsp;Add new";
	}
	
	$reflect = new ReflectionClass("ROLE_ALIAS");
	$display["aliases"] = array_values($reflect->getConstants());
	
	if ($_POST)
	{	    
	    $Validator = new Validator();
	    
	    if (!preg_match("/[A-Za-z0-9-]+/", $post_name))
            $err[] = "Allowed chars for role name is [A-Za-z0-9-]";
	       
	    if (!$Validator->IsNumeric($post_default_minLA) || $post_default_minLA < 1)
	       $err[] = "Invalid value for minimum LA";
	    
	    if (!$Validator->IsNumeric($post_default_maxLA) || $post_default_maxLA > 99)
	       $err[] = "Invalid value for maximum LA";

	    if ($post_ami_id)
	    {
	    	$info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=? AND roletype=?", array($post_ami_id, ROLE_TYPE::SHARED));
	    	if (!$info)
	    	{
	    		$AmazonEC2 = new AmazonEC2(
					APPPATH . "/etc/pk-".CONFIG::$AWS_KEYNAME.".pem", 
					APPPATH . "/etc/cert-".CONFIG::$AWS_KEYNAME.".pem", true
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
						$err[] = "Cannot get information about AMI from amazon";
						$display = array_merge($display, $_POST);
					}
				}
				catch(Exception $e)
				{
					$err[] = $e->getMessage();
				}
	    	}
	    }
	    else
	    {
	    	UI::Redirect("shared_roles.php");
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
		           $db->Execute("INSERT INTO ami_roles SET 
		           		ami_id=?, 
		           		roletype=?, 
		           		clientid='0', 
		           		name=?, 
		           		default_minLA=?,
		           		default_maxLA=?, 
		           		alias=?, 
		           		architecture=?,
		           		isstable=?,
		           		description=?", 
		           array(
		           		$post_ami_id,
		           		ROLE_TYPE::SHARED, 
		           		$post_name, 
		           		$post_default_minLA, 
		           		$post_default_maxLA, 
		           		$post_alias, 
		           		$post_arch,
		           		$isstable,
		           		$post_description)
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
	            
		        $db->CommitTrans();
		        
		        $okmsg = "Role successfully assigned to AMI";
		        UI::Redirect("shared_roles.php");
		    }
		    else 
		    {
		        $db->BeginTrans();
		    	try
		        {
		           // Add ne role to database
		           $db->Execute("UPDATE ami_roles SET isstable=?, description=?, name=?, default_minLA=?, default_maxLA=?, alias=? WHERE ami_id=?", 
		           		array($isstable, $post_description, $post_name, $post_default_minLA, $post_default_maxLA, $post_alias, $post_ami_id)
		           );
		        
	    	       $roleid = $db->Insert_ID();
	    	       
	    	       // Add security rules
	    	       $roleid = $db->GetOne("SELECT id FROM ami_roles WHERE ami_id=?", $post_ami_id);
	    	       $db->Execute("DELETE FROM security_rules WHERE roleid=?", $roleid);
	    	       foreach ($post_rules as $rule)
	                    $db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($roleid, $rule));

	                    
	                if ($info['name'] != $post_name)
	                {
	                	$db->Execute("UPDATE elastic_ips SET role_name=? WHERE role_name=?",
	                		array($post_name, $info['name'])
	                	);
	                	
	                	$db->Execute("UPDATE zones SET role_name=? WHERE role_name=?",
	                		array($post_name, $info['name'])
	                	);
	                	
	                	$db->Execute("UPDATE farm_instances SET role_name=? WHERE role_name=?",
	                		array($post_name, $info['name'])
	                	);
	                }
		        }
		        catch (Exception $e)
		        {
		            $db->RollbackTrans();
		        	throw new ApplicationException($e->getMessage(), E_ERROR);
		        }
	            
		        $db->CommitTrans();
		        
		        $okmsg = "Role successfully updated";
		        UI::Redirect("shared_roles.php");
		    }
	    }
	}
	
	if ($req_ami_id)
	{
		$display["ami_id"] = $req_ami_id;
		
		$info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=? AND roletype=?", array($req_ami_id, ROLE_TYPE::SHARED));
		if ($info)
		{
		    $display = array_merge($display, $info);
		    $display["arch"] = $info["architecture"];
		    
		    $rules = $db->GetAll("SELECT * FROM security_rules WHERE roleid='{$info['id']}'");
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
	
	require("src/append.inc.php"); 
?>