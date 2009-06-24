<?
    require("../src/prepend.inc.php");
    
    switch($_REQUEST["_cmd"])
    {            
    	case "purchaseReservedOffering":
    
	    	try
	    	{
    			$Client = Client::Load($_SESSION['uid']);
	    		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($req_region));
				$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
			
				$response = $AmazonEC2Client->PurchaseReservedInstancesOffering($req_offeringID);
				exit();
	    	}
	    	catch(Exception $e)
	    	{
	    		die(sprintf(_("Cannot purchase reserved instances offering: %s"), $e->getMessage()));
	    	}
    		
    		break;
    	
    	case "get_script_args":
    		
    		$scriptid = (int)$req_scriptid;
    		
    		$dbversions = $db->GetAll("SELECT * FROM script_revisions WHERE scriptid=? AND approval_state=? ORDER BY revision DESC", 
	        	array($scriptid, APPROVAL_STATE::APPROVED)
	        );
    		
    		$versions = array();
	        foreach ($dbversions as $version)
	        {
	        	preg_match_all("/\%([^\%\s]+)\%/si", $version["script"], $matches);
	        	$vars = $matches[1];
			    $data = array();
			    foreach ($vars as $var)
			    {
			    	if (!in_array($var, CONFIG::$SCRIPT_BUILTIN_VARIABLES))
			    		$data[$var] = ucwords(str_replace("_", " ", $var));
			    }
			    $data = json_encode($data);
	        	
	        	$versions[] = array("revision" => $version['revision'], "fields" => $data);
	        }
    		
	        print json_encode($versions);
	        exit();
	        
    		break;
    	
    	case "check_role_name":
    		
    		$role_name = $req_name;
    		$ami_id = $req_ami_id;
    		
    		if (!preg_match("/^[A-Za-z0-9-]+$/", $role_name))
            	die(_("Allowed chars for role name is [A-Za-z0-9-]"));
    		
            $role_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=? AND clientid=? AND roletype=?",
            	array($ami_id, $_SESSION['uid'], ROLE_TYPE::CUSTOM)
            );
            if (!$role_info)
            	die("REDIRECT");
            	
            if ($role_info['name'] == $role_name)
            	die("ok");
            	
            $chk = $db->GetOne("SELECT id FROM ami_roles WHERE name=? AND iscompleted != '2' AND ami_id != ? AND region=?", 
            	array($role_name, $ami_id, $role_info['region'])
            );
            if (!$chk)
            	die("ok");
            else
            	die(_("Name is already used by an existing role. Please choose another name."));
            	
    		break;
    	
    	case "get_script_props":
    		
    		$script_id = (int)$req_id;
    		$version = (int)$req_version;
    		
    		$script_info = $db->GetRow("SELECT * FROM scripts WHERE id=?", 
    			array($script_id)
    		);
    		
    		$script_info['revision'] = $version;
    		$script_info['script'] = $db->GetOne("SELECT script FROM script_revisions WHERE scriptid=? AND revision=?",
    			array($script_id, $version)
    		);
    		
    		if ($_SESSION['uid'] != 0)
    		{
    			if ($script_info['origin'] != SCRIPT_ORIGIN_TYPE::SHARED && $script_info['clientid'] != $_SESSION['uid'])
    				die();
    		}
    		
    		print json_encode($script_info);
    		exit();
    		
    		break;
    	
    	case "get_role_params":
    		
    		$farmid = (int)$req_farmid;
    		$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($req_ami_id));
    		if ($ami_info['clientid'] != 0 && $ami_info['clientid'] != $_SESSION['uid'] && $_SESSION['uid'] != 0)
    			die(_("There are no parameters for this role"));
    		
    		$params = $db->GetAll("SELECT * FROM role_options WHERE ami_id=?", array($req_ami_id));
    		if (count($params) > 0)
    		{
    			$DataForm = new DataForm();
    			foreach ($params as $param)
    			{
    				// Prepare options array 
    				if ($param['options'])
    				{
	    				$options = json_decode($param['options'], true);
	    				$fopts = array();
	    				foreach ($options as $option)
	    					$fopts[$option[0]] = $option[1];
    				}
					
    				// Get field value
    				$value = $db->GetOne("SELECT value FROM farm_role_options WHERE farmid=? AND ami_id=? AND name=?",
    					array($farmid, $req_ami_id, $param['name'])
    				);
    				if ($value === false)
    					$value = $param['defval'];
    				
    				$field = new DataFormField(
    					$param['name'],
    					$param['type'],
    					$param['name'], 
    					$param['isrequired'], 
    					$fopts, 
    					$param['defval'], 
    					$value,
    					null,
    					$param['allow_multiple_choice']
    				);
    				
    				$DataForm->AppendField($field);
    			}
    			
    			$fields = $DataForm->ListFields();
    			
    			if (count($fields) != 0)
    			{
    				$Smarty->assign(array("DataForm" => $DataForm, "elem_id"=> "role_params", "field_prefix" => "", "field_suffix" => ""));
    				print $Smarty->fetch("inc/dynamicform.tpl");
    			}
    			else
    				die(_("There are no parameters for this role"));
    		}
    		else
    			die(_("There are no parameters for this role"));
    		
    		exit();
    		
    		break;
    	
    	case "get_script_template_source":
    		
    		$id = (int)$req_scriptid;
    		$version = $req_version;
    		
			$templateinfo = $db->GetRow("SELECT * FROM scripts WHERE id=?", array($id));
    		if ($_SESSION['uid'] != 0)
    		{
    			if ($templateinfo['origin'] == SCRIPT_ORIGIN_TYPE::CUSTOM && $templateinfo['clientid'] != $_SESSION['uid'])
    				die(_('There is no source avaiable for selected script'));
    		}
    		
    		$sql = "SELECT * FROM script_revisions WHERE scriptid='{$id}'";
    		
    		if ($version == "latest")
    			$sql .= " AND revision=(SELECT MAX(revision) FROM script_revisions WHERE scriptid='{$id}' AND approval_state='".APPROVAL_STATE::APPROVED."')";
    		else
    		{
    			$version = (int)$version;
    			$sql .= " AND revision='{$version}'";
    		}
    		
    		$script = $db->GetRow($sql);
    		
    		if ($templateinfo['origin'] == SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED)
    		{
    			if ($templateinfo['clientid'] != $_SESSION['uid'] && $script['approval_state'] != APPROVAL_STATE::APPROVED)
    				die(_('There is no source avaiable for selected script'));
    		}
    		
    		if ($script)
    		{
    			print $script['script'];
    		}
    		else
    			print _('There is no source avaiable for selected script');
    			
    		exit();
    		
    		break;
    	    		    		    	
    	case "get_instance_info":
            
        	Core::Load("NET/SNMP");
			$SNMP = new SNMP();
        	
            $instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($get_iid));
            if (!$instanceinfo)
            	die(_("Instance not found in database."));
			
            $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($instanceinfo['farmid']));
            if (!$farminfo || ($_SESSION['uid'] != 0 && $farminfo['clientid'] != $_SESSION['uid']))
            	die(_("Instance not found in database."));

            $Client = Client::Load($farminfo['clientid']);

	    	try
	    	{
    			$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region']));
				$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
			
    			$response = $AmazonEC2Client->DescribeInstances($get_iid);
    			$instanceset = $response->reservationSet->item->instancesSet;
    			$instanceinfo['type'] = $instanceset->item->instanceType;
    			$instanceinfo['placement'] = $instanceset->item->placement->availabilityZone;
    			$instanceinfo['launchtime'] = date("Y-m-d H:i:s", strtotime($instanceset->item->launchTime));
	    	}
	    	catch(Exception $e)
	    	{
	    		die(sprintf(_("Cannot fetch instance information from EC2: %s"), $e->getMessage()));
	    	}
    		
	    	$instanceinfo['LA'] = _("Unknown");
	    	if ($instanceinfo['external_ip'])
	    	{
	    		$SNMP->Connect($instanceinfo['external_ip'], null, $farminfo['hash'], null, null, true);
	            $res = $SNMP->Get(".1.3.6.1.4.1.2021.10.1.3.3");
	            if ($res)
	                $instanceinfo['LA'] = number_format((float)$res, 2);
	    	}
    		
	    	$eip = $db->GetOne("SELECT id FROM elastic_ips WHERE ipaddress=? AND farmid=?",
		    	array($instanceinfo["external_ip"], $instanceinfo['farmid'])
		    );
		    $instanceinfo['IsElastic'] = ($instanceinfo['custom_elastic_ip'] || $eip) ? 1 : 0;
	    	
		    $instanceinfo['role_alias'] = $db->GetOne("SELECT alias FROM ami_roles WHERE ami_id=?", array($instanceinfo['ami_id']));
		    
            $Smarty->assign(array("i" => $instanceinfo));
            $Smarty->display("inc/popup_instanceinfo.tpl");
            	
            break;   
    }
    
    exit();
?>