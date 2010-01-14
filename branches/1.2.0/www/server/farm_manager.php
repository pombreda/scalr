<?
    $context = 6;
    			
    try
    {
		$enable_json = true;
		require("../src/prepend.inc.php");
		
	    set_time_limit(360);
	    
	    $Validator = new Validator();
	    
	    //
	    // Prepare input data
	    //
	    $farm_id = (int)$req_farm_id;
	    $farm_name = $req_farm_name;
	    $farm_roles_launch_order = $req_launch_order_type;
	    $roles = @file_get_contents("php://input");
		$roles = json_decode($roles, true);
    	
    	if ($farm_id && !$_SESSION['farm_builder_region'])
			throw new Exception(_("Session expired. Please login again."));
    	
		define("SUB_TRANSACTIONID", rand(10000, 99999));
        define("LOGGER_FARMID", $farm_id);
			
    	$uid = 0;
    	// Get User ID
    	if ($_SESSION['uid'] == 0)
	    {
	        if (!$farm_id)
	        	throw new Exception(_("You don't have permissions for this action"));
	         
			$uid = $db->GetOne("SELECT clientid FROM farms WHERE id=?", array($farm_id));
	    }
	    else 
	        $uid = $_SESSION['uid'];
	    
	    $Client = Client::Load($uid);
			    
	    // Create AmazonEC2 Client object
		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['farm_builder_region'])); //TODO: region
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);

		// Validate farm name
		if (!$Validator->IsNotEmpty($farm_name))
			throw new Exception(_("Farm name required"));
		
		// Instances limit
		$client_max_instances = $Client->GetSettingValue(CLIENT_SETTINGS::MAX_INSTANCES_LIMIT);
    	$i_limit = $client_max_instances ? $client_max_instances : CONFIG::$CLIENT_MAX_INSTANCES;

    	// EIPs limit
    	$client_max_eips = $Client->GetSettingValue(CLIENT_SETTINGS::MAX_EIPS_LIMIT);
		$eips_limit = $client_max_eips ? $client_max_eips : CONFIG::$CLIENT_MAX_EIPS;
    	
		// Prepare role information
    	$total_max_count = 0;
    	$farm_roles = array();
    	
        // Validate input vars
		foreach ($roles as $role)
		{
			if (!$role)
				continue;
				
			$rolename = $db->GetOne("SELECT name FROM roles WHERE ami_id=?", array($role['ami_id']));
            $farm_roles[$role['ami_id']] = $role;
			
            // Create empty DBFarmRole object (Only for validation)
            $DBFarmRole = new DBFarmRole(0);
            $DBFarmRole->AMIID = $role['ami_id'];
            
            /* Validate scaling */
            $minCount = (int)$role['settings'][DBFarmRole::SETTING_SCALING_MIN_INSTANCES];
			if ($minCount <=0 || $minCount > 400)
				throw new Exception(sprintf(_("Min instances for '%s' must be a number between 1 and 400"), $rolename));
                   
			$maxCount = (int)$role['settings'][DBFarmRole::SETTING_SCALING_MAX_INSTANCES];
			if ($maxCount < 1 || $maxCount > 400)
				throw new Exception(sprintf(_("Max instances for '%s' must be a number between 1 and 400"), $rolename));

			if ($maxCount < $minCount)
				throw new Exception(sprintf(_("Max instances should be greater or equal than Min instances for role '%s'"), $rolename));
				
			$polling_interval = (int)$role['settings'][DBFarmRole::SETTING_SCALING_POLLING_INTERVAL];
			if ($polling_interval < 1 || $polling_interval > 50)
				throw new Exception(sprintf(_("Polling interval for role '%s' must be a number between 1 and 50"), $rolename));
				
			$total_max_count = $total_max_count+$maxCount;
                   
			if ($role['settings'][DBFarmRole::SETTING_AWS_USE_ELASIC_IPS])
                $need_elastic_ips_for_farm += $maxCount;
            

			/** Validate BW based scaling **/
            foreach (RoleScalingManager::$ScalingAlgos as $Algo)
            	$Algo->ValidateConfiguration($role['options']['scaling_algos'], $DBFarmRole);
            	
			if ($role['settings'][DBFarmRole::SETTING_AWS_USE_EBS] && $role['settings'][DBFarmRole::SETTING_AWS_AVAIL_ZONE] == "")
				throw new Exception(sprintf(_("EBS cannot be enabled if Placement is set to 'Choose randomly'. Please select a different option for 'Placement' parameter for role '%s'."), $rolename));
				
			if ($role['options']['mysql_data_storage_engine'] == MYSQL_STORAGE_ENGINE::EBS)
			{
				if ($role['settings'][DBFarmRole::SETTING_AWS_AVAIL_ZONE] == "" || $role['settings'][DBFarmRole::SETTING_AWS_AVAIL_ZONE] == "x-scalr-diff")
					throw new Exception(sprintf(_("If you want to use EBS as MySQL data storage, you should select specific 'Placement' parameter for role '%s'."), $rolename));
			}
				
			if ($role['settings'][DBFarmRole::SETTING_AWS_USE_EBS] && $role['settings'][DBFarmRole::SETTING_AWS_EBS_SIZE])
			{
				if ($role['settings'][DBFarmRole::SETTING_AWS_EBS_SIZE] < 1 || $role['settings'][DBFarmRole::SETTING_AWS_EBS_SIZE] > 1000)
					throw new Exception(sprintf(_("EBS volume size for role '%s' must be between 1 and 1000 GB"), $rolename));
			}
				
			if ($role['alias'] == ROLE_ALIAS::MYSQL)
            {
				if (!$Validator->IsNumeric($role['options']['mysql_bundle_every']) || $role['options']['mysql_bundle_every'] < 1)
					throw new Exception(_("'Mysql bundle every' must be a number > 0"));
                        
				if ($role['options']['mysql_make_backup'] == 1)
				{
					if (!$Validator->IsNumeric($role['options']['mysql_make_backup_every']) || $role['options']['mysql_make_backup_every'] < 1)
						throw new Exception(_("'Mysql backup every' must be a number > 0"));
						
					//TODO: Move 15 minutes limit to config
					if ($role['options']['mysql_make_backup_every'] < 15)
						throw new Exception(_("Minimum allowed value for 'Mysql backup every' is 15 minutes"));
				}
				
				$farm_mysql_bundle_every = $role['options']['mysql_bundle_every'];
				$farm_mysql_bundle = (int)$role['options']['mysql_bundle'];
				$farm_mysql_make_backup_every = $role['options']['mysql_make_backup_every'];
				$farm_mysql_make_backup = (int)$role['options']['mysql_make_backup'];
				
				$farm_mysql_data_storage_engine = $role['options']['mysql_data_storage_engine']; 
				$farm_mysql_ebs_size = (int)$role['options']['mysql_ebs_size'];
			}
        }
        
        // Check limits
        $used_slots = $db->GetOne("SELECT SUM(value) FROM farm_role_settings WHERE name=? 
	        AND farm_roleid IN (SELECT id FROM farm_roles WHERE farmid IN (SELECT id FROM farms WHERE clientid=?) AND farmid != ?)",
	        array(DBFarmRole::SETTING_SCALING_MAX_INSTANCES, $uid, $farm_id)
	    );
        
        if ($used_slots+$total_max_count > $i_limit)
			throw new Exception(sprintf(_("The total amount of instances across all your farms is %s (aggregated Maximum instances Setting for all roles across all farms). If all farms will be running simultaneously you might hit the Amazon Limit of %s simultaneously running instances. Contact  Amazon to increase your instances limit, and then update this value in <a href=\"https://scalr.net/system_settings.php\">Settings->System->Instances limit</a>."), ($used_slots+$total_max_count), $i_limit));
		
		$used_ips = $db->GetOne("SELECT COUNT(*) FROM elastic_ips WHERE clientid=? AND farmid != ?", array($uid, $farm_id));
		if ($used_ips+$need_elastic_ips_for_farm > $eips_limit)
			throw new Exception(sprintf(_("According to your settings, scalr can alocate %s Elastic IPs. With your current farm settings, %s IPs need to be allocated. %s IPs already reserved across your farms."), 
				$eips_limit, $need_elastic_ips_for_farm, ($used_ips+$need_elastic_ips_for_farm))
			);
			
		$db->BeginTrans();
		$transaction_started = true;		
		
		// comments from farm_add (edit form)
		$comments = trim($req_farm_comments);		
			
	    switch($req_action)
	    {            
	        case "create":
	            	        	 
	        	// Count client farms
	        	$farms_count = $db->GetOne("SELECT COUNT(*) FROM farms WHERE clientid=?", array($uid));
	        	
	        	// Check farms limit
	        	if ($farms_count >= $Client->FarmsLimit && $Client->FarmsLimit != 0)
					throw new Exception(_("Sorry, you have reached maximum allowed amount of running farms."));
	        	
				// Prepare farm options
				$farmhash = $Crypto->Sault(14);
				$create_key_pair = true;
                $create_farm_s3_bucket = true;
                
                
                try
                {
	                // Create farm in database
	                $db->Execute("INSERT INTO farms SET 
						status='0', 
						name=?, 
						clientid=?, 
						hash=?, 
						dtadded=NOW(),
						region = ?,
						farm_roles_launch_order = ?,
						comments = ?
					", array( 
	                	trim($farm_name), 
						$_SESSION['uid'], 
						$farmhash, 
						$_SESSION['farm_builder_region'],
						$farm_roles_launch_order,
						$comments
	                ));
	                
	                
	                $farm_id = $db->Insert_ID();
	                
	                // Set farm S3 bucket name
	                $bucket_name = "farm-{$farm_id}-{$Client->AWSAccountID}";
                }
                catch(Exception $e)
                {
                	$db->RollbackTrans();
                    throw new Exception($e->getMessage(), E_ERROR);	
                }
	        	
	            break;

	        case "edit":
	        	
	        	// validate farmid
	    		$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($farm_id));
		        if (!$farminfo || ($uid != 0 && $uid != $farminfo["clientid"]))
					throw new Exception(_("Farm not found in database"));
	        	
				try
				{
					$db->Execute("UPDATE farms SET   
						name=?, 
						farm_roles_launch_order = ?,
						comments = ?
						WHERE id=?", 
					array(  
						trim($farm_name), 
						$farm_roles_launch_order,
						$comments,
						$farm_id
					));
				}
				catch(Exception $e)
				{
					$db->RollbackTrans();
                    throw new Exception($e->getMessage(), E_ERROR);
				}
					
	        	break;
	    }
	    	    
	    if (in_array($req_action, array('create','edit')))
	    {
	    	$DBFarm = new DBFarm($farm_id);
	    	
	    	if ($bucket_name)
	    		$DBFarm->SetSetting(DBFarm::SETTING_AWS_S3_BUCKET_NAME, $bucket_name);
	                
			$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_BCP_ENABLED, $farm_mysql_make_backup);
			$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_BCP_EVERY, $farm_mysql_make_backup_every);	                
			$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_BUNDLE_ENABLED, $farm_mysql_bundle);
			$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_BUNDLE_EVERY, $farm_mysql_bundle_every);
			$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_DATA_STORAGE_ENGINE, $farm_mysql_data_storage_engine);	                
			$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_EBS_VOLUME_SIZE, $farm_mysql_ebs_size);
	    	
	    	// Remove unused roles
	    	try
			{
                $db_farm_roles = $db->GetAll("SELECT * FROM farm_roles WHERE farmid=?", array($farm_id));
                foreach ($db_farm_roles as $farm_ami)
                {
                    if (!$farm_roles[$farm_ami["ami_id"]])
                    {
                        if (0 == $db->GetOne("SELECT COUNT(*) FROM zones WHERE ami_id=? AND farmid=?", array($farm_ami["ami_id"], $farm_id)))
                        {
							$DBFarmRole = DBFarmRole::Load($farm_id, $farm_ami["ami_id"]);
							$farm_roleid = $DBFarmRole->ID;
                           	$DBFarmRole->Delete();
                           	$DBFarmRole = null;
                       
							$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farm_roleid=?", array($farm_roleid));
                           	foreach ($instances as $instance)
                           	{
                               if ($instance["instance_id"] && $instance['state'] != INSTANCE_STATE::TERMINATED)
                               {
									try
                               		{
                                   		$res = $AmazonEC2Client->TerminateInstances(array($instance["instance_id"]));
                                   		if ($res instanceof SoapFault )
                                       		$Logger->fatal(new FarmLogMessage($farm_roleid ,sprintf(_("Cannot terminate instance '%s'. Please do it manualy. (%s)"), $instance["instance_id"], $res->faultString)));
                               		}
                               		catch (Exception $e)
                               		{
                                   		$Logger->fatal(new FarmLogMessage($farm_roleid ,sprintf(_("Cannot terminate instance '%s'. Please do it manualy. (%s)"), $instance["instance_id"], $e->getMessage())));
                               		}
                               }
							}
                        }
                        else
                        {
                            $rolename = $db->GetOne("SELECT name FROM roles WHERE ami_id='{$farm_ami["ami_id"]}'");
                            $sitename = $db->GetOne("SELECT zone FROM zones WHERE ami_id=? AND farmid=?", array($farm_ami["ami_id"], $farm_id));
                            throw new Exception(sprintf(_("You cannot delete role %s because there are DNS records bind to it. Please delete application %s first."), $rolename, $sitename));
                        }
                    }
                }
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			// Add and update roles.
	    	try
			{
				foreach ($farm_roles as $ami_id => $role)
				{
                    $info = $db->GetRow("SELECT * FROM farm_roles WHERE farmid=? AND ami_id=?", array($farm_id, $ami_id));
                    if ($info)
                    {
                        $DBFarmRole = DBFarmRole::Load($farm_id, $ami_id);
                    	
                    	if (!$DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_USE_ELASIC_IPS) && $role['settings'][DBFarmRole::SETTING_AWS_USE_ELASIC_IPS])
                    		$assign_elastic_ips[$info['id']] = $info['ami_id'];
                    	
                    	$db->Execute("UPDATE farm_roles SET 
                            reboot_timeout=?, launch_timeout=?, status_timeout=?, launch_index=?
                            WHERE farmid=? AND ami_id=?
                            ", array(
                            (int)$role['options']['reboot_timeout'],
                            (int)$role['options']['launch_timeout'],
                         	(int)$role['options']['status_timeout'],
                         	(int)$role['launch_index'],
                            $farm_id, 
                            $ami_id
						));
						
						$DBFarmRole = DBFarmRole::Load($farm_id, $ami_id);
						
						$farm_roles[$ami_id]['DBFarmRole'] = $DBFarmRole;
                    }
    	            else 
    	            {
                        $db->Execute("INSERT INTO farm_roles SET 
							farmid=?, ami_id=?,
                            reboot_timeout=?, launch_timeout=?, status_timeout = ?, launch_index = ?
                            ", array( 
                        		$farm_id, 
                        		$ami_id, 
	                            (int)$role['options']['reboot_timeout'],
                            	(int)$role['options']['launch_timeout'],
                         		(int)$role['options']['status_timeout'],
                         		(int)$role['launch_index']
						));
						
						$farm_role_id = $db->Insert_ID();
						
						/**
						 * We need to init object manually (DB transaction not closed at this point)
						 */
						$DBFarmRole = new DBFarmRole($farm_role_id);
						$DBFarmRole->FarmID = $farm_id;
						$DBFarmRole->AMIID = $ami_id; 
						
						$farm_roles[$ami_id]['DBFarmRole'] = $DBFarmRole;
					}
					
					foreach ($role['options']['scaling_algos'] as $k=>$v)
					{
						if ($k != TimeScalingAlgo::PROPERTY_TIME_PERIODS)
							$DBFarmRole->SetSetting($k, $v);
					}
											
					foreach ($role['settings'] as $k=>$v)
						$DBFarmRole->SetSetting($k, $v);
					
					/** Time scaling */
					//TODO: optimize this code...
					$db->Execute("DELETE FROM farm_role_scaling_times WHERE farm_roleid=?", 
						array($DBFarmRole->ID)
					);
					if ($DBFarmRole->GetSetting("scaling.time.enabled") == 1)
					{
						foreach ($role['options']['scaling_algos'][TimeScalingAlgo::PROPERTY_TIME_PERIODS] as $scal_period)
						{
							$chunks = explode(":", $scal_period);
							$db->Execute("INSERT INTO farm_role_scaling_times SET
								farm_roleid		= ?,
								start_time		= ?,
								end_time		= ?,
								days_of_week	= ?,
								instances_count	= ?
							", array(
								$DBFarmRole->ID,
								$chunks[0],
								$chunks[1],
								$chunks[2],
								$chunks[3]
							));
						}
					}	
					/*****************/
						
					/* Update role params */
										
					//var_dump($role);
					
					if (count($role['params']) > 0)
					{						
						$current_role_options = $db->GetAll("SELECT * FROM farm_role_options WHERE farm_roleid=?", array($DBFarmRole->ID));
						$role_opts = array();
						foreach ($current_role_options as $cro)
							$role_opts[$cro['hash']] = md5($cro['value']);
						
						//$db->Execute("DELETE FROM farm_role_options WHERE farm_roleid=?", array($DBFarmRole->ID));						
						$params = array();
						foreach ($role['params'] as $name => $value)
						{
							if (preg_match("/^(.*?)\[(.*?)\]$/", $name, $matches))
							{
								if (!$multiselect[$matches[1]])
									$params[$matches[1]] = array();
								
								if ($matches[2] != '' && $value == 1)
								{
									$params[$matches[1]][] = $matches[2];
								}
								continue;
							}
							else
								$params[$name] = $value;
						}

						$saved_opts = array();
						foreach($params as $name => $value)
						{
							if ($name)
							{
								$val = (is_array($value)) ? implode(',', $value) : $value;
								$hash = preg_replace("/[^A-Za-z0-9]+/", "_", strtolower($name));
								
								if (!$role_opts[$hash])
								{
									$db->Execute("INSERT INTO farm_role_options SET
										farmid		= ?,
										farm_roleid	= ?,
										name		= ?,
										value		= ?,
										hash	 	= ? 
										ON DUPLICATE KEY UPDATE name = ?
									", array(
										$farm_id,
										$DBFarmRole->ID,
										$name,
										$val,
										$hash,
										$name
									));
									
									$fire_event = true;
								}
								else
								{
									if (md5($val) != $role_opts[$hash])
									{
										$db->Execute("UPDATE farm_role_options SET value = ? WHERE
											farm_roleid	= ? AND hash = ?	
										", array(
											$val,
											$DBFarmRole->ID,
											$hash
										));
										
										$fire_event = true;
									}
								}
								
								// Submit event only for existing farm. 
								// If we create a new farm, no need to fire this event.
								if ($fire_event && $req_action == 'edit')
								{
									Scalr::FireEvent($farm_id, new RoleOptionChangedEvent(
										$DBFarmRole, $hash
									));
									
									$fire_event = false;
								}
								
								$saved_opts[] = $hash;
							}
						}	

						foreach ($role_opts as $k=>$v)
						{
							if (!in_array($k, array_values($saved_opts)))
							{
								$db->Execute("DELETE FROM farm_role_options WHERE farm_roleid = ? AND hash = ?",
									array($DBFarmRole->ID, $k)
								);
							}
						}
					}
					
					/* End of role params management */
					
					/* Add script options to databse */
					$db->Execute("DELETE FROM farm_role_scripts WHERE farm_roleid=?", array($DBFarmRole->ID));
					
					if (count($role['scripts']) > 0)
					{						
						foreach ($role['scripts'] as $script => $params)
						{							
							if ($params === false)
								continue;
	
							$config = $params['config'];
							$target = $params['target'];
							$version = $params['version'];
							$issync = $params['issync'];
							$timeout = (int)$params['timeout'];
							$order_index = (int)$params['order_index'];
							if (!$timeout)
								$timeout = CONFIG::$SYNCHRONOUS_SCRIPT_TIMEOUT;
							
							preg_match("/^(.*?)_([0-9]+)$/", $script, $matches);
							$event_name = $matches[1];
							$scriptid = $matches[2];
							if ($event_name && $scriptid)
							{
								$db->Execute("INSERT INTO farm_role_scripts SET
									scriptid	= ?,
									farmid		= ?,
									farm_roleid	= ?,
									params		= ?,
									event_name	= ?,
									target		= ?,
									version		= ?,
									timeout		= ?,
									issync		= ?,
									order_index = ?
								", array(
									$scriptid,
									$farm_id,
									$DBFarmRole->ID,
									serialize($config),
									$event_name,
									$target,
									$version,
									$timeout,
									$issync,
									$order_index
								));
							}
						}
					}
					/* End of scripting section */
				}
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			//exit();
			
			$Logger->getLogger("FarmEdit")->info("Farm Edit: {$farm_id}");
			
			try
			{
				$elbs = array();
				foreach ($farm_roles as $ami_id => $role)
				{
					$Logger->getLogger("FarmEdit")->info("Role: {$ami_id}");
								
					$AmazonELB = AmazonELB::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
					$AmazonELB->SetRegion($_SESSION['farm_builder_region']);
		        	$DBFarmRole = $role['DBFarmRole'];
					
					// Load balancer settings
		        	if ($role['settings'][DBFarmRole::SETTING_BALANCING_USE_ELB] == 1)
		        	{
		        		// Listeners
						$DBFarmRole->ClearSettings("lb.role.listener");
		        		$ELBListenersList = new ELBListenersList();
					    $li = 0;
					    foreach ($role['settings'] as $sk=>$sv)
					    {
					    	if (stristr($sk, "lb.role.listener"))
					    	{
					    		$li++;
					    		$listener_chunks = explode("#", $sv);
					    		$ELBListenersList->AddListener($listener_chunks[0], $listener_chunks[1], $listener_chunks[2]);
					    		$DBFarmRole->SetSetting("lb.role.listener.{$li}", $sv);
					    	}
					    }			
		        		
					    $Logger->getLogger("FarmEdit")->info("Role: {$ami_id}, Host: ".$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_HOSTNAME));
					    
		        		$avail_zones = array();
		        		$avail_zones_setting_hash = "";
					    foreach ($role['settings'] as $skey => $sval)
					    {
					    	if (preg_match("/^lb.avail_zone.(.*)?$/", $skey, $macthes))
					    	{
					    		if ($sval == 1)
					    			array_push($avail_zones, $macthes[1]);
					    			
					    		$avail_zones_setting_hash .= "[{$macthes[1]}:{$sval}]";
					    	}
					    }
					    
					    if (!$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_HOSTNAME))
		        		{
		        			$elb_name = sprintf("scalr-%s-%s", $farm_id, rand(100,999));
		        			
			        				        			
		        			//CREATE NEW ELB
		        			$elb_dns_name = $AmazonELB->CreateLoadBalancer($elb_name, $avail_zones, $ELBListenersList);
		        			
		        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_HOSTNAME, $elb_dns_name);
		        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_NAME, $elb_name);
		        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_AZ_HASH, $avail_zones_setting_hash);
		        			
		        			
		        			$elbs[] = $elb_dns_name;
		        			//Update healthcheck Settings 
		        		}
		        		
						$ELBHealthCheckType = new ELBHealthCheckType(
							$role['settings'][DBFarmRole::SETTING_BALANCING_HC_TARGET],
							$role['settings'][DBFarmRole::SETTING_BALANCING_HC_HTH],
							$role['settings'][DBFarmRole::SETTING_BALANCING_HC_INTERVAL],
							$role['settings'][DBFarmRole::SETTING_BALANCING_HC_TIMEOUT],
							$role['settings'][DBFarmRole::SETTING_BALANCING_HC_UTH]
						);
	        			
						$hash = md5(serialize($ELBHealthCheckType));
						
						if ($elb_name || ($hash != $DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_HC_HASH)))
						{
		        			//UPDATE CURRENT ELB
		        			$AmazonELB->ConfigureHealthCheck(
		        				$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME),
		        				$ELBHealthCheckType
		        			);
		        			
		        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_HC_HASH, $hash);
						}
						
						// Configure AVAIL zones for the LB
						if (!$elb_name && $avail_zones_setting_hash != $DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_AZ_HASH))
						{
							$info = $AmazonELB->DescribeLoadBalancers(array($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME)));
							$elb = $info->DescribeLoadBalancersResult->LoadBalancerDescriptions->member;
							
							$c = (array)$elb->AvailabilityZones;
							
							if (!is_array($c['member']))
								$c_zones = array($c['member']);
							else
								$c_zones = $c['member'];
								
							$add_avail_zones = array();
							$rem_avail_zones = array();
							foreach ($role['settings'] as $skey => $sval)
						    {
						    	if (preg_match("/^lb.avail_zone.(.*)?$/", $skey, $m))
						    	{
									if ($sval == 1 && !in_array($m[1], $c_zones))
										array_push($add_avail_zones, $m[1]);
									
									if ($sval == 0 && in_array($m[1], $c_zones))
										array_push($rem_avail_zones, $m[1]);
						    	}
						    }
						    
						    if (count($add_avail_zones) > 0)
						    {
						    	$AmazonELB->EnableAvailabilityZonesForLoadBalancer(
									$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME),
									$add_avail_zones
								);
						    }
						    
							if (count($rem_avail_zones) > 0)
						    {
						    	$AmazonELB->DisableAvailabilityZonesForLoadBalancer(
									$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME),
									$rem_avail_zones
								);
						    }
							
							$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_AZ_HASH, $avail_zones_setting_hash);
						}
		        	}
		        	else
		        	{
		        		if ($role['settings'][DBFarmRole::SETTING_BALANCING_HOSTNAME])
		        		{
		        			$AmazonELB->DeleteLoadBalancer($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME));
		        					        			
		        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_NAME, "");
		        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_HOSTNAME, "");
		        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB, "0");
		        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_HC_HASH, "");
		        		}
		        	}
				}

				// Asign elastic IPs
				if (count($assign_elastic_ips) > 0)
				{
					foreach ($assign_elastic_ips as $id => $ami_id)
					{
						if (!$id || !$ami_id)
							continue;

						$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND ami_id=? AND state != ?",
							array($farm_id, $ami_id, INSTANCE_STATE::TERMINATED)
						);
						
						foreach ($instances as $instance)
						{
							// Alocate new IP address
							$address = $AmazonEC2Client->AllocateAddress();
							
							// Add allocated IP address to database
							$db->Execute("INSERT INTO elastic_ips SET farmid=?, farm_roleid=?, ipaddress=?, state='0', instance_id='', clientid=?, instance_index=?",
								array($farm_id, $instance['farm_roleid'], $address->publicIp, $uid, $instance['index'])
							);
							
							$allocated_ips[] = $address->publicIp;
							
							$Logger->debug(sprintf(_("Allocated new IP: %s"), $ip['ipaddress']));
							
							// Waiting...
							$Logger->debug(_("Waiting 5 seconds..."));
							sleep(5);
							
							$assign_retries = 1;
							while (true)
							{
								try
								{
									// Associate elastic ip address with instance
									$AmazonEC2Client->AssociateAddress($instance['instance_id'], $address->publicIp);
								}
								catch(Exception $e)
								{
									if (!stristr($e->getMessage(), "does not belong to you") || $assign_retries == 3)
										throw new Exception($e->getMessage());
									else
									{
										// Waiting...
										$Logger->debug(_("Waiting 2 seconds..."));
										sleep(2);
										$assign_retries++;
										continue;
									}
								}
								
								break;
							}

							$Logger->info(sprintf(_("IP: %s assigned to instance '%s'"), $address->publicIp, $instance['instance_id']));
							
							// Update leastic IPs table
							$db->Execute("UPDATE elastic_ips SET state='1', instance_id=? WHERE ipaddress=?",
								array($instance['instance_id'], $address->publicIp)
							);
							
							// Update instance info in database
							$db->Execute("UPDATE farm_instances SET external_ip=?, isipchanged='1', isactive='0' WHERE instance_id=?",
								array($address->publicIp, $instance['instance_id'])
							);
							
							Scalr::FireEvent($farm_id, new IPAddressChangedEvent(DBInstance::LoadByIID($instance['instance_id']), $address->publicIp));
						}
					}
				}
				
				// Create S3 bucket
                if ($create_farm_s3_bucket)
                {
                    //
                    // Create S3 Bucket (For MySQL, BackUs, etc.)
                    //
                    $AmazonS3 = new AmazonS3($Client->AWSAccessKeyID, $Client->AWSAccessKey);
                    $buckets = $AmazonS3->ListBuckets();
                    $create_bucket = true;
                    foreach ($buckets as $bucket)
                    {
                        if ($bucket->Name == $bucket_name)
                        {
                           $create_bucket = false;
                           break;
                        }
                    }
                    
                    if ($create_bucket)
                    {
                       if ($AmazonS3->CreateBucket($bucket_name, $_SESSION['farm_builder_region']))
							$created_bucket = $bucket_name;
                    }
                }
                
                // Create security key-pair
                if ($create_key_pair)
                {
                	//
                    // Create FARM KeyPair
                    //
                        $key_name = "FARM-{$farm_id}";
                        $result = $AmazonEC2Client->CreateKeyPair($key_name);
                        if ($result->keyMaterial)
                        {
                            $DBFarm = new DBFarm($farm_id);
	        				$DBFarm->SetSetting(DBFarm::SETTING_AWS_PRIVATE_KEY, $result->keyMaterial);
	        				$DBFarm->SetSetting(DBFarm::SETTING_AWS_KEYPAIR_NAME, $key_name);
                        	
                        	$created_key_name = $key_name;
                        }
                        else
                            throw new Exception(_("Cannot create key pair for farm."), E_ERROR);
                }
			}
			catch(Exception $e)
			{
				 $db->RollbackTrans();
				 
				 if ($created_bucket)
				 	$AmazonS3->DeleteBucket($created_bucket);
				 	
				 if ($created_key_name)
				 	$AmazonEC2Client->DeleteKeyPair($created_key_name);
				 	
				 if (count($elbs) > 0)
				 {
				 	foreach ($elbs as $elb)
				 		$AmazonELB->DeleteLoadBalancer($elb);
				 }
				 	
				 foreach ($allocated_ips as $allocated_ip)
				 {
				 	if ($allocated_ip)
				 		$AmazonEC2Client->ReleaseAddress($allocated_ip);
				 }
				 	
				 throw new Exception($e->getMessage());
			}
	    }
    }
    catch(Exception $e)
    {
    	print json_encode(array("result" => "error", "data" => $e->getMessage()));
    	exit();
    }
    
    if ($transaction_started)
    	$db->CommitTrans();
    
    print json_encode(array("result" => "ok", "data" => $farm_id));
    exit();
?>