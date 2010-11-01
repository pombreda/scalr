<? 
	require("src/prepend.inc.php"); 
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	
	if ($post_cancel)
        UI::Redirect("roles_view.php");
	
	if ($req_id)
	{
		$display["id"] = $req_id;
		
		try
		{
			$DBRole = DBRole::loadById($req_id);
			if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($DBRole->envId))
				throw new Exception("You have no access to selected role");
		}
		catch(Exception $e)
		{
			$errmsg = $e->getMessage();
			UI::Redirect("/roles_view.php");
		}
		
		$display['DBRole'] = $DBRole;


		$options = $db->GetAll("SELECT * FROM role_parameters WHERE role_id=?", array($info['id']));
	    
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
	    
	    if ($req_task == 'switch')
	    {
	    	$display["title"] = _("Role&nbsp;&raquo;&nbsp;Switch to new AMI");
	    	
	    	$template_name = 'switch_client_role.tpl';
	    }
		else
	    {
	    	$display["title"] = _("Role&nbsp;&raquo;&nbsp;Edit");
	    }
	}
	else
	   UI::Redirect("roles_view.php");

	//TODO:
	//$reflect = new ReflectionClass("ROLE_ALIAS");
	//$display["aliases"] = array_values($reflect->getConstants());
	
	if ($_POST)
	{	    
		$display = array_merge($display, $_POST);
		
		$Validator = new Validator();
	    
	    if (count($err) == 0)
	    {    		
	        $db->BeginTrans();
	        
	        $HTMLPurifier_Config = HTMLPurifier_Config::createDefault();
		    $HTMLPurifier_Config->set('HTML', 'Allowed', 'a[href|title],b,i,p,div,span');
		    $HTMLPurifier_Config->set('Cache', 'DefinitionImpl', null);	    
			$HTMLPurifier_Config->set('Core', 'CollectErrors', true);
			    	
			$purifier = new HTMLPurifier($HTMLPurifier_Config);
			$description = $purifier->purify($post_description);	        
					
	        try
	        {		        	
	           if ($post_alias)
	           {
		           // Add ne role to database
		           $db->Execute("UPDATE roles SET alias=?, description=?, default_ssh_port=? WHERE id=?", 
		           		array($post_alias, $description, $req_default_SSH_port, $req_id)
		           );
	           }
	           else
	           {
	           		// Add ne role to database
		           $db->Execute("UPDATE roles SET description=?, default_ssh_port=? WHERE id=?", 
		           		array($description, $req_default_SSH_port, $req_id)
		           );
	           }
	        
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
				    		
				    	$db->Execute("INSERT INTO role_parameters SET 
				    		name=?, type=?, isrequired=?, defval=?, allow_multiple_choice=?, options=?, role_id=?, hash=?
				    		ON DUPLICATE KEY UPDATE type=?, isrequired=?, defval=?, allow_multiple_choice=?, options=?
				    	", array(
				    		// For insertion
				    		$option['name'],
				    		$option['type'],
				    		$option['required'],
				    		$option['defval'],
				    		(int)$option['allow_multiple_choise'],
				    		json_encode($option['options']),
				    		$info['id'],
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
			    $db->Execute("DELETE FROM role_parameters WHERE role_id=? AND name NOT IN (".implode(",", $opt_names).")", 
			    	array($info['id'])
			    );
		    }
		    catch(Exception $e)
		    {
		    	$db->RollbackTrans();
		        throw new ApplicationException($e->getMessage(), E_ERROR);
		    }
        }
	    	
    	if (!$errmsg && count($err) == 0)
    	{
		    // Commit transaction and redirect to shared roles view page
		    $db->CommitTrans();
		    UI::Redirect("roles_view.php");
    	}
	}
		
	$display["selected_tab"] = "general";
	
	$display["tabs_list"] = array(
		"general"  => _("Role information"),
		"options"  => _("Options")
	);
	
	require("src/append.inc.php"); 
?>