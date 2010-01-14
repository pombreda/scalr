<?
	class PollerProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Main poller";
        public $Logger;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = LoggerManager::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
            
            $this->Logger->info("Fetching completed farms...");
            
            $this->ThreadArgs = $db->GetAll("SELECT farms.id FROM farms 
            	INNER JOIN clients ON clients.id = farms.clientid WHERE clients.isactive='1'"
            );
                        
            $this->Logger->info("Found ".count($this->ThreadArgs)." farms.");
        }
        
        public function OnEndForking()
        {
			$db = Core::GetDBInstance(null, true);
        	
			$trap_wait_timeout = 240; // 120 seconds
			
			try
			{
				// Check sync roles (Rebundle trap received or not?)
	            $roles = $db->GetAll("SELECT * FROM roles WHERE iscompleted='0' AND rebundle_trap_received='0'");
	            foreach ($roles as $ami_role)
	            {
	            	if (strtotime($ami_role['dtbuildstarted'])+$trap_wait_timeout < time())
	            	{
	            		$this->Logger->warn("Role '{$ami_role['name']}' sync failed. Instance did not reply on SNMP trap.");
	            		
	            		$db->Execute("UPDATE roles SET iscompleted='2', fail_details=?, `replace`='' WHERE id=?",
	            			array("Instance did not reply on SNMP trap. Make sure that snmpd and snmptrapd are running.", $ami_role["id"]));
	            	}
	            }
	            
				// Check timeouted roles sync
	            $roles = $db->GetAll("SELECT * FROM roles WHERE iscompleted='0'");
	            foreach ($roles as $ami_role)
	            {
	            	$sync_timeout = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array('sync_timeout', $ami_role['client_id']));
    				$sync_timeout = $sync_timeout ? $sync_timeout : CONFIG::$SYNC_TIMEOUT;
	            	
	            	if ((strtotime($ami_role['dtbuildstarted'])+($sync_timeout*60)) < time())
	            	{
	            		$this->Logger->warn("Role '{$ami_role['name']}' sync timeouted.");
	            		
	            		$db->Execute("UPDATE roles SET iscompleted='2', fail_details=?, `replace`='' WHERE id=?",
	            			array("Aborted due to timeout ({$sync_timeout} minutes).", $ami_role["id"]));
	            	}
	            }
	            
	            $db->Execute("DELETE FROM farm_instances WHERE state=? AND UNIX_TIMESTAMP(dtadded)+3600 < UNIX_TIMESTAMP(NOW())", array(INSTANCE_STATE::TERMINATED));
	            $db->Execute("DELETE FROM messages WHERE instance_id NOT IN (SELECT instance_id FROM farm_instances)");
			}
			catch (Exception $e)
			{
				$this->Logger->fatal("Poller::OnEndForking failed: {$e->getMessage()}");
			}
        }
        
        public function StartThread($farminfo)
        {
            // Reconfigure observers;
        	Scalr::ReconfigureObservers();
        	
        	$db = Core::GetDBInstance();
            $SNMP = new SNMP();
            
            $DBFarm = DBFarm::LoadByID($farminfo['id']);
            
            define("SUB_TRANSACTIONID", posix_getpid());
            define("LOGGER_FARMID", $DBFarm->ID);
            
            $this->Logger->info("[".SUB_TRANSACTIONID."] Begin polling farm (ID: {$DBFarm->ID}, Name: {$DBFarm->Name}, Status: {$DBFarm->Status})");
                        
            $DNSZoneController = new DNSZoneControler();
            
            
            
            //
            // Collect information from database
            //
            $Client = Client::Load($DBFarm->ClientID);
                        
            $farm_instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state != ?",
            	array($DBFarm->ID, INSTANCE_STATE::TERMINATED)
            );
            
            $this->Logger->info("[FarmID: {$DBFarm->ID}] Found ".count($farm_instances)." farm instances in database");

            if ($DBFarm->Status == FARM_STATUS::TERMINATED && count($farm_instances) == 0)
            	exit();
                        
            // Get AmazonEC2 Object
            $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBFarm->Region)); 
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
                        
			try
			{
	            // Get instances from EC2
	            $this->Logger->debug("[FarmID: {$DBFarm->ID}] Receiving instances info from EC2...");
	            $result = $AmazonEC2Client->DescribeInstances();
	            $ec2_items = array();
	            $ec2_items_by_instanceid = array();
			}
			catch(Exception $e)
			{
				$this->Logger->warn("Cannot get instances list from amazon: {$e->getMessage()}");
				exit();
			}
                                   
            if (!is_array($result->reservationSet->item))
                $result->reservationSet->item = array($result->reservationSet->item);
            
            if (is_array($result->reservationSet->item))
            {
                $this->Logger->debug("[FarmID: {$DBFarm->ID}] Found ".count($result->reservationSet->item)." total instances...");
                $num = 0;
                foreach ($result->reservationSet->item as $item)
                {
					$ami_role_name = $db->GetOne("SELECT role_name FROM farm_instances WHERE instance_id=? AND farmid=? AND state != ?", 
						array($item->instancesSet->item->instanceId, $DBFarm->ID, INSTANCE_STATE::TERMINATED)
					);
					if ($ami_role_name)
					{
	                	if (!is_array($ec2_items[$ami_role_name]))
							$ec2_items[$ami_role_name] = array();
	                            
						array_push($ec2_items[$ami_role_name], $item->instancesSet->item);
						$ec2_items_by_instanceid[$item->instancesSet->item->instanceId] = $item->instancesSet->item;
						$num++;
					}
                }
                
                $this->Logger->debug("[FarmID: {$DBFarm->ID}] Found {$num} instances");
            }
            else 
                $this->Logger->debug("[FarmID: {$DBFarm->ID}] No instances found for this client.");
                
            
            foreach ($farm_instances as $farm_instance)
            {
                $instance_terminated = false;
                
                if (!isset($ec2_items_by_instanceid[$farm_instance["instance_id"]]))
                {
                    $farm_instance['isrebootlaunched'] = 0;
                	
                	// Add entry to farm log
                    $this->Logger->warn(new FarmLogMessage($DBFarm->ID, "Instance '{$farm_instance["instance_id"]}' found in database but not found on EC2. Crashed."));
                	Scalr::FireEvent($DBFarm->ID, new HostCrashEvent(DBInstance::LoadByID($farm_instance['id'])));
                }
                else 
                {
                    switch ($ec2_items_by_instanceid[$farm_instance["instance_id"]]->instanceState->name)
                    {
                        case "terminated":
                            
                            $this->Logger->warn("[FarmID: {$DBFarm->ID}] Instance '{$farm_instance["instance_id"]}' not running (Terminated).");
                            $instance_terminated = true;
                            
                            break;
                            
                        case "shutting-down":
                            
                            $this->Logger->warn("[FarmID: {$DBFarm->ID}] Instance '{$farm_instance["instance_id"]}' not running (Shutting Down).");
                            $instance_terminated = true;
                            
                            break;
                    }
                }
                
                if ($instance_terminated)
                {
                    $DBInstance = DBInstance::LoadByID($farm_instance['id']);
                    $DBInstance->IsRebootLaunched = 0;
                	Scalr::FireEvent($DBFarm->ID, new HostDownEvent($DBInstance));
                }
                
                if ($farm_instance['state'] == INSTANCE_STATE::PENDING_TERMINATE)
                {
                	if ($farm_instance['dtshutdownscheduled'] && strtotime($farm_instance['dtshutdownscheduled'])+60*3 < time())
                	{
						$this->Logger->warn(new FarmLogMessage($DBFarm->ID, "Terminating instance '{$farm_instance["instance_id"]}'..."));
                		$AmazonEC2Client->TerminateInstances(array($farm_instance['instance_id']));
                	}
                }
            }

        	//
            // Check farm status
            //
            if ($db->GetOne("SELECT status FROM farms WHERE id=?", array($DBFarm->ID)) != FARM_STATUS::RUNNING)
            {
            	$this->Logger->debug("[FarmID: {$DBFarm->ID}] Farm is not running.");
            	return;
            }
            
            $db_amis = $db->Execute("SELECT id FROM farm_roles WHERE farmid=? ORDER BY launch_index ASC", array($DBFarm->ID));
                        
            while ($db_ami = $db_amis->FetchRow())
            {
                if (!$db_ami)
                    continue;
                
                $DBFarmRole = DBFarmRole::LoadByID($db_ami['id']);
                    
                if ($DBFarmRole->ReplaceToAMI)
                {
                    $this->Logger->debug("[FarmID: {$DBFarm->ID}] AMIID {$DBFarmRole->AMIID} need to be replaced with AMIID {$DBFarmRole->ReplaceToAMI}");
                	
                	$num_instances = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE ami_id=? AND farmid = ? AND state != ?", array(
                		$DBFarmRole->AMIID,
                		$DBFarmRole->FarmID,
                		INSTANCE_STATE::TERMINATED
                	));
                 	if ($num_instances == 0)
                    {
                        $delete_old_ami = false;
                    	
                    	if ($DBFarmRole->GetRoleOrigin() == ROLE_TYPE::SHARED)
                    		$sync_complete = true;
                        elseif ($DBFarmRole->GetRoleOrigin() == ROLE_TYPE::CUSTOM)
                        {
                        	if ($DBFarmRole->GetRoleName() == $db->GetOne("SELECT name FROM roles WHERE ami_id='{$DBFarmRole->ReplaceToAMI}'"))
                            {
                            	if ($db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE ami_id='{$DBFarmRole->AMIID}' AND state != '".INSTANCE_STATE::TERMINATED."' AND farmid IN (SELECT id FROM farms WHERE clientid='{$DBFarm->ClientID}')") == 0)
                            	{
									$this->Logger->info("Deleting old role AMI ('{$DBFarmRole->AMIID}') from database.");
                                    $delete_old_ami = $DBFarmRole->AMIID;
                                    $sync_complete = true;
                            	}
                            	else
                            	{
                            		$this->Logger->info("AMI ('{$DBFarmRole->AMIID}') used on another farm. Waiting until all instances swaped.");
                            	}
                            }
                            else
                            	$sync_complete = true;
                        }
                        
                        if ($sync_complete)
                        {
                        	$new_role_name = $db->GetOne("SELECT name FROM roles WHERE ami_id='{$DBFarmRole->ReplaceToAMI}'");
							$old_role_name = $DBFarmRole->GetRoleName();
                        	
                        	$this->Logger->info("[FarmID: {$DBFarm->ID}] Role '{$DBFarmRole->GetRoleName()}' successfully synchronized.");
			
                        	if ($DBFarmRole->GetRoleOrigin() != ROLE_TYPE::SHARED && $new_role_name == $old_role_name)
                        	{
                        		$db->Execute("UPDATE farm_roles SET ami_id='{$DBFarmRole->ReplaceToAMI}', replace_to_ami='' WHERE ami_id='{$DBFarmRole->AMIID}' AND farmid IN (SELECT id FROM farms WHERE clientid='{$DBFarm->ClientID}')");
                        		$db->Execute("UPDATE zones SET ami_id='{$DBFarmRole->ReplaceToAMI}', role_name='{$new_role_name}', isobsoleted='1' WHERE ami_id='{$DBFarmRole->AMIID}' AND clientid='{$DBFarm->ClientID}'");
	                        	if ($old_role_name != $new_role_name)
	                        	{
	                        		try
	                        		{
	                        			$db->Execute("UPDATE records SET `rkey` = REPLACE(`rkey`, '{$old_role_name}', '{$new_role_name}') WHERE issystem='1' AND zoneid IN (SELECT id FROM zones WHERE farmid IN (SELECT id FROM farms WHERE clientid='{$DBFarm->ClientID}'))");
	                        		}
	                        		catch(Exception $e){}
	                        	}                        		
                        	}
                        	else
                        	{
                        		$db->Execute("UPDATE farm_roles SET ami_id='{$DBFarmRole->ReplaceToAMI}', replace_to_ami='' WHERE ami_id='{$DBFarmRole->AMIID}' AND farmid='{$DBFarm->ID}'");
                        		$db->Execute("UPDATE zones SET ami_id='{$DBFarmRole->ReplaceToAMI}', role_name='{$new_role_name}', isobsoleted='1' WHERE ami_id='{$DBFarmRole->AMIID}' AND clientid='{$DBFarm->ClientID}' AND farmid='{$DBFarm->ID}'");
                        		if ($old_role_name != $new_role_name)
	                        	{
	                        		try
	                        		{
	                        			$db->Execute("UPDATE records SET `rkey` = REPLACE(`rkey`, '{$old_role_name}', '{$new_role_name}') WHERE issystem='1' AND zoneid IN (SELECT id FROM zones WHERE farmid='{$DBFarm->ID}')");
	                        		}
	                        		catch(Exeption $e){}
	                        	} 
                        	}
                        	
                        	                        	
                        	$db->Execute("UPDATE roles SET `replace`='' WHERE ami_id='{$DBFarmRole->ReplaceToAMI}'");
                        	
                        	if ($delete_old_ami)
                        	{
                        		$db->Execute("DELETE FROM roles WHERE ami_id=? AND roletype=?", array(
                        			$delete_old_ami,
                        			ROLE_TYPE::CUSTOM
                        		));
                        	}
                        }
                    }
                    else 
                    {
                        $this->Logger->warn("[FarmID: {$DBFarm->ID}] Role '{$DBFarmRole->GetRoleName()}' being synchronized. {$num_instances} instances still running on the old AMI. This role will not be checked by poller.");
                        
                        $chk = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state IN(?,?) AND ami_id='{$DBFarmRole->ReplaceToAMI}' AND farmid='{$DBFarm->ID}'", array(INSTANCE_STATE::PENDING, INSTANCE_STATE::INIT));
                        if ($chk != 0)
                        {
                            $this->Logger->info("There is one pending instance being swapped Skipping next instance swap until previous one will boot up.");
                            continue;
                        }
                        
                   		$chk = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state = ? AND ami_id='{$DBFarmRole->AMIID}' AND farmid='{$DBFarm->ID}'", array(INSTANCE_STATE::PENDING_TERMINATE));
                        if ($chk != 0)
                        {
                            $this->Logger->info("There is an instance scheduled for termination. Waiting...");
                            continue;
                        }
                        
                        $this->Logger->info("No pending instances with new AMI found.");
                        
                        // Terminate old instance
                        try 
            			{            
           					$old_instances = $db->GetAll("SELECT * FROM farm_instances WHERE ami_id='{$DBFarmRole->AMIID}' AND farmid='{$DBFarm->ID}' AND state NOT IN (?,?) ORDER BY id ASC", array(INSTANCE_STATE::PENDING_TERMINATE, INSTANCE_STATE::TERMINATED));
           					
           					$ami_info = $db->GetRow("SELECT * FROM roles WHERE ami_id='{$DBFarmRole->ReplaceToAMI}'");
            				if (!$ami_info)
           					{
           						$this->Logger->warn("Cannot replace farm ami to new one. Role with ami '{$DBFarmRole->ReplaceToAMI}' was removed.");
           						$db->Execute("UPDATE farm_roles SET replace_to_ami='' WHERE id='{$DBFarmRole->ID}'");
           						continue;
           					}
           					
            			    foreach ($old_instances as $old_instance)
            			    {            			    	
            			    	if ($old_instance['isdbmaster'] == 1 && $ami_info['ismasterbundle'])
            			    	{
            			    		$db->Execute("UPDATE farm_instances SET ami_id=?, role_name=? WHERE id=?",
            			    			array($DBFarmRole->ReplaceToAMI, $ami_info['name'], $old_instance['id'])
            			    		);
            			    		
            			    		continue;
            			    	}
            			    	
            			    	$DBFarmRole = DBFarmRole::LoadByID($old_instance['farm_roleid']);
            			    	
            			    	// Start new instance with new AMI_ID
            			    	//
            			        $res = Scalr::RunInstance($DBFarmRole, $ami_info['ami_id'], false, true, $old_instance['avail_zone'], $old_instance['index']);
                                if ($res)
                                {
                                    $this->Logger->warn(new FarmLogMessage($DBFarm->ID, "The instance ('{$ami_info["ami_id"]}') '{$old_instance['instance_id']}' will be terminated after instance '{$res}' will boot up."));
                                    $db->Execute("UPDATE farm_instances SET replace_iid='{$old_instance['instance_id']}', `index`='{$old_instance['index']}' WHERE instance_id='{$res}'");
                                    break;
                                }
            			    }
            			}
            			catch (Exception $e)
            			{
            				$this->Logger->fatal(new FarmLogMessage($DBFarm->ID, "Cannot launch new instances to replace old ones. ".$e->getMessage()));
            			}
                    }
                    
                    continue;
                }

                $role_running_instances = 0;
                $role_pending_instances = 0;
                $role_terminated_instances = 0;
                $role_running_instances_with_la = 0;
                $role_instances_by_time = array();
                                    
                $this->Logger->info("[FarmID: {$DBFarm->ID}] Begin check '{$DBFarmRole->GetRoleName()}' role instances...");
                
                $items = (array)$ec2_items[$DBFarmRole->GetRoleName()];
                
                foreach ($items as $item)
                {                	
                	$db_item_info = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=? AND farmid=?", array($item->instanceId, $DBFarm->ID));                        
                    if ($db_item_info)
                    {
                        if ($db_item_info['state'] == INSTANCE_STATE::PENDING_TERMINATE || $db_item_info['state'] == INSTANCE_STATE::TERMINATED)
                        	continue;
                    	
                    	$role_instance_ids[$item->instanceId] = $item;
                        $this->Logger->info("[FarmID: {$DBFarm->ID}] Checking '{$item->instanceId}' instance...");
                        
                        // IF instance on EC2 - running AND db state of instance - running
                        if ($item->instanceState->name == 'running' && ($db_item_info["state"] == INSTANCE_STATE::RUNNING))
                        {
                            if ($db_item_info["isrebootlaunched"] == 0)
                            {
                                $instance_dns = $item->dnsName;
                                $community = $DBFarm->Hash;
                                
                                if ($instance_dns)
                                {
        	                    	$SNMP->Connect($db_item_info['external_ip'], null, $community, null, null, true);
                                	$res = $SNMP->Get(".1.3.6.1.4.1.2021.10.1.3.3");
                                }
                                else
                                	continue;
                                
                                if ($res === false)
                                {
                                	//Check Manual change of IP address
                                	$ip = @gethostbyname($instance_dns);
                                	
                                	if ($ip != $instance_dns && substr($ip, 0, 3) == '10.')
                                    {
                                    	preg_match("/([0-9]{2,3}-[0-9]{1,3}-[0-9]{1,3}-[0-9]{1,3})/si", $instance_dns, $matches);
										$ip = str_replace("-", ".", $matches[1]);
                                    }
                                	
                                	if ($ip && $ip != $instance_dns && $ip != $db_item_info['external_ip'])
                                	{
                                		$old_ip = $db_item_info['external_ip'];
                                		
                                		try
                                		{
                                			$this->Logger->info(new FarmLogMessage(
                                				$DBFarm->ID, 
                                				sprintf(_("Releasing old address '%s' from EC2"), $old_ip)
                                			));
                                			
                                			//$AmazonEC2Client->ReleaseAddress($old_ip);
                                		}
                                		catch(Exception $e)
                                		{
                                			if (!stristr($e->getMessage(), "does not belong to you."))
                                			{
	                                			$this->Logger->error(new FarmLogMessage(
	                                				$DBFarm->ID, 
	                                				sprintf(_("Cannot release address '%s': %s"), $old_ip, $e->getMessage())
	                                			));
                                			}
                                		}
                                		
                                		$db->Execute("UPDATE elastic_ips SET ipaddress=? WHERE ipaddress=?",
                                			array($ip, $old_ip)
                                		);
                                		
                                		// Change IP
                                		Scalr::FireEvent(
                                    		$db_item_info['farmid'],
                                    		new IPAddressChangedEvent(DBInstance::LoadByID($db_item_info['id']), $ip) 
                                    	);
                                    	
                                    	continue 2;		
                                	}
                                }
                                else 
                                {
                                    if ($db_item_info['isipchanged'] == 1)
                                    {
                                    	$ip = @gethostbyname($instance_dns);
                                    	
                                   		if ($ip != $instance_dns && substr($ip, 0, 3) == '10.')
                                    	{
                                    		preg_match("/([0-9]{2,3}-[0-9]{1,3}-[0-9]{1,3}-[0-9]{1,3})/si", $instance_dns, $matches);
											$ip = str_replace("-", ".", $matches[1]);
                                    	}
                                    	
                                		if ($ip && $ip != $instance_dns)
                                		{
	                                    	Scalr::FireEvent(
	                                    		$db_item_info['farmid'],
	                                    		new IPAddressChangedEvent(DBInstance::LoadByID($db_item_info['id']), $ip) 
	                                    	);
	                                    	
	                                    	continue 2;
                                		}
                                    }
                                }
                            }
                            else 
                            {
                                $this->Logger->debug("[FarmID: {$DBFarm->ID}] Instance '{$item->instanceId}' rebooting...");
                                
                                $dtrebootstart = strtotime($db_item_info["dtrebootstart"]);
                                
                                $reboot_timeout = (int)$db->GetOne("SELECT `reboot_timeout` FROM farm_roles WHERE farmid=? AND ami_id=? OR replace_to_ami=?", 
                                	array($DBFarm->ID, $db_item_info['ami_id'], $db_item_info['ami_id'])
                                );	
								$reboot_timeout = $reboot_timeout > 0 ? $reboot_timeout : CONFIG::$REBOOT_TIMEOUT;
                                
                                if ($dtrebootstart+$reboot_timeout < time())
                                {                                        
                                    // Add entry to farm log
                    				$this->Logger->warn(new FarmLogMessage($DBFarm->ID, "Instance '{$db_item_info["instance_id"]}' did not send 'rebootFinish' event in {$reboot_timeout} seconds after reboot start. Considering it broken. Terminating instance."));
                                    
                                    try
                                    {
                                        $this->Logger->info(new FarmLogMessage($DBFarm->ID, "Scheduled termination for instance '{$db_item_info["instance_id"]}' ({$db_item_info["external_ip"]}). It will be terminated in 3 minutes."));
						                Scalr::FireEvent($DBFarm->ID, new BeforeHostTerminateEvent(DBInstance::LoadByID($db_item_info['id'])));
                                    }
                                    catch (Exception $err)
                                    {
                                        $this->Logger->fatal($err->getMessage());
                                    }
                                }
                            }
                        }
                        // IF instance on EC2 - not running AND db state of instance - running
                        elseif ($item->instanceState->name != 'running' && $db_item_info["state"] == INSTANCE_STATE::RUNNING)
                        {
                            $this->Logger->warn("[FarmID: {$DBFarm->ID}] {$item->instanceId}' have state '{$item->instanceState->name}'");
                            
                        	try
	                        {
	                            Scalr::FireEvent($DBFarm->ID, new HostDownEvent(DBInstance::LoadByID($db_item_info['id'])));
	                            // Add entry to farm log
                    			$this->Logger->info(new FarmLogMessage($DBFarm->ID, "'{$db_item_info["instance_id"]}' ({$db_item_info["external_ip"]}) Terminated!"));
	                        }
	                        catch (Exception $e)
	                        {
	                            
	                        }
                            
                            //$role_terminated_instances++;
                        }
                        elseif ($item->instanceState->name == 'pending')
                        {
                            //$role_pending_instances++;
                        }
                        elseif ($item->instanceState->name == 'running' && $db_item_info["state"] != INSTANCE_STATE::RUNNING)
                        {
                            //
                            $dtadded = strtotime($db_item_info["dtadded"]);
                            
                            $launch_timeout = (int)$db->GetOne("SELECT `launch_timeout` FROM farm_roles WHERE farmid=? AND ami_id=? OR replace_to_ami=?", 
                            	array($DBFarm->ID, $db_item_info['ami_id'], $db_item_info['ami_id'])
                            );	
							$launch_timeout = $launch_timeout > 0 ? $launch_timeout : CONFIG::$LAUNCH_TIMEOUT;
                            
							if (!$db_item_info["internal_ip"])
							{
								$event = "hostInit";
								$scripting_event = EVENT_TYPE::HOST_INIT;
							}
                            else
                            { 
								$event = "hostUp";
								$scripting_event = EVENT_TYPE::HOST_UP;
                            }
							
							$scripting_timeout = (int)$db->GetOne("SELECT sum(timeout) FROM farm_role_scripts  
								WHERE event_name=? AND 
								farm_roleid=? AND issync='1'",
								array($scripting_event, $db_item_info['farm_roleid'])
							);
							
							if ($scripting_timeout)
								$launch_timeout = $launch_timeout+$scripting_timeout;
							
							$this->Logger->info("[FarmID: {$DBFarm->ID}] Check event timeout: {$launch_timeout}");
								
                            if ($dtadded+$launch_timeout < time())
                            {
                                                                    
                                // Add entry to farm log
                    			$this->Logger->warn(new FarmLogMessage($DBFarm->ID, "Instance '{$db_item_info["instance_id"]}' did not send '{$event}' event in {$launch_timeout} seconds after launch (Try increasing timeouts in role settings). Considering it broken. Terminating instance."));
                                
                                try
                                {
                                    $this->Logger->info(new FarmLogMessage($DBFarm->ID, "Scheduled termination for instance '{$db_item_info["instance_id"]}' ({$db_item_info["external_ip"]}). It will be terminated in 3 minutes."));
						            Scalr::FireEvent($DBFarm->ID, new BeforeHostTerminateEvent(DBInstance::LoadByID($db_item_info['id'])));
                                }
                                catch (Exception $err)
                                {
                                    $this->Logger->fatal($err->getMessage());
                                }
                            }
                            //
                            //
                            //$role_pending_instances++; 
                        }
                    }
                    
                } //for each items

            }
        }
    }
?>