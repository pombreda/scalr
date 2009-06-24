<? 
	require("src/prepend.inc.php"); 
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
	
	// Load Client Object
    $Client = Client::Load($farminfo['clientid']);
    
    if ($post_cancel)
		UI::Redirect("instances_view.php?farmid={$farminfo['id']}");
    
        
	$display["title"] = _("Instances&nbsp;&raquo;&nbsp;View");
			
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

	if ($req_task || $req_action)
	{
		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region'])); 
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		
		if ($req_task == 'setactive')
		{
			
			$instanceinfo = $db->GetRow("SELECT * FROM farm_instances
				WHERE instance_id=? AND farmid=?",
				array($req_iid, $farminfo["id"])
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
						$records = DNSZoneControler::GetInstanceDNSRecordsList($instanceinfo, $zoneinfo["role_name"], $ami_info['alias']);
														
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
			
			if (count($err) == 0)
				$okmsg = _("Instance succesfully marked as active");
		}
		elseif ($req_task == 'setinactive')
		{
			$instanceinfo = $db->GetRow("SELECT * FROM farm_instances
				WHERE instance_id=? AND farmid=?",
				array($req_iid, $farminfo["id"])
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

			if (count($err) == 0)
				$okmsg = _("Instance succesfully marked as active");
		}
		else
		{			
			if (isset($req_action) && $_POST)
			{
				$req_instances = $post_id;
				$req_task = $req_action;
			}
			else
			{
				$req_instances = array($req_iid);
			}

			foreach ($req_instances as $instanceid)
			{
				$instance_info = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=? AND farmid=?", array($instanceid, $farminfo["id"]));
				
				if ($instance_info)
				{
					try 
					{
						$instances = array($instanceid);
						
						// Do something
						if ($req_task == "terminate")
						{
							$running_instances = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE ami_id=? AND farmid=?", 
								array($instance_info['ami_id'], $farminfo["id"])
							);
							
							$db->BeginTrans();
							
							$DBFarmRole = DBFarmRole::Load($farminfo["id"], $instance_info['ami_id']);
							
							if (count($req_instances) == 1)
							{
								$min_instances = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES);
								
								if ($post_cbtn_2)
								{
									$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES, $min_instances-1);
								}
								elseif ($post_cbtn_3)
								{
									//
								}
								else
								{
									if ($running_instances == $min_instances && $min_instances > 1)
									{
										$display["instance_id"] = $instance_info['instance_id'];
										$display["min_count"] = $min_instances;
										$display["role_name"] = $instance_info["role_name"];
										$display["min_count_new"] = $min_instances-1;
										$display["action"] = $post_action;
										
										$Smarty->assign($display);
										$Smarty->display("instance_terminate_confirm.tpl");
										exit();
									}
								}
							}
							
							try
							{
								$response = $AmazonEC2Client->TerminateInstances($instances);
							}
							catch(Exception $e)
							{
								$db->RollbackTrans();
								$err[] = $e->getMessage();
							}
							
							$db->CommitTrans();
						}
						elseif ($req_task == "reboot")
						{
							$response = $AmazonEC2Client->RebootInstances($instances);
						}
							
						if ($response instanceof SoapFault)
							$err[] = $response->faultstring;
					}
					catch (Exception $e)
					{
						$err[] = $e->getMessage(); 
					}
				}
			}

			if (count($err) == 0)
				$okmsg = sprintf(_("%d instance(s) %s"),
				 count($req_instances),
				($req_task == "reboot" ? _("going to reboot") : _("terminated")));
		}
		
		if ($okmsg)
			UI::Redirect("instances_view.php?farmid={$req_farmid}");
	}
	
	$display["grid_query_string"] = "&farmid={$farminfo["id"]}";
	
	require("src/append.inc.php"); 
	
?>