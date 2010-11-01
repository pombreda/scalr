<?
	require_once('src/prepend.inc.php');
    $display['load_extjs'] = true;
	
    try
    {
    	$DBFarm = DBFarm::LoadByID($req_farmid);
    	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($DBFarm->EnvID))
    		throw new Exception("Farm not found");
    }
    catch(Exception $e)
    {	
    	UI::Redirect("/farms_view.php");
    }
    
	
	if ($req_role_id)
		$display['grid_query_string'] .= "&role_id={$req_role_id}";
	
	if ($req_farm_roleid)
		$display['grid_query_string'] .= "&farm_roleid={$req_farm_roleid}";
	// Post actions
	
	if ($req_action == 'download_private_key')
	{
		try
		{
			$DBFarmRole = DBFarmRole::LoadByID($req_farm_roleid);
			$DBFarm = $DBFarmRole->GetFarmObject();
			
			$sshKey = Scalr_Model::init(Scalr_Model::SSH_KEY)->loadGlobalByFarmId(
				$DBFarm->ID, 
				$DBFarmRole->GetSetting(DBFarmRole::SETTING_CLOUD_LOCATION)
			);
			
			if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($DBFarm->EnvID))
				throw new Exception("Farm role not found");
		}
		catch(Exception $e)
		{
			UI::Redirect("/farm_roles_view.php?farmid={$DBFarm->ID}");
		}
		
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
	    header('Content-type: plain/text');
        header('Content-Disposition: attachment; filename="'.$DBFarm->Name.'-'.$DBFarmRole->GetRoleObject()->name.'.pem"');
        header('Content-Length: '.strlen($sshKey->getPrivate()));

        print $sshKey->getPrivate();
        exit();
	}
		
	if ($req_action == "launch_new_instance" && $DBFarm->Status == FARM_STATUS::RUNNING)
	{		
		try
		{
			$DBFarmRole = DBFarmRole::LoadByID($req_farm_roleid);
			$DBFarm = $DBFarmRole->GetFarmObject();
			
			if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($DBFarm->EnvID))
				throw new Exception("Farm role not found");
			
			$roleinfo = $db->GetRow("SELECT * FROM roles WHERE id=?", array($DBFarmRole->RoleID));
			if ($roleinfo)
	        {				
				$n = $DBFarmRole->GetPendingInstancesCount(); 
				if ($n > 0)
					throw new Exception("There are {$n} pending instances. You cannot launch new instances while you have pending ones.");
				
				$max_instances = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES);
				$min_instances = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES);
				
	        	if ($max_instances < $min_instances+1)
	        	{
	        		$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES, $max_instances+1);
	        		
	        		$warnmsg = sprintf(_("The number of running %s instances is equal to maximum instances setting for this role. Maximum Instances setting for role %s has been increased automatically"), 
	        			$roleinfo['name'], $roleinfo['name']
	        		);
	        	}
	
	        	$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES, $min_instances+1);
	        	
	        	$ServerCreateInfo = new ServerCreateInfo($DBFarmRole->Platform, $DBFarmRole);
                
				Scalr::LaunchServer($ServerCreateInfo);
                      
	            $okmsg = _("Instance successfully launched");
			}
			else
				$errmsg = _("Role not found in database.");
			
		} catch(Exception $e)
		{
			$errmsg = $e->getMessage();
		}
		
    	UI::Redirect("farm_roles_view.php?farmid={$req_farmid}");
	}
	
	$display["title"] = _("Farms > View roles");
	
	$display["farmid"] = $req_farmid;
	
	require_once ("src/append.inc.php");
?>