<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Application creation wizard";
    $Validator = new Validator();
	
    if ($req_step == 4)
    {
        $_SESSION["wizard"]["dnsami"] = $post_dnsami;
        
        $AmazonEC2Root = new AmazonEC2(
            APPPATH . "/etc/pk-{$cfg['aws_keyname']}.pem", 
            APPPATH . "/etc/cert-{$cfg['aws_keyname']}.pem");
        	                            
        $AmazonEC2Client = new AmazonEC2(
            APPPATH . "/etc/clients_keys/{$_SESSION['uid']}/pk.pem", 
            APPPATH . "/etc/clients_keys/{$_SESSION['uid']}/cert.pem");
        
        //
        // Set launch permissions and create security groups
        //
        foreach ($_SESSION['wizard']['amis'] as $ami_id)
        {
            $role = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", $ami_id);
            $rolename = $role["name"];
            $roleid = $role["id"];
            
            //get current permisssions for role ami
            $perms = $AmazonEC2Root->DescribeImageAttribute($ami_id, "launchPermission");
            $perms = $perms->launchPermission;
            
            // check current permissions for AMIs
            if ($role["clientid"] != $_SESSION["clientid"])
            {
                $set_perms = true;	                    
                if (is_array($perms->item) && $perms->item[1])
                {
                    foreach ($perms->item as $item)
                       if ($item->userId == $_SESSION['aws_accountid'])
                           $set_perms = false;
                }
                elseif ($perms->item && $perms->item->userId == $_SESSION['aws_accountid'])
                   $set_perms = false;
                   
               
                // If we need add permisssions for current user - do it.
                if ($set_perms)
                   $AmazonEC2Root->ModifyImageAttribute($ami_id, "add", array("userId" => $_SESSION["aws_accountid"]));
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
        
        //
        // Add farm information to database
        //
        $farmname = preg_replace("/[^A-Za-z0-9]+/", "", $_SESSION['wizard']["domainname"]);
        
        $farmhash = $Crypto->Sault(14);
        $db->Execute("INSERT INTO farms SET status='0', name=?, clientid=?, hash=?, dtadded=NOW()", array($farmname, $_SESSION['uid'], $farmhash));
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
        
        //
        // Add farm amis information
        //
        foreach ($_SESSION['wizard']['amis'] as $ami_id)
        {
            $roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$ami_id}'");
            
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
                                   1, 
                                   2,
                                   ($roleinfo["default_minLA"] ? $roleinfo["default_minLA"] : 5),
                                   ($roleinfo["default_maxLA"] ? $roleinfo["default_maxLA"] : 10)
                                 )
                         );
        }

        //
        // Create S3 Bucket (For MySQL, BackUs, etc.)
        //
        try
        {
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
        	                
        //
        // Add DNS Zone
        //
        $records = array();
		$nss = $db->GetAll("SELECT * FROM nameservers");
		foreach ($nss as $ns)
            $records[] = array("rtype" => "NS", "ttl" => 14400, "rvalue" => "{$ns["host"]}.", "rkey" => "{$_SESSION['wizard']["domainname"]}.", "issystem" => 1);
		 
            
		$db->Execute("insert into zones (`zone`, `soa_owner`, `soa_ttl`, `soa_parent`, `soa_serial`, `soa_refresh`, `soa_retry`, `soa_expire`, `min_ttl`, `dtupdated`, `farmid`, `ami_id`, `clientid`)
		values (?,'{$cfg["def_soa_owner"]}','14400','{$cfg["def_soa_parent"]}',?,'14400','7200','3600000','86400',NOW(), ?, ?, ?)", 
		array($_SESSION['wizard']["domainname"], date("Ymd")."01", $farmid, $_SESSION['wizard']["dnsami"], $_SESSION["uid"]));
		$zoneid = $db->Insert_ID();
		
		$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id='{$zoneid}'");
		
		foreach ($records as $k=>$v)
		{
			if ($v["rkey"] != '' && $v["rvalue"] != '')
				$db->Execute("REPLACE INTO records SET zoneid=?, `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?, `issystem`=?", array($zoneinfo["id"], $v["rtype"], $v["ttl"], $v["rpriority"], $v["rvalue"], $v["rkey"], $v["issystem"] ? 1 : 0));
		}
		
		$ZoneControler = new DNSZoneControler();
			
		if (!$ZoneControler->Update($zoneid))
		{
		    $err[] = "Cannot add new DNS zone: ".Core::GetLastWarning();
		}
		
		$okmsg = "Application successfully created.";
		CoreUtils::Redirect("farms_control.php?farmid={$farmid}&new=1");
    }
    
	if ($req_step == 3)
	{
	    if (count($post_amis) == 0)
	    {
	        $err[] = "You must select at least one role";
	        $req_step = 2;
	    }
	    else 
	    {
	        $total_max_count = count($post_amis)*2; 	    
            $used_slots = $db->GetOne("SELECT SUM(max_count) FROM farm_amis WHERE farmid=(SELECT id FROM farms WHERE clientid='{$_SESSION['uid']}')");
            if ($used_slots+$total_max_count > CF_CLIENT_MAX_INSTANCES)
            {
                $err[] = "You cannot launch more than ".CF_CLIENT_MAX_INSTANCES." instances on your account. Please adjust Max Instances setting.";
                $req_step = 2;
            }
	        else 
	        {
    	        $_SESSION["wizard"]["amis"] = $post_amis;
    	        
    	        $roles = $db->GetAll("SELECT * FROM ami_roles WHERE clientid='0' OR clientid='{$_SESSION['uid']}'");
    	        foreach ($roles as $role)
    	        {
    	            if (in_array($role["ami_id"], $post_amis))
    	               $display["roles"][] = $role;
    	        }
    	        
    	        $template_name = "app_wizard_step3.tpl";
	        }
	    }
	}
    
	if ($req_step == 2)
	{
	    if ($post_step == 3)
	       $post_domainname = $_SESSION["wizard"]["domainname"];
	    
	    if (!$Validator->IsDomain($post_domainname))
	    {
	        $err[] = "Invalid domain name";
	        $req_step = 1;
	    }
	    else 
	    {
	        $clientinfo = $db->GetRow("SELECT * FROM clients WHERE id='{$_SESSION['uid']}'");
            $num = $db->GetOne("SELECT COUNT(*) FROM farms WHERE clientid='{$_SESSION['uid']}'");   
            	       
           if ($num >= $clientinfo['farms_limit'])
           {
               $err[] = "Sorry, you have reached maximum allowed amount of running farms.";
               $req_step = 1;
           }
	        
            if (count($err) == 0)
            {
    	        if ($db->GetOne("SELECT * FROM zones WHERE zone='{$post_domainname}'"))
    	        {
    	           $err[] = "Selected domain name already exists in database.";
    	           $req_step = 1;
    	        }
    	        else
    	        {
        	        $display["amis"] = $post_amis;
        	        $display["roles"] = $db->GetAll("SELECT * FROM ami_roles WHERE clientid='0' OR clientid='{$_SESSION['uid']}'");
        	        $_SESSION["wizard"]["domainname"] = $post_domainname;
    	        }
            }
	    }
	    
	    $template_name = "app_wizard_step2.tpl";
	}
	
    if (!$req_step || $req_step == 1)
    {
        $_SESSION["wizard"] = null;
        $template_name = "app_wizard_step1.tpl";
        $display["domainname"] = $_POST["domainname"];
    }
	
	require("src/append.inc.php"); 
?>