<?
define("NO_AUTH", true);
define("NO_TEMPLATES", true);
include("src/prepend.inc.php");

if ($req_FarmID && $req_Hash)
{
	$farm_id = (int)$req_FarmID;
	$hash = preg_replace("/[^A-Za-z0-9]+/", "", $req_Hash);

	$Logger->debug("Event '{$req_EventType}' received from '{$_SERVER['REMOTE_ADDR']}': FarmID={$farm_id}, Hash={$hash}, InstanceID={$req_InstanceID}, Data={$req_Data}");

	$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND hash=?", array($farm_id, $hash));
	$instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE farmid=?
                                     AND instance_id=?", array($farm_id, $req_InstanceID));

	$AmazonEC2Client = new AmazonEC2(
	APPPATH . "/etc/clients_keys/{$farminfo['clientid']}/pk.pem",
	APPPATH . "/etc/clients_keys/{$farminfo['clientid']}/cert.pem");


	if ($farminfo && $instanceinfo)
	{
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

					$res = $Shell->QueryRaw(CONFIG::$SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$farm_instance_snmp['external_ip'].' "" SNMPv2-MIB::snmpTrap.11.0 SNMPv2-MIB::sysName.0 s "'.$alias.'" SNMPv2-MIB::sysLocation.0 s "'.$instanceinfo['internal_ip'].'" SNMPv2-MIB::sysDescr.0 s "'.$isfirstinrole.'" 2>&1', true);
					$Logger->debug("[FarmID: {$farminfo['id']}] Sending SNMP Trap 11.0 (hostDown) to '{$farm_instance_snmp['instance_id']}' ('{$farm_instance_snmp['external_ip']}') complete ({$res})");
				}

				//
				// Update DNS
				//
				$DNSZoneController = new DNSZoneControler();
				$records = $db->GetAll("SELECT * FROM records WHERE rvalue='{$instanceinfo['external_ip']}' OR rvalue='{$instanceinfo['internal_ip']}'");
				foreach ($records as $record)
				{
					$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id='{$record['zoneid']}'");

					if ($zoneinfo)
					{
						$db->Execute("DELETE FROM records WHERE id='{$record['id']}'");
						if (!$DNSZoneController->Update($record["zoneid"]))
						$Logger->error("[FarmID: {$farminfo['id']}] Cannot remove terminated instance '{$instanceinfo['instance_id']}' ({$instanceinfo['external_ip']}) from DNS zone '{$zoneinfo['zone']}'");
						else
						$Logger->debug("[FarmID: {$farminfo['id']}] Terminated instance '{$instanceinfo['instance_id']}' (ExtIP: {$instanceinfo['external_ip']}, IntIP: {$instanceinfo['internal_ip']}) removed from DNS zone '{$zoneinfo['zone']}'");
					}
				}

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

				break;

			case "rebootFinish":

				$db->Execute("UPDATE farm_instances SET isrebootlaunched='0', dtrebootstart=NULL WHERE id='{$instanceinfo['id']}'");

				break;
				 
			case "newMysqlMaster":

				$Logger->info("New mysql master ({$req_InstanceID}) for farm ID {$farm_id}");

				$db->Execute("UPDATE farm_instances SET isdbmaster='0' WHERE farmid=?", $farm_id);
				$db->Execute("UPDATE farm_instances SET isdbmaster='1' WHERE farmid=?
                                     AND instance_id=?", array($farm_id, $req_InstanceID));

				$Shell = ShellFactory::GetShellInstance();
				$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND role_name=?", array($farminfo["id"], $instanceinfo["role_name"]));
				foreach ((array)$instances as $instance)
				{
					$res = $Shell->QueryRaw(CONFIG::$SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$instance['external_ip'].' "" SNMPv2-MIB::snmpTrap.10.1 SNMPv2-MIB::sysName.0 s "'.$instanceinfo['internal_ip'].'" SNMPv2-MIB::sysLocation.0 s "'.$data['snapurl'].'" 2>&1', true);
					$Logger->debug("Sending SNMP Trap 10.1 (newMysqlMaster) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})", E_USER_NOTICE);
				}

				break;

			case "hostInit":

				$Logger->debug("Instance '{$req_InstanceID}' ('{$_SERVER['REMOTE_ADDR']}') initialized");

				$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id='{$farminfo['clientid']}'");

				$s3cfg = CONFIG::$S3CFG_TEMPLATE;
				$s3cfg = str_replace("[access_key]", $clientinfo["aws_accesskeyid"], $s3cfg);
				$s3cfg = str_replace("[secret_key]", $clientinfo["aws_accesskey"], $s3cfg);
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
					$res = $SSH2->SendFile("/etc/aws/keys/pk.pem", APPPATH . "/etc/clients_keys/{$farminfo['clientid']}/pk.pem");
					$res2 = $SSH2->SendFile("/etc/aws/keys/cert.pem", APPPATH . "/etc/clients_keys/{$farminfo['clientid']}/cert.pem");
					$res3 = $SSH2->SendFile("/etc/aws/keys/s3cmd.cfg", $s3cfg, "w+", false);
				}
				else
				{
					$Logger->error("Cannot upload keys on '{$req_InstanceID}'. Failed to connect to  ('{$_SERVER['REMOTE_ADDR']}').");	
				}

				@unlink($pub_key_file);
				@unlink($priv_key_file);

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

				break;

			case "rebundleFail":

				$Logger->debug("New role creation failed. InstanceId = {$req_InstanceID}");

				$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$instanceinfo['ami_id']}'");

				$db->Execute("UPDATE ami_roles SET iscompleted='2', `replace`='' WHERE prototype_iid=? AND iscompleted='0'", array($req_InstanceID));
				
								
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
						$db->Execute("UPDATE farm_amis SET replace_to_ami='{$newAMI}' WHERE ami_id='{$ami_info['replace']}' AND farmid='{$instanceinfo['farmid']}'");
					else
					{
						// If new role name == old role name we need replace all instances on all farms with new ami
						$db->Execute("UPDATE farm_amis SET replace_to_ami='{$newAMI}' WHERE ami_id='{$ami_info['replace']}'");
					}
				}

				break;

			case "hostUp":

				switch ($instanceinfo["state"])
				{
					case "Pending":

						$Logger->debug("Instance '{$req_InstanceID}' ('{$_SERVER['REMOTE_ADDR']}') started.");

						$db->Execute("UPDATE farm_instances SET state='Running' WHERE id='{$instanceinfo['id']}'");
						
						//
						// Update DNS
						//
						try
						{
							$zones = $db->GetAll("SELECT * FROM zones WHERE farmid='{$farminfo['id']}'");
							if (count($zones) > 0)
							{
								$DNSZoneController = new DNSZoneControler();
	
								foreach ($zones as $zone)
								{								
									if ($zone['id'])
									{									
										if ($zone["role_name"] == $instanceinfo['role_name'])
											$records_attrs[] = array("@", $_SERVER['REMOTE_ADDR'], CONFIG::$DYNAMIC_A_REC_TTL);
										
										if ($instanceinfo["isdbmaster"] == 1)
											$records_attrs[] = array("int-{$instanceinfo['role_name']}-master", $instanceinfo["internal_ip"], 20);
											
										$records_attrs[] = array("int-{$instanceinfo['role_name']}", $instanceinfo["internal_ip"], 20);
											
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

						$alias = $db->GetOne("SELECT alias FROM ami_roles WHERE name='{$instanceinfo["role_name"]}' AND iscompleted='1'");
						 
						$Shell = ShellFactory::GetShellInstance();
						$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state='Running'", array($farminfo["id"]));
						foreach ((array)$instances as $instance)
						{
							$res = $Shell->QueryRaw(CONFIG::$SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$instance['external_ip'].' "" SNMPv2-MIB::snmpTrap.11.1 SNMPv2-MIB::sysName.0 s "'.$alias.'" SNMPv2-MIB::sysLocation.0 s "'.$instanceinfo['internal_ip'].'" 2>&1', true);
							$Logger->debug("Sending SNMP Trap 11.1 (hostUp) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})");
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

									$DNSZoneController = new DNSZoneControler();

									$zones = $db->GetAll("SELECT * FROM zones WHERE id IN (SELECT zoneid FROM records WHERE (rvalue='{$old_instance['external_ip']}' OR rvalue='{$old_instance['internal_ip']}') AND `rtype`='A')");
									foreach ($zones as $zone)
									{
										$db->Execute("REPLACE INTO records SET zoneid='{$zone['id']}', rtype='A', ttl=?, rvalue=?, rkey='@', issystem='1'",
										array(CONFIG::$DYNAMIC_A_REC_TTL, $_SERVER['REMOTE_ADDR']));
										 
										if (!$DNSZoneController->Update($zone["id"]))
										$Logger->error("Cannot update zone when 'hostUp' event raised for instance swap.");
										else
										$Logger->debug("A record for instance {$instanceinfo['instance_id']} with IP {$_SERVER['REMOTE_ADDR']} added to zone '{$zone['zone']}'");
									}
								}
							}
						}
						catch (Exception $e)
						{
							$Logger->fatal($e->getMessage());
						}

						break;

					case "Running":

						$Logger->warn("Strange situation. Received hostUp event from Running instance '{$req_InstanceID}' ('{$_SERVER['REMOTE_ADDR']}')!");

						break;
				}

				break;

					case "logEvent":

						try
						{
							$db->Execute("INSERT INTO logentries SET serverid=?, message=?, time=?, severity=?, source=?, farmid=?", array($req_InstanceID, base64_decode($data["msg"]), time(), $data["severity"], $data["source"], $farminfo["id"]));
						}
						catch (Exception $e)
						{
							throw new ApplicationException($e->getMessage(), E_ERROR);
						}

						break;
		}
	}
}
?>