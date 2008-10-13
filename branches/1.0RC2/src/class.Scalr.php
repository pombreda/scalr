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
			
			Logger::getLogger(__CLASS__)->info("GetFarmNotificationsConfig({$farmid}, {$observer->ObserverName})");
			
			// Reconfigure farm settings if changes made
			$farms = $DB->GetAll("SELECT farms.id as fid FROM farms INNER JOIN client_settings ON client_settings.clientid = farms.clientid WHERE client_settings.`key` = 'reconfigure_event_daemon' AND client_settings.`value` = '1'");
			if (count($farms) > 0)
			{
				Logger::getLogger(__CLASS__)->info("Found ".count($farms)." with new settings. Cleaning cache.");
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
				Logger::getLogger(__CLASS__)->info("There is no cached config for this farm or config updated. Loading config...");
				
				// Get configuration form
				self::$ConfigsCache[$farmid][$observer->ObserverName] = $observer->GetConfigurationForm();
				
				// Get farm observer id
				$farm_observer_id = $DB->GetOne("SELECT * FROM farm_event_observers 
					WHERE farmid=? AND event_observer_name=?",
					array($farmid, get_class($observer))
				);
				
				Logger::getLogger(__CLASS__)->info("Farm observer id: {$farm_observer_id}");
				
				// Get Configuration values
				if ($farm_observer_id)
				{
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
		public static function FireEvent($farmid, $event_type /* args1, args2 ... argN */)
		{
			$args = func_get_args();
			
			try
			{
				// Notify class observers
				foreach (self::$EventObservers as $observer)
				{
					$observer->SetFarmID($farmid);					
					Logger::getLogger(__CLASS__)->info(sprintf("Fire event: %s::%s", get_class($observer), "On{$event_type}"));
					call_user_func_array(array($observer, "On{$event_type}"), array_slice($args, 2));
				}
			}
			catch(Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal("Exception thrown in Scalr::FireEvent(): ".$e->getMessage());
				throw new Exception($e->getMessage());
			}
			
			// invoke StoreEvent method
			$reflect = new ReflectionMethod("Scalr", "StoreEvent");
			$reflect->invokeArgs(null, $args);
		}
		
		/**
		 * Store event in database
		 *
		 * @param integer $farmid
		 * @param string $event_name
		 */
		public static function StoreEvent($farmid, $event_type /* args1, args2 ... argN */)
		{
			try
			{
				$DB = Core::GetDBInstance();
				
				$ReflectionInterface = new ReflectionClass("IEventObserver");
				$event = $ReflectionInterface->getMethod("On{$event_type}");
				$props = $event->getParameters();
				$vars = array();
				
				// Get list of arguments
				$args = func_get_args();
				
				// Remove first argument - farmid
				array_shift($args);
				
				// Remove second argument - event_type
				array_shift($args);
				
				// Get farm infor from database
				$farminfo = $DB->GetRow("SELECT * FROM farms WHERE id=?", array($farmid));
				if (!$farminfo)
					return;
				else
					$vars["farm"] = $farminfo;
				
				// Generate template vars array
				foreach ($props as $prop)
				{
					if ($prop->name != 'instanceinfo')
						$vars[$prop->name] = array_shift($args);
					else
					{
						$info = array_shift($args);
						$ninfo = $DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
							array($info['id'])
						);
						
						$vars[$prop->name] = ($ninfo) ? $ninfo : $info;
					}
				}
				
				// Get Smarty object
				$Smarty = Core::GetSmartyInstance();
				
				// Assign vars
				$Smarty->assign($vars);
				
				// Generate event message 
				$message = $Smarty->fetch("event_messages/{$event_type}.tpl");
				$short_message = $Smarty->fetch("event_messages/{$event_type}.short.tpl");
					
				// Store event in database
				$DB->Execute("INSERT INTO events SET 
					farmid	= ?, 
					type	= ?, 
					dtadded	= NOW(), 
					message	= ?,
					short_message = ?
					",
					array($farmid, $event_type, $message, $short_message)
				);
				
				$eventid = $DB->Insert_ID();
				
				// Add task for fire deferred event
				TaskQueue::Attach(QUEUE_NAME::DEFERRED_EVENTS)->Put(new FireDeferredEventTask($eventid));
			}
			catch(Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal("Cannot store event in database: ".$e->getMessage());
			}
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
		public static function RunInstance($AmazonEC2Client, $sec_group, $farmid, $role, $farmhash, $ami, $dbmaster = false, $active = true)
	    {
	        $DB = Core::GetDBInstance();
	        
	        $farminfo = $DB->GetRow("SELECT * FROM farms WHERE id='{$farmid}'");
	        $clientinfo = $DB->GetRow("SELECT * FROM clients WHERE id='{$farminfo["clientid"]}'");
	        
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
	           throw new Exception("Cannot describe security groups for client.");
	                
	        $client_security_groups = $client_security_groups->securityGroupInfo->item;
	        if ($client_security_groups instanceof stdClass)
	        	$client_security_groups = array($client_security_groups);  
	        
	        // Check security groups
	        $addSecGroup = true;
	        $addMysqlStatGroup = ($alias == ROLE_ALIAS::MYSQL) ? true : false;
	        foreach ($client_security_groups as $group)
	        {
	            // Group exist. No need to add new
	            if (strtolower($group->groupName) == strtolower($sec_group))
	        	    $addSecGroup = false;
				
	        	if (strtolower($group->groupName) == strtolower(CONFIG::$MYSQL_STAT_SEC_GROUP))
	        	    $addMysqlStatGroup = false;
	        }
	        	
	    	if ($addSecGroup)
		    {
				$res = $AmazonEC2Client->CreateSecurityGroup($sec_group, $name);
				if (!$res)
					throw new Exception("Cannot create security group", E_USER_ERROR);	                        
	                           
				// Get permission rules for group
	            $group_rules = $DB->GetAll("SELECT * FROM security_rules WHERE roleid=(SELECT id FROM ami_roles WHERE name='{$alias}')");	                        
	            $IpPermissionSet = new IpPermissionSetType();
	            foreach ($group_rules as $rule)
	            {
	            	$group_rule = explode(":", $rule["rule"]);
	                $IpPermissionSet->AddItem($group_rule[0], $group_rule[1], $group_rule[2], null, array($group_rule[3]));
	            }
	
	            // Create security group
	            $AmazonEC2Client->AuthorizeSecurityGroupIngress($clientinfo['aws_accountid'], $sec_group, $IpPermissionSet);
		    }
		    
		    if ($addMysqlStatGroup)
		    {
		    	$res = $AmazonEC2Client->CreateSecurityGroup(CONFIG::$MYSQL_STAT_SEC_GROUP, "Security group for access to mysql replication status from Scalr app");
				if (!$res)
					throw new Exception("Cannot create security group", E_USER_ERROR);	                        
	                           
				// Get permission rules for group
	            $IpPermissionSet = new IpPermissionSetType();
	            //$ipProtocol, $fromPort, $toPort, $groups, $ipRanges
	            $IpPermissionSet->AddItem("tcp", "3306", "3306", null, array(CONFIG::$APP_SYS_IPADDRESS."/32"));
	
	            // Create security group
	            $AmazonEC2Client->AuthorizeSecurityGroupIngress($clientinfo['aws_accountid'], CONFIG::$MYSQL_STAT_SEC_GROUP, $IpPermissionSet);
		    }
	        //
	        // Check Security group - end
	        //
	        if (!$farminfo['bucket_name'])
	        	$bucket_name = "FARM-{$farmid}-{$clientinfo['aws_accountid']}";
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
	        	$RunInstancesType->SetAvailabilityZone($farm_role_info["avail_zone"]);
	        	        	
	        $RunInstancesType->additionalInfo = "";
	        $RunInstancesType->keyName = "FARM-{$farmid}";
	        $RunInstancesType->SetUserData("farmid={$farmid};role={$alias};eventhandlerurl=".CONFIG::$EVENTHANDLER_URL.";hash={$farmhash};s3bucket={$bucket_name};realrolename={$role_name};httpproto=".CONFIG::$HTTP_PROTO);
	        $RunInstancesType->instanceType = $i_type;
	                
	        $result = $AmazonEC2Client->RunInstances($RunInstancesType);
	        
	        if ($result->instancesSet)
	        {
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
		        					  		role_name = ?
		        			 ", array($farmid, $instace_id, $ami, $isdbmaster, $isactive, $role_name));
	        }
	        else 
	        {
	            LoggerManager::getLogger('RunInstance')->fatal($result->faultstring);
	            return false;
	        }
	        
	        return $instace_id;
	    }
	}
?>