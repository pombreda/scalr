<?php

	class Scalr
	{
		private static $EventObservers = array();
		private static $DeferredEventObservers = array();
		private static $ConfigsCache = array();
		
		/**
		 * Attach observer
		 *
		 * @param EventObserver $observer
		 */
		public static function AttachObserver ($observer, $isdeffered)
		{
			if ($isdeffered)
				$list = & self::$DeferredEventObservers;
			else
				$list = & self::$EventObservers;
			
			if (array_search($observer, $list) !== false)
				throw new Exception(_('Observer already attached to class <Scalr>'));
				
			$list[] = $observer;
		}
		
		/**
		 * Method for multiprocess scripts. We must recreate DB connection created in constructor
		 */
		public static function ReconfigureObservers()
		{
			foreach (self::$EventObservers as &$observer)
			{
				if (method_exists($observer, "__construct"))
					$observer->__construct();
			}
		}
		
		/**
		 * Return observer configuration for farm
		 *
		 * @param string $farmid
		 * @param EventObserver $observer
		 * @return DataForm
		 */
		private static function GetFarmNotificationsConfig($farmid, $observer)
		{
			$DB = Core::GetDBInstance(NULL, true);
			
			Logger::getLogger(__CLASS__)->debug("GetFarmNotificationsConfig({$farmid}, {$observer->ObserverName})");
			
			// Reconfigure farm settings if changes made
			$farms = $DB->GetAll("SELECT farms.id as fid FROM farms INNER JOIN client_settings ON client_settings.clientid = farms.clientid WHERE client_settings.`key` = 'reconfigure_event_daemon' AND client_settings.`value` = '1'");
			if (count($farms) > 0)
			{
				Logger::getLogger(__CLASS__)->debug("Found ".count($farms)." with new settings. Cleaning cache.");
				foreach ($farms as $cfarmid)
				{
					Logger::getLogger(__CLASS__)->info("Cache for farm {$cfarmid["fid"]} cleaned.");
					self::$ConfigsCache[$cfarmid["fid"]] = false;
				}
			}
				
			// Update reconfig flag
			$DB->Execute("UPDATE client_settings SET `value`='0' WHERE `key`='reconfigure_event_daemon'");
							
			// Check config in cache
			if (!self::$ConfigsCache[$farmid] || !self::$ConfigsCache[$farmid][$observer->ObserverName])
			{
				Logger::getLogger(__CLASS__)->debug("There is no cached config for this farm or config updated. Loading config...");
				
				// Get configuration form
				self::$ConfigsCache[$farmid][$observer->ObserverName] = $observer->GetConfigurationForm();
				
				// Get farm observer id
				$farm_observer_id = $DB->GetOne("SELECT * FROM farm_event_observers 
					WHERE farmid=? AND event_observer_name=?",
					array($farmid, get_class($observer))
				);
								
				// Get Configuration values
				if ($farm_observer_id)
				{
					Logger::getLogger(__CLASS__)->info("Farm observer id: {$farm_observer_id}");
					
					$config_opts = $DB->Execute("SELECT * FROM farm_event_observers_config 
						WHERE observerid=?", array($farm_observer_id)
					);
					
					// Set value for each config option
					while($config_opt = $config_opts->FetchRow())
					{
						$field = &self::$ConfigsCache[$farmid][$observer->ObserverName]->GetFieldByName($config_opt['key']);
						if ($field)
							$field->Value = $config_opt['value'];
					}
				}
				else
					return false;
			}
			
			return self::$ConfigsCache[$farmid][$observer->ObserverName];
		}
		
		/**
		 * Fire event
		 *
		 * @param integer $farmid
		 * @param string $event_name
		 * @param string $event_message
		 */
		public static function FireDeferredEvent ($farmid, $event_type, $event_message)
		{
			try
			{
				// Notify class observers
				foreach (self::$DeferredEventObservers as $observer)
				{
					// Get observer config for farm
					$config = self::GetFarmNotificationsConfig($farmid, $observer);
					
					// If observer configured -> set config and fire event
					if ($config)
					{
						$observer->SetConfig($config);
						$res = call_user_func(array($observer, "On{$event_type}"), $event_message);
					}
				}
			}
			catch(Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal("Exception thrown in Scalr::FireEvent(): ".$e->getMessage());
			}
				
			return;
		}
		
		/**
		 * File event in database
		 *
		 * @param integer $farmid
		 * @param string $event_name
		 */
		public static function FireEvent($farmid, Event $event)
		{
			try
			{
				// Notify class observers
				foreach (self::$EventObservers as $observer)
				{
					$observer->SetFarmID($farmid);					
					Logger::getLogger(__CLASS__)->info(sprintf("Fire event: %s::%s", get_class($observer), "On{$event->GetName()}"));
					call_user_func(array($observer, "On{$event->GetName()}"), &$event);
				}
			}
			catch(Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal("Exception thrown in Scalr::FireEvent(): ".$e->getMessage());
				throw new Exception($e->getMessage());
			}
			
			// invoke StoreEvent method
			$reflect = new ReflectionMethod("Scalr", "StoreEvent");
			$reflect->invoke(null, $farmid, $event);
		}
		
		/**
		 * Store event in database
		 *
		 * @param integer $farmid
		 * @param string $event_name
		 */
		public static function StoreEvent($farmid, Event $event)
		{
			try
			{
				$DB = Core::GetDBInstance();

				// Get farm infor from database
				$farminfo = $DB->GetRow("SELECT * FROM farms WHERE id=?", array($farmid));
				if (!$farminfo)
					return;
				else
					$event->Farm = $farminfo;
				
				if ($event->InstanceInfo && $event->GetName() != EVENT_TYPE::HOST_DOWN)
					$event->InstanceInfo = $DB->GetRow("SELECT * FROM farm_instances WHERE id=?", array($event->InstanceInfo['id']));
					
				// Get Smarty object
				$Smarty = Core::GetSmartyInstance();
				
				// Assign vars
				$Smarty->assign(array("event" => $event));
				
				// Generate event message 
				$message = $Smarty->fetch("event_messages/{$event->GetName()}.tpl");
				$short_message = $Smarty->fetch("event_messages/{$event->GetName()}.short.tpl");
					
				// Store event in database
				$DB->Execute("INSERT INTO events SET 
					farmid	= ?, 
					type	= ?, 
					dtadded	= NOW(), 
					message	= ?,
					short_message = ?
					",
					array($farmid, $event->GetName(), $message, $short_message)
				);
				
				$eventid = $DB->Insert_ID();
				
				// Add task for fire deferred event
				TaskQueue::Attach(QUEUE_NAME::DEFERRED_EVENTS)->AppendTask(new FireDeferredEventTask($eventid));
			}
			catch(Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal(sprintf(_("Cannot store event in database: %s"), $e->getMessage()));
			}
		}
		
		/**
		 * Attach EBS volume to instance
		 *
		 * @param EC2Client $AmazonEC2Client
		 * @param array $instanceinfo
		 * @param array $farminfo
		 * @param string $volume_id
		 * @return boolean
		 */
		public static function AttachEBS2Instance($AmazonEC2Client, $instanceinfo, $farminfo, $volume_id)
		{
			$DB = Core::GetDBInstance();
			
			Logger::getLogger(__CLASS__)->info(new FarmLogMessage($farminfo['id'],
				sprintf(_("Attaching volume %s to instance %s"), $volume_id, $instanceinfo['instance_id'])
			));
			
			$AttachVolumeType = new AttachVolumeType();
			$AttachVolumeType->instanceId = $instanceinfo['instance_id'];
			$AttachVolumeType->volumeId = $volume_id;
			
			$SNMP = new SNMP();
			$SNMP->Connect($instanceinfo['external_ip'], 161, $farminfo['hash'], false, false, true);
			$result = $SNMP->GetTree("UCD-DISKIO-MIB::diskIODevice");
			
			$map = array(
				"a", "b", "c", "d", "e", "f", "g", "h", "i", "j", 
				"k", "l", "m", "n", "o", "p", "q", "r", "s", "t", 
				"u", "v", "w", "x", "y", "z"
			);
			
			$map_used = array();
			
			foreach ($result as $disk)
			{
				if (preg_match("/^sd([a-z])[0-9]*$/", $disk, $matches))
					array_push($map_used, $matches[1]);
			}
			
			$device_l = false;
			while (count($map) != 0 && (in_array($device_l, $map_used) || $device_l == false))
				$device_l = array_shift($map);
				
			if (!$device_l)
				throw new Exception(_("There is no available device letter on instance for attaching EBS"));
				
			$AttachVolumeType->device = "/dev/sd{$device_l}";
			$res = $AmazonEC2Client->AttachVolume($AttachVolumeType);
			
			if ($res->status == AMAZON_EBS_STATE::ATTACHING)
			{
				$DB->Execute("UPDATE farm_ebs SET device=?, instance_id=? WHERE volumeid=?", 
					array($AttachVolumeType->device, $instanceinfo['instance_id'], $volume_id)
				);
				
				// Check volume status
				$response = $AmazonEC2Client->DescribeVolumes($volume_id);
				$volume = $response->volumeSet->item;
				if ($volume->status == AMAZON_EBS_STATE::ATTACHING || $volume->status == AMAZON_EBS_STATE::IN_USE)
				{					
					$ebsinfo = $DB->GetRow("SELECT * FROM farm_ebs WHERE volumeid=?", array($volume_id));
					if (!$ebsinfo)
					{
						return true;
					}

					Logger::getLogger(__CLASS__)->info(new FarmLogMessage($farminfo['id'],
						sprintf(_("Volume %s status: %s"), $volume_id, $volume->status)
					));
					
					$farm_role_info = $DB->GetRow("SELECT * FROM farm_amis WHERE (ami_id=? OR replace_to_ami=?) AND farmid=?",
						array($instanceinfo['ami_id'], $instanceinfo['ami_id'], $farminfo['id'])
					);
					
					Logger::getLogger(__CLASS__)->info(new FarmLogMessage($farminfo['id'],
						sprintf(_("Need mount: %s"), $farm_role_info['ebs_mount'])
					));
					
					if ($volume->status == AMAZON_EBS_STATE::IN_USE)
					{										
						Logger::getLogger(__CLASS__)->info(new FarmLogMessage($farminfo['id'],
							sprintf(_("Volume %s successfully attached to instance %s"), $volume_id, $instanceinfo['instance_id'])
						));
						
						if ($farm_role_info['ebs_mount'] == 1)
						{
							$createfs = ($farm_role_info['ebs_snapid'] || $ebsinfo['isfsexists'] == 1) ? 0 : 1;
							
							Logger::getLogger(__CLASS__)->info(new FarmLogMessage($farminfo['id'],
								sprintf(_("Waiting 5 seconds"))
							));
							
							// Nicolas request. Device not avaiable on instance after attached state. need some time.
							sleep(5);
							
							Logger::getLogger(__CLASS__)->info(new FarmLogMessage($farminfo['id'],
								sprintf(_("Sending trap mountEBS to %s"), $instanceinfo['external_ip'])
							));
							
							$trap = vsprintf(SNMP_TRAP::MOUNT_EBS, array($AttachVolumeType->device, $farm_role_info['ebs_mountpoint'], $createfs));
				            $res = $SNMP->SendTrap($trap);
				            Logger::getLogger(__CLASS__)->info("[FarmID: {$farminfo['id']}] Sending SNMP Trap mountEBS ({$trap}) to '{$instanceinfo['instance_id']}' ('{$instanceinfo['external_ip']}') complete ({$res})");
				            
				            $DB->Execute("UPDATE farm_ebs SET state=?, isfsexists = '1' WHERE volumeid=?", array(FARM_EBS_STATE::MOUNTING, $volume_id));
						}
						else
						{
							$DB->Execute("UPDATE farm_ebs SET state=? WHERE volumeid=?", array(FARM_EBS_STATE::ATTACHED, $volume_id));
						}
					}
					elseif ($volume->status == AMAZON_EBS_STATE::ATTACHING)
					{
						if ($farm_role_info['ebs_mount'] == 1)
						{
							// Add task to queue for EBS volume mount
							TaskQueue::Attach(QUEUE_NAME::EBS_MOUNT)->Append(new EBSMountTask($volume_id));	
						}
						
						$DB->Execute("UPDATE farm_ebs SET state=? WHERE volumeid=?", array(FARM_EBS_STATE::ATTACHING, $volume_id));
					}
				}
				else
				{
					throw new Exception(sprintf(_("Volume status after attaching: %s"), $volume->status));
				}
			}
			else
				throw new Exception(_("Cannot attach volume now. Please try again later."));
		}
		
		/**
		 * Run new instance
		 *
		 * @param EC2Client $AmazonEC2Client
		 * @param string $sec_group
		 * @param integer $farmid
		 * @param string $role
		 * @param string $farmhash
		 * @param string $ami
		 * @param bool $dbmaster
		 * @param bool $active
		 * @return string Instance ID
		 */
		public static function RunInstance( AmazonEC2 $AmazonEC2Client, $sec_group, $farmid, $role, $farmhash, $ami, $dbmaster = false, $active = true, $avail_zone = "")
	    {
	        global $Crypto, $cpwd;
	        
	    	$DB = Core::GetDBInstance();
	        
	        $farminfo = $DB->GetRow("SELECT * FROM farms WHERE id='{$farmid}'");
	        
	        $Client = Client::Load($farminfo["clientid"]);
	        
	        $ami_info = $DB->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$ami}'");
	        
	        $alias = $ami_info["alias"];
	        $role_name = $ami_info["name"];
	                
	        $farm_role_info = $DB->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND (ami_id=? OR replace_to_ami=?)", array($farmid, $ami, $ami));
	        if ($farm_role_info)
	        	$i_type = $farm_role_info["instance_type"];
	        else
	        	$i_type = $DB->GetOne("SELECT instance_type FROM ami_roles WHERE ami_id='{$ami}'");
	
	       	//
	       	// Check Security group - start
	       	//	    
	       	
	        // get security group list for client
		    $client_security_groups = $AmazonEC2Client->DescribeSecurityGroups();
	        if (!$client_security_groups)
	           throw new Exception(_("Cannot describe security groups for client."));
	                
	        $client_security_groups = $client_security_groups->securityGroupInfo->item;
	        if ($client_security_groups instanceof stdClass)
	        	$client_security_groups = array($client_security_groups);  
	        
	        // Check security groups
	        $addSecGroup = true;
	        $parent_role_sec_group = false;
	        $addMysqlStatGroup = ($alias == ROLE_ALIAS::MYSQL) ? true : false;
	        foreach ($client_security_groups as $group)
	        {
	            // Group exist. No need to add new
	            if (strtolower($group->groupName) == strtolower($sec_group))
	        	    $addSecGroup = false;
				
	        	if (strtolower($group->groupName) == strtolower(CONFIG::$MYSQL_STAT_SEC_GROUP))
	        	    $addMysqlStatGroup = false;
	        	    
	        	if ($ami_info['prototype_role'] && strtolower($group->groupName) == strtolower(CONFIG::$SECGROUP_PREFIX.$ami_info['prototype_role']))
	        		$parent_role_sec_group = $group;
	        }
	        	
	    	if ($addSecGroup)
		    {
				$res = $AmazonEC2Client->CreateSecurityGroup($sec_group, $name);
				if (!$res)
					throw new Exception(_("Cannot create security group"), E_USER_ERROR);	                        

		    	$IpPermissionSet = new IpPermissionSetType();
				
		    	$group_rules = $DB->GetAll("SELECT * FROM security_rules WHERE roleid=?", array($ami_info['id']));
		    	
		    	//
				// Check parent security group
				//
				if ($parent_role_sec_group && count($group_rules) == 0)
					$IpPermissionSet->item = $parent_role_sec_group->ipPermissions->item; 
				else
				{
					if (count($group_rules) == 0)
					{
						$prototype_roleid = $DB->GetOne("SELECT id FROM ami_roles WHERE name=?", array($ami_info['prototype_role']));
						if (!$prototype_roleid)
							$prototype_roleid = $DB->GetOne("SELECT id FROM ami_roles WHERE name=?", array($alias));	
						
						// Get permission rules for group
			            $group_rules = $DB->GetAll("SELECT * FROM security_rules WHERE roleid=?", array($prototype_roleid));	                        
					}
		            
		            foreach ($group_rules as $rule)
		            {
		            	$group_rule = explode(":", $rule["rule"]);
		                $IpPermissionSet->AddItem($group_rule[0], $group_rule[1], $group_rule[2], null, array($group_rule[3]));
		            }
				}
	
	            // Create security group
	            $AmazonEC2Client->AuthorizeSecurityGroupIngress($Client->AWSAccountID, $sec_group, $IpPermissionSet);
		    }
		    
		    if ($addMysqlStatGroup)
		    {
		    	$res = $AmazonEC2Client->CreateSecurityGroup(CONFIG::$MYSQL_STAT_SEC_GROUP, "Security group for access to mysql replication status from Scalr app");
				if (!$res)
					throw new Exception(_("Cannot create security group"), E_USER_ERROR);	                        
			              
				// Get permission rules for group
	            $IpPermissionSet = new IpPermissionSetType();
	            //$ipProtocol, $fromPort, $toPort, $groups, $ipRanges
	            $ips = explode(",", CONFIG::$APP_SYS_IPADDRESS);
	            
	            foreach ($ips as $ip)
	            {
	            	if ($ip != '')
	            		$IpPermissionSet->AddItem("tcp", "3306", "3306", null, array(trim($ip)."/32"));
	            }
	
	            // Create security group
	            $AmazonEC2Client->AuthorizeSecurityGroupIngress($Client->AWSAccountID, CONFIG::$MYSQL_STAT_SEC_GROUP, $IpPermissionSet);
		    }
	        //
	        // Check Security group - end
	        //
	        if (!$farminfo['bucket_name'])
	        	$bucket_name = "FARM-{$farmid}-{$Client->AWSAccountID}";
	        else
	        	$bucket_name = $farminfo['bucket_name'];
	        	
	        $RunInstancesType = new RunInstancesType();
	        $RunInstancesType->imageId = $ami;
	        $RunInstancesType->minCount = 1;
	        $RunInstancesType->maxCount = 1;
	        $RunInstancesType->AddSecurityGroup("default");
	        
	        if ($alias == ROLE_ALIAS::MYSQL)
	        	$RunInstancesType->AddSecurityGroup(CONFIG::$MYSQL_STAT_SEC_GROUP);
	        
	        $RunInstancesType->AddSecurityGroup($sec_group);
	        
	        if ($farm_role_info["avail_zone"])
	        {
	        	if ($farm_role_info["avail_zone"] == "x-scalr-diff")
	        	{
	        		// Get list of all available zones
	        		$avail_zones_resp = $AmazonEC2Client->DescribeAvailabilityZones();
				    $avail_zones = array();
				    foreach ($avail_zones_resp->availabilityZoneInfo->item as $zone)
				    {
				    	if (strstr($zone->zoneState,'available')) //TODO:
				    		array_push($avail_zones, (string)$zone->zoneName);
				    }
	        		
				    // Get count of curently running instances
	        		$instance_count = $DB->GetOne("SELECT COUNT(*) FROM farm_instances WHERE farmid=? AND ami_id=? AND state != ?", 
	        			array($farmid, $ami, INSTANCE_STATE::PENDING_TERMINATE)
	        		);
	        		
	        		// Get zone index.
	        		$zone_index = $instance_count % count($avail_zones);
	        		
	        		LoggerManager::getLogger('RunInstance')->debug(sprintf(_("[FarmID: %s] Selected zone: %s"), $farmid, $avail_zones[$zone_index]));
	        		
	        		// Set avail zone
	        		$RunInstancesType->SetAvailabilityZone($avail_zones[$zone_index]);
	        	}
	        	else
	        		$RunInstancesType->SetAvailabilityZone($farm_role_info["avail_zone"]);
	        }
	        elseif ($avail_zone != '')
	        	$RunInstancesType->SetAvailabilityZone($avail_zone);        
	        
	        $RunInstancesType->additionalInfo = "";
	        $RunInstancesType->keyName = "FARM-{$farmid}";
	        $RunInstancesType->SetUserData("farmid={$farmid};role={$alias};eventhandlerurl=".CONFIG::$EVENTHANDLER_URL.";hash={$farmhash};s3bucket={$bucket_name};realrolename={$role_name};httpproto=".CONFIG::$HTTP_PROTO);
	        $RunInstancesType->instanceType = $i_type;

	        try
	        {
	        	$result = $AmazonEC2Client->RunInstances($RunInstancesType);
	        }
	        catch(Exception $e)
	        {
	        	LoggerManager::getLogger('RunInstance')->fatal(new FarmLogMessage($farmid, sprintf(_("Cannot launch new instance on role %s. %s"), $role_name, $e->getMessage())));
	            return false;
	        }
	        
	        if ($result->instancesSet)
	        {
	        	$avail_zone = $result->instancesSet->item->placement->availabilityZone;
	        	$isdbmaster = ($dbmaster) ? '1' : '0';
	        	$isactive = ($active) ? '1' : '0';
	            
	        	$instace_id = $result->instancesSet->item->instanceId;
		        $DB->Execute("INSERT INTO 
		        							farm_instances 
		        					  SET 
		        					  		farmid=?, 
		        					  		instance_id=?, 
		        					  		ami_id=?, 
		        					  		dtadded=NOW(), 
		        					  		isdbmaster=?,
		        					  		isactive = ?,
		        					  		role_name = ?,
		        					  		avail_zone = ?
		        			 ", array($farmid, $instace_id, $ami, $isdbmaster, $isactive, $role_name, $avail_zone));
	        }
	        else 
	        {
	            LoggerManager::getLogger('RunInstance')->fatal(new FarmLogMessage($farmid, sprintf(_("Cannot launch new instance. %s"), $result->faultstring)));
	            return false;
	        }
	        
	        return $instace_id;
	    }
	}
?>