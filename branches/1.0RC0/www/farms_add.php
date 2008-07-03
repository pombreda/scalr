<? 
	require("src/prepend.inc.php"); 
	$display["title"] = "Farms&nbsp;&raquo;&nbsp;Add / Edit";
    
	if ($_SESSION['uid'] == 0)
    {
        $req_farmid = ($req_farmid) ? $req_farmid : $req_id;
        
        if (!$req_farmid)   
            CoreUtils::Redirect("farms_view.php");
        else 
        {
            $uid = $db->GetOne("SELECT clientid FROM farms WHERE id='{$req_farmid}'");
        }
    }
    else 
    {
        $uid = $_SESSION['uid'];
    }
	
    $used_slots = $db->GetOne("SELECT SUM(max_count) FROM farm_amis WHERE farmid=(SELECT id FROM farms WHERE clientid='{$uid}')");
    $avail_slots = CF_CLIENT_MAX_INSTANCES - $used_slots;
    $errmsg = "You have {$avail_slots} spare instances available on your account.";
    
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
	           if ($num >= $clientinfo['farms_limit'])
	               $err[] = "Sorry, you have reached maximum allowed amount of running farms.";
	        }
	           
	        $total_max_count = 0;
	        
	        // Validate input vars
            foreach ($post_ami_id as $k=>$ami_id)
            {
                $rolename = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", $ami_id);
                
                $minCount = (int)$post_minCount[$k];
                if ($minCount <=0 || $minCount > 99)
                   $err[] = "Min instances for '{$rolename}' must be an integer from 1 to 99";
                   
                $maxCount = (int)$post_maxCount[$k];
                if ($maxCount <=0 || $maxCount > 99)
                   $err[] = "Max instances for '{$rolename}' must be an integer from 1 to 99";
                   
                $total_max_count = $total_max_count+$maxCount;
                   
                $minLA = (int)$post_minLA[$k];
                if ($minLA <=0 || $minLA > 200)
                   $err[] = "Min LA for '{$rolename}' must be an integer from 1 to 200";
                   
                $maxLA = (int)$post_maxLA[$k];
                if ($maxLA <=0 || $maxLA > 200)
                   $err[] = "Max LA for '{$rolename}' must be an integer from 1 to 99";
                   
                if ($rolename == "mysql")
                {
                    if (!$Validator->IsNumeric($post_mysql_rebundle_every) || $post_mysql_rebundle_every < 1)
                        $err[] = "'Mysql rebundle every' must be an integer > 0";
                        
                    if ($post_mysql_bcp == 1)
                    {
                        if (!$Validator->IsNumeric($post_mysql_bcp_every) || $post_mysql_bcp_every < 1)
                            $err[] = "'Mysql backup every' must be an integer > 0";
                    }
                }
            }
	           
            $used_slots = $db->GetOne("SELECT SUM(max_count) FROM farm_amis WHERE farmid=(SELECT id FROM farms WHERE clientid='{$uid}' AND id!='{$post_farmid}')");
            if ($used_slots+$total_max_count > CF_CLIENT_MAX_INSTANCES)
                $err[] = "You cannot launch more than ".CF_CLIENT_MAX_INSTANCES." instances on your account. Please adjust Max Instances setting.";
            
	        if (count($err) == 0)
	        {
    	        $AmazonEC2Root = new AmazonEC2(
                    APPPATH . "/etc/pk-{$cfg['aws_keyname']}.pem", 
                    APPPATH . "/etc/cert-{$cfg['aws_keyname']}.pem");
                	    
                                                
                $AmazonEC2Client = new AmazonEC2(
                    APPPATH . "/etc/clients_keys/{$uid}/pk.pem", 
                    APPPATH . "/etc/clients_keys/{$uid}/cert.pem");
	            
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
                    
                    //get current permisssions for role ami
                    $perms = $AmazonEC2Root->DescribeImageAttribute($ami_id, "launchPermission");
                    $perms = $perms->launchPermission;
                    
                    // check current permissions for AMIs
                    if ($role["clientid"] != $uid)
                    {
                        $set_perms = true;	                    
                        if (is_array($perms->item) && $perms->item[1])
                        {
                            foreach ($perms->item as $item)
                               if ($item->userId == $aws_accountid)
                                   $set_perms = false;
                        }
                        elseif ($perms->item && $perms->item->userId == $aws_accountid)
                           $set_perms = false;
                           
                        // If we need add permisssions for current user - do it.
                        if ($set_perms)
                           $AmazonEC2Root->ModifyImageAttribute($ami_id, "add", array("userId" => $aws_accountid));
                    }
                    
                    $security_group_name = CF_SECGROUP_PREFIX.$rolename;
                       
                    $addSecGroup = true;
                    
                    $client_security_groups = $AmazonEC2Client->DescribeSecurityGroups();
                    if (!$client_security_groups)
                        Core::RaiseError("Cannot describe security groups for client.");
                        
                    $client_security_groups = $client_security_groups->securityGroupInfo->item;
                    
                    // Now we need add missed security groups
                    if (is_array($client_security_groups))
                    {
                        foreach ($client_security_groups as $group)
                        {
                            if ($group->groupName == $security_group_name)
                            {
                               $addSecGroup = false;
                               break;
                            }
                        }
                    }
                    elseif ($client_security_groups->groupName == $security_group_name)
                       $addSecGroup = false;
                    
                    if ($addSecGroup)
                    {
                        try
                        {
	                        $res = $AmazonEC2Client->CreateSecurityGroup($security_group_name, $rolename);
	                        if (!$res)
	                           Core::RaiseError("Cannot create security group", E_USER_ERROR);	                        
	                           
	                        // Set permissions for group
    	                    $group_rules = $db->GetAll("SELECT * FROM security_rules WHERE roleid='{$roleid}'");	                        
                            $IpPermissionSet = new IpPermissionSetType();
                            foreach ($group_rules as $rule)
                            {
                               $group_rule = explode(":", $rule["rule"]);
                               $IpPermissionSet->AddItem($group_rule[0], $group_rule[1], $group_rule[2], null, array($group_rule[3]));
                            }
                            
                            $AmazonEC2Client->AuthorizeSecurityGroupIngress($_SESSION['aws_accountid'], $security_group_name, $IpPermissionSet);
                        }
                        catch (Exception $e)
                        {
                            Core::RaiseError($e->getMessage(), E_USER_ERROR);
                        }
                    }
                }
	            
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
                            Core::RaiseError("Cannot create key pair for farm.", E_ERROR);
                    }
                    catch (Exception $e)
                    {
                        Core::RaiseError($e->getMessage(), E_ERROR);
                    }
                    
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
	                                               max_LA=?
	                                 ", array( $farmid, 
	                                           $ami_id, 
	                                           $post_minCount[$k], 
	                                           $post_maxCount[$k],
	                                           $post_minLA[$k],
	                                           $post_maxLA[$k]
	                                         )
	                                 );
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
	                    Core::RaiseError($e->getMessage(), E_ERROR);
	                }
	                	                
	                $okmsg = "Farm succesfully built.";
	                CoreUtils::Redirect("farms_control.php?farmid={$farmid}&new=1");
    	        }
    	        else 
    	        {
    	            $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($post_farmid));
                    
    	            if ($_SESSION['uid'] != 0 && $_SESSION['uid'] != $farminfo["clientid"])
                        CoreUtils::Redirect("farms_view.php");
    	            
    	            if (!$farminfo)
                    {
                        $errmsg = "Farm not found";
                        CoreUtils::Redirect("farms_view.php");
                    }
                    
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
    	                    if (0 == $db->GetOne("SELECT COUNT(*) FROM zones WHERE ami_id='{$farm_ami["ami_id"]}' AND farmid='{$post_farmid}'"))
    	                    {
    	                       $db->Execute("DELETE FROM farm_amis WHERE farmid=? AND ami_id=?", array($post_farmid, $farm_ami["ami_id"]));
    	                       $instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND ami_id=?", array($post_farmid, $farm_ami["ami_id"]));
    	                       foreach ($instances as $instance)
    	                       {
    	                           try
    	                           {
    	                               $res = $AmazonEC2Client->TerminateInstances(array($instance["instance_id"]));
    	                               if ($res instanceof SoapFault )
    	                                   Log::Log("Cannot terminate instance '{$instance["instance_id"]}'. Please do it manualy. ({$res->faultString})");
    	                           }
    	                           catch (Exception $err)
    	                           {
    	                               Log::Log("Cannot terminate instance '{$instance["instance_id"]}'. Please do it manualy. ({$err->getMessage()})");
    	                           }
    	                       }
    	                       
    	                       $db->Execute("DELETE FROM farm_instances WHERE farmid=? AND ami_id=?", array($post_farmid, $farm_ami["ami_id"]));
    	                    }
    	                    else
    	                    {
    	                        $rolename = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$farm_ami["ami_id"]}'");
    	                        $sitename = $db->GetOne("SELECT zone FROM zones WHERE ami_id='{$farm_ami["ami_id"]}' AND farmid='{$post_farmid}'");
    	                        $err[] = "You cannot delete role {$rolename} because there are DNS records bind to it. Please delete application {$sitename} first.";
    	                    }
    	                }
    	            }
    	            
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
    	                                               max_LA=?
    	                                          WHERE farmid=? AND ami_id=?
    	                                 ", array( 
    	                                           $post_minCount[$k], 
    	                                           $post_maxCount[$k],
    	                                           $post_minLA[$k],
    	                                           $post_maxLA[$k],
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
	                                               max_LA=?
	                                 ", array( $post_farmid, 
	                                           $ami, 
	                                           $post_minCount[$k], 
	                                           $post_maxCount[$k],
	                                           $post_minLA[$k],
	                                           $post_maxLA[$k]
	                                         )
	                                 );
    	                }
    	            }
    	            
    	            if (count($err) == 0)
    	            {
    	               $okmsg = "Farm successfully updated";
    	               CoreUtils::Redirect("farms_view.php");
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
            CoreUtils::Redirect("farms_view.php");
        
        if (!$display["farminfo"])
        {
            $errmsg = "Farm not found";
            CoreUtils::Redirect("farms_view.php");
        }
        
        $display["servers"] = $db->GetAll("SELECT * FROM farm_amis WHERE farmid=?", array($req_id));
        foreach ($display["servers"] as &$row)
        {
            $row["role"] = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$row['ami_id']}'");
            if ($row["role"] == "mysql")
                $display["mysql_visible"] = "";
        }
            
        $display["id"] = $req_id;
    }
	
    
	require("src/append.inc.php"); 
?>