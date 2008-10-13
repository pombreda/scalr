<?
define("NO_AUTH", true);
include("src/prepend.inc.php");

if ($req_FarmID && $req_Hash)
{
	$farm_id = (int)$req_FarmID;
	$hash = preg_replace("/[^A-Za-z0-9]+/", "", $req_Hash);

	$Logger->debug("Event '{$req_EventType}' received from '{$_SERVER['REMOTE_ADDR']}': FarmID={$farm_id}, Hash={$hash}, InstanceID={$req_InstanceID}, Data={$req_Data}");

	$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND hash=?", array($farm_id, $hash));
	$instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE farmid=?
                                     AND instance_id=?", array($farm_id, $req_InstanceID));

	$cpwd = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
	
	$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($farminfo['clientid']));
	
	// Decrypt client prvate key and certificate
    $private_key = $Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
    $certificate = $Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
	
	$AmazonEC2Client = new AmazonEC2($private_key, $certificate);


	if ($farminfo && $instanceinfo)
	{
		// Check instance external IP
		if ($instanceinfo['external_ip'] && $instanceinfo['external_ip'] != $_SERVER['REMOTE_ADDR'])
		{
			try
			{
				// Set severity to fatal to track ip changes.
				// Downgrade severity to info after few weeks
				$Logger->fatal("IP changed for instance {$req_InstanceID}. Old: {$instanceinfo['external_ip']}, new: {$_SERVER['REMOTE_ADDR']}");
				$db->Execute("UPDATE farm_instances SET external_ip=? WHERE id=?",
					array($_SERVER['REMOTE_ADDR'], $instanceinfo["id"])
				);
			}
			catch(Exception $e)
			{
				$Logger->fatal("Cannot update instance IP: {$e->getMessage()}");
			}
		}
		
		$chunks = explode(";", $req_Data);
		foreach ($chunks as $chunk)
		{
			$dt = explode(":", $chunk);
			$data[$dt[0]] = trim($dt[1]);
		}

		switch ($req_EventType)
		{
			case "go2Halt":
				
				$Logger->warn("[FarmID: {$farminfo['id']}] Instance '{$instanceinfo["instance_id"]}' sent event 'go2halt'");
				$db->Execute("DELETE FROM farm_instances WHERE farmid=? AND instance_id=?", array($farminfo['id'], $instanceinfo["instance_id"]));

				$db->Execute("UPDATE farms SET isbcprunning='0' WHERE bcp_instance_id=?", array($instanceinfo["instance_id"]));
				
				$farm_instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid='{$farminfo['id']}'");

				$alias = $db->GetOne("SELECT alias FROM ami_roles WHERE ami_id='{$instanceinfo['ami_id']}'");
				 
				$Shell = ShellFactory::GetShellInstance();
				$first_in_role_handled = false;
				foreach ($farm_instances as $farm_instance_snmp)
				{
					if ($farm_instance_snmp["state"] != 'Running' || !$farm_instance_snmp["external_ip"])
						continue;

					if ($farm_instance_snmp["id"] == $instanceinfo["id"])
						continue;

					$isfirstinrole = '0';

					if ($instanceinfo['role_name'] == $farm_instance_snmp["role_name"] && !$first_in_role_handled)
					{
						$first_in_role_handled = true;
						$isfirstinrole = '1';
					}

					$res = $Shell->QueryRaw(CONFIG::$SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$farm_instance_snmp['external_ip'].' "" SNMPv2-MIB::snmpTrap.11.0 SNMPv2-MIB::sysName.0 s "'.$alias.'" SNMPv2-MIB::sysLocation.0 s "'.$instanceinfo['internal_ip'].'" SNMPv2-MIB::sysDescr.0 s "'.$isfirstinrole.'" SNMPv2-MIB::sysContact.0 s "'.$instanceinfo['role_name'].'" 2>&1', true);
					$Logger->debug("[FarmID: {$farminfo['id']}] Sending SNMP Trap 11.0 (hostDown) to '{$farm_instance_snmp['instance_id']}' ('{$farm_instance_snmp['external_ip']}') complete ({$res})");
				}

				//
				// Update DNS
				//
				
				// Update DNS zone only if farm running
				if ($farminfo['status'] == 1)
				{
					try
					{
						$DNSZoneController = new DNSZoneControler();
						$records = $db->GetAll("SELECT * FROM records WHERE rvalue='{$instanceinfo['external_ip']}' OR rvalue='{$instanceinfo['internal_ip']}'");
						foreach ($records as $record)
						{
							$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id='{$record['zoneid']}'");
		
							if ($zoneinfo)
							{
								$db->Execute("DELETE FROM records WHERE id='{$record['id']}'");
								if (!$DNSZoneController->Update($record["zoneid"]))
									$Logger->warn("[FarmID: {$farminfo['id']}] Cannot remove terminated instance '{$instanceinfo['instance_id']}' ({$instanceinfo['external_ip']}) from DNS zone '{$zoneinfo['zone']}'");
								else
									$Logger->debug("[FarmID: {$farminfo['id']}] Terminated instance '{$instanceinfo['instance_id']}' (ExtIP: {$instanceinfo['external_ip']}, IntIP: {$instanceinfo['internal_ip']}) removed from DNS zone '{$zoneinfo['zone']}'");
							}
						}
					}
					catch(Exception $e)
					{
						$Logger->warn(new FarmLogMessage($farminfo['id'], "Update DNS zone on go2halt event failed: {$e->getMessage()}"));
					}
				}
				
				//
				// Check running synchronizations
				//
				$sync_roles = $db->GetAll("SELECT * FROM ami_roles WHERE prototype_iid=? AND iscompleted='0'", array($req_InstanceID));
				foreach ($sync_roles as $sync_role)
				{
					$db->Execute("UPDATE ami_roles SET iscompleted='2', `replace`='', prototype_iid='', fail_details=? WHERE id='{$sync_role['id']}'", array("Instance terminated during synchronization."));
				}
				
				Scalr::StoreEvent($farminfo['id'], EVENT_TYPE::HOST_DOWN, $instanceinfo);
				
				break;

			case "mysqlBckComplete":

				if ($data["operation"] == "backup")
					$db->Execute("UPDATE farms SET dtlastbcp='".time()."' WHERE id='{$farminfo['id']}'");
				else
					$db->Execute("UPDATE farms SET dtlastrebundle='".time()."' WHERE id='{$farminfo['id']}'");

				$db->Execute("UPDATE farms SET isbcprunning='0' WHERE id='{$farminfo['id']}'");

				break;

			case "mysqlBckFail":

				$db->Execute("UPDATE farms SET isbcprunning='0' WHERE id='{$farminfo['id']}'");

				$op = ucfirst($data["operation"]);

				$Logger->error("[Farm {$farminfo['id']}] {$op} failed!");

				break;

			case "rebootStart":

				$db->Execute("UPDATE farm_instances SET isrebootlaunched='1', dtrebootstart=NOW() WHERE id='{$instanceinfo['id']}'");
				
				Scalr::StoreEvent($farminfo['id'], EVENT_TYPE::REBOOT_BEGIN, $instanceinfo);
				
				break;

			case "rebundleStatus":
				
				$roleid = $db->GetOne("SELECT id FROM ami_roles WHERE iscompleted='0' AND prototype_iid=?", array($req_InstanceID));
				if ($roleid)
				{
					$Logger->info("Received 'rebundleStatus' event for roleID = {$roleid}");
					$db->Execute("INSERT INTO rebundle_log SET roleid=?, dtadded=NOW(), message=?", array($roleid, $req_Data));
				}
				else
					$Logger->warn("Received 'rebundleStatus' event for unknown role");
				
				break;
				
			case "rebootFinish":

				$db->Execute("UPDATE farm_instances SET isrebootlaunched='0', dtrebootstart=NULL WHERE id='{$instanceinfo['id']}'");
				
				Scalr::StoreEvent($farminfo['id'], EVENT_TYPE::REBOOT_COMPLETE, $instanceinfo);
				
				break;
				 
			case "newMysqlMaster":

				$Logger->info("New mysql master ({$req_InstanceID}) for farm ID {$farm_id}");

				$db->Execute("UPDATE farm_instances SET isdbmaster='0' WHERE farmid=?", $farm_id);
				$db->Execute("UPDATE farm_instances SET isdbmaster='1' WHERE farmid=?
                                     AND instance_id=?", array($farm_id, $req_InstanceID));

				$Shell = ShellFactory::GetShellInstance();
				$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=?", array($farminfo["id"]));
				foreach ((array)$instances as $instance)
				{
					$res = $Shell->QueryRaw(CONFIG::$SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$instance['external_ip'].' "" SNMPv2-MIB::snmpTrap.10.1 SNMPv2-MIB::sysName.0 s "'.$instanceinfo['internal_ip'].'" SNMPv2-MIB::sysLocation.0 s "'.$data['snapurl'].'" 2>&1', true);
					$Logger->debug("Sending SNMP Trap 10.1 (newMysqlMaster) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})", E_USER_NOTICE);
				}

				// Update DNS
				$zones = $db->GetAll("SELECT * FROM zones WHERE farmid='{$farminfo['id']}' AND status IN (?,?)", array(ZONE_STATUS::ACTIVE, ZONE_STATUS::PENDING));
				if (count($zones) > 0)
				{
					$DNSZoneController = new DNSZoneControler();
					
					foreach ($zones as $zone)
					{								
						$records_attrs = array();
						
						if ($zone['id'])
						{
							$records_attrs[] = array("int-{$instanceinfo['role_name']}-master", $instanceinfo["internal_ip"], 20);
							$records_attrs[] = array("ext-{$instanceinfo['role_name']}-master", $_SERVER['REMOTE_ADDR'], 20);
							
							foreach ($records_attrs as $record_attrs)
							{									
								$db->Execute("REPLACE INTO records SET zoneid='{$zone['id']}', rtype='A', ttl=?, rvalue=?, rkey=?, issystem='1'",
								array($record_attrs[2], $record_attrs[1], $record_attrs[0]));
							}
							
							if (!$DNSZoneController->Update($zone["id"]))
								$Logger->error("Cannot update zone when 'hostUp' event raised.");
							else
								$Logger->debug("Instance {$instanceinfo['instance_id']} added to DNS zone '{$zone['zone']}'");
						}
					}
				}
				
				break;

			case "hostInit":

				$Logger->debug("Instance '{$req_InstanceID}' ('{$_SERVER['REMOTE_ADDR']}') initialized.");
				
				$db->BeginTrans();
				
				try
				{
					$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id='{$farminfo['clientid']}'");
	
					$s3cfg = CONFIG::$S3CFG_TEMPLATE;
					$s3cfg = str_replace("[access_key]", $Crypto->Decrypt($clientinfo["aws_accesskeyid"], $cpwd), $s3cfg);
					$s3cfg = str_replace("[secret_key]", $Crypto->Decrypt($clientinfo["aws_accesskey"], $cpwd), $s3cfg);
					$s3cfg = str_replace("\r\n", "\n", $s3cfg);
	
					$local_ip = $data["localip"];
					$based64_pubkey = base64_decode($data["based64_pubkey"]);
	
					//Update instance info in database;
					$db->Execute("UPDATE farm_instances SET internal_ip=?, external_ip=? WHERE id='{$instanceinfo['id']}'", array($local_ip, $_SERVER['REMOTE_ADDR']));
	
					if (!$farminfo["public_key"])
						$db->Execute("UPDATE farms SET public_key=? WHERE id=?", array($based64_pubkey, $farminfo["id"]));
	
					$pub_key_file = tempnam("/tmp", "AWSK");
					@file_put_contents($pub_key_file, $based64_pubkey);
	
					$priv_key_file = tempnam("/tmp", "AWSK");
					@file_put_contents($priv_key_file, $farminfo["private_key"]);
	
					$SSH2 = new SSH2();
					$SSH2->AddPubkey("root", $pub_key_file, $priv_key_file);
					if ($SSH2->Connect($_SERVER['REMOTE_ADDR'], 22))
					{
						// Upload keys and s3 config to instance
						$res = $SSH2->SendFile("/etc/aws/keys/pk.pem", $private_key, "w+", false);
						$res2 = $SSH2->SendFile("/etc/aws/keys/cert.pem", $certificate, "w+", false);
						$res3 = $SSH2->SendFile("/etc/aws/keys/s3cmd.cfg", $s3cfg, "w+", false);
						
						try
						{
							$hooks = glob(APPPATH."/hooks/hostInit/*.sh");
							if (count($hooks) > 0)
							{
								foreach ($hooks as $hook)
								{
									$name = basename($hook);
									$Logger->info("Executing onHostInit hook: {$name}");
									$SSH2->SendFile("/usr/local/bin/{$name}", $hook, "w+");
									$res = $SSH2->Exec("chmod 0700 /usr/local/bin/{$name} && /usr/local/bin/{$name}", $hook, "w+");
									$Logger->info("{$name} hook execution output: {$res}");
								}
							}
						}
						catch(Exception $e)
						{
							$Logger->fatal("Cannot execute hostInit hooks: {$e->getMessage()}");
						}
						
						@unlink($pub_key_file);
						@unlink($priv_key_file);
					}
					else
					{
						@unlink($pub_key_file);
						@unlink($priv_key_file);
						
						$Logger->warn(new FarmLogMessage($farminfo['id'], "Cannot upload ec2 keys to '{$req_InstanceID}' instance. Failed to connect to SSH ('{$_SERVER['REMOTE_ADDR']}':22)"));
						throw new Exception("Cannot upload keys on '{$req_InstanceID}'. Failed to connect to  ('{$_SERVER['REMOTE_ADDR']}').");
					}
				}
				catch(Exception $e)
				{
					$Logger->error($e->getMessage());
					$db->RollbackTrans();
					$initFail = true;
				}

				if (!$initFail)
				{
					$db->CommitTrans();
					
					try
					{
						$Shell = ShellFactory::GetShellInstance();
						$res = $Shell->QueryRaw(CONFIG::$SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$_SERVER['REMOTE_ADDR'].' "" SNMPv2-MIB::snmpTrap.12.1 SNMPv2-MIB::sysName.0 s "'.$clientinfo['aws_accountid'].'" 2>&1', true);
	
						$Logger->debug("Sending SNMP Trap 12.1 (hostInit) to '{$req_InstanceID}' ('{$_SERVER['REMOTE_ADDR']}') complete ({$res})", E_USER_NOTICE);
					}
					catch (Exception $e)
					{
						$Logger->fatal($e->getMessage(), E_ERROR);
					}
				}

				break;

			case "rebundleFail":

				$Logger->debug("New role creation failed. InstanceId = {$req_InstanceID}");

				$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$instanceinfo['ami_id']}'");

				$db->Execute("UPDATE ami_roles SET iscompleted='2', `replace`='', fail_details=? WHERE prototype_iid=? AND iscompleted='0'", array("Rebundle script failed. See event log for more information.", $req_InstanceID));
				
				Scalr::StoreEvent($farminfo['id'], EVENT_TYPE::REBUNDLE_FAILED, $instanceinfo);
								
				break;

			case "newAMI":

				$Logger->debug("New role creation complete. New AMI_ID = {$data["amiid"]}");

				$newAMI = $data["amiid"];

				$db->Execute("UPDATE ami_roles SET ami_id=?, iscompleted='1', dtbuilt=NOW(), prototype_iid='' WHERE prototype_iid=? AND iscompleted='0'", array($newAMI, $req_InstanceID));

				$old_ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$instanceinfo['ami_id']}'");
				
				$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($newAMI));
				if ($ami_info["replace"])
				{
					if ($old_ami_info["roletype"] == 'SHARED' || $old_ami_info["name"] != $ami_info["name"])
					{
						$db->Execute("UPDATE farm_amis SET replace_to_ami='{$newAMI}' WHERE ami_id='{$ami_info['replace']}' AND farmid='{$instanceinfo['farmid']}'");
					}
					else
					{
						// If new role name == old role name we need replace all instances on all farms with new ami
						$db->Execute("UPDATE farm_amis SET replace_to_ami='{$newAMI}' WHERE ami_id='{$ami_info['replace']}' AND farmid IN (SELECT id FROM farms WHERE clientid='{$farminfo['clientid']}')");
					}
				}

				// Add record to log
				$roleid = $db->GetOne("SELECT id FROM ami_roles WHERE ami_id=?", array($newAMI));
				$db->Execute("INSERT INTO rebundle_log SET roleid=?, dtadded=NOW(), message=?", array($roleid, "Rebundle complete."));
				
				Scalr::StoreEvent($farminfo['id'], EVENT_TYPE::REBUNDLE_COMPLETE, $ami_info, $instanceinfo);
				
				break;

			case "hostUp":

				switch ($instanceinfo["state"])
				{
					case "Pending":

						$Logger->info(new FarmLogMessage($farminfo['id'], "Instance '{$req_InstanceID}' ('{$_SERVER['REMOTE_ADDR']}') initialized and started."));
						
						$db->Execute("UPDATE farm_instances SET state='Running' WHERE id='{$instanceinfo['id']}'");
						
						$alias = $db->GetOne("SELECT alias FROM ami_roles WHERE name='{$instanceinfo["role_name"]}' AND iscompleted='1'");
						 
						$Shell = ShellFactory::GetShellInstance();
						$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state='Running'", array($farminfo["id"]));
						foreach ((array)$instances as $instance)
						{
							$res = $Shell->QueryRaw(CONFIG::$SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$instance['external_ip'].' "" SNMPv2-MIB::snmpTrap.11.1 SNMPv2-MIB::sysName.0 s "'.$alias.'" SNMPv2-MIB::sysLocation.0 s "'.$instanceinfo['internal_ip'].'" SNMPv2-MIB::sysDescr.0 s "'.$instanceinfo["role_name"].'" 2>&1', true);
							$Logger->debug("Sending SNMP Trap 11.1 (hostUp) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})");
						}

						//
						// Update DNS
						//
						if ($instanceinfo['isactive'] == 1)
						{
							try
							{
								$zones = $db->GetAll("SELECT * FROM zones WHERE farmid='{$farminfo['id']}' AND status IN (?,?)", array(ZONE_STATUS::ACTIVE, ZONE_STATUS::PENDING));
								if (count($zones) > 0)
								{
									$DNSZoneController = new DNSZoneControler();
									
									foreach ($zones as $zone)
									{								
										$records_attrs = array();
										
										if ($zone['id'])
										{									
											$replace = false;
											if ($instanceinfo["replace_iid"])
											{
												$old_instance_info = $db->GetRow("SELECT role_name FROM farm_instances 
													WHERE instance_id=?", 
													array($instanceinfo["replace_iid"])
												);
												
												if ($old_instance_info['role_name'] == $zone["role_name"])
													$replace = true;
											}
											
											if ($zone["role_name"] == $instanceinfo['role_name'] || $replace)
											{
												$records_attrs[] = array("@", $_SERVER['REMOTE_ADDR'], CONFIG::$DYNAMIC_A_REC_TTL);
												
												$Logger->info(new FarmLogMessage($farminfo['id'], "Adding '@ IN A {$_SERVER['REMOTE_ADDR']}' to zone {$zone['zone']} pointed to role '{$zone["role_name"]}'"));
											}
											
											if ($instanceinfo["isdbmaster"] == 1)
											{
												$records_attrs[] = array("int-{$instanceinfo['role_name']}-master", $instanceinfo["internal_ip"], 20);
												$records_attrs[] = array("ext-{$instanceinfo['role_name']}-master", $_SERVER['REMOTE_ADDR'], 20);
											}
												
											$records_attrs[] = array("int-{$instanceinfo['role_name']}", $instanceinfo["internal_ip"], 20);
											
											$records_attrs[] = array("ext-{$instanceinfo['role_name']}", $_SERVER['REMOTE_ADDR'], 20);
	
											$Logger->info(new FarmLogMessage($farminfo['id'], "Adding ext-* and int-* to zone {$zone['zone']}"));
											
											foreach ($records_attrs as $record_attrs)
											{									
												$db->Execute("REPLACE INTO records SET zoneid='{$zone['id']}', rtype='A', ttl=?, rvalue=?, rkey=?, issystem='1'",
												array($record_attrs[2], $record_attrs[1], $record_attrs[0]));
											}
											
											if (!$DNSZoneController->Update($zone["id"]))
												$Logger->error("Cannot update zone when 'hostUp' event raised.");
											else
												$Logger->debug("Instance {$instanceinfo['instance_id']} added to DNS zone '{$zone['zone']}'");
										}
									}
								}
							}
							catch(Exception $e)
							{
								$Logger->fatal("Eventhandler::hostUp. DNS zone update failed: ".$e->getMessage());
							}
						}
						
						$Logger->debug("Going to termination old instance...");

						try
						{
							if ($instanceinfo["replace_iid"])
							{
								 
								$db->Execute("UPDATE farm_instances SET replace_iid='' WHERE id='{$instanceinfo['id']}'");

								$old_instance = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id='{$instanceinfo["replace_iid"]}'");
								 
								$Logger->debug("Old instance: {$old_instance['id']}.");

								if ($old_instance)
								{
									$res = $AmazonEC2Client->TerminateInstances(array($old_instance["instance_id"]));
									if ($res instanceof SoapFault)
									{
										$Logger->fatal("Cannot terminate instance '{$old_instance["instance_id"]}' ({$res->faultString}). Please do it manualy.");
									}
									else
									$Logger->warn("Instance '{$old_instance["instance_id"]}' has been swapped with the instance {$instanceinfo['instance_id']}");
								}
							}
						}
						catch (Exception $e)
						{
							$Logger->fatal($e->getMessage());
						}
						
						Scalr::StoreEvent($farminfo['id'], EVENT_TYPE::HOST_UP, $instanceinfo);
						
						break;

					case "Running":

						$Logger->warn("Strange situation. Received hostUp event from Running instance '{$req_InstanceID}' ('{$_SERVER['REMOTE_ADDR']}')!");

						break;
				}

				break;

					case "logEvent":

						try
						{
							$message = base64_decode($data["msg"]);
							$db->Execute("INSERT INTO logentries SET serverid=?, message=?, time=?, severity=?, source=?, farmid=?", array($req_InstanceID, $message, time(), $data["severity"], $data["source"], $farminfo["id"]));
							
							if (stristr($message, "Received rebundle trap"))
								$db->Execute("UPDATE ami_roles SET rebundle_trap_received='1' WHERE prototype_iid=?", array($req_InstanceID));
						}
						catch (Exception $e)
						{
							throw new ApplicationException($e->getMessage(), E_ERROR);
						}

						break;
		}
	}
}
	exit();
?>