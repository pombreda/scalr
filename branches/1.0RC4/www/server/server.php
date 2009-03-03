<?
    require("../src/prepend.inc.php");
    
    switch($_GET["_cmd"])
    {            
    	case "get_script_args":
    		
    		$scriptid = (int)$req_scriptid;
    		
    		$dbversions = $db->GetAll("SELECT * FROM script_revisions WHERE scriptid=? AND approval_state=? ORDER BY revision ASC", 
	        	array($scriptid, APPROVAL_STATE::APPROVED)
	        );
    		
    		$versions = array();
	        foreach ($dbversions as $version)
	        {
	        	preg_match_all("/\%([^\%]+)\%/si", $version["script"], $matches);
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
            	
            $chk = $db->GetOne("SELECT id FROM ami_roles WHERE name=? AND iscompleted != '2' AND ami_id != ?", 
            	array($role_name, $ami_id)
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
    	
    	case "get_snapshots_list":

    		$AmazonEC2Client = new AmazonEC2($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);
    		
    		// Rows
			$response = $AmazonEC2Client->DescribeSnapshots();
		
			$rowz = $response->snapshotSet->item;
			
			if ($rowz instanceof stdClass)
				$rowz = array($rowz);
					
			foreach ($rowz as $pk=>$pv)
			{		
				$pv->startTime = date("Y-m-d H:i:s", strtotime($pv->startTime));
				$item = $pv;	
				
				$item->comment = $db->GetOne("SELECT comment FROM ebs_snaps_info WHERE snapid=?", array(
					$item->snapshotId
				));
				
				$item->progress = (int)preg_replace("/[^0-9]+/", "", $item->progress);
				
				$item->free = 100 - $item->progress;
				
				$item->bar_begin = ($item->progress == 0) ? "empty" : "filled";
		    	$item->bar_end = ($item->free != 0) ? "empty" : "filled";
		    	
		    	$item->used_percent_width = round(120/100*$item->progress, 2);
		    	$item->free_percent_width = round(120/100*$item->free, 2);
		
		    	if ($req_volumeid)
		    	{
		    		if ($req_volumeid == $item->volumeId)
		    		{
		    			$snaps[] = $item;
		    		}
		    		
		    		$display["snaps_header"] = sprintf(_("Snapshots for %s"), $item->volumeId); 
		    	}
		    	elseif (!$req_snapid || $req_snapid == $item->snapshotId)
		    	{
		    		$snaps[] = $item;
		    	}
			}
			
			$Smarty->assign(array("snaps" => $snaps, "error" => $error));
            $content = $Smarty->fetch("ajax_tables/snapshots_list.tpl");
			
            print $content;
            exit();
            
    		break;
    		
    	case "get_instance_process_list":
    		
    		Core::Load("NET/SNMP");
			$SNMP = new SNMP();
			
			// Check cache
		    $plist_cache = CACHEPATH."/ajax_plist.{$get_iid}.cache";
		    if (file_exists($plist_cache))
		    {
		        clearstatcache();
		        $time = filemtime($plist_cache);
		        
		        if ($time > time()-CONFIG::$AJAX_PROCESSLIST_CACHE_LIFETIME) //TODO: Move to config
		        {
		        	readfile($plist_cache);
		        	exit();
		        }
		    }
			
			try
			{
				$instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($get_iid));
	            if (!$instanceinfo)
	            	throw new Exception(_("Instance not found in database."));
				
	            $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($instanceinfo['farmid']));
	            if (!$farminfo || ($_SESSION['uid'] != 0 && $farminfo['clientid'] != $_SESSION['uid']))
	            	throw new Exception("Instance not found in database.");
	            	
	            $SNMP->Connect($instanceinfo['external_ip'], null, $farminfo['hash'], null, null, true);
	            	
	            $res = $SNMP->GetFullTree(".1.3.6.1.2.1.25.4.2");
		
				foreach ((array)$res as $k=>$v)
				{
					if (stristr($k, "HOST-RESOURCES-MIB::hrSWRunIndex"))
					{
						//
					}
					elseif (stristr($k, "HOST-RESOURCES-MIB::hrSWRunName"))
					{
						preg_match("/HOST-RESOURCES-MIB::hrSWRunName.([0-9]+)/si", $k, $matches);
						$processes[$matches[1]]["hrSWRunName"] = $v;
					}
					elseif (stristr($k, "HOST-RESOURCES-MIB::hrSWRunPath"))
					{
						preg_match("/HOST-RESOURCES-MIB::hrSWRunPath.([0-9]+)/si", $k, $matches);
						$processes[$matches[1]]["hrSWRunPath"] = $v;
					}
					elseif (stristr($k, "HOST-RESOURCES-MIB::hrSWRunParameters"))
					{
						preg_match("/HOST-RESOURCES-MIB::hrSWRunParameters.([0-9]+)/si", $k, $matches);
						$processes[$matches[1]]["hrSWRunParameters"] = trim($v);
					}
					elseif (stristr($k, "HOST-RESOURCES-MIB::hrSWRunType"))
					{
						preg_match("/HOST-RESOURCES-MIB::hrSWRunType.([0-9]+)/si", $k, $matches);
						
						switch(trim($v))
						{
							case 1:
									$processes[$matches[1]]["hrSWRunType"] = "unknown";
								break;
							case 2:
									$processes[$matches[1]]["hrSWRunType"] = "operatingSystem";
								break;
							case 3:
									$processes[$matches[1]]["hrSWRunType"] = "deviceDriver";
								break;
							case 4:
									$processes[$matches[1]]["hrSWRunType"] = "application";
								break;
						}
					}
					elseif (stristr($k, "HOST-RESOURCES-MIB::hrSWRunStatus"))
					{
						preg_match("/HOST-RESOURCES-MIB::hrSWRunStatus.([0-9]+)/si", $k, $matches);
						
						switch(trim($v))
						{
							case 1:
									$processes[$matches[1]]["hrSWRunStatus"] = "running";
								break;
							case 2:
									$processes[$matches[1]]["hrSWRunStatus"] = "runnable";
								break;
							case 3:
									$processes[$matches[1]]["hrSWRunStatus"] = "notRunnable";
								break;
							case 4:
									$processes[$matches[1]]["hrSWRunStatus"] = "invalid";
								break;
						}						
					}
				}   
			            
			    $res = $SNMP->GetFullTree(".1.3.6.1.2.1.25.5.1");
				foreach ((array)$res as $k=>$v)
				{
					if (stristr($k, "hrSWRunPerfCPU"))
					{
						preg_match("/HOST-RESOURCES-MIB::hrSWRunPerfCPU.([0-9]+)/si", $k, $matches);
						$processes[$matches[1]]["hrSWRunPerfCPU"] = trim($v);
					}
					elseif (stristr($k, "HOST-RESOURCES-MIB::hrSWRunPerfMem"))
					{
						preg_match("/HOST-RESOURCES-MIB::hrSWRunPerfMem.([0-9]+)/si", $k, $matches);
						$processes[$matches[1]]["hrSWRunPerfMem"] = Formater::Bytes2String(((int)trim($v))*1024);
					} 
				}    
			            
			    sort($processes);
			}
			catch(Exception $e)
			{
				$error = $e->getMessage();
			}
			
		    $Smarty->assign(array("processes" => $processes, "error" => $error));
            $content = $Smarty->fetch("ajax_tables/process_list.tpl");
		    
            @file_put_contents($plist_cache, $content);
            
            print $content;
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
    			$AmazonEC2Client = new AmazonEC2($Client->AWSPrivateKey, $Client->AWSCertificate);
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