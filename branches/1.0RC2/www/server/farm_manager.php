<?
    require("../src/prepend.inc.php");
    
    set_time_limit(360);
    
    $Validator = new Validator();
    
    //
    // Prepare input data
    //
    $farm_id = (int)$req_farm_id;
    $farm_name = $req_farm_name;
    $roles = @file_get_contents("php://input");
	$roles = json_decode($roles, true);
    
    try
    {
		$uid = 0;
    	// Get User ID
    	if ($_SESSION['uid'] == 0)
	    {
	        if (!$farm_id)
	        	throw new Exception("You don't have permissions for this action");
	         
			$uid = $db->GetOne("SELECT clientid FROM farms WHERE id=?", array($farm_id));
	    }
	    else 
	        $uid = $_SESSION['uid'];
	    
	    // Decrypt access keys for amazon
	    if ($uid == 0)
	    {
		    $clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($uid));
			
			// Decrypt client prvate key and certificate
		    $private_key = $Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
		    $certificate = $Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
		    
		    $aws_accesskeyid = $Crypto->Decrypt($clientinfo["aws_accesskeyid"], $cpwd);
	    	$aws_accesskey = $Crypto->Decrypt($clientinfo["aws_accesskey"], $cpwd);
	    }
	    else
	    {
	    	$private_key = $_SESSION["aws_private_key"];
	    	$certificate = $_SESSION["aws_certificate"];
	    	
	    	$aws_accesskeyid = $_SESSION["aws_accesskeyid"];
	    	$aws_accesskey = $_SESSION["aws_accesskey"];
	    }
			    
	    // Create new AmazonEC2 Client object
		$AmazonEC2Client = new AmazonEC2($private_key, $certificate);

		// Validate farm name
		if (!$Validator->IsNotEmpty($farm_name))
			throw new Exception("Farm name required");
			
		// Get client info
		$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($uid));
		$aws_accountid = $clientinfo['aws_accountid'];
		
		// Instances limit
		$client_max_instances = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array('client_max_instances', $uid));
    	$i_limit = $client_max_instances ? $client_max_instances : CONFIG::$CLIENT_MAX_INSTANCES;

    	// EIPs limit
    	$client_max_eips = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array('client_max_eips', $uid));
		$eips_limit = $client_max_eips ? $client_max_eips : CONFIG::$CLIENT_MAX_EIPS;
    	
		// Prepare role information
    	$total_max_count = 0;
    	$farm_amis = array();
	        
        // Validate input vars
		foreach ($roles as $role)
		{
			if (!$role)
				continue;
			
			$rolename = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($role['ami_id']));
            $farm_amis[$role['ami_id']] = $role;
			
			$minCount = (int)$role['options']['min_instances'];
			if ($minCount <=0 || $minCount > 99)
				throw new Exception("Min instances for '{$rolename}' must be a number between 1 and 99");
                   
			$maxCount = (int)$role['options']['max_instances'];
			if ($maxCount < 1 || $maxCount > 99)
				throw new Exception("Max instances for '{$rolename}' must be a number between 1 and 99");
                   
			$total_max_count = $total_max_count+$maxCount;
                   
			if ($role['options']['use_elastic_ips'])
                $need_elastic_ips_for_farm += $maxCount;
                
			$minLA = (float)$role['options']['min_LA'];
			if ($minLA <=0 || $minLA > 50)
				throw new Exception("Min LA for '{$rolename}' must be a number between 0.1 and 50");
                   
            $maxLA = (float)$role['options']['max_LA'];
			if ($maxLA <=0 || $maxLA > 50)
				throw new Exception("Max LA for '{$rolename}' must be a number between 0.01 and 50");
                   
            if ($maxLA <= $minLA)
				throw new Exception("Maximum LA for '{$rolename}' must be greather than minimum LA");				                
                   
			if ($role['alias'] == ROLE_ALIAS::MYSQL)
            {
				if (!$Validator->IsNumeric($role['options']['mysql_bundle_every']) || $role['options']['mysql_bundle_every'] < 1)
					throw new Exception("'Mysql bundle every' must be a number > 0");
                        
				if ($role['options']['mysql_make_backup'] == 1)
				{
					if (!$Validator->IsNumeric($role['options']['mysql_make_backup_every']) || $role['options']['mysql_make_backup_every'] < 1)
						throw new Exception("'Mysql backup every' must be a number > 0");
				}
				
				$farm_mysql_bundle_every = $role['options']['mysql_bundle_every'];
				$farm_mysql_bundle = (int)$role['options']['mysql_bundle'];
				$farm_mysql_make_backup_every = $role['options']['mysql_make_backup_every'];
				$farm_mysql_make_backup = (int)$role['options']['mysql_make_backup'];
				
			}
        }
        
        // Check limits
        $used_slots = $db->GetOne("SELECT SUM(max_count) FROM farm_amis WHERE farmid IN (SELECT id FROM farms WHERE clientid=? AND id != ?)", array($uid, $farm_id));
		if ($used_slots+$total_max_count > $i_limit)
			throw new Exception("You cannot launch more than {$i_limit} instances on your account. Please adjust Max Instances setting.");
		
		$used_ips = $db->GetOne("SELECT COUNT(*) FROM elastic_ips WHERE clientid=? AND farmid != ?", array($uid, $farm_id));
		if ($used_ips+$need_elastic_ips_for_farm > $eips_limit)
			throw new Exception("According to your settings, scalr can alocate {$eips_limit} Elastic IPs. With your current farm settings, {$need_elastic_ips_for_farm} IPs need to be allocated. ".($used_ips+$need_elastic_ips_for_farm)." IPs already reserved across your farms.");
			
	    switch($req_action)
	    {            
	        case "create":
	            	        	
	        	// Count client farms
	        	$farms_count = $db->GetOne("SELECT COUNT(*) FROM farms WHERE clientid=?", array($uid));
	        	
	        	// Check farms limit
	        	if ($farms_count >= $clientinfo['farms_limit'] && $clientinfo['farms_limit'] != 0)
					throw new Exception("Sorry, you have reached maximum allowed amount of running farms.");
	        	
				$db->BeginTrans();

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
						mysql_bcp = ?,
						mysql_bcp_every = ?,
						mysql_rebundle_every = ?,
						mysql_bundle = ?
					", array( 
	                	trim($farm_name), 
						$_SESSION['uid'], 
						$farmhash, 
						$farm_mysql_make_backup, 
						$farm_mysql_make_backup_every, 
						$farm_mysql_bundle_every,
						$farm_mysql_bundle
	                ));
	                
	                $farm_id = $db->Insert_ID();
	                
	                // Set farm S3 bucket name
	                $bucket_name = "farm-{$farm_id}-{$aws_accountid}";
	                $db->Execute("UPDATE farms SET bucket_name=? WHERE id=?",
	                	array($bucket_name, $farm_id)
	                );
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
					throw new Exception("Farm not found in database");
	        	
				try
				{
					$db->Execute("UPDATE farms SET   
						name=?, 
						mysql_bcp = ?,
						mysql_bcp_every = ?,
						mysql_rebundle_every = ?,
						mysql_bundle = ?
						WHERE id=?", 
					array(  
						trim($farm_name), 
						$farm_mysql_make_backup, 
						$farm_mysql_make_backup_every, 
						$farm_mysql_bundle_every,
						$farm_mysql_bundle, 
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
	    	// Remove unused roles
	    	try
			{
                $db_farm_amis = $db->GetAll("SELECT * FROM farm_amis WHERE farmid=?", array($farm_id));
                foreach ($db_farm_amis as $farm_ami)
                {
                    if (!$farm_amis[$farm_ami["ami_id"]])
                    {
                        if (0 == $db->GetOne("SELECT COUNT(*) FROM zones WHERE ami_id=? AND farmid=?", array($farm_ami["ami_id"], $farm_id)))
                        {
                           $db->Execute("DELETE FROM farm_amis WHERE farmid=? AND ami_id=?", array($farm_id, $farm_ami["ami_id"]));
                           $instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND ami_id=?", array($farm_id, $farm_ami["ami_id"]));
                           foreach ($instances as $instance)
                           {
                               try
                               {
                                   $res = $AmazonEC2Client->TerminateInstances(array($instance["instance_id"]));
                                   if ($res instanceof SoapFault )
                                       $Logger->fatal("Cannot terminate instance '{$instance["instance_id"]}'. Please do it manualy. ({$res->faultString})");
                               }
                               catch (Exception $e)
                               {
                                   $Logger->fatal("Cannot terminate instance '{$instance["instance_id"]}'. Please do it manualy. ({$err->getMessage()})");
                               }
                           }
                        }
                        else
                        {
                            $rolename = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$farm_ami["ami_id"]}'");
                            $sitename = $db->GetOne("SELECT zone FROM zones WHERE ami_id=? AND farmid=?", array($farm_ami["ami_id"], $farm_id));
                            throw new Exception("You cannot delete role {$rolename} because there are DNS records bind to it. Please delete application {$sitename} first.");
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
				foreach ($farm_amis as $ami_id => $role)
				{
                    $info = $db->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND ami_id=?", array($farm_id, $ami_id));
                    if ($info)
                    {
                        if ($info['use_elastic_ips'] == 0 && $role['options']['use_elastic_ips'])
                    		$assign_elastic_ips[$info['id']] = $info['ami_id'];
                    	
                    	$db->Execute("UPDATE farm_amis SET 
							min_count=?, max_count=?, min_LA=?, max_LA=?,
                                avail_zone=?, instance_type=?, use_elastic_ips=?,
                                reboot_timeout=?, launch_timeout=?
                                WHERE farmid=? AND ami_id=?
                                ", array(
                    		$role['options']['min_instances'], 
                    		$role['options']['max_instances'], 
                    		$role['options']['min_LA'],
                            $role['options']['max_LA'], 
                            $role['options']['placement'], 
                            $role['options']['i_type'],
                            (int)$role['options']['use_elastic_ips'],
                            (int)$role['options']['reboot_timeout'],
                            (int)$role['options']['launch_timeout'],
                            $farm_id, 
                            $ami_id
						));
                    }
    	            else 
    	            {
                        $db->Execute("INSERT INTO farm_amis SET 
							farmid=?, ami_id=?, min_count=?, max_count=?, 
                            min_LA=?, max_LA=?, avail_zone=?, instance_type=?, use_elastic_ips=?,
                            reboot_timeout=?, launch_timeout=?
                            ", array( 
                        		$farm_id, 
                        		$ami_id, 
                        		$role['options']['min_instances'], 
	                    		$role['options']['max_instances'], 
	                    		$role['options']['min_LA'],
	                            $role['options']['max_LA'], 
	                            $role['options']['placement'], 
	                            $role['options']['i_type'],
	                            (int)$role['options']['use_elastic_ips'],
	                            (int)$role['options']['reboot_timeout'],
                            	(int)$role['options']['launch_timeout']
						));
					}
				}
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
	    	try
			{
	        	// Asign elastic IPs
				if (count($assign_elastic_ips) > 0)
				{
					foreach ($assign_elastic_ips as $id => $ami_id)
					{
						if (!$id || !$ami_id)
							continue;

						$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND ami_id=?",
							array($farm_id, $ami_id)
						);
						
						foreach ($instances as $instance)
						{
							// Alocate new IP address
							$address = $AmazonEC2Client->AllocateAddress();
							
							// Add allocated IP address to database
							$db->Execute("INSERT INTO elastic_ips SET farmid=?, role_name=?, ipaddress=?, state='0', instance_id='', clientid=?",
								array($farm_id, $roles[$ami_id]['name'], $address->publicIp, $uid)
							);
							
							$allocated_ips[] = $address->publicIp;
							
							$Logger->debug("Allocated new IP: {$ip['ipaddress']}");
							
							// Waiting...
							$Logger->debug("Waiting 5 seconds...");
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
										$Logger->debug("Waiting 2 seconds...");
										sleep(2);
										$assign_retries++;
										continue;
									}
								}
								
								break;
							}

							$Logger->info("IP: {$address->publicIp} assigned to instance '{$instance['instance_id']}'");
							
							// Update leastic IPs table
							$db->Execute("UPDATE elastic_ips SET state='1', instance_id=? WHERE ipaddress=?",
								array($instance['instance_id'], $address->publicIp)
							);
							
							// Update instance info in database
							$db->Execute("UPDATE farm_instances SET external_ip=?, isipchanged='1', isactive='0' WHERE instance_id=?",
								array($address->publicIp, $instance['instance_id'])
							);
						}
					}
				}
				
				// Create S3 bucket
                if ($create_farm_s3_bucket)
                {
                    //
                    // Create S3 Bucket (For MySQL, BackUs, etc.)
                    //
                    $AmazonS3 = new AmazonS3($aws_accesskeyid, $aws_accesskey);
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
                       if ($AmazonS3->CreateBucket($bucket_name))
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
                            $db->Execute("UPDATE farms SET private_key=?, private_key_name=? WHERE id=?", array($result->keyMaterial, $key_name, $farm_id));
                            $created_key_name = $key_name;
                        }
                        else
                            throw new Exception("Cannot create key pair for farm.", E_ERROR);
                }
			}
			catch(Exception $e)
			{
				 $db->RollbackTrans();
				 
				 if ($created_bucket)
				 	$AmazonS3->DeleteBucket($created_bucket);
				 	
				 if ($created_key_name)
				 	$AmazonEC2Client->DeleteKeyPair($created_key_name);
				 	
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
    
    $db->CommitTrans();
    
    print json_encode(array("result" => "ok", "data" => $farm_id));
    exit();
?>