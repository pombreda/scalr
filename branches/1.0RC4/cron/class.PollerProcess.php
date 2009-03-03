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
            $this->Logger->info("[FarmID: {$farminfo['id']}] Begin polling...");

            $Client = Client::Load($farminfo['clientid']);
            
            $farm_amis = $db->GetAll("SELECT * FROM farm_amis WHERE farmid='{$farminfo['id']}'");
            $this->Logger->debug("[FarmID: {$farminfo['id']}] Farm used ".count($farm_amis)." AMIs");
            
            $farm_instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid='{$farminfo['id']}'");
            $this->Logger->info("[FarmID: {$farminfo['id']}] Found ".count($farm_instances)." farm instances in database");

            if ($farminfo['status'] == FARM_STATUS::TERMINATED && count($farm_instances) == 0)
            	exit();
                        
            // Get AmazonEC2 Object
            $AmazonEC2Client = new AmazonEC2($Client->AWSPrivateKey, $Client->AWSCertificate);
                        
            // Get instances from EC2
            $this->Logger->debug("[FarmID: {$farminfo['id']}] Receiving instances info from EC2...");
            $result = $AmazonEC2Client->DescribeInstances();
            $ec2_items = array();
            $ec2_items_by_instanceid = array();
                                   
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
            }

        	//
            // Check farm status
            //
            if ($db->GetOne("SELECT status FROM farms WHERE id=?", array($farminfo["id"])) != FARM_STATUS::RUNNING)
            {
            	$this->Logger->warn("[FarmID: {$farminfo['id']}] Farm is not running.");
            	return;
            }
            
            $db_amis = $db->Execute("SELECT * FROM farm_amis WHERE farmid=?", array($farminfo["id"]));
                        
            while ($db_ami = $db_amis->FetchRow())
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
                        if ($roleinfo["roletype"] == ROLE_TYPE::SHARED)
                    		$sync_complete = true;
                        elseif ($roleinfo["roletype"] == ROLE_TYPE::CUSTOM)
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
                        	$this->Logger->info("[FarmID: {$farminfo['id']}] Role '{$role}' successfully synchronized.");

                        	$role_name = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$db_ami["replace_to_ami"]}'");
                        	
                        	$db->Execute("UPDATE elastic_ips SET role_name=? WHERE farmid=? AND role_name=?",
                        		array($role_name, $farminfo['id'], $roleinfo['name'])
                        	);
                        	
                        	$db->Execute("UPDATE farm_ebs SET role_name=? WHERE farmid=? AND role_name=?",
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
                        	}
                        	else
                        	{
                        		$db->Execute("UPDATE farm_amis SET ami_id='{$db_ami["replace_to_ami"]}', replace_to_ami='' WHERE ami_id='{$ami}' AND farmid='{$farminfo['id']}'");
                        		$db->Execute("UPDATE zones SET ami_id='{$db_ami["replace_to_ami"]}', role_name='{$role_name}' WHERE ami_id='{$ami}' AND clientid='{$farminfo['clientid']}' AND farmid='{$farminfo['id']}'");
                        		
                        		// Update ami in role scripts
                        		$db->Execute("UPDATE farm_role_scripts SET ami_id='{$db_ami["replace_to_ami"]}' WHERE ami_id='{$ami}' AND farmid='{$farminfo['id']}'");
                        	}
                        	
                        	$db->Execute("UPDATE ami_roles SET `replace`='' WHERE ami_id='{$db_ami["replace_to_ami"]}'");
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
                        
                        $this->Logger->info("No pending instances with new AMI found.");
                        
                        // Terminate old instance
                        try 
            			{            
           					$old_instances = $db->GetAll("SELECT * FROM farm_instances WHERE ami_id='{$ami}' AND farmid='{$farminfo['id']}' ORDER BY id ASC");
           					
           					$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$db_ami["replace_to_ami"]}'");
            				if (!$ami_info)
           					{
           						$this->Logger->warn("Cannot replace farm ami to new one. Role with ami '{$db_ami["replace_to_ami"]}' was removed.");
           						$db->Execute("UPDATE farm_amis SET replace_to_ami='' WHERE id='{$db_ami['id']}'");
           						continue;
           					}
           					
            			    foreach ($old_instances as $old_instance)
            			    {            			    	
            			    	// Start new instance with new AMI_ID
            			        $res = Scalr::RunInstance($AmazonEC2Client, CONFIG::$SECGROUP_PREFIX.$ami_info["name"], $farminfo['id'], $ami_info["name"], $farminfo['hash'], $ami_info["ami_id"], false, true, $old_instance['avail_zone']);
                                if ($res)
                                {
                                    $this->Logger->warn(new FarmLogMessage($farminfo['id'], "The instance ('{$ami_info["ami_id"]}') '{$old_instance['instance_id']}' will be terminated after instance '{$res}' will boot up."));
                                    $db->Execute("UPDATE farm_instances SET replace_iid='{$old_instance['instance_id']}' WHERE instance_id='{$res}'");
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
        	                    	$SNMP->Connect($instance_dns, null, $community, null, null, true);
                                	$res = $SNMP->Get(".1.3.6.1.4.1.2021.10.1.3.3");
                                }
                                else
                                	$res = false;
                                
                                if (!$res)
                                {
                                    if ($db_item_info['isipchanged'] == 0)
                                		$this->Logger->warn(new FarmLogMessage($farminfo['id'], "Cannot retrieve LA. Instance did not respond on {$db_item_info['external_ip']}:161."));
                                	else
                                	{
                                		$ip = @gethostbyname($instance_dns);
                                		$this->Logger->warn("[FarmID: {$farminfo['id']}] Cannot retrieve LA. Instance did not respond on {$ip}.");
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
                                		}
                                    }
                                	
                                    preg_match_all("/[0-9]+/si", $SNMP->Get(".1.3.6.1.2.1.2.2.1.10.2"), $matches);
                                    $bw_in = $matches[0][0];
						                        
						            preg_match_all("/[0-9]+/si", $SNMP->Get(".1.3.6.1.2.1.2.2.1.16.2"), $matches);
						            $bw_out = $matches[0][0];
						            
						            if ($bw_in > $db_item_info["bwusage_in"] && ($bw_in-(int)$db_item_info["bwusage_in"]) > 0)
						            	$bw_in_used[] = round(((int)$bw_in-(int)$db_item_info["bwusage_in"])/1024, 2);
						            else
						            	$bw_in_used[] = $bw_in/1024;
						            	
						            if ($bw_out > $db_item_info["bwusage_out"] && ($bw_out-(int)$db_item_info["bwusage_out"]) > 0)
						            	$bw_out_used[] = round(((int)$bw_out-(int)$db_item_info["bwusage_out"])/1024, 2);
						            else
						            	$bw_out_used[] = $bw_out/1024;
						            
						            $db->Execute("UPDATE farm_instances SET bwusage_in=?, bwusage_out=? WHERE id=?",
						            	array($bw_in, $bw_out, $db_item_info["id"])
						            );
                                    
                                	$la = (float)$res;
                                    $this->Logger->info("[FarmID: {$farminfo['id']}] LA (15 min average) on '{$item->instanceId}' = {$la}");
                                    
                                    $roleLA += $la;
                                    
                                    $role_running_instances_with_la++;
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
							
							$scripting_timeout = (int)$db->GetOne("SELECT sum(timeout) FROM farm_role_scripts INNER JOIN 
								scripts ON scripts.id = farm_role_scripts.scriptid  
								WHERE farm_role_scripts.farmid=? AND farm_role_scripts.event_name=? AND 
								farm_role_scripts.ami_id=? AND scripts.issync='1'",
								array($db_item_info['farmid'], $scripting_event, $db_item_info['ami_id'])
							);
							
							if ($scripting_timeout)
								$launch_timeout = $launch_timeout+$scripting_timeout;
							
							$this->Logger->info("[FarmID: {$farminfo['id']}] Check event timeout: {$launch_timeout}");
								
                            if ($dtadded+$launch_timeout < time())
                            {
                                                                    
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
                                
                if ($role_running_instances_with_la > 0)
                	$AvgLA = round($roleLA/$role_running_instances_with_la, 2);
                else
                	$AvgLA = 0;
                	
                $this->Logger->info("[FarmID: {$farminfo['id']}] '{$role}' statistics: Running={$role_running_instances}/{$role_running_instances_with_la}, Terminated={$role_terminated_instances}, Pending={$role_pending_instances}, SumLA={$roleLA}, AvgLA={$AvgLA}");
                
                $db_ami_info = $db->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND ami_id=?", array($farminfo['id'], $ami));
                
                //
                //Checking if there are spare instances that need to be terminated
                //                                    
                if ($AvgLA <= $db_ami_info["min_LA"])
                {
                    if (count($role_instances_by_time) > $db_ami_info["min_count"])
                    {
                        $db_ami_info['name'] = $role;
                    	Scalr::FireEvent($farminfo['id'], new LAUnderMinimumEvent($db_ami_info, $AvgLA, $db_ami_info["min_LA"]));
                    	
                    	$this->Logger->debug(new FarmLogMessage($farminfo['id'], "Average LA for '{$role}' ({$AvgLA}) <= min_LA ({$db_ami_info["min_LA"]})"));
                    	
                    	
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
							$this->Logger->info("[FarmID: {$farminfo['id']}] Terminating '{$instanceinfo['instance_id']}'");
                        	$allow_terminate = false;
                        	
                        	// Shutdown an instance just before a full hour running 
	                        $response = $AmazonEC2Client->DescribeInstances($instanceinfo['instance_id']);
	                        if ($response && $response->reservationSet->item)
	                        {
                        		$launch_time = strtotime($response->reservationSet->item->instancesSet->item->launchTime);
                        		$time = 3600 - (time() - $time) % 3600;
                        		
                        		// Terminate instance in < 10 minutes for full hour. 
                        		if ($time <= 600)
                        			$allow_terminate = true;
                        		else
                        		{
                        			$timeout = round(($time - 600) / 60, 1);
                        			//
                        			$this->Logger->info(new FarmLogMessage($farminfo['id'], "Farm {$farminfo['name']}, role {$instanceinfo['role_name']} scaling down. {$instanceinfo['instance_id']} will be terminated in {$timeout} minutes"));
                        		}
	                        }
	                        //
                        	
                        	if ($allow_terminate)
                        	{                       
		                        try
		                        {
		                            $this->Logger->info(new FarmLogMessage($farminfo['id'], "Sending termination request for instance '{$instanceinfo["instance_id"]}' ({$instanceinfo["external_ip"]}) "));
		                        	$AmazonEC2Client->TerminateInstances(array($instanceinfo["instance_id"]));
		                        }
		                        catch (Exception $e)
		                        {
		                            $this->Logger->fatal("[FarmID: {$farminfo['id']}] Cannot terminate {$item->instanceId}': {$e->getMessage()}");
		                        }
                        	}
                        }
                    }
                    else 
                    {
                        // Add entry to farm log
                        if ($db_ami_info["min_count"] > 1)
                    		$this->Logger->debug(new FarmLogMessage($farminfo['id'], "Role {$role} is idle, but needs at least {$db_ami_info["min_count"]} instances, currently running: ".count($role_instances_by_time)."."));
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
                    Scalr::FireEvent($farminfo['id'], new LAOverMaximumEvent($db_ami_info, $AvgLA, $db_ami_info["max_LA"]));
                    
                    // Add entry to farm log
                    $this->Logger->info(new FarmLogMessage($farminfo['id'], "Average LA for '{$role}' ({$AvgLA}) >= max_LA ({$db_ami_info["max_LA"]})"));
                }
                elseif (count($role_instances_by_time) == 0)
                {
                    if ($role_pending_instances == 0)
                    {
	                	$need_new_instance = true; 
	                    // Add entry to farm log
	                    $this->Logger->warn(new FarmLogMessage($farminfo['id'], "Disaster: No instances running in role {$role}!"));
                    }
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
                            $instance_id = Scalr::RunInstance($AmazonEC2Client, CONFIG::$SECGROUP_PREFIX.$role, $farminfo["id"], $role, $farminfo["hash"], $ami, false, true);
                            
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
                    	$this->Logger->info(new FarmLogMessage($farminfo['id'], "Role {$role} is full. MaxInstances ({$db_ami_info["max_count"]}) = Instances count (".count($role_instances_by_time)."). Pending: {$role_pending_instances} instances"));
                    }
                }
                
            }
            
            //
            // Update statistics
            //
			$this->Logger->info("Updating statistics for farm.");
                
			$current_stat = $db->GetRow("SELECT * FROM farm_stats WHERE farmid=? AND month=? AND year=?",
				array($farminfo['id'], date("m"), date("Y"))
			);
                
			foreach ($ec2_items as $ami_id => $items)
			{				
				foreach ($items as $item)
				{
					$launch_time = strtotime($item->launchTime);
					$uptime = time() - $launch_time;
	                    
					$last_uptime = $db->GetOne("SELECT uptime FROM farm_instances WHERE instance_id=?", array($item->instanceId));
					$uptime_delta = $uptime-$last_uptime;
	                    
					$stat_uptime[$item->instanceType] += $uptime_delta;
					
					$db->Execute("UPDATE farm_instances SET uptime=? WHERE instance_id=?",
						array($uptime, $item->instanceId)
					);
				}
			}
                                
			if (!$current_stat)
			{
				$db->Execute("INSERT INTO farm_stats SET farmid=?, month=?, year=?",
					array($farminfo['id'], date("m"), date("Y"))
				);
			}
			
			$data = array(
                (int)array_sum((array)$bw_in_used),
                (int)array_sum((array)$bw_out_used),
                (int)$stat_uptime['m1.small'],
                (int)$stat_uptime['m1.large'],
                (int)$stat_uptime['m1.xlarge'],
                (int)$stat_uptime['c1.medium'],
                (int)$stat_uptime['c1.xlarge'],
                
                time(),
                $farminfo['id'],
                date("m"),
                date("Y")
			);
						
			$db->Execute("UPDATE farm_stats SET 
                bw_in		= bw_in+?, 
                bw_out		= bw_out+?, 
                m1_small	= m1_small+?,
                m1_large	= m1_large+?,
                m1_xlarge	= m1_xlarge+?,
                c1_medium	= c1_medium+?,
                c1_xlarge	= c1_xlarge+?,
                dtlastupdate = ?
                WHERE farmid = ? AND month = ? AND year = ?
			", $data);                
                
 			//
			//Statistics update - end
			//
        }
    }
?>