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
				
			$DBRole = DBRole::loadById($role['role_id']);
            			
            // Create empty DBFarmRole object (Only for validation)
            $DBFarmRole = new DBFarmRole(0);
            $DBFarmRole->RoleID = $DBRole->id;
            
            /* Validate scaling */
            $minCount = (int)$role['settings'][DBFarmRole::SETTING_SCALING_MIN_INSTANCES];
			if ($minCount < 0 || $minCount > 400)
				throw new Exception(sprintf(_("Min instances for '%s' must be a number between 1 and 400"), $DBRole->name));
                   
			$maxCount = (int)$role['settings'][DBFarmRole::SETTING_SCALING_MAX_INSTANCES];
			if ($maxCount < 1 || $maxCount > 400)
				throw new Exception(sprintf(_("Max instances for '%s' must be a number between 1 and 400"), $DBRole->name));

			if ($maxCount < $minCount)
				throw new Exception(sprintf(_("Max instances should be greater or equal than Min instances for role '%s'"), $DBRole->name));
				
			$polling_interval = (int)$role['settings'][DBFarmRole::SETTING_SCALING_POLLING_INTERVAL];
			if ($polling_interval < 1 || $polling_interval > 50)
				throw new Exception(sprintf(_("Polling interval for role '%s' must be a number between 1 and 50"), $DBRole->name));
				
			$total_max_count = $total_max_count+$maxCount;
                   
			if ($role['settings'][DBFarmRole::SETTING_AWS_USE_ELASIC_IPS])
                $need_elastic_ips_for_farm += $maxCount;

			/** Validate BW based scaling **/
            foreach (RoleScalingManager::$ScalingAlgos as $Algo)
            	$Algo->ValidateConfiguration($role['options']['scaling_algos'], $DBFarmRole);
            	
			
			switch($DBRole->platform)
			{
				case SERVER_PLATFORMS::EC2:
					Modules_Platforms_Ec2_Helpers_Ebs::farmValidateRoleSettings($role['settings'], $DBRole->name);
					Modules_Platforms_Ec2_Helpers_Eip::farmValidateRoleSettings($role['settings'], $DBRole->name);
					Modules_Platforms_Ec2_Helpers_Elb::farmValidateRoleSettings($role['settings'], $DBRole->name);	
					break;
				
				case SERVER_PLATFORMS::RDS:
						Modules_Platforms_Rds_Helpers_Rds::farmValidateRoleSettings($role['settings'], $DBRole->name);
					break;
					
			}
			
			Scalr_Helpers_Dns::farmValidateRoleSettings($role['settings'], $DBRole->name);

			if ($role['alias'] == ROLE_ALIAS::MYSQL && $DBRole->platform == SERVER_PLATFORMS::EC2)
            {	            	            
	            //TODO: Move to Helper
				if ($role['settings'][DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE] == MYSQL_STORAGE_ENGINE::EBS)
				{
					if ($role['settings'][DBFarmRole::SETTING_AWS_AVAIL_ZONE] == "" || $role['settings'][DBFarmRole::SETTING_AWS_AVAIL_ZONE] == "x-scalr-diff")
						throw new Exception(sprintf(_("If you want to use EBS as MySQL data storage, you should select specific 'Placement' parameter for role '%s'."), $DBRole->name));
				}
            	
            	if ($role['settings'][DBFarmRole::SETTING_MYSQL_BCP_ENABLED] == 1)
				{
					if (!$Validator->IsNumeric($role['settings'][DBFarmRole::SETTING_MYSQL_BCP_EVERY]) || $role['settings'][DBFarmRole::SETTING_MYSQL_BCP_EVERY] < 1)
						throw new Exception(_("'Mysql backup every' must be a number > 0"));
						
					//TODO: Move 15 minutes limit to config
					if ($role['settings'][DBFarmRole::SETTING_MYSQL_BCP_EVERY] < 15)
						throw new Exception(_("Minimum allowed value for 'Mysql backup every' is 15 minutes"));
				}
				
				if ((int)$role['settings'][DBFarmRole::SETTING_MYSQL_BUNDLE_ENABLED] == 1)
				{
					if (!$Validator->IsNumeric($role['settings'][DBFarmRole::SETTING_MYSQL_BUNDLE_EVERY]) || $role['settings'][DBFarmRole::SETTING_MYSQL_BUNDLE_EVERY] < 1)
						throw new Exception(_("'Mysql bundle every' must be a number > 0"));
					
					//pbw1_hh, pbw2_hh, pbw1_mm, pbw2_mm
					$pbw_from = (int)"{$role['settings']['mysql.pbw1_hh']}{$role['settings']['mysql.pbw1_mm']}";
					$pbw_to = (int)"{$role['settings']['mysql.pbw2_hh']}{$role['settings']['mysql.pbw2_mm']}";
					if ($role['settings']['mysql.pbw1_hh'] < 0 || $role['settings']['mysql.pbw1_hh'] > 24)
						throw new Exception(_("Please specify correct 'Preferred bundle window' in format: hh24:mi - hh24:mi"));
						
					if ($role['settings']['mysql.pbw2_hh'] < 0 || $role['settings']['mysql.pbw2_hh'] > 24)
						throw new Exception(_("Please specify correct 'Preferred bundle window' in format: hh24:mi - hh24:mi"));
					
					if ($role['settings']['mysql.pbw1_mm'] < 0 || $role['settings']['mysql.pbw1_mm'] > 59)
						throw new Exception(_("Please specify correct 'Preferred bundle window' in format: hh24:mi - hh24:mi"));
	
					if ($role['settings']['mysql.pbw2_mm'] < 0 || $role['settings']['mysql.pbw2_mm'] > 59)
						throw new Exception(_("Please specify correct 'Preferred bundle window' in format: hh24:mi - hh24:mi"));
						
					if ($pbw_from > $pbw_to)
						throw new Exception(_("Start time for 'Preferred bundle window' should be greater than end time"));
						
					if ($pbw_to-$pbw_from < 60)
						throw new Exception(_("Preferred bundle window should be at least 1 hour"));
						
					$role['settings'][DBFarmRole::SETTING_MYSQL_BUNDLE_WINDOW_START] = $pbw_from;
					$role['settings'][DBFarmRole::SETTING_MYSQL_BUNDLE_WINDOW_END] = $pbw_to;
				}
			}
			
			$farm_roles[$role['role_id']] = $role;
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
	                $bucket_name = "farm-{$farmhash}-{$Client->AWSAccountID}";
                }
                catch(Exception $e)
                {
                	$db->RollbackTrans();
                    throw new Exception($e->getMessage(), E_ERROR);	
                }
	        	
	            break;

	        case "edit":
	        	
	        	// validate farmid
	    		$farminfo = $db->GetRow("SELECT clientid FROM farms WHERE id=?", array($farm_id));
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
	                	    	
	    	// Remove unused roles
	    	try
			{
                $db_farm_roles = $db->GetAll("SELECT * FROM farm_roles WHERE farmid=?", array($farm_id));
                foreach ($db_farm_roles as $dbfarm_role)
                {
                    if (!$farm_roles[$dbfarm_role["role_id"]])
                    {
                        if (0 == $db->GetOne("SELECT COUNT(*) FROM dns_zones WHERE farm_roleid=?", array($dbfarm_role["id"])))
                        {
							$DBFarmRole = DBFarmRole::LoadByID($dbfarm_role['id']);
                           	$DBFarmRole->Delete();
                           	$DBFarmRole = null;
                       
							$servers = $db->GetAll("SELECT server_id FROM servers WHERE farm_roleid=?", array($dbfarm_role['id']));
                           	foreach ($servers as $server)
                           	{
								$DBServer = DBServer::LoadByID($server['server_id']);
                           		
                           		if ($DBServer->status != SERVER_STATUS::TERMINATED)
                           		{
									try
									{
                           				PlatformFactory::NewPlatform($DBServer->platform)->TerminateServer($DBServer);
                           				
                           				$db->Execute("UPDATE servers_history SET
											dtterminated	= NOW(),
											terminate_reason	= ?
											WHERE server_id = ?
										", array(
											sprintf("Farm terminated"),
											$DBServer->serverId
										));
									}
									catch(Exception $e){}
									
									$DBServer->status = SERVER_STATUS::TERMINATED;
									
									if (defined("SCALR_SERVER_TZ"))
									{
										$tz = date_default_timezone_get();
										date_default_timezone_set(SCALR_SERVER_TZ);
									}
										
									$DBServer->dateShutdownScheduled = date("Y-m-d H:i:s");
									
									if ($tz)
										date_default_timezone_set($tz);
									
									$DBServer->Save();
                           		}
							}
                        }
                        else
                        {
                            $rolename = $db->GetOne("SELECT name FROM roles WHERE id='{$dbfarm_role["id"]}'");
                            $sitename = $db->GetOne("SELECT zone_name FROM dns_zones WHERE farm_roleid=?", array($dbfarm_role['id']));
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
				foreach ($farm_roles as $role_id => $role)
				{
                    $info = $db->GetRow("SELECT * FROM farm_roles WHERE farmid=? AND role_id=?", array($farm_id, $role_id));
                    if ($info)
                    {
                        $DBFarmRole = DBFarmRole::LoadByID($info['id']);
                    	
                    	$db->Execute("UPDATE farm_roles SET 
                            reboot_timeout=?, launch_timeout=?, status_timeout=?, launch_index=?
                            WHERE farmid=? AND role_id=?
                            ", array(
                            (int)$role['options']['reboot_timeout'],
                            (int)$role['options']['launch_timeout'],
                         	(int)$role['options']['status_timeout'],
                         	(int)$role['launch_index'],
                            $farm_id, 
                            $role_id
						));
						
						$DBFarmRole = DBFarmRole::LoadByID($info['id']);
						
						$farm_roles[$ami_id]['DBFarmRole'] = $DBFarmRole;
                    }
    	            else 
    	            {
                        $DBRole = DBRole::loadById($role_id);
    	            	
    	            	$db->Execute("INSERT INTO farm_roles SET 
							farmid=?, role_id=?,
                            reboot_timeout=?, launch_timeout=?, status_timeout = ?, launch_index = ?, platform = ?
                            ", array( 
                        		$farm_id, 
                        		$role_id, 
	                            (int)$role['options']['reboot_timeout'],
                            	(int)$role['options']['launch_timeout'],
                         		(int)$role['options']['status_timeout'],
                         		(int)$role['launch_index'],
                         		$DBRole->platform
						));
						
						$farm_role_id = $db->Insert_ID();
						
						/**
						 * We need to init object manually (DB transaction not closed at this point)
						 */
						$DBFarmRole = new DBFarmRole($farm_role_id);
						$DBFarmRole->FarmID = $farm_id;
						$DBFarmRole->RoleID = $role_id; 
						$DBFarmRole->Platform = $DBRole->platform;
						
						$farm_roles[$role_id]['DBFarmRole'] = $DBFarmRole;
					}
					
					$oldRoleSettings = $DBFarmRole->GetAllSettings();
					
					foreach ($role['options']['scaling_algos'] as $k => $v)
					{
						if ($k != TimeScalingAlgo::PROPERTY_TIME_PERIODS)
							$DBFarmRole->SetSetting($k, $v);
					}
					
					foreach ($role['settings'] as $k => $v)
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
									$params[$matches[1]][] = $matches[2];

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
					
					Scalr_Helpers_Dns::farmUpdateRoleSettings($DBFarmRole, $oldRoleSettings, $role['settings']);
					
					/**
					 * Platfrom specified updates
					 */
					if ($DBFarmRole->Platform == SERVER_PLATFORMS::EC2)
					{
						Modules_Platforms_Ec2_Helpers_Ebs::farmUpdateRoleSettings($DBFarmRole, $oldRoleSettings, $role['settings']);
						Modules_Platforms_Ec2_Helpers_Eip::farmUpdateRoleSettings($DBFarmRole, $oldRoleSettings, $role['settings']);
						Modules_Platforms_Ec2_Helpers_Elb::farmUpdateRoleSettings($DBFarmRole, $oldRoleSettings, $role['settings']);
					}
				}
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			$Logger->getLogger("FarmEdit")->info("Farm Edit: {$farm_id}");
			
			try
			{				
				//TODO:: Move to helpers
				
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