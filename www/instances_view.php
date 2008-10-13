<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] != 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION["uid"]));
    else
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
        
	if (!$farminfo)
	{
	    $errmsg = "Farm not found";
	    UI::Redirect("farms_view.php");
	}
	
	if ($_SESSION['uid'] == 0)
    {
    	$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($farminfo['clientid']));
	
		// Decrypt client prvate key and certificate
    	$private_key = $Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
    	$certificate = $Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
    }
    else
    {
    	$private_key = $_SESSION["aws_private_key"];
    	$certificate = $_SESSION["aws_certificate"];
    }
	
    $AmazonEC2Client = new AmazonEC2($private_key, $certificate);
                        
	$display["title"] = "Instances&nbsp;&raquo;&nbsp;View";
	
	Core::Load("NET/SNMP");
	$SNMP = new SNMP();
	
	if ($req_action == "sshClient")
	{
		$ssh_host = $db->GetOne("SELECT external_ip FROM farm_instances WHERE instance_id=? AND farmid=?", array($req_instanceid, $farminfo["id"]));
		if ($ssh_host)
		{
			$Smarty->assign(array("i" => $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=? AND farmid=?", array($req_instanceid, $farminfo["id"])), "host" => $ssh_host, "key" => base64_encode($farminfo["private_key"])));
			$Smarty->display("ssh_applet.tpl");
			exit();
		}
	}
	
	if ($_POST && $post_actionsubmit)
	{
		$i = 0;
		if (is_array($post_actid))
		{
				
			if ($post_action == 'setactive')
			{
				foreach ($post_actid as $v)
				{
					$instanceinfo = $db->GetRow("SELECT * FROM farm_instances
						WHERE instance_id=? AND farmid=?",
						array($v, $farminfo["id"])
					);
					
					if ($instanceinfo)
					{
						$db->Execute("UPDATE farm_instances SET isactive='1' 
							WHERE id=?", 
							array($instanceinfo['id'])
						);
						
						$zones = $db->GetAll("SELECT * FROM zones WHERE farmid=?", array($instanceinfo['farmid']));
						
						$DNSZoneController = new DNSZoneControler();
						
						$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($instanceinfo['ami_id']));
						
						try
						{
							foreach ($zones as $zoneinfo)
							{
								$records = array();
								
								if ($instanceinfo["role_name"] == $zoneinfo["role_name"])
				    		    {
				    				$records[] = array("rtype" => "A", "ttl" => CONFIG::$DYNAMIC_A_REC_TTL, "rvalue" => $instanceinfo["external_ip"], "rkey" => "@", "issystem" => 1);
				    		    }
				    		    
				    		    if ($instanceinfo["isdbmaster"] == 1)
								{
									$records[] = array("rtype" => "A", "rkey" => "int-{$instanceinfo['role_name']}-master", "rvalue" => $instanceinfo["internal_ip"], "ttl" => 20, "issystem" => 1);
									$records[] = array("rtype" => "A", "rkey" => "ext-{$instanceinfo['role_name']}-master", "rvalue" => $instanceinfo["external_ip"], "ttl" => 20, "issystem" => 1);
								}

								if ($ami_info['alias'] == 'mysql' && $instanceinfo['role_name'] != 'mysql')
								{
									$records[] = array("rtype" => "A", "rkey" => "int-mysql", "rvalue" => $instanceinfo["internal_ip"], "ttl" => 20, "issystem" => 1);
									$records[] = array("rtype" => "A", "rkey" => "ext-mysql", "rvalue" => $instanceinfo["external_ip"], "ttl" => 20, "issystem" => 1);
									if ($instanceinfo["isdbmaster"] == 1)
									{
										$records[] = array("rtype" => "A", "rkey" => "int-mysql-master", "rvalue" => $instanceinfo["internal_ip"], "ttl" => 20, "issystem" => 1);
										$records[] = array("rtype" => "A", "rkey" => "ext-mysql-master", "rvalue" => $instanceinfo["external_ip"], "ttl" => 20, "issystem" => 1);
									}
								}
								
								$records[] = array("rtype" => "A", "rkey" => "int-{$instanceinfo['role_name']}", "rvalue" => $instanceinfo["internal_ip"], "ttl" => 20, "issystem" => 1);
								$records[] = array("rtype" => "A", "rkey" => "ext-{$instanceinfo['role_name']}", "rvalue" => $instanceinfo["external_ip"], "ttl" => 20, "issystem" => 1);
								
								foreach ($records as $k=>$v)
								{
									if ($v["rkey"] != '' && $v["rvalue"] != '')
										$db->Execute("REPLACE INTO records SET zoneid=?, `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?, `issystem`=?", array($zoneinfo["id"], $v["rtype"], $v["ttl"], $v["rpriority"], $v["rvalue"], $v["rkey"], $v["issystem"] ? 1 : 0));
								}
								
								$DNSZoneController->Update($zoneinfo["id"]);
							}
							
							$i++;
						}
						catch(Exception $e)
						{
							$err[] = $e->getMessage();
						}
					}
				}
				
				if (count($err) == 0)
					$okmsg = "{$i} instances succesfully marked as active";
			}
			elseif ($post_action == 'setinactive')
			{
				foreach ($post_actid as $v)
				{
					$instanceinfo = $db->GetRow("SELECT * FROM farm_instances
						WHERE instance_id=? AND farmid=?",
						array($v, $farminfo["id"])
					);
					
					if ($instanceinfo)
					{
						$db->Execute("UPDATE farm_instances SET isactive='0' 
							WHERE id=?", 
							array($instanceinfo['id'])
						);
						
						$zones = $db->GetAll("SELECT zoneid FROM records 
							WHERE (rvalue='{$instanceinfo['internal_ip']}' OR 
								rvalue='{$instanceinfo['external_ip']}') AND issystem='1' GROUP BY zoneid"
						);
						
						$DNSZoneController = new DNSZoneControler();
						
						try
						{
							foreach ($zones as $zoneid)
							{
								$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id=?", array($zoneid));
								
								// Add A records pointed to new active instance
								$db->Execute("DELETE FROM records WHERE 
									zoneid='{$zoneinfo['id']}' AND 
									(rvalue='{$instanceinfo['internal_ip']}' OR 
										rvalue='{$instanceinfo['external_ip']}'
									) AND issystem='1'"
								);
								
								$DNSZoneController->Update($zoneinfo["id"]);
							}
							
							$i++;
						}
						catch(Exception $e)
						{
							$err[] = $e->getMessage();
						}
					}
				}

				if (count($err) == 0)
					$okmsg = "{$i} instances succesfully marked as active";
			}
			else
			{
				try 
				{
					$instances = array();
					foreach ($post_actid as $v)
					{
						array_push($instances, $v);
						$i++;
					}
	
					// Do something
					if ($post_action == "terminate")
						$response = $AmazonEC2Client->TerminateInstances($instances);
					elseif ($post_action == "reboot")
						$response = $AmazonEC2Client->RebootInstances($instances);
						
					if ($response instanceof SoapFault)
					{
						$err[] = $response->faultstring;
					}
				}
				catch (Exception $e)
				{
					$err[] = $e->getMessage(); 
				}
					
				$okmsg = "{$i} instances succesfully " . ($post_action == "reboot" ? "going to reboot" : "terminated");
			}
		}	
		
		if ($okmsg)
			UI::Redirect("?farmid={$req_farmid}");
	}
	
	//Paging
	$paging = new Paging();
	$paging->ItemsOnPage = 20;

	// Rows
	$response = $AmazonEC2Client->DescribeInstances();
	
		
	$rowz = $response->reservationSet->item;
		
	if ($rowz instanceof stdClass)
		$rowz = array($rowz);
	
	//var_dump($rowz[0]->instancesSet->item->dnsName);
		
	// Custom properties
	foreach ($rowz as $pk=>$pv)
	{
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
			
		$alias = $db->GetOne("SELECT alias FROM ami_roles WHERE ami_id='{$iinfo['ami_id']}'");
		
		if ($alias == ROLE_ALIAS::MYSQL && $iinfo['state'] == INSTANCE_STATE::RUNNING)
			$prefix = $iinfo['isdbmaster'] ? " (master)" : " (slave)";
		else
			$prefix = "";
		
	    $rowz[$pk]->Role = $iinfo["role_name"].$prefix;
		
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
	    
	    	    
	    $rowz[$pk]->IP = $iinfo["external_ip"];
	    
	    $eip = $db->GetOne("SELECT id FROM elastic_ips WHERE ipaddress=? AND farmid=?",
	    	array($iinfo["external_ip"], $iinfo['farmid'])
	    );
	    $rowz[$pk]->IsElastic = ($iinfo['custom_elastic_ip'] || $eip) ? 1 : 0;
	    
	    $rowz[$pk]->IsActive = $iinfo["isactive"];
	    
	    $rowz[$pk]->IsRebootLaunched = $iinfo["isrebootlaunched"];
	    
	    $farm_ami_info = $db->GetRow("SELECT * From farm_amis WHERE ami_id=? AND farmid=?",
	    	array($rowz[$pk]->instancesSet->item->imageId, $get_farmid)
	    );
	    $rowz[$pk]->canUseCustomEIPs = ($farm_ami_info['use_elastic_ips']) ? false : true;
	    $rowz[$pk]->customEIP = $iinfo['custom_elastic_ip'];
	    
	    ///
	    ///
	    ///
	    if ($rowz[$pk]->IP)
	    {
            $community = $db->GetOne("SELECT hash FROM farms WHERE id=(SELECT farmid FROM farm_instances WHERE instance_id='{$rowz[$pk]->instancesSet->item->instanceId}')");
            
            $SNMP->Connect($rowz[$pk]->IP, null, $community, null, null, true);
            $res = $SNMP->Get(".1.3.6.1.4.1.2021.10.1.3.3");
            if (!$res)
                $rowz[$pk]->LA = "Unknown";
            else 
                $rowz[$pk]->LA = number_format((float)$res, 2);
	    }
	    else 
	       $rowz[$pk]->LA = "Unknown";
	    ///
	    
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
		
		if ($rowz[$pk]->instancesSet->item->instanceState->name == 'running' ||
			$rowz[$pk]->instancesSet->item->instanceState->name == 'pending')
			{
				if ($iinfo["state"])
					$rowz[$pk]->State = $iinfo["state"];
				else
					$rowz[$pk]->State = "Unknown";
			}
			else
				$rowz[$pk]->State = ucfirst($rowz[$pk]->instancesSet->item->instanceState->name);
		
		if (isset($get_farmid))
		{
		    if (!$db->GetOne("SELECT id FROM farm_instances WHERE farmid=? AND instance_id=?", array($get_farmid, $rowz[$pk]->instancesSet->item->instanceId)))
		      $doadd = false;
		}
		
		if ($doadd)
			$rowz1[] = $rowz[$pk];
	}
	
	$rowz = $rowz1;
	
	$paging->Total = count($rowz); 
	
	$paging->ParseHTML();
	
	$display["rows"] = (count($rowz) > CONFIG::$PAGING_ITEMS) ? array_slice($rowz, ($paging->PageNo-1) * CONFIG::$PAGING_ITEMS, CONFIG::$PAGING_ITEMS) : $rowz;
	
	$display["paging"] = $paging->GetHTML("inc/paging.tpl");
	
	$display["farmid"] = $farminfo["id"];
	
	$display["page_data_options"] = array(
		array("name" => "Terminate", "action" => "terminate"),
		array("name" => "Reboot", "action" => "reboot"),
		array("name" => "Create DNS records", "action" => "setactive"),
		array("name" => "Delete DNS records", "action" => "setinactive")
	);
	$display["page_data_options_add"] = false;
	
	require("src/append.inc.php"); 
	
?>