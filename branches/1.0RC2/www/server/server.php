<?
    require("../src/prepend.inc.php");
    
    switch($_GET["_cmd"])
    {            
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
	            	throw new Exception("Instance not found in database.");
				
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
            	die("Instance not found in database.");
			
            $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($instanceinfo['farmid']));
            if (!$farminfo || ($_SESSION['uid'] != 0 && $farminfo['clientid'] != $_SESSION['uid']))
            	die("Instance not found in database.");
            	
            $clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($farminfo['clientid']));
            	
			// Decrypt client prvate key and certificate
	    	$private_key = $Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
	    	$certificate = $Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
	
	    	try
	    	{
    			$AmazonEC2Client = new AmazonEC2($private_key, $certificate);
    			$response = $AmazonEC2Client->DescribeInstances($get_iid);
    			$instanceset = $response->reservationSet->item->instancesSet;
    			$instanceinfo['type'] = $instanceset->item->instanceType;
    			$instanceinfo['placement'] = $instanceset->item->placement->availabilityZone;
    			$instanceinfo['launchtime'] = date("Y-m-d H:i:s", strtotime($instanceset->item->launchTime));
	    	}
	    	catch(Exception $e)
	    	{
	    		die("Cannot fetch instance information from EC2: {$e->getMessage()}");
	    	}
    		
	    	$instanceinfo['LA'] = "Unknown";
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
	    	
            $Smarty->assign(array("i" => $instanceinfo));
            $Smarty->display("inc/popup_instanceinfo.tpl");
            	
            break;   
    }
    
    exit();
?>