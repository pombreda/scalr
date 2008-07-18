<? 
	require("src/prepend.inc.php"); 
	$display["title"] = "Farms&nbsp;&raquo;&nbsp;Add / Edit";
    
	$display["experimental"] = true;
		
	if ($_SESSION['uid'] == 0)
    {
        $req_farmid = ($req_farmid) ? $req_farmid : $req_id;
        
        if (!$req_farmid)   
            UI::Redirect("farms_view.php");
        else 
        {
            $uid = $db->GetOne("SELECT clientid FROM farms WHERE id='{$req_farmid}'");
        }
    }
    else 
    {
        $uid = $_SESSION['uid'];
    }
	
    $used_slots = $db->GetOne("SELECT SUM(max_count) FROM farm_amis WHERE farmid IN (SELECT id FROM farms WHERE clientid='{$uid}')");
    $avail_slots = CONFIG::$CLIENT_MAX_INSTANCES - $used_slots;
    $errmsg = "You have {$avail_slots} spare instances available on your account.";
    
    $AmazonEC2Client = new AmazonEC2(
                    APPPATH . "/etc/clients_keys/{$uid}/pk.pem", 
                    APPPATH . "/etc/clients_keys/{$uid}/cert.pem");
                    
    // Get Avail zones
    $avail_zones_resp = $AmazonEC2Client->DescribeAvailabilityZones();
    $display["avail_zones"] = array();
    
    // Random assign zone
    array_push($display["avail_zones"], "");
    
    foreach ($avail_zones_resp->availabilityZoneInfo->item as $zone)
    {
    	if ($zone->zoneState == 'available')
    		array_push($display["avail_zones"], (string)$zone->zoneName);
    }
    
	if ($_POST)
	{	    	    
	    if ($post_action == "save")
	    {
	        
	        
	        $Validator = new Validator();
	        if (!$Validator->IsNotEmpty($post_name))
	           $err[] = "Farm name required";

	        $clientinfo = $db->GetRow("SELECT * FROM clients WHERE id='{$uid}'");
	        $num = $db->GetOne("SELECT COUNT(*) FROM farms WHERE clientid='{$uid}'");   
	        	       
	         
	        if (!$post_farmid)
	        {
	           if ($num >= $clientinfo['farms_limit'] && $clientinfo['farms_limit'] != 0)
	               $err[] = "Sorry, you have reached maximum allowed amount of running farms.";
	        }
	           
	        $total_max_count = 0;
	        
	        // Validate input vars
            foreach ($post_ami_id as $k=>$ami_id)
            {
                $rolename = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", $ami_id);
                
                $minCount = (int)$post_minCount[$k];
                if ($minCount <=0 || $minCount > 99)
                   $err[] = "Min instances for '{$rolename}' must be a number between 1 and 99";
                   
                $maxCount = (int)$post_maxCount[$k];
                if ($maxCount <=0 || $maxCount > 99)
                   $err[] = "Max instances for '{$rolename}' must be a number between 1 and 99";
                   
                $total_max_count = $total_max_count+$maxCount;
                   
                $minLA = (float)$post_minLA[$k];
                if ($minLA <=0 || $minLA > 50)
                   $err[] = "Min LA for '{$rolename}' must be a number between 0.01 and 50";
                   
                $maxLA = (float)$post_maxLA[$k];
                if ($maxLA <=0 || $maxLA > 50)
                   $err[] = "Max LA for '{$rolename}' must be a number between 0.01 and 50";
                   
                if ($maxLA == $minLA)
					$err[] = "Maximum LA for '{$rolename}' must be greather than minimum LA";				                
                   
                if ($rolename == "mysql")
                {
                    if (!$Validator->IsNumeric($post_mysql_rebundle_every) || $post_mysql_rebundle_every < 1)
                        $err[] = "'Mysql rebundle every' must be a number > 0";
                        
                    if ($post_mysql_bcp == 1)
                    {
                        if (!$Validator->IsNumeric($post_mysql_bcp_every) || $post_mysql_bcp_every < 1)
                            $err[] = "'Mysql backup every' must be a number > 0";
                    }
                }
            }
	           
            $used_slots = $db->GetOne("SELECT SUM(max_count) FROM farm_amis WHERE farmid IN (SELECT id FROM farms WHERE clientid=? AND id != ?)", array($uid, $post_farmid));
            if ($used_slots+$total_max_count > CONFIG::$CLIENT_MAX_INSTANCES)
                $err[] = "You cannot launch more than ".CONFIG::$CLIENT_MAX_INSTANCES." instances on your account. Please adjust Max Instances setting.";
            
	        if (count($err) == 0)
	        {
    	        $AmazonEC2Root = new AmazonEC2(
                    APPPATH . "/etc/pk-".CONFIG::$AWS_KEYNAME.".pem", 
                    APPPATH . "/etc/cert-".CONFIG::$AWS_KEYNAME.".pem");
                	    
                if ($post_farmid)
                {
                    $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($post_farmid));
                    $uid = $farminfo["clientid"];
                    $aws_accountid = $db->GetOne("SELECT aws_accountid FROM clients WHERE id='{$uid}'");
                }
                else
                { 
                    $uid = $_SESSION["uid"];
                    $aws_accountid = $_SESSION["aws_accountid"];
                }
                    
	            foreach ($post_ami_id as $k=>$ami_id)
                {
                    $role = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", $ami_id);
                    $rolename = $role["name"];
                    $roleid = $role["id"];
                                       	                     
                    $security_group_name = CONFIG::$SECGROUP_PREFIX.$rolename;
                }
	            
                $db->BeginTrans();
                
	            if (!$post_farmid)
    	        {
	                    	            
    	            // Add Farm Information to database;
	                $farmhash = $Crypto->Sault(14);
	                $db->Execute("INSERT INTO 
	                                           farms 
	                                      SET 
	                                           status='0', 
	                                           name=?, 
	                                           clientid=?, 
	                                           hash=?, 
	                                           dtadded=NOW(),
	                                           mysql_bcp = ?,
	                                           mysql_bcp_every = ?,
	                                           mysql_rebundle_every = ?
	                             ", array( $post_name, 
	                                       $_SESSION['uid'], 
	                                       $farmhash, 
	                                       ($post_mysql_bcp == 1 ? '1' : '0'), 
	                                       $post_mysql_bcp_every, 
	                                       $post_mysql_rebundle_every)
	                             );
	                $farmid = $db->Insert_ID();
                    
                    //
                    // Create FARM KeyPair
                    //
                    try
                    {
                        $key_name = "FARM-{$farmid}";
                        $result = $AmazonEC2Client->CreateKeyPair($key_name);
                        if ($result->keyMaterial)
                            $db->Execute("UPDATE farms SET private_key=?, private_key_name=? WHERE id=?", array($result->keyMaterial, $key_name, $farmid));
                        else
                            throw new Exception("Cannot create key pair for farm.", E_ERROR);
                    }
                    catch (Exception $e)
                    {
                        $db->RollbackTrans();
                    	throw new ApplicationException($e->getMessage(), E_ERROR);
                    }
                    
                    try
                    {
		                foreach ($post_ami_id as $k=>$ami_id)
		                {
		                    $db->Execute("INSERT INTO 
		                                               farm_amis 
		                                          SET 
		                                               farmid=?, 
		                                               ami_id=?, 
		                                               min_count=?, 
		                                               max_count=?, 
		                                               min_LA=?, 
		                                               max_LA=?,
		                                               avail_zone = ?,
		                                               instance_type=?
		                                 ", array( $farmid, 
		                                           $ami_id, 
		                                           $post_minCount[$k], 
		                                           $post_maxCount[$k],
		                                           $post_minLA[$k],
		                                           $post_maxLA[$k],
		                                           $post_availZone[$k],
		                                           $post_iType[$k],
		                                         )
		                                 );
		                }
                    }
    	        	catch (Exception $e)
                    {
                        $db->RollbackTrans();
                    	throw new ApplicationException($e->getMessage(), E_ERROR);
                    }
	                
	                try
	                {
    	                //
    	                // Create S3 Bucket (For MySQL, BackUs, etc.)
    	                //
    	                $bucket_name = "FARM-{$farmid}-{$_SESSION['aws_accountid']}";
    	                $AmazonS3 = new AmazonS3($_SESSION['aws_accesskeyid'], $_SESSION['aws_accesskey']);
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
	                       $AmazonS3->CreateBucket($bucket_name);
	                }
	                catch (Exception $e)
	                {
	                    $db->RollbackTrans();
	                	throw new ApplicationException($e->getMessage(), E_ERROR);
	                }

	                $db->CommitTrans();
	                
	                $okmsg = "Farm succesfully built.";
	                UI::Redirect("farms_control.php?farmid={$farmid}&new=1");
    	        }
    	        else 
    	        {
    	            $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($post_farmid));
                    
    	            if ($_SESSION['uid'] != 0 && $_SESSION['uid'] != $farminfo["clientid"])
                        UI::Redirect("farms_view.php");
    	            
    	            if (!$farminfo)
                    {
                        $errmsg = "Farm not found";
                        UI::Redirect("farms_view.php");
                    }
                    
                    try
                    {
	    	            $db->Execute("UPDATE 
	    	                                   farms 
	    	                             SET   
	    	                                   name=?, 
	    	                                   mysql_bcp = ?,
		                                       mysql_bcp_every = ?,
		                                       mysql_rebundle_every = ? 
		                                 WHERE id=?", 
	    	                               array(  $post_name, 
	    	                                       ($post_mysql_bcp == 1 ? '1' : '0'), 
		                                           $post_mysql_bcp_every, 
		                                           $post_mysql_rebundle_every, $post_farmid)
		                            );
	    	            
	    	            $farm_amis = $db->GetAll("SELECT * FROM farm_amis WHERE farmid=?", $post_farmid);
	    	            foreach ($farm_amis as $farm_ami)
	    	            {
	    	                if (!in_array($farm_ami["ami_id"], $post_ami_id))
	    	                {
	    	                    if (0 == $db->GetOne("SELECT COUNT(*) FROM zones WHERE ami_id=? AND farmid=?", array($farm_ami["ami_id"], $post_farmid)))
	    	                    {
	    	                       $db->Execute("DELETE FROM farm_amis WHERE farmid=? AND ami_id=?", array($post_farmid, $farm_ami["ami_id"]));
	    	                       $instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND ami_id=?", array($post_farmid, $farm_ami["ami_id"]));
	    	                       foreach ($instances as $instance)
	    	                       {
	    	                           try
	    	                           {
	    	                               $res = $AmazonEC2Client->TerminateInstances(array($instance["instance_id"]));
	    	                               if ($res instanceof SoapFault )
	    	                                   $Logger->fatal("Cannot terminate instance '{$instance["instance_id"]}'. Please do it manualy. ({$res->faultString})");
	    	                           }
	    	                           catch (Exception $err)
	    	                           {
	    	                               $Logger->fatal("Cannot terminate instance '{$instance["instance_id"]}'. Please do it manualy. ({$err->getMessage()})");
	    	                           }
	    	                       }
	    	                       
	    	                       $db->Execute("DELETE FROM farm_instances WHERE farmid=? AND ami_id=?", array($post_farmid, $farm_ami["ami_id"]));
	    	                    }
	    	                    else
	    	                    {
	    	                        $rolename = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$farm_ami["ami_id"]}'");
	    	                        $sitename = $db->GetOne("SELECT zone FROM zones WHERE ami_id=? AND farmid=?", array($farm_ami["ami_id"], $post_farmid));
	    	                        $err[] = "You cannot delete role {$rolename} because there are DNS records bind to it. Please delete application {$sitename} first.";
	    	                    }
	    	                }
	    	            }
                    }
                    catch(Exception $e)
                    {
                    	$db->RollbackTrans();
                    	throw new ApplicationException($e->getMessage(), E_ERROR);
                    }
    	            
                    try
                    {
	    	            foreach ($post_ami_id as $k=>$ami)
	    	            {
	    	                $info = $db->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND ami_id=?", array($post_farmid, $ami));
	    	                
	    	                if ($info)
	    	                {
	        	                $db->Execute("UPDATE 
	    	                                               farm_amis 
	    	                                          SET 
	    	                                               min_count=?, 
	    	                                               max_count=?, 
	    	                                               min_LA=?, 
	    	                                               max_LA=?,
	    	                                               avail_zone=?,
	    	                                               instance_type=?
	    	                                          WHERE farmid=? AND ami_id=?
	    	                                 ", array( 
	    	                                           $post_minCount[$k], 
	    	                                           $post_maxCount[$k],
	    	                                           $post_minLA[$k],
	    	                                           $post_maxLA[$k],
	    	                                           $post_availZone[$k],
	    	                                           $post_iType[$k],
	    	                                           $post_farmid, 
	    	                                           $ami
	    	                                         )
	    	                                 );
	    	                }
	    	                else 
	    	                {
	    	                    $db->Execute("INSERT INTO 
		                                               farm_amis 
		                                          SET 
		                                               farmid=?, 
		                                               ami_id=?, 
		                                               min_count=?, 
		                                               max_count=?, 
		                                               min_LA=?, 
		                                               max_LA=?,
		                                               avail_zone=?,
		                                               instance_type=?
		                                 ", array( $post_farmid, 
		                                           $ami, 
		                                           $post_minCount[$k], 
		                                           $post_maxCount[$k],
		                                           $post_minLA[$k],
		                                           $post_maxLA[$k],
		                                           $post_availZone[$k],
		                                           $post_iType[$k],
		                                         )
		                                 );
	    	                }
	    	            }
                    }
                    catch(Exception $e)
                    {
                    	$db->RollbackTrans();
                    	throw new ApplicationException($e->getMessage(), E_ERROR);
                    }
    	            
    	            if (count($err) == 0)
    	            {
    	               $db->CommitTrans();
    	            	
    	               $okmsg = "Farm successfully updated";
    	               UI::Redirect("farms_view.php");
    	            }
    	            else 
    	                $req_id = $req_farmid;
    	        }
	        }
	    }
	}

	$display["mysql_visible"] = "none";
	
    if ($req_id)
    {
        $display["farminfo"] = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_id));
        
        if ($_SESSION['uid'] != 0 && $_SESSION['uid'] != $display["farminfo"]["clientid"])
            UI::Redirect("farms_view.php");
        
        if (!$display["farminfo"])
        {
            $errmsg = "Farm not found";
            UI::Redirect("farms_view.php");
        }
        
        $display["servers"] = $db->GetAll("SELECT * FROM farm_amis WHERE farmid=?", array($req_id));
        foreach ($display["servers"] as &$row)
        {
            $ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($row['ami_id']));
        	$row["role"] = $ami_info["name"]; 
            if ($ami_info["alias"] == "mysql")
            {
                $display["mysql_visible"] = "";
                $row["ismysql"] = true;
            }
                
            if ($ami_info['architecture'] == INSTANCE_ARCHITECTURE::I386)
            	$TypesClass = new ReflectionClass("I386_TYPE");
            elseif ($ami_info['architecture'] == INSTANCE_ARCHITECTURE::X86_64)
            	$TypesClass = new ReflectionClass("X86_64_TYPE");
            
            $row["types"] = array_values($TypesClass->getConstants());
        }
            
        $display["id"] = $req_id;
    }
	
    $r = new ReflectionClass("X86_64_TYPE");
    $display["64bit_types"] = array_values($r->getConstants());
    
    $r = new ReflectionClass("I386_TYPE");
    $display["32bit_types"] = array_values($r->getConstants());
    unset($r);
    
	require("src/append.inc.php"); 
?>