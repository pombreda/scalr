<?
	require_once('src/prepend.inc.php');
    $display['load_extjs'] = true;
	
	if ($_SESSION["uid"] != 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION["uid"]));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
        
	if (!$farminfo)
	{
	    $errmsg = _("Farm not found");
	    UI::Redirect("farms_view.php");
	}
	
	if ($get_ami_id)
		$display['grid_query_string'] .= "&ami_id={$get_ami_id}";
	
	$display["farm_status"] = $farminfo["status"];
	
	// Post actions
	
	if ($req_task == "launch_new_instance" && $farminfo["status"] == FARM_STATUS::RUNNING)
	{
		$Client = Client::Load($farminfo["clientid"]);
				    
		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region'])); 
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		   
		try
		{
		
			$roleinfo = $db->GetRow("SELECT * FROM roles WHERE ami_id=?", array($req_ami_id));
			if ($roleinfo)
	        {
	        	$role = $roleinfo["name"];    
				$ami = $v;
	        		
				$DBFarmRole = DBFarmRole::Load($farminfo['id'], $req_ami_id);
				
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
	        	
	        	$res = Scalr::RunInstance($DBFarmRole, false, false, true);                        
	            if (!$res)
	            	$errmsg = _("Cannot run instance. See system log for details.");
				else
	            	$okmsg = _("Instance successfully launched");
			}
			else
				$errmsg = _("Role not found in database.");
			
		} catch(Exception $e)
		{
			$errmsg = $e->getMessage();
		}
		
    	UI::Redirect("roles_view.php?farmid={$req_farmid}");
	}
	
	$display["title"] = _("Farms > View roles");
	
	$display["farmid"] = $req_farmid;
	
	require_once ("src/append.inc.php");
?>