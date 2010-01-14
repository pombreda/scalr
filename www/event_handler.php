<?
	define("NO_AUTH", true);
	try
	{
		include("src/prepend.inc.php");
		
		if ($req_FarmID && $req_Hash)
		{
			$chunks = explode(";", $req_Data);
			foreach ($chunks as $chunk)
			{
				$dt = explode(":", $chunk);
				$data[$dt[0]] = trim($dt[1]);
			}
						
			// prepare GET params
			$farm_id = (int)$req_FarmID;
			$hash = preg_replace("/[^A-Za-z0-9]+/", "", $req_Hash);
		
			$pkg_ver = $req_PkgVer;
			
			// Add log infomation about event received from instance
			#$Logger->info("Event data: {$req_Data}");
			$Logger->info("Scalarizr version: {$req_PkgVer}");
			
			// Get farminfo and instanceinfo from database
			$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND hash=?", array($farm_id, $hash));
			$instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE farmid=?
		                                     AND instance_id=?", array($farm_id, $req_InstanceID));

			if ($instanceinfo['scalarizr_pkg_version'] != $pkg_ver)
			{
				$db->Execute("UPDATE farm_instances SET scalarizr_pkg_version=? WHERE id=?",
					array($pkg_ver, $instanceinfo['id'])
				);
			}
						
			/**
			 * Deserialize data from instance
			 */
			if (!$instanceinfo['internal_ip'])
				$instanceinfo['internal_ip'] = $data["localip"];
			
			if ($instanceinfo['internal_ip'] == $_SERVER['REMOTE_ADDR'])
			{
				if (!$instanceinfo['external_ip'])
				{
					try
					{
						$Client = Client::Load($farminfo['clientid']);

						$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region'])); 
						$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
						
				    	$response = $AmazonEC2Client->DescribeInstances($req_InstanceID);
				    	$ip = @gethostbyname($response->reservationSet->item->instancesSet->item->dnsName);
				    	
				    	$_SERVER['REMOTE_ADDR'] = $ip;
				    	
				    	$Logger->info(sprintf("Instance external ip = '%s'", $_SERVER['REMOTE_ADDR']));
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
				//
				// Check instance external IP
				// And fire event if it changed
				//
				if ($instanceinfo["isipchanged"] == 0 && 
					$instanceinfo['external_ip'] && 
					$instanceinfo['external_ip'] != $_SERVER['REMOTE_ADDR'] &&
					$req_EventType != 'go2Halt'
				)
				{
					try
					{
						Scalr::FireEvent($farminfo['id'], new IPAddressChangedEvent(DBInstance::LoadByID($instanceinfo['id']), $_SERVER['REMOTE_ADDR']));
					}
					catch(Exception $e)
					{
						$Logger->fatal("Cannot update instance IP: {$e->getMessage()}");
					}
				}
				
				$instance_events = array(
            		"hostInit" 			=> "HostInit",
            		"hostUp" 			=> "HostUp",
            		"rebootFinish" 		=> "RebootComplete",
            		"IPAddressChanged" 	=> "IPAddressChanged",
            		"newMysqlMaster"	=> "NewMysqlMasterUp"
            	);
						
				switch ($req_EventType)
				{
					case "go2Halt": break;
		
					case "hostDown":
							$event = new HostDownEvent(DBInstance::LoadByID($instanceinfo['id']));
						break;
						
					case "rebootFinish":
							$event = new RebootCompleteEvent(DBInstance::LoadByID($instanceinfo['id']));
						break;
						
					case "rebootStart":
							$event = new RebootBeginEvent(DBInstance::LoadByID($instanceinfo['id']));
						break;
						
					case "ebsVolumeAttached":
						
							$event = new EBSVolumeAttachedEvent(
								DBInstance::LoadByID($instanceinfo['id']),
								$data["device_name"],
								$data["volume_id"]
							);
						
						break;
						
					case "hostUp":
						
						switch ($instanceinfo["state"])
						{
							case INSTANCE_STATE::INIT:
									$event = new HostUpEvent(DBInstance::LoadByID($instanceinfo['id']), $data['ReplUserPass']);
								break;
		
							default:
									$Logger->error("Strange situation. Received hostUp event from instance '{$req_InstanceID}' ('{$_SERVER['REMOTE_ADDR']}') with state {$instanceinfo['state']}!");
								break;
						}
						
						break;
											 
					case "newMysqlMaster":
						
							$old_master_info = $db->GetRow("SELECT id FROM farm_instances WHERE isdbmaster='1' AND farmid=?", array($farm_id));	
						
							$OldMasterInstance = ($old_master_info) ? DBInstance::LoadByID($old_master_info['id']) : null;
							
							$event = new NewMysqlMasterUpEvent(DBInstance::LoadByID($instanceinfo['id']), $data['snapurl'], $OldMasterInstance);
						break;
		
					case "hostInit":
						
							switch ($instanceinfo["state"])
							{
								case INSTANCE_STATE::PENDING:
										$event = new HostInitEvent(
											DBInstance::LoadByID($instanceinfo['id']), 
											$data["localip"], 
											$_SERVER['REMOTE_ADDR'], 
											base64_decode($data["based64_pubkey"])
										);
									break;
			
								default:
										$Logger->error("Strange situation. Received hostInit event from instance '{$req_InstanceID}' ('{$_SERVER['REMOTE_ADDR']}') with state {$instanceinfo['state']}!");
									break;
							}
						
							
						break;
		
					case "rebundleFail":
							$event = new RebundleFailedEvent(DBInstance::LoadByID($instanceinfo['id']));							
						break;
		
					case "mysqlBckComplete":
							$event = new MysqlBackupCompleteEvent(DBInstance::LoadByID($instanceinfo['id']), $data["operation"], $data['snapinfo']);
						break;
		
					case "mysqlBckFail":
							$event = new MysqlBackupFailEvent(DBInstance::LoadByID($instanceinfo['id']), $data["operation"]);
						break;
						
					case "newAMI":			
							$event = new RebundleCompleteEvent(DBInstance::LoadByID($instanceinfo['id']), $data["amiid"]);
						break;
											
				    //********************
				    //* LOG Events
				    //********************		
					case "mountResult":
						
						if (!$data['name'])
						{
							$ebsinfo = $db->GetRow("SELECT * FROM farm_ebs WHERE instance_id=? AND state=?", array($req_InstanceID, FARM_EBS_STATE::MOUNTING));
							if ($ebsinfo)
								$db->Execute("UPDATE farm_ebs SET state=?, isfsexists='1' WHERE id=?", array(FARM_EBS_STATE::ATTACHED, $ebsinfo['id']));
						}
						else
						{
							if ($data['isarray'] == 1)
							{
								// EBS array
								$db->Execute("UPDATE ebs_arrays SET status=?, isfscreated='1' WHERE name=? AND instance_id=?",
									array(EBS_ARRAY_STATUS::IN_USE, $data['name'], $req_InstanceID)
								);
							}
							else
							{
								// Single volume
								$ebsinfo = $db->GetRow("SELECT * FROM farm_ebs WHERE volumeid=?", array($data['name']));
								if ($ebsinfo)
									$db->Execute("UPDATE farm_ebs SET state=?, isfsexists='1' WHERE id=?", array(FARM_EBS_STATE::ATTACHED, $ebsinfo['id']));
							}
						}
						
						if ($data['mountpoint'] && $data['success'] == 1)
							Scalr::FireEvent($req_FarmID, new EBSVolumeMountedEvent(DBInstance::LoadByID($instanceinfo['id']), $data['mountpoint'], $data['name']));
						
						break;
					
					case "basicEvent":
						
						
						
						if ($data['MessageName'] == BASIC_MESSAGE_NAMES::MYSQL_PMA_CREDENTIALS)
						{
							$DBFarmRole = DBFarmRole::LoadByID($data['Arg1']);
							if ($data['PmaUser'] && $data['PmaPass'])
							{
								$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_USER, $data['PmaUser']);
								$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_PASS, $data['PmaPass']);
							}
							else
							{
								if (!$data['Error'])
									$data['Error'] = 'Cannot create mysql user/password for PMA.';
								else
									$data['Error'] = base64_decode($data['Error']);
									
								$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_TIME, "");
								$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_ERROR, $data['Error']);
							}
						}
						
						break;
						
					case "trapACK": 
					
						$db->Execute("UPDATE messages SET isdelivered='1' WHERE messageid=?", array($data['trap_id']));
						
						break;
						
					case "scriptingLog":
												
	            		$event_name = ($instance_events[$data['eventName']]) ? $instance_events[$data['eventName']] : $data['eventName'];  
	            		
						$Logger->info(new ScriptingLogMessage(
							$req_FarmID, 
							$event_name,
							$req_InstanceID,
							base64_decode($data['msg'])
						));
						
						break;
						
					case "execResult":
							
							$event_name = ($instance_events[$data['eventName']]) ? $instance_events[$data['eventName']] : $data['eventName'];
						
							$stderr = base64_decode($data['stderr']);
							if (trim($stderr))
								$stderr = "\n stderr: {$stderr}";
								
							$stdout = base64_decode($data['stdout']);
							if (trim($stdout))
								$stdout = "\n stdout: {$stdout}";
						
							if (!$stderr && !$stdout)
								$stdout = _("Script executed without any output.");
								
							$Logger->warn(new ScriptingLogMessage(
								$req_FarmID, 
								$event_name,
								$req_InstanceID,
								sprintf(_("Script '%s' execution result (Execution time: %s seconds). %s %s"), 
									$data['script_path'], $data['time_elapsed'], $stderr, $stdout
								)
							));
						
						break;
						
					case "newInstanceStatus":
							$db->Execute("UPDATE farm_instances SET status=? WHERE instance_id=?", array($req_Data, $req_InstanceID));
						break;
						
					case "rebundleStatus":
					
						$roleid = $db->GetOne("SELECT id FROM roles WHERE iscompleted='0' AND prototype_iid=?", array($req_InstanceID));
						if ($roleid)
							$db->Execute("INSERT INTO rebundle_log SET roleid=?, dtadded=NOW(), message=?", array($roleid, $req_Data));
						
						break;
					
					case "logEvent":
	
						try
						{
							$message = base64_decode($data["msg"]);
							$db->Execute("INSERT DELAYED INTO logentries SET serverid=?, message=?, time=?, severity=?, source=?, farmid=?", array($req_InstanceID, $message, time(), $data["severity"], $data["source"], $farminfo["id"]));
							
							if (stristr($message, "Received rebundle trap"))
								$db->Execute("UPDATE roles SET rebundle_trap_received='1' WHERE prototype_iid=?", array($req_InstanceID));
						}
						catch (Exception $e)
						{
							throw new Exception($e->getMessage(), E_ERROR);
						}
	
						break;
				}
			}
		}
		
		if ($event)
		{
			Scalr::FireEvent($farminfo['id'], $event);
		}
		
		exit();
	}
    catch(Exception $e)
    {
    	header("HTTP/1.0 500 Internal Server Error");
    	die($e->getMessage());
    }
?>
