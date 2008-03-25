<?
	class PollerProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Main poller";
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance(null, true);
            
            Log::Log("Fetching completed farms...", E_NOTICE);
            
            $this->ThreadArgs = $db->GetAll("SELECT * FROM farms WHERE status='1'");
            
            Log::Log("Found ".count($this->ThreadArgs)." farms.", E_NOTICE);
        }
        
        public function OnEndForking()
        {
            
        }
        
        public function StartThread($farminfo)
        {
            $db = Core::GetDBInstance(null, true);
            $SNMP = new SNMP();
            
            if (Log::HasLogger("Default"))
                Log::Reload("Default");
            
            //
            // Collect information from database
            //
            Log::Log("[FarmID: {$farminfo['id']}] Begin polling...", E_NOTICE);
                
            $clientinfo = $db->GetRow("SELECT * FROM clients WHERE id='{$farminfo['clientid']}'");
            Log::Log("[FarmID: {$farminfo['id']}] Farm client ID: {$clientinfo['id']}", E_NOTICE);
            
            $farm_amis = $db->GetAll("SELECT * FROM farm_amis WHERE farmid='{$farminfo['id']}'");
            Log::Log("[FarmID: {$farminfo['id']}] Farm used ".count($farm_amis)." AMIs", E_NOTICE);
            
            $farm_instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid='{$farminfo['id']}'");
            Log::Log("[FarmID: {$farminfo['id']}] Found ".count($farm_instances)." farm instances in database", E_NOTICE);
                
            // Get AmazonEC2 Object
            $AmazonEC2Client = new AmazonEC2(
                        APPPATH . "/etc/clients_keys/{$clientinfo['id']}/pk.pem", 
                        APPPATH . "/etc/clients_keys/{$clientinfo['id']}/cert.pem");
                        
            // Get instances from EC2
            Log::Log("[FarmID: {$farminfo['id']}] Receiving instances info from EC2...", E_NOTICE);
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
                Log::Log("[FarmID: {$farminfo['id']}] Found ".count($result->reservationSet->item)." total instances...", E_NOTICE);
                $num = 0;
                foreach ($result->reservationSet->item as $item)
                {
                    if ($db->GetOne("SELECT id FROM farm_amis WHERE farmid=? AND (ami_id=? OR `replace_to_ami`=?)", array($farminfo['id'], $item->instancesSet->item->imageId, $item->instancesSet->item->imageId)))
                    {
                        if (!is_array($ec2_items[$item->instancesSet->item->imageId]))
                            $ec2_items[$item->instancesSet->item->imageId] = array();
                            
                        array_push($ec2_items[$item->instancesSet->item->imageId], $item->instancesSet->item);
                        $ec2_items_by_instanceid[$item->instancesSet->item->instanceId] = $item->instancesSet->item;
                        $num++;
                    }
                }
                
                Log::Log("[FarmID: {$farminfo['id']}] Found {$num} instances assigned to current farm", E_NOTICE);
            }
            else 
                Log::Log("[FarmID: {$farminfo['id']}] No instances found for this client.", E_NOTICE);
                
            
            foreach ($farm_instances as $farm_instance)
            {
                $instance_terminated = false;
                
                if (!isset($ec2_items_by_instanceid[$farm_instance["instance_id"]]))
                {
                    Log::Log("[FarmID: {$farminfo['id']}] Instance '{$farm_instance["instance_id"]}' found in database but not found on EC2!", E_WARNING);
                    $db->Execute("DELETE FROM farm_instances WHERE farmid=? AND instance_id=?", array($farminfo['id'], $farm_instance["instance_id"]));
                    
                    $instance_terminated = true;
                }
                else 
                {
                    switch ($ec2_items_by_instanceid[$farm_instance["instance_id"]]->instanceState->name)
                    {
                        case "terminated":
                            
                            Log::Log("[FarmID: {$farminfo['id']}] Instance '{$farm_instance["instance_id"]}' not running (Terminated).", E_WARNING);
                            $db->Execute("DELETE FROM farm_instances WHERE farmid=? AND instance_id=?", array($farminfo['id'], $farm_instance["instance_id"]));
                            
                            $instance_terminated = true;
                            
                            break;
                            
                        case "shutting-down":
                            
                            Log::Log("[FarmID: {$farminfo['id']}] Instance '{$farm_instance["instance_id"]}' not running (Shutting Down).", E_WARNING);
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
                    $role = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$farm_instance['ami_id']}'");
                    $alias = $db->GetOne("SELECT alias FROM ami_roles WHERE ami_id='{$farm_instance['ami_id']}'");
                    $Shell = ShellFactory::GetShellInstance();
                    $first_in_role_handled = false;
                    foreach ($farm_instances as $farm_instance_snmp)
                    {
                        if ($farm_instance_snmp["state"] != 'Running' || !$farm_instance_snmp["external_ip"])
                            continue;
                        
                        if ($farm_instance_snmp["id"] == $farm_instance["id"])
                            continue;
                            
                        $isfirstinrole = '0';
                        
                        if ($farm_instance['ami_id'] == $farm_instance_snmp["ami_id"] && !$first_in_role_handled)
                        {
                            $first_in_role_handled = true;
                            $isfirstinrole = '1';
                        }
                        
                        $res = $Shell->QueryRaw(CF_SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$farm_instance_snmp['external_ip'].' "" SNMPv2-MIB::snmpTrap.11.0 SNMPv2-MIB::sysName.0 s "'.$alias.'" SNMPv2-MIB::sysLocation.0 s "'.$farm_instance['internal_ip'].'" SNMPv2-MIB::sysDescr.0 s "'.$isfirstinrole.'" 2>&1', true);
                        Log::Log("[FarmID: {$farminfo['id']}] Sending SNMP Trap 11.0 (hostDown) to '{$farm_instance_snmp['instance_id']}' ('{$farm_instance_snmp['external_ip']}') complete ({$res})", E_USER_NOTICE);
                    }
    
                    //
                    // Update DNS
                    //
                    $DNSZoneController = new DNSZoneControler();
                    $records = $db->GetAll("SELECT * FROM records WHERE rvalue='{$farm_instance['external_ip']}'");
                    foreach ($records as $record)
                    {
                        $zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id='{$record['zoneid']}'");
                        
                        if ($zoneinfo)
                        {
                            $db->Execute("DELETE FROM records WHERE id='{$record['id']}'");
                            if (!$DNSZoneController->Update($record["zoneid"]))
                                Log::Log("[FarmID: {$farminfo['id']}] Cannot remove terminated instance '{$farm_instance['instance_id']}' ({$farm_instance['external_ip']}) from DNS zone '{$zoneinfo['zone']}'", E_ERROR);
                            else 
                                Log::Log("[FarmID: {$farminfo['id']}] Terminated instance '{$farm_instance['instance_id']}' ({$farm_instance['external_ip']}) removed from DNS zone '{$zoneinfo['zone']}'", E_USER_NOTICE);
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
                        $db->Execute("UPDATE farm_amis SET ami_id='{$db_ami["replace_to_ami"]}', replace_to_ami='' WHERE id='{$db_ami['id']}'");
                        
                        Log::Log("[FarmID: {$farminfo['id']}] Role '{$role}' successfully syncronized.", E_USER_NOTICE);
                        
                        if ($roleinfo["roletype"] == "CUSTOM")
                        {
                            if ($db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE ami_id='{$ami}'") == 0)
                            {
                                if ($roleinfo['name'] == $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$db_ami["replace_to_ami"]}'"))
                                {
                                    Log::Log("Deleting old role AMI ('{$roleinfo["ami_id"]}') from database.", E_NOTICE);
                                    $db->Execute("DELETE FROM ami_roles WHERE ami_id='{$ami}'");
                                }
                            }
                        }
                        
                        $db->Execute("UPDATE ami_roles SET `replace`='' WHERE ami_id='{$db_ami["replace_to_ami"]}'");
                        $db->Execute("UPDATE zones SET ami_id='{$db_ami["replace_to_ami"]}' WHERE ami_id='{$ami}'");
                    }
                    else 
                    {
                        Log::Log("[FarmID: {$farminfo['id']}] Role '{$role}' being syncronized. {$num_instances} instances still running on the old AMI. This role will not be checked by poller.", E_USER_WARNING);
                        
                        $chk = $db->GetRow("SELECT * FROM farm_instances WHERE state='Pending' AND ami_id='{$db_ami["replace_to_ami"]}' AND farmid='{$farminfo['id']}'");
                        if ($chk)
                        {
                            Log::Log("There is one pending instance being swapped Skipping next instance swap until previous one will boot up.", E_NOTICE);
                            continue;
                        }
                        
                        Log::Log("No pending instances with new AMI found.", E_NOTICE);
                        
                        // Terminate old instance
                        try 
            			{            
           					$old_instances = $db->GetAll("SELECT * FROM farm_instances WHERE ami_id='{$ami}' AND farmid='{$farminfo['id']}' ORDER BY id ASC");
           					
           					$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$db_ami["replace_to_ami"]}'");
           					
            			    foreach ($old_instances as $old_instance)
            			    {
            			        // Start new instance with new AMI_ID
            			        $res = RunInstance($AmazonEC2Client, CF_SECGROUP_PREFIX.$ami_info["name"], $farminfo['id'], $ami_info["name"], $farminfo['hash'], $ami_info["ami_id"]);
                                if ($res)
                                {
                                    Log::Log("The instance ('{$ami_info["ami_id"]}') '{$old_instance['instance_id']}' ill be terminated after instance '{$res}' will boot up.", E_USER_WARNING);
                                    $db->Execute("UPDATE farm_instances SET replace_iid='{$old_instance['instance_id']}' WHERE instance_id='{$res}'");
                                }
                                else 
                                    Log::Log("Cannot start new instance with new AMI. Analyse log for more information", E_USER_ERROR);
            			    }
            			}
            			catch (Exception $e)
            			{
            				Log::Log("Cannot terminate old instance: ".$e->getMessage(), E_WARNING);
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
                                    
                Log::Log("[FarmID: {$farminfo['id']}] Begin check '{$role}' role instances... (AMI: {$ami})", E_NOTICE);
                
                $items = $ec2_items[$ami];
                
                foreach ($items as $item)
                {
                    $role_instance_ids[$item->instanceId] = $item;
                    
                    $db_item_info = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=? AND farmid=?", array($item->instanceId, $farminfo["id"]));                        
                    if ($db_item_info)
                    {
                        Log::Log("[FarmID: {$farminfo['id']}] Checking '{$item->instanceId}' instance...", E_USER_NOTICE);
                        
                        // IF instance on EC2 - running AND db state of instance - running
                        if ($item->instanceState->name == 'running' && $db_item_info["state"] == "Running")
                        {
                            if ($db_item_info["isrebootlaunched"] == 0)
                            {
                                Log::Log("[FarmID: {$farminfo['id']}] Instance '{$item->instanceId}' running. Get LA information from SNMP.", E_USER_NOTICE);
                                
                                $instance_dns = $item->dnsName;
                                $community = $farminfo["hash"];
                                
        	                    $SNMP->Connect($instance_dns, null, $community);
                                $res = $SNMP->Get(".1.3.6.1.4.1.2021.10.1.3.3");
                                if (!$res)
                                {
                                    Log::Log("[FarmID: {$farminfo['id']}] Cannot receive SNMP information from instance '{$item->instanceId}'", E_WARNING);
                                }
                                else 
                                {
                                    $la = (float)$res;
                                    Log::Log("[FarmID: {$farminfo['id']}] LA for 15 minutes on '{$item->instanceId}' = {$la}", E_NOTICE);
                                    
                                    $roleLA += $la;
                                    
                                    $role_running_instances_with_la++;
                                }
                            }
                            else 
                            {
                                Log::Log("[FarmID: {$farminfo['id']}] Instance '{$item->instanceId}' rebooting...", E_USER_NOTICE);
                                
                                $dtrebootstart = strtotime($db_item_info["dtrebootstart"]);
                                if ($dtrebootstart+CF_REBOOT_TIMEOUT < time())
                                {                                        
                                    Log::Log("Instance '{$db_item_info["instance_id"]}' did not send 'rebootFinish' event in ".CF_REBOOT_TIMEOUT." seconds after reboot start. Considering it broken. Terminating instance." ,E_USER_ERROR);
                                    
                                    try
                                    {
                                        $res = $AmazonEC2Client->TerminateInstances(array($db_item_info["instance_id"]));
                                        if ($res instanceof SoapFault)
                                            Log::Log($res->faultString, E_ERROR);
                                    }
                                    catch (Exception $err)
                                    {
                                        Log::Log($err->getMessage(), E_ERROR);
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
                            Log::Log("[FarmID: {$farminfo['id']}] {$item->instanceId}' have state '{$item->instanceState->name}'", E_WARNING);
                            
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
                                
                                $res = $Shell->QueryRaw(CF_SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$farm_instance['external_ip'].' "" SNMPv2-MIB::snmpTrap.11.0 SNMPv2-MIB::sysName.0 s "'.$alias.'" SNMPv2-MIB::sysLocation.0 s "'.$db_item_info['internal_ip'].'" SNMPv2-MIB::sysDescr.0 s "'.$isfirstinrole.'" 2>&1', true);
                                Log::Log("[FarmID: {$farminfo['id']}] Sending SNMP Trap 11.0 (hostDown) to '{$farm_instance['instance_id']}' ('{$farm_instance['external_ip']}') complete ({$res})", E_USER_NOTICE);
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
                            if ($dtadded+CF_LAUNCH_TIMEOUT < time())
                            {
                                if (!$db_item_info["internal_ip"])
                                    $event = "hostInit";
                                else 
                                    $event = "hostUp";
                                    
                                Log::Log("Instance '{$db_item_info["instance_id"]}' did not send '{$event}' event in ".CF_LAUNCH_TIMEOUT." seconds after launch. Considering it broken. Terminating instance." ,E_USER_ERROR);
                                
                                try
                                {
                                    $res = $AmazonEC2Client->TerminateInstances(array($db_item_info["instance_id"]));
                                    if ($res instanceof SoapFault)
                                        Log::Log($res->faultString, E_ERROR);
                                }
                                catch (Exception $err)
                                {
                                    Log::Log($err->getMessage(), E_ERROR);
                                }
                            }
                            //
                            //
                            $role_pending_instances++;
                        }
                    }
                    
                } //for each items
                
                $AvgLA = round($roleLA/$role_running_instances_with_la, 2);
                Log::Log("[FarmID: {$farminfo['id']}] '{$role}' statistics: Running={$role_running_instances}/{$role_running_instances_with_la}, Terminated={$role_terminated_instances}, Pending={$role_pending_instances}, SumLA={$roleLA}, AvgLA={$AvgLA}", E_USER_NOTICE);
                
                $db_ami_info = $db->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND ami_id=?", array($farminfo['id'], $ami));
                
                //
                //Checking if there are spare instances that need to be terminated
                //
                $need_terminate_instance = false;
                
                if ($AvgLA <= $db_ami_info["min_LA"])
                {
                    Log::Log("[FarmID: {$farminfo['id']}] Average LA for '{$role}' ({$AvgLA}) &lt;= min_LA ({$db_ami_info["min_LA"]})", E_USER_NOTICE);
                    $need_terminate_instance = true;
                }
                elseif (count($role_instances_by_time) > $db_ami_info["min_count"])
                {
                    Log::Log("[FarmID: {$farminfo['id']}] Min count instances for role '{$role}' decreased to '{$db_ami_info["min_count"]}'. Need instance termination...", E_USER_WARNING);
                    $need_terminate_instance = true;
                }
                    
                if ($need_terminate_instance)
                {
                    if (count($role_instances_by_time) > $db_ami_info["min_count"])
                    {
                        $item = array_shift($role_instances_by_time);
                        Log::Log("[FarmID: {$farminfo['id']}] Terminate '{$item->instanceId}'", E_NOTICE);
                        
                        $instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE farmid='{$farminfo['id']}' AND ami_id='{$db_ami_info["ami_id"]}' AND isdbmaster='0' ORDER BY id ASC");
                        
                        try
                        {
                            $AmazonEC2Client->TerminateInstances(array($instanceinfo["instance_id"]));
                            $db->Execute("DELETE FROM farm_instances WHERE instance_id=? AND farmid=?", array($instanceinfo["instance_id"], $farminfo['id']));
                            
                            Log::Log("[FarmID: {$farminfo['id']}] '{$instanceinfo["instance_id"]}' ({$instanceinfo["external_ip"]}) Terminated!", E_USER_NOTICE);
                        }
                        catch (Exception $e)
                        {
                            Log::Log("[FarmID: {$farminfo['id']}] Cannot terminate {$item->instanceId}': {$e->getMessage()}", E_WARNING);
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
                            
                            $alias = $db->GetOne("SELECT alias FROM ami_roles WHERE ami_id='{$instanceinfo['ami_id']}'");
                            
                            $res = $Shell->QueryRaw(CF_SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$farm_instance['external_ip'].' "" SNMPv2-MIB::snmpTrap.11.0 SNMPv2-MIB::sysName.0 s "'.$alias.'" SNMPv2-MIB::sysLocation.0 s "'.$instanceinfo['internal_ip'].'" SNMPv2-MIB::sysDescr.0 s "'.$isfirstinrole.'" 2>&1', true);
                            Log::Log("[FarmID: {$farminfo['id']}] Sending SNMP Trap 11.0 (hostDown) to '{$farm_instance['instance_id']}' ('{$farm_instance['external_ip']}') complete ({$res})", E_USER_NOTICE);
                        }
                        
                        //
                        // Update DNS
                        //
                        $DNSZoneController = new DNSZoneControler();
                        $records = $db->GetAll("SELECT * FROM records WHERE rvalue='{$instanceinfo['external_ip']}'");
                        foreach ($records as $record)
                        {
                            $zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id='{$record['zoneid']}'");
                            
                            if ($zoneinfo)
                            {
                                $db->Execute("DELETE FROM records WHERE id='{$record['id']}'");
                                if (!$DNSZoneController->Update($record["zoneid"]))
                                    Log::Log("[FarmID: {$farminfo['id']}] Cannot remove terminated instance '{$item->instanceId}' ({$instanceinfo['external_ip']}) from DNS zone '{$zoneinfo['zone']}'", E_ERROR);
                                else 
                                    Log::Log("[FarmID: {$farminfo['id']}] Terminated instance '{$item->instanceId}' ({$instanceinfo['external_ip']}) removed from DNS zone '{$zoneinfo['zone']}'", E_USER_NOTICE);
                            }
                        }
                    }
                    else 
                    {
                        Log::Log("[FarmID: {$farminfo['id']}] Group {$role} is idle, but needs at least {$db_ami_info["min_count"]} nodes, currently running: ".count($role_instances_by_time).".", E_USER_NOTICE);
                    }
                }

                //
                // Checking if we need new instances launched
                //
                $need_new_instance = false;
                if ($AvgLA >= $db_ami_info["max_LA"])
                {
                    Log::Log("[FarmID: {$farminfo['id']}] Average LA for '{$role}' ({$AvgLA}) &rt;= max_LA ({$db_ami_info["max_LA"]})", E_USER_WARNING);
                    $need_new_instance = true;
                }
                elseif (count($role_instances_by_time) == 0)
                {
                    Log::Log("[FarmID: {$farminfo['id']}] Disaster: No instances running in group {$role}!", E_USER_WARNING);
                    $need_new_instance = true;
                }
                elseif (count($role_instances_by_time) < $db_ami_info["min_count"])
                {
                    Log::Log("[FarmID: {$farminfo['id']}] Min count instances for role '{$role}' increased to '{$db_ami_info["min_count"]}'. Need more instances...", E_USER_WARNING);
                    $need_new_instance = true;
                }
                
                    
                if ($need_new_instance)
                {
                    if (count($role_instances_by_time) < $db_ami_info["max_count"])
                    {
                        if ($role_pending_instances > 0)
                        {
                            Log::Log("[FarmID: {$farminfo['id']}] {$role_pending_instances} instances in pending state. We didn't need more instances at this time.", E_USER_NOTICE);
                        }
                        else 
                        {
                            $instance_id = RunInstance($AmazonEC2Client, CF_SECGROUP_PREFIX.$role, $farminfo["id"], $role, $farminfo["hash"], $ami);
                            
                            if ($instance_id)
                                Log::Log("[FarmID: {$farminfo['id']}] Starting new instance. InstanceID = {$instance_id}.", E_USER_NOTICE);
                            else 
                                Log::Log("[FarmID: {$farminfo['id']}] Cannot run new instance! See system log for details.", E_USER_ERROR);
                        }
                    }
                    else 
                    {
                        Log::Log("[FarmID: {$farminfo['id']}] Group {$role} is full. Max count {$db_ami_info["max_count"]} of nodes exceed, currently running: ".count($role_instances_by_time).", pending: {$role_pending_instances} instances.", E_USER_NOTICE);
                    }
                }
                
            }
        }
    }
?>