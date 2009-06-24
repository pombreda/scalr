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
            
            $this->ThreadArgs = $db->GetAll("SELECT farms.*, clients.isactive FROM farms 
            	INNER JOIN clients ON clients.id = farms.clientid WHERE clients.isactive='1'"
            );
                        
            $this->Logger->info("Found ".count($this->ThreadArgs)." farms.");
        }
        
        public function OnEndForking()
        {
			$db = Core::GetDBInstance(null, true);
        	
			$trap_wait_timeout = 120; // 120 seconds
			
			try
			{
				// Check sync ami_roles (Rebundle trap received or not?)
	            $ami_roles = $db->GetAll("SELECT * FROM ami_roles WHERE iscompleted='0' AND rebundle_trap_received='0'");
	            foreach ($ami_roles as $ami_role)
	            {
	            	if (strtotime($ami_role['dtbuildstarted'])+$trap_wait_timeout < time())
	            	{
	            		$this->Logger->warn("Role '{$ami_role['name']}' sync failed. Instance did not reply on SNMP trap.");
	            		
	            		$db->Execute("UPDATE ami_roles SET iscompleted='2', fail_details=?, `replace`='' WHERE id=?",
	            			array("Instance did not reply on SNMP trap. Make sure that snmpd and snmptrapd are running.", $ami_role["id"]));
	            	}
	            }
	            
				// Check timeouted ami_roles sync
	            $ami_roles = $db->GetAll("SELECT * FROM ami_roles WHERE iscompleted='0'");
	            foreach ($ami_roles as $ami_role)
	            {
	            	$sync_timeout = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array('sync_timeout', $ami_role['client_id']));
    				$sync_timeout = $sync_timeout ? $sync_timeout : CONFIG::$SYNC_TIMEOUT;
	            	
	            	if ((strtotime($ami_role['dtbuildstarted'])+($sync_timeout*60)) < time())
	            	{
	            		$this->Logger->warn("Role '{$ami_role['name']}' sync timeouted.");
	            		
	            		$db->Execute("UPDATE ami_roles SET iscompleted='2', fail_details=?, `replace`='' WHERE id=?",
	            			array("Aborted due to timeout ({$sync_timeout} minutes).", $ami_role["id"]));
	            	}
	            }
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
            
            define("SUB_TRANSACTIONID", posix_getpid());
            define("LOGGER_FARMID", $farminfo["id"]);
            
            $this->Logger->info("[".SUB_TRANSACTIONID."] Begin polling farm (ID: {$farminfo['id']}, Name: {$farminfo['name']}, Status: {$farminfo['status']})");
                        
            $DNSZoneController = new DNSZoneControler();
            
            
            //
            // Collect information from database
            //
            $Client = Client::Load($farminfo['clientid']);
            
            $farm_amis = $db->GetAll("SELECT * FROM farm_amis WHERE farmid='{$farminfo['id']}'");
            $this->Logger->debug("[FarmID: {$farminfo['id']}] Farm used ".count($farm_amis)." AMIs");
            
            $farm_instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid='{$farminfo['id']}'");
            $this->Logger->info("[FarmID: {$farminfo['id']}] Found ".count($farm_instances)." farm instances in database");

            if ($farminfo['status'] == FARM_STATUS::TERMINATED && count($farm_instances) == 0)
            	exit();
                        
            // Get AmazonEC2 Object
            $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region'])); 
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
                        
			try
			{
	            // Get instances from EC2
	            $this->Logger->debug("[FarmID: {$farminfo['id']}] Receiving instances info from EC2...");
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
                $this->Logger->debug("[FarmID: {$farminfo['id']}] Found ".count($result->reservationSet->item)." total instances...");
                $num = 0;
                foreach ($result->reservationSet->item as $item)
                {
					$ami_role_name = $db->GetOne("SELECT role_name FROM farm_instances WHERE instance_id=? AND farmid=?", 
						array($item->instancesSet->item->instanceId, $farminfo['id'])
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
                
                $this->Logger->debug("[FarmID: {$farminfo['id']}] Found {$num} instances");
            }
            else 
                $this->Logger->debug("[FarmID: {$farminfo['id']}] No instances found for this client.");
                
            
            foreach ($farm_instances as $farm_instance)
            {
                $instance_terminated = false;
                
                if (!isset($ec2_items_by_instanceid[$farm_instance["instance_id"]]))
                {
                    $farm_instance['isrebootlaunched'] = 0;
                	
                	// Add entry to farm log
                    $this->Logger->warn(new FarmLogMessage($farminfo['id'], "Instance '{$farm_instance["instance_id"]}' found in database but not found on EC2. Crashed."));
                	Scalr::FireEvent($farminfo['id'], new HostCrashEvent($farm_instance));
                }
                else 
                {
                    switch ($ec2_items_by_instanceid[$farm_instance["instance_id"]]->instanceState->name)
                    {
                        case "terminated":
                            
                            $this->Logger->warn("[FarmID: {$farminfo['id']}] Instance '{$farm_instance["instance_id"]}' not running (Terminated).");
                            $instance_terminated = true;
                            
                            break;
                            
                        case "shutting-down":
                            
                            $this->Logger->warn("[FarmID: {$farminfo['id']}] Instance '{$farm_instance["instance_id"]}' not running (Shutting Down).");
                            $instance_terminated = true;
                            
                            break;
                    }
                }
                
                if ($instance_terminated)
                {
                    $farm_instance['isrebootlaunched'] = 0;
                	Scalr::FireEvent($farminfo['id'], new HostDownEvent($farm_instance));
                }
                
                if ($farm_instance['state'] == INSTANCE_STATE::PENDING_TERMINATE)
                {
                	if ($farm_instance['dtshutdownscheduled'] && strtotime($farm_instance['dtshutdownscheduled'])+60*3 < time())
                	{
						$this->Logger->warn(new FarmLogMessage($farminfo['id'], "Terminating instance '{$farm_instance["instance_id"]}'..."));
                		$AmazonEC2Client->TerminateInstances(array($farm_instance['instance_id']));
                	}
                }
            }

        	//
            // Check farm status
            //
            if ($db->GetOne("SELECT status FROM farms WHERE id=?", array($farminfo["id"])) != FARM_STATUS::RUNNING)
            {
            	$this->Logger->debug("[FarmID: {$farminfo['id']}] Farm is not running.");
            	return;
            }
            
            $db_amis = $db->Execute("SELECT * FROM farm_amis WHERE farmid=? ORDER BY launch_index ASC", array($farminfo["id"]));
                        
            while ($db_ami = $db_amis->FetchRow())
            {
                if (!$db_ami)
                    continue;
                
                $ami = $db_ami["ami_id"];
                $roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($ami));
                $role = $roleinfo["name"];
                    
                if ($db_ami["replace_to_ami"])
                {
                    $this->Logger->debug("[FarmID: {$farminfo['id']}] AMIID {$ami} need to be replaced with AMIID {$db_ami["replace_to_ami"]}");
                	
                	$num_instances = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE ami_id='{$ami}' AND farmid='{$farminfo['id']}'");
                 	if ($num_instances == 0)
                    {
                        $delete_old_ami = false;
                    	
                    	if ($roleinfo["roletype"] == ROLE_TYPE::SHARED)
                    		$sync_complete = true;
                        elseif ($roleinfo["roletype"] == ROLE_TYPE::CUSTOM)
                        {
                        	if ($roleinfo['name'] == $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$db_ami["replace_to_ami"]}'"))
                            {
                            	if ($db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE ami_id='{$ami}'") == 0)
                            	{
									$this->Logger->info("Deleting old role AMI ('{$roleinfo["ami_id"]}') from database.");
                                    $delete_old_ami = $ami;
                                    $sync_complete = true;
                            	}
                            	else
                            	{
                            		$this->Logger->info("AMI ('{$roleinfo["ami_id"]}') used on another farm. Waiting until all instances swaped.");
                            	}
                            }
                            else
                            	$sync_complete = true;
                        }
                        
                        if ($sync_complete)
                        {
                        	$role_name = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$db_ami["replace_to_ami"]}'");
                        	
                        	$this->Logger->info("[FarmID: {$farminfo['id']}] Role '{$role_name}' successfully synchronized.");
                        	
                        	$db->Execute("UPDATE elastic_ips SET role_name=? WHERE farmid=? AND role_name=?",
                        		array($role_name, $farminfo['id'], $roleinfo['name'])
                        	);
                        	
                        	$db->Execute("UPDATE farm_ebs SET role_name=? WHERE farmid=? AND role_name=?",
                        		array($role_name, $farminfo['id'], $roleinfo['name'])
                        	);
                        	
                        	$ebs = $db->GetAll("SELECT * FROM farm_ebs WHERE farmid=?", array($farminfo['id']));
                        	
                        	$db->Execute("UPDATE ebs_arrays SET role_name=? WHERE farmid=? AND role_name=?",
	                        	array($role_name, $farminfo['id'], $roleinfo['name'])
	                        );
                        	
                        	$db->Execute("UPDATE vhosts SET role_name=? WHERE farmid=? AND role_name=?",
                        		array($role_name, $farminfo['id'], $roleinfo['name'])
                        	);
                        	
                        	if ($roleinfo["roletype"] != ROLE_TYPE::SHARED)
                        	{
                        		$db->Execute("UPDATE farm_amis SET ami_id='{$db_ami["replace_to_ami"]}', replace_to_ami='' WHERE ami_id='{$ami}' AND farmid IN (SELECT id FROM farms WHERE clientid='{$farminfo['clientid']}')");
                        		$db->Execute("UPDATE zones SET ami_id='{$db_ami["replace_to_ami"]}', role_name='{$role_name}' WHERE ami_id='{$ami}' AND clientid='{$farminfo['clientid']}'");
                        		
                        		// Update ami in role scripts
                        		$db->Execute("UPDATE farm_role_scripts SET ami_id='{$db_ami["replace_to_ami"]}' WHERE ami_id='{$ami}' AND farmid IN (SELECT id FROM farms WHERE clientid='{$farminfo['clientid']}')");
                        		$db->Execute("UPDATE farm_role_options SET ami_id='{$db_ami["replace_to_ami"]}' WHERE ami_id='{$ami}' AND farmid IN (SELECT id FROM farms WHERE clientid='{$farminfo['clientid']}')");
                        	}
                        	else
                        	{
                        		$db->Execute("UPDATE farm_amis SET ami_id='{$db_ami["replace_to_ami"]}', replace_to_ami='' WHERE ami_id='{$ami}' AND farmid='{$farminfo['id']}'");
                        		$db->Execute("UPDATE zones SET ami_id='{$db_ami["replace_to_ami"]}', role_name='{$role_name}' WHERE ami_id='{$ami}' AND clientid='{$farminfo['clientid']}' AND farmid='{$farminfo['id']}'");
                        		
                        		// Update ami in role scripts
                        		$db->Execute("UPDATE farm_role_scripts SET ami_id='{$db_ami["replace_to_ami"]}' WHERE ami_id='{$ami}' AND farmid='{$farminfo['id']}'");
                        		$db->Execute("UPDATE farm_role_options SET ami_id='{$db_ami["replace_to_ami"]}' WHERE ami_id='{$ami}' AND farmid='{$farminfo['id']}'");
                        	}
                        	
                        	$db->Execute("UPDATE ami_roles SET `replace`='' WHERE ami_id='{$db_ami["replace_to_ami"]}'");
                        	
                        	if ($delete_old_ami)
                        	{
                        		$db->Execute("DELETE FROM ami_roles WHERE ami_id='{$delete_old_ami}'");
                        	}
                        }
                    }
                    else 
                    {
                        $this->Logger->warn("[FarmID: {$farminfo['id']}] Role '{$role}' being synchronized. {$num_instances} instances still running on the old AMI. This role will not be checked by poller.");
                        
                        $chk = $db->GetRow("SELECT * FROM farm_instances WHERE state IN(?,?) AND ami_id='{$db_ami["replace_to_ami"]}' AND farmid='{$farminfo['id']}'", array(INSTANCE_STATE::PENDING, INSTANCE_STATE::INIT));
                        if ($chk)
                        {
                            $this->Logger->info("There is one pending instance being swapped Skipping next instance swap until previous one will boot up.");
                            continue;
                        }
                        
                   		$chk = $db->GetRow("SELECT * FROM farm_instances WHERE state = ? AND ami_id='{$ami}' AND farmid='{$farminfo['id']}'", array(INSTANCE_STATE::PENDING_TERMINATE));
                        if ($chk)
                        {
                            $this->Logger->info("There is an instance scheduled for termination. Waiting...");
                            continue;
                        }
                        
                        $this->Logger->info("No pending instances with new AMI found.");
                        
                        // Terminate old instance
                        try 
            			{            
           					$old_instances = $db->GetAll("SELECT * FROM farm_instances WHERE ami_id='{$ami}' AND farmid='{$farminfo['id']}' AND state != ? ORDER BY id ASC", array(INSTANCE_STATE::PENDING_TERMINATE));
           					
           					$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$db_ami["replace_to_ami"]}'");
            				if (!$ami_info)
           					{
           						$this->Logger->warn("Cannot replace farm ami to new one. Role with ami '{$db_ami["replace_to_ami"]}' was removed.");
           						$db->Execute("UPDATE farm_amis SET replace_to_ami='' WHERE id='{$db_ami['id']}'");
           						continue;
           					}
           					
            			    foreach ($old_instances as $old_instance)
            			    {            			    	
            			    	if ($old_instance['isdbmaster'] == 1 && $ami_info['ismasterbundle'])
            			    	{
            			    		$db->Execute("UPDATE farm_instances SET ami_id=?, role_name=? WHERE id=?",
            			    			array($db_ami["replace_to_ami"], $ami_info['name'], $old_instance['id'])
            			    		);
            			    		
            			    		continue;
            			    	}
            			    	
            			    	// Start new instance with new AMI_ID
            			        $res = Scalr::RunInstance(CONFIG::$SECGROUP_PREFIX.$ami_info["name"], $farminfo['id'], $ami_info["name"], $farminfo['hash'], $ami_info["ami_id"], false, true, $old_instance['avail_zone'], $old_instance['index']);
                                if ($res)
                                {
                                    $this->Logger->warn(new FarmLogMessage($farminfo['id'], "The instance ('{$ami_info["ami_id"]}') '{$old_instance['instance_id']}' will be terminated after instance '{$res}' will boot up."));
                                    $db->Execute("UPDATE farm_instances SET replace_iid='{$old_instance['instance_id']}', `index`='{$old_instance['index']}' WHERE instance_id='{$res}'");
                                }
                                else 
                                    $this->Logger->error("Cannot start new instance with new AMI. Analyse log for more information");
            			    }
            			}
            			catch (Exception $e)
            			{
            				$this->Logger->fatal(new FarmLogMessage($farminfo['id'], "Cannot launch new instances to replace old ones. ".$e->getMessage()));
            			}
                    }
                    
                    continue;
                }

                $role_running_instances = 0;
                $role_pending_instances = 0;
                $role_terminated_instances = 0;
                $role_running_instances_with_la = 0;
                $role_instances_by_time = array();
                                    
                $this->Logger->info("[FarmID: {$farminfo['id']}] Begin check '{$role}' role instances...");
                
                $items = $ec2_items[$role];
                
                foreach ((array)$items as $item)
                {                	
                	$db_item_info = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=? AND farmid=?", array($item->instanceId, $farminfo["id"]));                        
                    if ($db_item_info)
                    {
                        if ($db_item_info['state'] == INSTANCE_STATE::PENDING_TERMINATE)
                        	continue;
                    	
                    	$role_instance_ids[$item->instanceId] = $item;
                        $this->Logger->info("[FarmID: {$farminfo['id']}] Checking '{$item->instanceId}' instance...");
                        
                        // IF instance on EC2 - running AND db state of instance - running
                        if ($item->instanceState->name == 'running' && ($db_item_info["state"] == INSTANCE_STATE::RUNNING))
                        {
                            if ($db_item_info["isrebootlaunched"] == 0)
                            {
                                $instance_dns = $item->dnsName;
                                $community = $farminfo["hash"];
                                
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
                                				$farminfo['id'], 
                                				sprintf(_("Releasing old address '%s' from EC2"), $old_ip)
                                			));
                                			
                                			$AmazonEC2Client->ReleaseAddress($old_ip);
                                		}
                                		catch(Exception $e)
                                		{
                                			$this->Logger->error(new FarmLogMessage(
                                				$farminfo['id'], 
                                				sprintf(_("Cannot release address '%s': %s"), $old_ip, $e->getMessage())
                                			));
                                		}
                                		
                                		$db->Execute("UPDATE elastic_ips SET ipaddress=? WHERE ipaddress=?",
                                			array($ip, $old_ip)
                                		);
                                		
                                		// Change IP
                                		Scalr::FireEvent(
                                    		$db_item_info['farmid'],
                                    		new IPAddressChangedEvent($db_item_info, $ip) 
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
	                                    		new IPAddressChangedEvent($db_item_info, $ip) 
	                                    	);
	                                    	
	                                    	continue 2;
                                		}
                                    }
                                }
                            }
                            else 
                            {
                                $this->Logger->debug("[FarmID: {$farminfo['id']}] Instance '{$item->instanceId}' rebooting...");
                                
                                $dtrebootstart = strtotime($db_item_info["dtrebootstart"]);
                                
                                $reboot_timeout = (int)$db->GetOne("SELECT `reboot_timeout` FROM farm_amis WHERE farmid=? AND ami_id=? OR replace_to_ami=?", array($farminfo['id'], $db_item_info['ami_id'], $db_item_info['ami_id']));	
								$reboot_timeout = $reboot_timeout > 0 ? $reboot_timeout : CONFIG::$REBOOT_TIMEOUT;
                                
                                if ($dtrebootstart+$reboot_timeout < time())
                                {                                        
                                    // Add entry to farm log
                    				$this->Logger->warn(new FarmLogMessage($farminfo['id'], "Instance '{$db_item_info["instance_id"]}' did not send 'rebootFinish' event in {$reboot_timeout} seconds after reboot start. Considering it broken. Terminating instance."));
                                    
                                    try
                                    {
                                        $this->Logger->info(new FarmLogMessage($farminfo['id'], "Scheduled termination for instance '{$db_item_info["instance_id"]}' ({$db_item_info["external_ip"]}). It will be terminated in 3 minutes."));
						                Scalr::FireEvent($farminfo['id'], new BeforeHostTerminateEvent($db_item_info));
                                    }
                                    catch (Exception $err)
                                    {
                                        $this->Logger->fatal($err->getMessage());
                                    }
                                }
                            }
                                 
                            $role_running_instances++;
                            
                            if ($role_instances_by_time[strtotime($item->launchTime)])
                                $role_instances_by_time[strtotime($item->launchTime)+rand(10, 99)] = $item;
                            else 
                                $role_instances_by_time[strtotime($item->launchTime)] = $item;
                                
                            ksort($role_instances_by_time);
                        }
                        // IF instance on EC2 - not running AND db state of instance - running
                        elseif ($item->instanceState->name != 'running' && $db_item_info["state"] == INSTANCE_STATE::RUNNING)
                        {
                            $this->Logger->warn("[FarmID: {$farminfo['id']}] {$item->instanceId}' have state '{$item->instanceState->name}'");
                            
                        	try
	                        {
	                            Scalr::FireEvent($farminfo['id'], new HostDownEvent($db_item_info));
	                            // Add entry to farm log
                    			$this->Logger->info(new FarmLogMessage($farminfo['id'], "'{$db_item_info["instance_id"]}' ({$db_item_info["external_ip"]}) Terminated!"));
	                        }
	                        catch (Exception $e)
	                        {
	                            
	                        }
                            
                            $role_terminated_instances++;
                        }
                        elseif ($item->instanceState->name == 'pending')
                        {
                            $role_pending_instances++;
                        }
                        elseif ($item->instanceState->name == 'running' && $db_item_info["state"] != INSTANCE_STATE::RUNNING)
                        {
                            //
                            $dtadded = strtotime($db_item_info["dtadded"]);
                            
                            $launch_timeout = (int)$db->GetOne("SELECT `launch_timeout` FROM farm_amis WHERE farmid=? AND ami_id=? OR replace_to_ami=?", array($farminfo['id'], $db_item_info['ami_id'], $db_item_info['ami_id']));	
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
								WHERE farmid=? AND event_name=? AND 
								ami_id=? AND issync='1'",
								array($db_item_info['farmid'], $scripting_event, $db_item_info['ami_id'])
							);
							
							if ($scripting_timeout)
								$launch_timeout = $launch_timeout+$scripting_timeout;
							
							$this->Logger->info("[FarmID: {$farminfo['id']}] Check event timeout: {$launch_timeout}");
								
                            if ($dtadded+$launch_timeout < time())
                            {
                                                                    
                                // Add entry to farm log
                    			$this->Logger->warn(new FarmLogMessage($farminfo['id'], "Instance '{$db_item_info["instance_id"]}' did not send '{$event}' event in {$launch_timeout} seconds after launch (Try increasing timeouts in role settings). Considering it broken. Terminating instance."));
                                
                                try
                                {
                                    $this->Logger->info(new FarmLogMessage($farminfo['id'], "Scheduled termination for instance '{$db_item_info["instance_id"]}' ({$db_item_info["external_ip"]}). It will be terminated in 3 minutes."));
						            Scalr::FireEvent($farminfo['id'], new BeforeHostTerminateEvent($db_item_info));
                                }
                                catch (Exception $err)
                                {
                                    $this->Logger->fatal($err->getMessage());
                                }
                            }
                            //
                            //
                            $role_pending_instances++; 
                        }
                    }
                    
                } //for each items
                	
                $DBFarmRole = DBFarmRole::Load($farminfo['id'], $ami);
                
                //
                // Checking if we need new instances launched
                //
                $need_new_instance = false;
                if (count($role_instances_by_time) == 0)
                {
                    if ($role_pending_instances == 0)
                    {
	                	$need_new_instance = true; 
	                    // Add entry to farm log
	                    $this->Logger->warn(new FarmLogMessage($farminfo['id'], "Disaster: No instances running in role {$role}!"));
                    }
                }
                elseif (count($role_instances_by_time) < $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES))
                {
                    $this->Logger->warn("[FarmID: {$farminfo['id']}] Min count instances for role '{$role}' increased to '".$DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES)."'. Need more instances...");
                    $need_new_instance = true;
                }
                
                if ($farminfo['farm_roles_launch_order'] == 1 && $role_pending_instances > 0)
                {
                	$this->Logger->info("{$role_pending_instances} instances in pending state. Launch roles one-by-one. Waiting...");
                	break;
                }
                    
                if ($need_new_instance)
                {
                    if (count($role_instances_by_time) < $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES))
                    {
                        if ($role_pending_instances > 0)
                        {
                            // Add entry to farm log
                    		$this->Logger->debug(new FarmLogMessage($farminfo['id'], "{$role_pending_instances} instances in pending state. We don't need more instances at this time."));
                        }
                        else 
                        {
                            $instance_id = Scalr::RunInstance(CONFIG::$SECGROUP_PREFIX.$role, $farminfo["id"], $role, $farminfo["hash"], $ami, false, true);
                            
                            if ($instance_id)
                            {
                                $this->Logger->info(new FarmLogMessage($farminfo['id'], "Starting new instance. InstanceID = {$instance_id}."));
                            	if ($farminfo['farm_roles_launch_order'] == 1)
				                {
				                	$this->Logger->info("Launch roles one-by-one. Waiting...");
				                	break;
				                }
                            }
                            else 
                                $this->Logger->error("[FarmID: {$farminfo['id']}] Cannot run new instance! See system log for details.");
                        }
                    }
                    else 
                    {
                        // Add entry to farm log
                    	$this->Logger->info(new FarmLogMessage($farminfo['id'], "Role {$role} is full. MaxInstances (".$DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES).") = Instances count (".count($role_instances_by_time)."). Pending: {$role_pending_instances} instances"));
                    }
                }
            }
        }
    }
?>