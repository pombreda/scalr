<?
	define("NO_AUTH", true);
	include("src/prepend.inc.php");
	try
	{
		if ($req_FarmID && $req_Hash)
		{
			$farm_id = (int)$req_FarmID;
			$hash = preg_replace("/[^A-Za-z0-9]+/", "", $req_Hash);
		
			$Logger->info("Event '{$req_EventType}' received from '{$_SERVER['REMOTE_ADDR']}': FarmID={$farm_id}, Hash={$hash}, InstanceID={$req_InstanceID}");
			$Logger->info("Event data: {$req_Data}");
			
			$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND hash=?", array($farm_id, $hash));
			$instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE farmid=?
		                                     AND instance_id=?", array($farm_id, $req_InstanceID));
		
			$cpwd = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
			
			$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($farminfo['clientid']));
				
			$SNMP = new SNMP();
		
			$chunks = explode(";", $req_Data);
			foreach ($chunks as $chunk)
			{
				$dt = explode(":", $chunk);
				$data[$dt[0]] = trim($dt[1]);
			}
			
			// For scalr instalations //
			if (!$instanceinfo['internal_ip'])
				$instanceinfo['internal_ip'] = $data["localip"];
			
			if ($instanceinfo['internal_ip'] == $_SERVER['REMOTE_ADDR'])
			{
				if (!$instanceinfo['external_ip'])
				{
					try
					{
						// Decrypt client prvate key and certificate
				    	$private_key = $Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
				    	$certificate = $Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
				    	
				    	$AmazonEC2Client = new AmazonEC2($private_key, $certificate);
				    	$response = $AmazonEC2Client->DescribeInstances($req_InstanceID);
				    	$ip = gethostbyname($response->reservationSet->item->instancesSet->item->dnsName);
				    	
				    	$_SERVER['REMOTE_ADDR'] = $ip;
					}
					catch(Exception $e)
					{
						$Logger->fatal(sprintf(_("Cannot determine external IP for instance %s: %s"),
							$req_InstanceID, $e->getMessage()
						));
						exit();
					}
				}
				else
					$_SERVER['REMOTE_ADDR'] = $instanceinfo['external_ip'];
			}
			//************************//
			
			if ($farminfo && $instanceinfo)
			{
				// Check instance external IP
				if ($instanceinfo["isipchanged"] == 0 && 
					$instanceinfo['external_ip'] && 
					$instanceinfo['external_ip'] != $_SERVER['REMOTE_ADDR'] &&
					$req_EventType != 'go2Halt'
				)
				{
					try
					{
						Scalr::FireEvent($farm_id, EVENT_TYPE::INSTANCE_IP_ADDRESS_CHANGED, $instanceinfo, $_SERVER['REMOTE_ADDR']);
					}
					catch(Exception $e)
					{
						$Logger->fatal("Cannot update instance IP: {$e->getMessage()}");
					}
				}
		
				switch ($req_EventType)
				{
					case "go2Halt":
							//Scalr::FireEvent($farminfo['id'], EVENT_TYPE::PENDING_TERMINATE, $instanceinfo);
						break;
		
					case "hostDown":
							if ($instanceinfo["isrebootlaunched"] == 0)
							{
								Scalr::FireEvent($farminfo['id'], EVENT_TYPE::HOST_DOWN, $instanceinfo);
							}
						break;
						
					case "rebootFinish":
							Scalr::FireEvent($farminfo['id'], EVENT_TYPE::REBOOT_COMPLETE, $instanceinfo);
						break;
						
					case "rebootStart":
							Scalr::FireEvent($farminfo['id'], EVENT_TYPE::REBOOT_BEGIN, $instanceinfo);
						break;
						
					case "hostUp":
		
						switch ($instanceinfo["state"])
						{
							case INSTANCE_STATE::INIT:
									
									//TODO: Move this code to DBEventObserver
									$db->Execute("UPDATE farm_instances SET mysql_stat_password = ? WHERE id = ?", 
										array($data['ReplUserPass'], $instanceinfo['id'])
									);
								
									Scalr::FireEvent($farminfo['id'], EVENT_TYPE::HOST_UP, $instanceinfo);
								break;
		
							default:
									$Logger->warn("Strange situation. Received hostUp event from instance '{$req_InstanceID}' ('{$_SERVER['REMOTE_ADDR']}') width state {$instanceinfo['state']}!");
								break;
						}
						
						break;
											 
					case "newMysqlMaster":
							Scalr::FireEvent($farm_id, EVENT_TYPE::NEW_MYSQL_MASTER, $instanceinfo, $data['snapurl']);
						break;
		
					case "hostInit":
							Scalr::FireEvent(
								$farminfo['id'], 
								EVENT_TYPE::HOST_INIT, 
								$instanceinfo, 
								$data["localip"], 
								$_SERVER['REMOTE_ADDR'], 
								base64_decode($data["based64_pubkey"])
							);
						break;
		
					case "rebundleFail":
							Scalr::FireEvent($farminfo['id'], EVENT_TYPE::REBUNDLE_FAILED, $instanceinfo);							
						break;
		
					case "mysqlBckComplete":
							Scalr::FireEvent($farminfo['id'], EVENT_TYPE::MYSQL_BACKUP_COMPLETE, $data["operation"]);
						break;
		
					case "mysqlBckFail":
							$op = ucfirst($data["operation"]);
							Scalr::FireEvent($farminfo['id'], EVENT_TYPE::MYSQL_BACKUP_FAIL, $data["operation"]);
						break;
						
					case "newAMI":			
						Scalr::FireEvent($farminfo['id'], EVENT_TYPE::REBUNDLE_COMPLETE, $data["amiid"], $instanceinfo);
						break;
											
				    //********************
				    //* LOG Events
				    //********************		
						
					case "newInstanceStatus":
							$db->Execute("UPDATE farm_instances SET status=? WHERE instance_id=?", array($req_Data, $req_InstanceID));
						break;
						
					case "rebundleStatus":
					
						$roleid = $db->GetOne("SELECT id FROM ami_roles WHERE iscompleted='0' AND prototype_iid=?", array($req_InstanceID));
						if ($roleid)
							$db->Execute("INSERT INTO rebundle_log SET roleid=?, dtadded=NOW(), message=?", array($roleid, $req_Data));
						
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
							throw new Exception($e->getMessage(), E_ERROR);
						}
	
						break;
				}
			}
		}
		exit();
	}
    catch(Exception $e)
    {
    	header("HTTP/1.0 500 Internal Server Error");
    	die($e->getMessage());
    }
?>