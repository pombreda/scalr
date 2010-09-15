<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Create new role";
		
	if ($req_server_id)
    {
    	try
		{
    		$DBServer = DBServer::LoadByID($req_server_id);
    		
    		// Validate client and server
    		if ($_SESSION['uid'] != 0 && $DBServer->clientId != $_SESSION['uid'])
    			throw new Excpeiotn("No such server");
    			
    		$DBFarmRole = $DBServer->GetFarmRoleObject();
    		
    		// Show warning for master rebundle
    		if ($DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER))
    		{
    			if ($DBFarmRole->GetRunningInstancesCount() == 1)
    				$display["show_dbmaster_warning"] = true;
    		}
    		
    		if ($DBFarmRole->GetRoleAlias() == ROLE_ALIAS::MYSQL)
            	$display["warnmsg"] = _("You are about to synchronize MySQL instance. The bundle will not include MySQL data. <a href='farm_mysql_info.php?farmid={$DBServer->farmId}'>Click here if you wish to bundle and save MySQL data</a>.");
            	
            //Check for already running bundle on selected instance
            $chk = $db->GetOne("SELECT id FROM bundle_tasks WHERE server_id=? AND status NOT IN ('success', 'failed')", 
            	array($DBServer->serverId)
            );
            
            if ($chk)
            {
            	$errmsg = sprintf(_("This server is already synchonizing. <a href='bundle_tasks.php?id=%s'>Check status</a>."), $chk);
                UI::Redirect("bundle_tasks.php");
            }
            
            if (!$DBServer->IsSupported("0.2-112"))
            {
            	$errmsg = sprintf(_("You cannot create snapshot from selected server because scalr-ami-scripts package on it is too old."));
                UI::Redirect("bundle_tasks.php");
            }
            
            //Check is role already synchronizing...
            $chk = $db->GetOne("SELECT server_id FROM bundle_tasks WHERE prototype_role_id=? AND status NOT IN ('success', 'failed')", array(
            	$DBServer->roleId
            ));
            if ($chk && $chk != $DBServer->serverId)
            {
            	try
            	{
            		$bDBServer = DBServer::LoadByID($chk);
	            	if ($bDBServer->farmId == $DBServer->farmId)
	            	{
	            		$errmsg = sprintf(_("This role is already synchonizing. <a href='bundle_tasks.php?id=%s'>Check status</a>."), $chk);
	                	UI::Redirect("bundle_tasks.php");
	            	}
            	}
            	catch(Exception $e) {}
            }
            
            if (!$req_replace_type && !$req_rolename)
            {
				$display['rolename'] = BundleTask::GenerateRoleName($DBServer->GetFarmRoleObject(), $DBServer);
            }
            else
            {
            	if (strlen($req_rolename) < 3)
            		$err[] = _("Role name should be greater than 3 chars");
            	
            	if (!preg_match("/^[A-Za-z0-9-]+$/si", $req_rolename))
            		$err[] = _("Role name is incorrect");
            	
            	$roleinfo = $db->GetRow("SELECT * FROM roles WHERE name=? AND (clientid=? OR clientid='0')", array($req_rolename, $DBServer->clientId));
            	if ($req_replace_type != SERVER_REPLACEMENT_TYPE::REPLACE_ALL)
            	{
            		if ($roleinfo)
	            		$err[] = _("Specified role name is already used by another role. You can use this role name only if you will replace old on on ALL your farms.");
            	}
            	else
            	{
            		//ADD CONFIRMATION:
            		if ($roleinfo && $roleinfo['clientid'] == 0)
            			$err[] = _("Selected role name is reserved and cannot be used for custom role");
            	}
            		
            	if (!$err)
            	{
            		try
            		{
            			$ServerSnapshotCreateInfo = new ServerSnapshotCreateInfo($DBServer, $req_rolename, $req_replace_type, false, $req_description);
            			$BundleTask = BundleTask::Create($ServerSnapshotCreateInfo);
            			
            			$okmsg = _("Bundle task successfully added");
            			UI::Redirect("bundle_tasks.php?id={$BundleTask->id}");
            		}
            		catch(Exception $e)
            		{
            			$err[] = $e->getMessage();
            		}
            	}
            }
            
            $DBServer->roleName = $DBServer->GetFarmRoleObject()->GetRoleName();
            $DBServer->farmName = $DBServer->GetFarmObject()->Name;
            $display["DBServer"] = $DBServer;
		}
		catch (Exception $e)
		{
			$err[] = $e->getMessage();
		}
    }
    else
    {
    	UI::Redirect("servers_view.php");
    }
    
	$display["form_action"] = $_SERVER['PHP_SELF'];
	
	require("src/append.inc.php"); 
?>