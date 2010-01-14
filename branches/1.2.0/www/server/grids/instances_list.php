<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		if ($req_farmid)
		{
			if ($_SESSION["uid"] != 0)
		        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION["uid"]));
		    else
		        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
		        
			if (!$farminfo)
			    throw new Exception(_("Farm not found in database"));
			
			$clientid = $farminfo['clientid'];
			
			$region = $farminfo['region'];
		}
		else
		{
			if ($_SESSION["uid"] == 0)
				throw new Exception(_("Requested page cannot be viewed from admin account"));
			else
				$clientid = $_SESSION['uid'];
				
			$region = $_SESSION['aws_region'];
		}
		
		// Load Client Object
	    $Client = Client::Load($clientid);
	    
	    $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region)); 
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
	    
		Core::Load("NET/SNMP");
		$SNMP = new SNMP();
			
		// Rows
		$aws_response = $AmazonEC2Client->DescribeInstances();
			
		$rowz = $aws_response->reservationSet->item;
			
		if ($rowz instanceof stdClass)
			$rowz = array($rowz);
			
		$rowz1 = array();
		// Custom properties
		foreach ($rowz as $pk=>$pv)
		{
			if ($req_query && (!stristr($rowz[$pk]->instancesSet->item->instanceId, $req_query) && 
				!stristr($rowz[$pk]->instancesSet->item->privateIpAddress, $req_query) && 
				!stristr($rowz[$pk]->instancesSet->item->ipAddress, $req_query)))
				continue;
			
			// Groupslist
			$gl = array();
			
			if (is_array($rowz[$pk]->groupSet->item))
			{
				foreach ($rowz[$pk]->groupSet->item as $g)
				{
					$gl[] = $g->groupId;
				}
				$rowz[$pk]->groupsList = implode($gl, ", ");
			}
			else
				$rowz[$pk]->groupsList = $rowz[$pk]->groupSet->item->groupId;
			
				
			$iinfo = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id='{$rowz[$pk]->instancesSet->item->instanceId}'");
			
			if ($iinfo)
			{
				if (isset($req_farm_roleid) && $iinfo['farm_roleid'] != $req_farm_roleid)
					continue;
				
				$alias = $db->GetOne("SELECT alias FROM roles WHERE ami_id='{$iinfo['ami_id']}'");
				
				if ($alias == ROLE_ALIAS::MYSQL && $iinfo['state'] == INSTANCE_STATE::RUNNING)
					$prefix = $iinfo['isdbmaster'] ? _(" (master)") : _(" (slave)");
				else
					$prefix = "";
				
			    $rowz[$pk]->Role = $iinfo["role_name"].$prefix;
			    $rowz[$pk]->Alias = $alias;
			    
			    $fname = $db->GetOne("SELECT name FROM farms WHERE id=?", array($iinfo['farmid']));
			    
			    $rowz[$pk]->Farm = $fname;
			    			    
			    $eip = $db->GetOne("SELECT id FROM elastic_ips WHERE ipaddress=? AND farmid=?",
			    	array($rowz[$pk]->instancesSet->item->ipAddress, $iinfo['farmid'])
			    );
			    $rowz[$pk]->IsElastic = ($iinfo['custom_elastic_ip'] || $eip) ? 1 : 0;
			    
			    $rowz[$pk]->IsActive = $iinfo["isactive"];
			    
			    $rowz[$pk]->IsRebootLaunched = $iinfo["isrebootlaunched"];
			    $rowz[$pk]->FarmID = $iinfo["farmid"];
			    
			    try
			    {
			    	$DBFarmRole = DBFarmRole::LoadByID($iinfo['farm_roleid']);
			    	
			    	$row->instancesSet->item->imageId = $DBFarmRole->AMIID;
			    	$rowz[$pk]->canUseCustomEIPs = ($DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_USE_ELASIC_IPS)) ? false : true;
			    }
			    catch(Exception $e)
			    {
			    	
			    }
			    
			    
			    $rowz[$pk]->customEIP = $iinfo['custom_elastic_ip'];
			    $rowz[$pk]->internalIP = $iinfo['internal_ip'];
			    $rowz[$pk]->InstanceIndex = $iinfo['index'];
			    
			    ///
			    ///
			    ///
			    if ($rowz[$pk]->instancesSet->item->ipAddress)
			    {
		            $community = $db->GetOne("SELECT hash FROM farms WHERE id=(SELECT farmid FROM farm_instances WHERE instance_id='{$rowz[$pk]->instancesSet->item->instanceId}')");
		            
		            $SNMP->Connect($rowz[$pk]->instancesSet->item->ipAddress, null, $community, null, null, true);
		            $res = $SNMP->Get(".1.3.6.1.4.1.2021.10.1.3.3");
		            if (!$res)
		                $rowz[$pk]->LA = _("Unknown");
		            else 
		                $rowz[$pk]->LA = number_format((float)$res, 2);
			    }
			    else 
			       $rowz[$pk]->LA = _("Unknown");
			    ///
			    
			    $rowz[$pk]->instancesSet->item->imageId = $iinfo['ami_id'];
			}
			else
			{
				if ($req_hide_non_scalr == 'true')
					continue;
			}
			
		    if ($rowz[$pk]->instancesSet->item->launchTime)
		    {
		        $ltime = strtotime($rowz[$pk]->instancesSet->item->launchTime);
		        
		        $uptime = time()-$ltime;
		        $uptime_days = floor($uptime/86400);
	
		        $uptime_h = floor($uptime % 86400 / 3600);
		        if ($uptime_h < 10)
		        	$uptime_h = "0{$uptime_h}";
		        
		        $uptime_m = floor($uptime % 86400 % 3600 / 60);
		        if ($uptime_m < 10)
		        	$uptime_m = "0{$uptime_m}";	
		        	
		        $pr = $uptime_days > 1 ? "s" : "";
		        $rowz[$pk]->Uptime = "{$uptime_days} day{$pr}, {$uptime_h}:{$uptime_m}";
		    }
		    
		   		       
			$doadd = true;
			if (isset($get_state))
			{
				if ($iinfo["state"] != $get_state)	
					$doadd = false; 
			}
			
			if (isset($get_iid))
			{
	    		if ($get_iid && $rowz[$pk]->instancesSet->item->instanceId != $get_iid)
	                $doadd = false;
			}
			
			if ($req_hide_terminated == 'true' && $rowz[$pk]->instancesSet->item->instanceState->name == 'terminated')
				continue;
			
			
			if ($rowz[$pk]->instancesSet->item->instanceState->name == 'running' ||
				$rowz[$pk]->instancesSet->item->instanceState->name == 'pending')
				{
					if ($iinfo["state"])
						$rowz[$pk]->State = $iinfo["state"];
				}
				else
					$rowz[$pk]->State = ucfirst($rowz[$pk]->instancesSet->item->instanceState->name);
			
			if ($get_farmid)
			{
			    if (!$db->GetOne("SELECT id FROM farm_instances WHERE farmid=? AND instance_id=?", array($get_farmid, $rowz[$pk]->instancesSet->item->instanceId)))
			      $doadd = false;
			}
			
			if ($doadd)
			{
				if ($iinfo['isdbmaster'] != 1)
					$rowz1[] = $rowz[$pk];
				else
					array_unshift($rowz1, $rowz[$pk]);
			}
		}
		
		$response["total"] = count($rowz1);
		
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		
		$rowz = (count($rowz1) > $limit) ? array_slice($rowz1, $start, $limit) : $rowz1;
		
		$response["data"] = array();
		
		foreach ($rowz as $row)
		{
			
			$response["data"][] = array(
				"role"			=> ($row->Role) ? $row->Role : "Non-Scalr instance",
				"farm"			=> ($row->Farm) ? $row->Farm : "Non-Scalr instance",
				"alias"			=> ($row->Alias) ? $row->Alias : "",
				"uptime"		=> $row->Uptime,
				"public_ip"		=> ($row->instancesSet->item->ipAddress) ? $row->instancesSet->item->ipAddress : "Unknown",
				"private_ip"	=> ($row->instancesSet->item->privateIpAddress) ? $row->instancesSet->item->privateIpAddress : "Unknown", 
				"is_elastic"	=> ($row->IsElastic) ? $row->IsElastic : "",
				"is_active"		=> ($row->IsActive) ? $row->IsActive : "",
				"is_rebooting"	=> ($row->IsRebootLaunched) ? $row->IsRebootLaunched : "",
				"can_use_ceip"	=> ($row->canUseCustomEIPs) ? $row->canUseCustomEIPs : "",
				"custom_eip"	=> ($row->customEIP) ? $row->customEIP : "",
				"instance_index"=> ($row->InstanceIndex) ? $row->InstanceIndex : 0,
				"LA"			=> $row->LA,
				"state"			=> ($row->State) ? $row->State : ucfirst($row->instancesSet->item->instanceState->name),
				"instance_id"	=> $row->instancesSet->item->instanceId,
				"ami_id"		=> $row->instancesSet->item->imageId,
				"type"			=> $row->instancesSet->item->instanceType,
				"avail_zone"	=> $row->instancesSet->item->placement->availabilityZone,
				"id"			=> $row->instancesSet->item->instanceId,
				"farmid"		=> $row->FarmID,
				"region"		=> $region 
			);
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>