<?php
	$enable_json = true;
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		include("../../src/prepend.inc.php");
	
		Core::Load("NET/SNMP");
		$SNMP = new SNMP();
		
		// Check cache
	    $plist_cache = CACHEPATH."/ajax_plist.{$req_iid}.cache";
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
			
		$response["total"] = count($processes);
		
		$response["data"] = array();
		
		// Rows
		foreach ($processes as $row)
		{
		    $response["data"][] = $row;
		}
		
		$content = json_encode($response);
		
		@file_put_contents($plist_cache, $content);
		
		print $content;
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
		print json_encode($response);
	}
	
	exit();
?>