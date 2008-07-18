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
            $db = Core::GetDBInstance(null, true);
            
            $this->Logger->info("Fetching completed farms...");
            
            $this->ThreadArgs = $db->GetAll("SELECT farms.*, clients.isactive FROM farms INNER JOIN clients ON clients.id = farms.clientid WHERE farms.status='1' AND clients.isactive='1'");
                        
            $this->Logger->info("Found ".count($this->ThreadArgs)." farms.");
        }
        
        public function OnEndForking()
        {
			$db = Core::GetDBInstance(null, true);
        	
			$trap_wait_timeout = 60; // 60 seconds
			
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
			}
			catch (Exception $e)
			{
				$this->Logger->fatal("Poller::OnEndForking failed: {$e->getMessage()}");
			}
        }
        
        public function StartThread($farminfo)
        {
            $db = Core::GetDBInstance(null, true);
            $SNMP = new SNMP();
            
            define("SUB_TRANSACTIONID", posix_getpid());
            define("LOGGER_FARMID", $farminfo["id"]);
            
            $this->Logger->info("[".SUB_TRANSACTIONID."] Begin polling farm (ID: {$farminfo['id']}, Name: {$farminfo['name']})");
            
            //
            // Check farm status
            //
            if ($db->GetOne("SELECT status FROM farms WHERE id=?", array($farminfo["id"])) != 1)
            {
            	$this->Logger->error("[FarmID: {$farminfo['id']}] Farm terminated by client.");
            	return;
            }
            
            $DNSZoneController = new DNSZoneControler();
            
            
            //
            // Collect information from database
            //
            $this->Logger->info("[FarmID: {$farminfo['id']}] Begin polling...");
                
            $clientinfo = $db->GetRow("SELECT * FROM clients WHERE id='{$farminfo['clientid']}'");
            $this->Logger->info("[FarmID: {$farminfo['id']}] Farm client ID: {$clientinfo['id']}");
            
            $farm_amis = $db->GetAll("SELECT * FROM farm_amis WHERE farmid='{$farminfo['id']}'");
            $this->Logger->info("[FarmID: {$farminfo['id']}] Farm used ".count($farm_amis)." AMIs");
            
            $farm_instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid='{$farminfo['id']}'");
            $this->Logger->info("[FarmID: {$farminfo['id']}] Found ".count($farm_instances)." farm instances in database");

            // Get Crypto object
            $Crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
            $cpwd = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
            
            // Decrypt client prvate key and certificate
            $private_key = $Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
            $certificate = $Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
            
            // Get AmazonEC2 Object
            $AmazonEC2Client = new AmazonEC2($private_key, $certificate);
                        
            // Get instances from EC2
            $this->Logger->info("[FarmID: {$farminfo['id']}] Receiving instances info from EC2...");
            $result = $AmazonEC2Client->DescribeInstances();
            $ec2_items = array();
            $ec2_items_by_instanceid = array();
                                   
            if (!is_array($result->reservationSet->item))
            {
                $item = $result->reservationSet->item;
                $result->reservationSet->item = array($item);
            }
            
            if (is_array($result->reservationSet->item))
            {
                $this->Logger->info("[FarmID: {$farminfo['id']}] Found ".count($result->reservationSet->item)." total instances...");
                $num = 0;
                foreach ($result->reservationSet->item as $item)
                {
					$ami_role_name = $db->GetOne("SELECT role_name FROM farm_instances WHERE instance_id=? AND farmid=?", array($item->instancesSet->item->instanceId, $farminfo['id']));
					if ($ami_role_name)
					{
	                	if (!is_array($ec2_items[$ami_role_name]))
							$ec2_items[$ami_role_name] = array();
	                            
						array_push($ec2_items[$ami_role_name], $item->instancesSet->item);
						$ec2_items_by_instanceid[$item->instancesSet->item->instanceId] = $item->instancesSet->item;
						$num++;
					}
                }
                
                $this->Logger->info("[FarmID: {$farminfo['id']}] Found {$num} instances");
            }
            else 
                $this->Logger->info("[FarmID: {$farminfo['id']}] No instances found for this client.");
                
            
            foreach ($farm_instances as $farm_instance)
            {
                $instance_terminated = false;
                
                if (!isset($ec2_items_by_instanceid[$farm_instance["instance_id"]]))
                {
                    $db->Execute("DELETE FROM farm_instances WHERE farmid=? AND instance_id=?", array($farminfo['id'], $farm_instance["instance_id"]));
                    
                    Scalr::StoreEvent($farminfo['id'], EVENT_TYPE::HOST_CRASH, $farm_instance);
                    
                    // Add entry to farm log
                    $this->Logger->warn(new FarmLogMessage($farminfo['id'], "Instance '{$farm_instance["instance_id"]}' found in database but not found on EC2. Crashed."));
                    
                    $instance_terminated = true;
                }
                else 
                {
                    switch ($ec2_items_by_instanceid[$farm_instance["instance_id"]]->instanceState->name)
                    {
                        case "terminated":
                            
                            $this->Logger->warn("[FarmID: {$farminfo['id']}] Instance '{$farm_instance["instance_id"]}' not running (Terminated).");
                            $db->Execute("DELETE FROM farm_instances WHERE farmid=? AND instance_id=?", array($farminfo['id'], $farm_instance["instance_id"]));
                            
                            $instance_terminated = true;
                            
                            break;
                            
                        case "shutting-down":
                            
                            $this->Logger->warn("[FarmID: {$farminfo['id']}] Instance '{$farm_instance["instance_id"]}' not running (Shutting Down).");
                            $db->Execute("DELETE FROM farm_instances WHERE farmid=? AND instance_id=?", array($farminfo['id'], $farm_instance["instance_id"]));
                            
                            $instance_terminated = true;
                            
                            break;
                    }
                }
                
                if ($instance_terminated)
                {
                    //
                    // SNMP Traps
                    //
                    $alias = $db->GetOne("SELECT alias FROM ami_roles WHERE name='{$farm_instance['role_name']}' AND iscompleted='1'");
                    $Shell = ShellFactory::GetShellInstance();
                    $first_in_role_handled = false;
                    foreach ($farm_instances as $farm_instance_snmp)
                    {
                        if ($farm_instance_snmp["state"] != 'Running' || !$farm_instance_snmp["external_ip"])
                            continue;
                        
                        if ($farm_instance_snmp["id"] == $farm_instance["id"])
                            continue;
                            
                        $isfirstinrole = '0';
                        
                        if ($farm_instance['role_name'] == $farm_instance_snmp["role_name"] && !$first_in_role_handled)
                        {
                            $first_in_role_handled = true;
                            $isfirstinrole = '1';
                        }
                        
                        $res = $Shell->QueryRaw(CONFIG::$SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$farm_instance_snmp['external_ip'].' "" SNMPv2-MIB::snmpTrap.11.0 SNMPv2-MIB::sysName.0 s "'.$alias.'" SNMPv2-MIB::sysLocation.0 s "'.$farm_instance['internal_ip'].'" SNMPv2-MIB::sysDescr.0 s "'.$isfirstinrole.'" 2>&1', true);
                        $this->Logger->debug("[FarmID: {$farminfo['id']}] Sending SNMP Trap 11.0 (hostDown) to '{$farm_instance_snmp['instance_id']}' ('{$farm_instance_snmp['external_ip']}') complete ({$res})");
                    }
    
                    //
                    // Update DNS
                    //
                    $DNSZoneController = new DNSZoneControler();
                    $records = $db->GetAll("SELECT * FROM records WHERE rvalue='{$farm_instance['external_ip']}' OR rvalue='{$farm_instance['internal_ip']}'");
                    foreach ($records as $record)
                    {
                        $zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id='{$record['zoneid']}' AND status != ?", array(ZONE_STATUS::DELETED));
                        
                        if ($zoneinfo)
                        {
                            $db->Execute("DELETE FROM records WHERE id='{$record['id']}'");
                            if (!$DNSZoneController->Update($record["zoneid"]))
                                $this->Logger->error("[FarmID: {$farminfo['id']}] Cannot remove terminated instance '{$farm_instance['instance_id']}' (ExtIP: {$farm_instance['external_ip']}, IntIP: {$farm_instance['internal_ip']}) from DNS zone '{$zoneinfo['zone']}'");
                            else 
                                $this->Logger->debug("[FarmID: {$farminfo['id']}] Terminated instance '{$farm_instance['instance_id']}' ({$farm_instance['external_ip']}) removed from DNS zone '{$zoneinfo['zone']}'");
                        }
                    }
                }
            }
                
            $db_amis = $db->GetAll("SELECT * FROM farm_amis WHERE farmid=?", array($farminfo["id"]));
            
            foreach ($db_amis as $db_ami)
            {
                if (!$db_ami)
                    continue;
                
                $ami = $db_ami["ami_id"];
                $roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($ami));
                $role = $roleinfo["name"];
                    
                if ($db_ami["replace_to_ami"])
                {
                    $num_instances = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE ami_id='{$ami}' AND farmid='{$farminfo['id']}'");
                 	if ($num_instances == 0)
                    {
                        if ($roleinfo["roletype"] == "SHARED")
                    		$sync_complete = true;
                        elseif ($roleinfo["roletype"] == "CUSTOM")
                        {
                        	if ($roleinfo['name'] == $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$db_ami["replace_to_ami"]}'"))
                            {
                            	if ($db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE ami_id='{$ami}'") == 0)
                            	{
									$this->Logger->info("Deleting old role AMI ('{$roleinfo["ami_id"]}') from database.");
                                    $db->Execute("DELETE FROM ami_roles WHERE ami_id='{$ami}'");
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
                        	$this->Logger->debug("[FarmID: {$farminfo['id']}] Role '{$role}' successfully synchronized.");

                        	$role_name = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$db_ami["replace_to_ami"]}'");
                        	
                        	if ($roleinfo["roletype"] != "SHARED")
                        	{
                        		$db->Execute("UPDATE farm_amis SET ami_id='{$db_ami["replace_to_ami"]}', replace_to_ami='' WHERE ami_id='{$ami}' AND farmid IN (SELECT id FROM farms WHERE clientid='{$farminfo['clientid']}')");
                        		$db->Execute("UPDATE zones SET ami_id='{$db_ami["replace_to_ami"]}', role_name='{$role_name}' WHERE ami_id='{$ami}' AND clientid='{$farminfo['clientid']}'");
                        	}
                        	else
                        	{
                        		$db->Execute("UPDATE farm_amis SET ami_id='{$db_ami["replace_to_ami"]}', replace_to_ami='' WHERE ami_id='{$ami}' AND farmid='{$farminfo['id']}'");
                        		$db->Execute("UPDATE zones SET ami_id='{$db_ami["replace_to_ami"]}', role_name='{$role_name}' WHERE ami_id='{$ami}' AND clientid='{$farminfo['clientid']}' AND farmid='{$farminfo['id']}'");
                        	}
                        	
                        	
                        	$db->Execute("UPDATE ami_roles SET `replace`='' WHERE ami_id='{$db_ami["replace_to_ami"]}'");
                        }
                    }
                    else 
                    {
                        $this->Logger->warn("[FarmID: {$farminfo['id']}] Role '{$role}' being synchronized. {$num_instances} instances still running on the old AMI. This role will not be checked by poller.");
                        
                        $chk = $db->GetRow("SELECT * FROM farm_instances WHERE state='Pending' AND ami_id='{$db_ami["replace_to_ami"]}' AND farmid='{$farminfo['id']}'");
                        if ($chk)
                        {
                            $this->Logger->info("There is one pending instance being swapped Skipping next instance swap until previous one will boot up.");
                            continue;
                        }
                        
                        $this->Logger->info("No pending instances with new AMI found.");
                        
                        // Terminate old instance
                        try 
            			{            
           					$old_instances = $db->GetAll("SELECT * FROM farm_instances WHERE ami_id='{$ami}' AND farmid='{$farminfo['id']}' ORDER BY id ASC");
           					
           					$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$db_ami["replace_to_ami"]}'");
           					
            			    foreach ($old_instances as $old_instance)
            			    {
            			        $dbmaster = ($old_instance["isdbmaster"] == 1) ? true : false;
            			        if ($dbmaster)
            			        	$db->Execute("UPDATE farm_instances SET isdbmaster='0' WHERE id='{$old_instance['id']}'");
            			    	
            			    	// Start new instance with new AMI_ID
            			        $res = RunInstance($AmazonEC2Client, CONFIG::$SECGROUP_PREFIX.$ami_info["name"], $farminfo['id'], $ami_info["name"], $farminfo['hash'], $ami_info["ami_id"], $dbmaster, true);
                                if ($res)
                                {
                                    $this->Logger->warn(new FarmLogMessage($farminfo['id'], "The instance ('{$ami_info["ami_id"]}') '{$old_instance['instance_id']}' ill be terminated after instance '{$res}' will boot up."));
                                    $db->Execute("UPDATE farm_instances SET replace_iid='{$old_instance['instance_id']}' WHERE instance_id='{$res}'");
                                }
                                else 
                                    $this->Logger->error("Cannot start new instance with new AMI. Analyse log for more information");
            			    }
            			}
            			catch (Exception $e)
            			{
            				$this->Logger->fatal(new FarmLogMessage($farminfo['id'], "Cannot run new instances for replaceing old ones: ".$e->getMessage()));
            			}
                    }
                    
                    continue;
                }
                    
                $roleLA = 0;
                $role_running_instances = 0;
                $role_pending_instances = 0;
                $role_terminated_instances = 0;
                $role_running_instances_with_la = 0;
                $role_instances_by_time = array();
                                    
                $this->Logger->info("[FarmID: {$farminfo['id']}] Begin check '{$role}' role instances...");
                
                $items = $ec2_items[$role];
                
                foreach ($items as $item)
                {
                    $db_item_info = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=? AND farmid=?", array($item->instanceId, $farminfo["id"]));                        
                    if ($db_item_info)
                    {
                        $role_instance_ids[$item->instanceId] = $item;
                        $this->Logger->debug("[FarmID: {$farminfo['id']}] Checking '{$item->instanceId}' instance...");
                        
                        // IF instance on EC2 - running AND db state of instance - running
                        if ($item->instanceState->name == 'running' && $db_item_info["state"] == "Running")
                        {
                            if ($db_item_info["isrebootlaunched"] == 0)
                            {
                                $this->Logger->info("[FarmID: {$farminfo['id']}] Instance '{$item->instanceId}' running. Get LA information from SNMP.");
                                
                                $instance_dns = $item->dnsName;
                                $community = $farminfo["hash"];
                                
        	                    $SNMP->Connect($instance_dns, null, $community, null, null, true);
                                $res = $SNMP->Get(".1.3.6.1.4.1.2021.10.1.3.3");
                                if (!$res)
                                {
                                    $this->Logger->warn("[FarmID: {$farminfo['id']}] Cannot receive SNMP information from instance '{$item->instanceId}'");
                                }
                                else 
                                {
                                    $la = (float)$res;
                                    $this->Logger->info("[FarmID: {$farminfo['id']}] LA for 15 minutes on '{$item->instanceId}' = {$la}");
                                    
                                    $roleLA += $la;
                                    
                                    $role_running_instances_with_la++;
                                }
                            }
                            else 
                            {
                                $this->Logger->debug("[FarmID: {$farminfo['id']}] Instance '{$item->instanceId}' rebooting...");
                                
                                $dtrebootstart = strtotime($db_item_info["dtrebootstart"]);
                                
                                $reboot_timeout = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array('reboot_timeout', $clientinfo['id']));	
								$reboot_timeout = $reboot_timeout ? $reboot_timeout : CONFIG::$REBOOT_TIMEOUT;
                                
                                if ($dtrebootstart+$reboot_timeout < time())
                                {                                        
                                    // Add entry to farm log
                    				$this->Logger->warn(new FarmLogMessage($farminfo['id'], "Instance '{$db_item_info["instance_id"]}' did not send 'rebootFinish' event in {$reboot_timeout} seconds after reboot start. Considering it broken. Terminating instance."));
                                    
                                    try
                                    {
                                        $res = $AmazonEC2Client->TerminateInstances(array($db_item_info["instance_id"]));
                                        if ($res instanceof SoapFault)
                                            $this->Logger->fatal($res->faultString);
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
                        elseif ($item->instanceState->name != 'running' && $db_item_info["state"] == "Running")
                        {
                            $this->Logger->warn("[FarmID: {$farminfo['id']}] {$item->instanceId}' have state '{$item->instanceState->name}'");
                            
                            // Update DB
                            $db->Execute("DELETE FROM farm_instances WHERE instance_id='{$item->instanceId}'");
                            
                            //
                            // SNMP Traps
                            //
                            $Shell = ShellFactory::GetShellInstance();
                            $first_in_role_handled = false;
                            foreach ($farm_instances as $farm_instance)
                            {
                                if ($farm_instance["state"] != 'Running' || !$farm_instance["external_ip"])
                                    continue;
                                
                                if ($db_item_info["id"] == $farm_instance["id"])
                                    continue;
                                    
                                $isfirstinrole = '0';
                                
                                if ($ami == $farm_instance["ami_id"] && !$first_in_role_handled)
                                {
                                    $first_in_role_handled = true;
                                    $isfirstinrole = '1';
                                }
                                
                                $alias = $db->GetOne("SELECT alias FROM ami_roles WHERE ami_id='{$db_item_info['ami_id']}'");
                                
                                $res = $Shell->QueryRaw(CONFIG::$SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$farm_instance['external_ip'].' "" SNMPv2-MIB::snmpTrap.11.0 SNMPv2-MIB::sysName.0 s "'.$alias.'" SNMPv2-MIB::sysLocation.0 s "'.$db_item_info['internal_ip'].'" SNMPv2-MIB::sysDescr.0 s "'.$isfirstinrole.'" 2>&1', true);
                                $this->Logger->debug("[FarmID: {$farminfo['id']}] Sending SNMP Trap 11.0 (hostDown) to '{$farm_instance['instance_id']}' ('{$farm_instance['external_ip']}') complete ({$res})");
                            }
                            //
                            //
                            
                            $role_terminated_instances++;
                        }
                        elseif ($item->instanceState->name == 'pending')
                        {
                            $role_pending_instances++;
                        }
                        elseif ($item->instanceState->name == 'running' && $db_item_info["state"] == "Pending")
                        {
                            //
                            $dtadded = strtotime($db_item_info["dtadded"]);
                            
                            $launch_timeout = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array('launch_timeout', $clientinfo['id']));	
							$launch_timeout = $launch_timeout ? $launch_timeout : CONFIG::$LAUNCH_TIMEOUT;
                            
                            if ($dtadded+$launch_timeout < time())
                            {
                                if (!$db_item_info["internal_ip"])
                                    $event = "hostInit";
                                else 
                                    $event = "hostUp";
                                    
                                // Add entry to farm log
                    			$this->Logger->warn(new FarmLogMessage($farminfo['id'], "Instance '{$db_item_info["instance_id"]}' did not send '{$event}' event in {$launch_timeout} seconds after launch. Considering it broken. Terminating instance."));
                                
                                try
                                {
                                    $res = $AmazonEC2Client->TerminateInstances(array($db_item_info["instance_id"]));
                                    if ($res instanceof SoapFault)
                                        $this->Logger->fatal($res->faultString);
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
                    else 
                    {
                        //
                    }
                    
                } //for each items
                
                $AvgLA = round($roleLA/$role_running_instances_with_la, 2);
                $this->Logger->debug("[FarmID: {$farminfo['id']}] '{$role}' statistics: Running={$role_running_instances}/{$role_running_instances_with_la}, Terminated={$role_terminated_instances}, Pending={$role_pending_instances}, SumLA={$roleLA}, AvgLA={$AvgLA}");
                
                $db_ami_info = $db->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND ami_id=?", array($farminfo['id'], $ami));
                
                //
                //Checking if there are spare instances that need to be terminated
                //
                $need_terminate_instance = false;
                
                if ($AvgLA <= $db_ami_info["min_LA"])
                {
                    $need_terminate_instance = true;
                    
                    // Add entry to farm log
                    $this->Logger->debug("[FarmID: {$farminfo['id']}] Average LA for '{$role}' ({$AvgLA}) <= min_LA ({$db_ami_info["min_LA"]})");
                }
                
                /* No need to terminate instance if number of instances more than min_count
                elseif (count($role_instances_by_time) > $db_ami_info["min_count"])
                {
                    $this->Logger->warn("[FarmID: {$farminfo['id']}] Min count instances for role '{$role}' decreased to '{$db_ami_info["min_count"]}'. Need instance termination...");
                    $need_terminate_instance = true;
                }
				*/
                    
                if ($need_terminate_instance)
                {
                    if (count($role_instances_by_time) > $db_ami_info["min_count"])
                    {
                        $db_ami_info['name'] = $role;
                    	Scalr::StoreEvent($farminfo['id'], EVENT_TYPE::LA_UNDER_MINIMUM, $db_ami_info, $AvgLA, $db_ami_info["min_LA"]);
                    	
                    	$this->Logger->info(new FarmLogMessage($farminfo['id'], "Average LA for '{$role}' ({$AvgLA}) <= min_LA ({$db_ami_info["min_LA"]})"));
                    	
                    	
                    	$instances = $role_instances_by_time;
                    	// Select instance that will be terminated
                        //
                        // * Instances ordered by uptime (oldest wil be choosen)
                        // * Instance cannot be mysql master
                        // * Choose the one that was rebundled recently
                    	while (!$got_valid_instance && count($instances) > 0)
                        {
                    		$item = array_shift($instances);
	                        $instanceinfo = $db->GetRow("SELECT * FROM farm_instances 
	                        	WHERE instance_id=?", array($item->instanceId));

	                        // Exclude db master
	                        if ($instanceinfo["isdbmaster"] != 1)
	                        {
	                        	/* 
	                        	 * We do not want to delete the most recently synced instance. Because of LA fluctuation. 
	                        	 * I.e. LA may skyrocket during sync and drop dramatically after sync.
	                        	 */

	                        	if ($instanceinfo["dtlastsync"] != 0)
	                        	{
	                        		$chk_sync_time = $db->GetOne("SELECT id FROM farm_instances 
	                        		WHERE dtlastsync > {$instanceinfo['dtlastsync']} 
		                        		AND farmid='{$instanceinfo['farmid']}' 
		                        		AND ami_id='{$instanceinfo['ami_id']}'");
	                        		if ($chk_sync_time)
	                        			$got_valid_instance = true;
	                        	}
	                        	else
	                        		$got_valid_instance = true;
	                        }
                        }
                        
                        if ($instanceinfo && $got_valid_instance)
                        {
	                        $this->Logger->info("[FarmID: {$farminfo['id']}] Terminate '{$instanceinfo['instance_id']}'");
	                        
	                        try
	                        {
	                            $AmazonEC2Client->TerminateInstances(array($instanceinfo["instance_id"]));
	                            $db->Execute("DELETE FROM farm_instances 
	                            	WHERE instance_id=? AND farmid=?", 
	                            array($instanceinfo["instance_id"], $farminfo['id']));
	                            
	                            
	                            // Add entry to farm log
                    			$this->Logger->info(new FarmLogMessage($farminfo['id'], "'{$instanceinfo["instance_id"]}' ({$instanceinfo["external_ip"]}) Terminated!"));
	                            
	                            Scalr::StoreEvent($farminfo['id'], EVENT_TYPE::HOST_DOWN, $instanceinfo);
	                        }
	                        catch (Exception $e)
	                        {
	                            $this->Logger->fatal("[FarmID: {$farminfo['id']}] Cannot terminate {$item->instanceId}': {$e->getMessage()}");
	                        }
	                        
	                        //
	                        // Send SNMP TRap
	                        //
	                        $Shell = ShellFactory::GetShellInstance();
	                        $first_in_role_handled = false;
	                        foreach ($farm_instances as $farm_instance)
	                        {
	                            if ($farm_instance["state"] != 'Running' || !$farm_instance["external_ip"])
	                                continue;
	                            
	                            if ($instanceinfo["id"] == $farm_instance["id"])
	                                continue;
	                                
	                            $isfirstinrole = '0';
	                            
	                            if ($ami == $farm_instance["ami_id"] && !$first_in_role_handled)
	                            {
	                                $first_in_role_handled = true;
	                                $isfirstinrole = '1';
	                            }
	                            
	                            $alias = $db->GetOne("SELECT alias FROM ami_roles WHERE name='{$instanceinfo['role_name']}' AND iscompleted='1'");
	                            
	                            $res = $Shell->QueryRaw(CONFIG::$SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$farm_instance['external_ip'].' "" SNMPv2-MIB::snmpTrap.11.0 SNMPv2-MIB::sysName.0 s "'.$alias.'" SNMPv2-MIB::sysLocation.0 s "'.$instanceinfo['internal_ip'].'" SNMPv2-MIB::sysDescr.0 s "'.$isfirstinrole.'" 2>&1', true);
	                            $this->Logger->debug("[FarmID: {$farminfo['id']}] Sending SNMP Trap 11.0 (hostDown) to '{$farm_instance['instance_id']}' ('{$farm_instance['external_ip']}') complete ({$res})");
	                        }
	                        
	                        //
	                        // Update DNS
	                        //
	                        $records = $db->GetAll("SELECT * FROM records WHERE rvalue='{$instanceinfo['external_ip']}' OR rvalue='{$instanceinfo['internal_ip']}'");
	                        foreach ($records as $record)
	                        {
	                            $zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id='{$record['zoneid']}' AND status != ?", array(ZONE_STATUS::DELETED));
	                            
	                            if ($zoneinfo)
	                            {
	                                $db->Execute("DELETE FROM records WHERE id='{$record['id']}'");
	                                if (!$DNSZoneController->Update($record["zoneid"]))
	                                    $this->Logger->error("[FarmID: {$farminfo['id']}] Cannot remove terminated instance '{$item->instanceId}' ({$instanceinfo['external_ip']}) from DNS zone '{$zoneinfo['zone']}'");
	                                else 
	                                    $this->Logger->debug("[FarmID: {$farminfo['id']}] Terminated instance '{$item->instanceId}' ({$instanceinfo['external_ip']}) removed from DNS zone '{$zoneinfo['zone']}'");
	                            }
	                        }
                        }
                    }
                    else 
                    {
                        // Add entry to farm log
                    	$this->Logger->debug(new FarmLogMessage($farminfo['id'], "Group {$role} is idle, but needs at least {$db_ami_info["min_count"]} nodes, currently running: ".count($role_instances_by_time)."."));
                    }
                }

                //
                // Checking if we need new instances launched
                //
                $need_new_instance = false;
                if ($AvgLA >= $db_ami_info["max_LA"])
                {
                    $need_new_instance = true;
                    
                    $db_ami_info['name'] = $role;
                    Scalr::StoreEvent($farminfo['id'], EVENT_TYPE::LA_OVER_MAXIMUM, $db_ami_info, $AvgLA, $db_ami_info["max_LA"]);
                    
                    // Add entry to farm log
                    $this->Logger->info(new FarmLogMessage($farminfo['id'], "Average LA for '{$role}' ({$AvgLA}) >= max_LA ({$db_ami_info["max_LA"]})"));
                }
                elseif (count($role_instances_by_time) == 0)
                {
                    $need_new_instance = true;
                    
                    // Add entry to farm log
                    $this->Logger->warn(new FarmLogMessage($farminfo['id'], "Disaster: No instances running in group {$role}!"));
                }
                elseif (count($role_instances_by_time) < $db_ami_info["min_count"])
                {
                    $this->Logger->warn("[FarmID: {$farminfo['id']}] Min count instances for role '{$role}' increased to '{$db_ami_info["min_count"]}'. Need more instances...");
                    $need_new_instance = true;
                }
                
                    
                if ($need_new_instance)
                {
                    if (count($role_instances_by_time) < $db_ami_info["max_count"])
                    {
                        if ($role_pending_instances > 0)
                        {
                            // Add entry to farm log
                    		$this->Logger->debug(new FarmLogMessage($farminfo['id'], "{$role_pending_instances} instances in pending state. We don't need more instances at this time."));
                        }
                        else 
                        {
                            $instance_id = RunInstance($AmazonEC2Client, CONFIG::$SECGROUP_PREFIX.$role, $farminfo["id"], $role, $farminfo["hash"], $ami, false, true);
                            
                            if ($instance_id)
                            {
                                $this->Logger->info(new FarmLogMessage($farminfo['id'], "Starting new instance. InstanceID = {$instance_id}."));
                            }
                            else 
                                $this->Logger->error("[FarmID: {$farminfo['id']}] Cannot run new instance! See system log for details.");
                        }
                    }
                    else 
                    {
                        // Add entry to farm log
                    	$this->Logger->info(new FarmLogMessage($farminfo['id'], "Group {$role} is full. Max count {$db_ami_info["max_count"]} of nodes exceed, currently running: ".count($role_instances_by_time).", pending: {$role_pending_instances} instances."));
                    }
                }
                
            }
        }
    }
?>