<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] != 0)
	   CoreUtils::Redirect("index.php");
	
	if (!$req_ami_id)
	{
	    $msgerr = "Please select AMI";
	    CoreUtils::Redirect("shared_roles.php");
	}
	
	$display["title"] = "Shared roles&nbsp;&raquo;&nbsp;Edit";
	
	if ($_POST)
	{	    
	    $Validator = new Validator();
	    
	    if (!$Validator->IsAlphaNumeric($post_name))
	       $err[] = "Role name required";
	       
	    if (!$Validator->IsNumeric($post_default_minLA) || $post_default_minLA < 1)
	       $err[] = "Invalid value for minimum LA";
	    
	    if (!$Validator->IsNumeric($post_default_maxLA) || $post_default_maxLA > 99)
	       $err[] = "Invalid value for maximum LA";
	          
	    $info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=? AND roletype='SHARED'", $post_ami_id);
	    if (!$info)
	    {
	        try
	        {
	           // Add ne role to database
	           $db->Execute("INSERT INTO ami_roles SET ami_id=?, roletype='SHARED', clientid='0', name=?, default_minLA=?, default_maxLA=?, alias=?", array($post_ami_id, $post_name, $post_default_minLA, $post_default_maxLA, $post_name));
	        
    	       $roleid = $db->Insert_ID();
    	        
    	       // Add security rules
    	       foreach ($post_rules as $rule)
                    $db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($roleid, $rule));
	            
	        }
	        catch (Exception $e)
	        {
	            Core::RaiseError($e->getMessage(), E_ERROR);
	        }
            
	        $okmsg = "Role successfully assigned to AMI";
	        CoreUtils::Redirect("shared_roles.php");
	    }
	    else 
	    {
	        try
	        {
	           // Add ne role to database
	           $db->Execute("UPDATE ami_roles SET name=?, default_minLA=?, default_maxLA=?, alias=? WHERE ami_id=?", array($post_name, $post_default_minLA, $post_default_maxLA, $post_name, $post_ami_id));
	        
    	       $roleid = $db->Insert_ID();
    	       
    	       // Add security rules
    	       $roleid = $db->GetOne("SELECT id FROM ami_roles WHERE ami_id=?", $post_ami_id);
    	       $db->Execute("DELETE FROM security_rules WHERE roleid=?", $roleid);
    	       foreach ($post_rules as $rule)
                    $db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($roleid, $rule));
	            
	        }
	        catch (Exception $e)
	        {
	            Core::RaiseError($e->getMessage(), E_ERROR);
	        }
            
	        $okmsg = "Role successfully updated";
	        CoreUtils::Redirect("shared_roles.php");
	    }
	}
	
	$display["ami_id"] = $req_ami_id;
	
	$info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=? AND roletype='SHARED'", $req_ami_id);
	if ($info)
	{
	    $display["name"] = $info["name"];
	    $display["default_minLA"] = $info["default_minLA"];
	    $display["default_maxLA"] = $info["default_maxLA"];
	    
	    $rules = $db->GetAll("SELECT * FROM security_rules WHERE roleid='{$info['id']}'");
	    $display["rules"] = array();
	    foreach ($rules as $rule)
	    {
	        $chunks = explode(":", $rule["rule"]);
	        $display["rules"][] = array(   "rule" => $rule["rule"], 
                	                       "id" => $rule["id"], 
                	                       "protocol" => $chunks[0],
                	                       "portfrom" => $chunks[1],
                	                       "portto" => $chunks[2],
                	                       "ipranges" => $chunks[3]
                	                   );
	    }
	}
	
	require("src/append.inc.php"); 
?>