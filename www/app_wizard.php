<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Application creation wizard";
    $Validator = new Validator();
    
    if ($_SESSION['uid'] == 0)
    {
    	$errmsg = "Requested page cannot be viewed from admin account";
    	UI::Redirect("index.php");
    }
    
    if ($req_step == 4)
    {
        if ($db->GetOne("SELECT id FROM zones WHERE zone=? AND status != ?", 
        	array($_SESSION['wizard']["domainname"], ZONE_STATUS::DELETED))
        ){
        	$errmsg = "'{$_SESSION['wizard']["domainname"]}' domain already exists in database.";
        	UI::Redirect("app_wizard.php");
        }
    	
    	$_SESSION["wizard"]["dnsami"] = $post_dnsami;
        	                            
        $AmazonEC2Client = new AmazonEC2($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);
                
        $db->BeginTrans();
        
        //
        // Add farm information to database
        //
        $farmname = preg_replace("/[^A-Za-z0-9]+/", "", $_SESSION['wizard']["domainname"]);
                
        $farmhash = $Crypto->Sault(14);
        $db->Execute("INSERT INTO farms SET status='0', name=?, clientid=?, hash=?, dtadded=NOW()", array($farmname, $_SESSION['uid'], $farmhash));
        $farmid = $db->Insert_ID();
        
        $bucket_name = "farm-{$farmid}-{$_SESSION['aws_accountid']}";
        
        $db->Execute("UPDATE farms SET bucket_name=? WHERE id=?",
			array($bucket_name, $farmid)
		);
        
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
        
        //
        // Add farm amis information
        //
        try
        {
	        foreach ($_SESSION['wizard']['amis'] as $ami_id)
	        {
	            $roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$ami_id}'");
	            
	            $itype = ($roleinfo["architecture"] == INSTANCE_ARCHITECTURE::I386) ? I386_TYPE::M1_SMALL : X86_64_TYPE::M1_LARGE;
	            
	            
	            $db->Execute("INSERT INTO 
	                                       farm_amis 
	                                  SET 
	                                       farmid=?, 
	                                       ami_id=?, 
	                                       min_count=?, 
	                                       max_count=?, 
	                                       min_LA=?, 
	                                       max_LA=?,
	                                       instance_type = ?,
	                                       avail_zone = ''
	                         ", array( $farmid, 
	                                   $ami_id, 
	                                   1, 
	                                   2,
	                                   ($roleinfo["default_minLA"] ? $roleinfo["default_minLA"] : 5),
	                                   ($roleinfo["default_maxLA"] ? $roleinfo["default_maxLA"] : 10),
	                                   $itype
	                                 )
	                         );
	                         
				if ($roleinfo['alias'] == ROLE_ALIAS::MYSQL)
					$db->Execute("UPDATE farms SET mysql_rebundle_every='48', mysql_bcp_every='180', mysql_bcp='0' WHERE id=?", array($farmid));
	        }
        }
        catch(Exception $e)
        {
        	$db->RollbackTrans();
        	throw new ApplicationException($e->getMessage(), E_ERROR);
        }

        //
        // Create S3 Bucket (For MySQL, BackUs, etc.)
        //
        try
        {
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
        
        try
        {
	        //
	        // Add DNS Zone
	        //
	        $roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$_SESSION['wizard']["dnsami"]}'");
        	
	        $records = array();
			$nss = $db->GetAll("SELECT * FROM nameservers");
			foreach ($nss as $ns)
	            $records[] = array("rtype" => "NS", "ttl" => 14400, "rvalue" => "{$ns["host"]}.", "rkey" => "{$_SESSION['wizard']["domainname"]}.", "issystem" => 1);
			
        	$def_records = $db->GetAll("SELECT * FROM default_records WHERE clientid='{$_SESSION['uid']}'");
            foreach ($def_records as $record)
            {
                $record["rkey"] = str_replace("%hostname%", "{$_SESSION['wizard']["domainname"]}.", $record["rkey"]);
                $record["rvalue"] = str_replace("%hostname%", "{$_SESSION['wizard']["domainname"]}.", $record["rvalue"]);
            	$records[] = $record;
            }
	            
			$db->Execute("REPLACE INTO zones (
				`zone`, 
				`soa_owner`, 
				`soa_ttl`, 
				`soa_parent`, 
				`soa_serial`, 
				`soa_refresh`, 
				`soa_retry`, 
				`soa_expire`, 
				`min_ttl`, 
				`dtupdated`, 
				`farmid`, 
				`ami_id`, 
				`clientid`, 
				`role_name`, 
				`status`
			)
			VALUES (
				?,?,?,?,?,?,?,?,?,NOW(), ?, ?, ?, ?, ?
			)", 
				array(
					$_SESSION['wizard']["domainname"],
					CONFIG::$DEF_SOA_OWNER,
					CONFIG::$DEF_SOA_TTL,
					CONFIG::$DEF_SOA_PARENT,
					date("Ymd")."01",
					CONFIG::$DEF_SOA_REFRESH,
					CONFIG::$DEF_SOA_RETRY,
					CONFIG::$DEF_SOA_EXPIRE,
					CONFIG::$DEF_SOA_MINTTL,
					$farmid, 
					$_SESSION['wizard']["dnsami"], 
					$_SESSION["uid"], 
					$roleinfo["name"], 
					ZONE_STATUS::PENDING)
			);
			$zoneid = $db->Insert_ID();
			
			$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id='{$zoneid}'");
			
			TaskQueue::Attach(QUEUE_NAME::CREATE_DNS_ZONE)->Put(new CreateDNSZoneTask($zoneid));
			
			foreach ($records as $k=>$v)
			{
				if ($v["rkey"] != '' || $v["rvalue"] != '')
				{
					$db->Execute("REPLACE INTO records SET 
						zoneid=?, 
						`rtype`=?, 
						`ttl`=?, 
						`rpriority`=?, 
						`rvalue`=?, 
						`rkey`=?, 
						`issystem`=?", 
					array(
						$zoneinfo["id"], 
						$v["rtype"], 
						$v["ttl"], 
						$v["rpriority"], 
						$v["rvalue"], 
						$v["rkey"], 
						$v["issystem"] ? 1 : 0)
					);
				}
			}
        }
        catch(Exception $e)
        {
        	$db->RollbackTrans();
        	throw new ApplicationException($e->getMessage(), E_ERROR);
        }
		
        $db->CommitTrans();

        try
        {
			$ZoneControler = new DNSZoneControler();
			if (!$ZoneControler->Update($zoneid))
			{
			    $err[] = "Cannot add new DNS zone: ".Core::GetLastWarning();
			}
        }
        catch(Exception $e)
        {
        	$Logger->warn("Exception thrown during zone update. Cron will fix this zone.");
        }
		
		$okmsg = "Application successfully created.";
		UI::Redirect("farms_control.php?farmid={$farmid}&new=1&iswiz=1");
    }
    
	if ($req_step == 3)
	{
	    if (count($post_amis) == 0)
	    {
	        $err[] = "You must select at least one role";
	    }
	    else 
	    {
	        $total_max_count = count($post_amis)*2; 	    
            $used_slots = $db->GetOne("SELECT SUM(max_count) FROM farm_amis WHERE farmid IN (SELECT id FROM farms WHERE clientid='{$_SESSION['uid']}')");
            
            $client_max_instances = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", 
            	array('client_max_instances', $_SESSION['uid'])
            );
            $i_limit = $client_max_instances ? $client_max_instances : CONFIG::$CLIENT_MAX_INSTANCES;
            
            if ($used_slots+$total_max_count > $i_limit)
            {
                $err[] = "You cannot launch more than {$i_limit} instances on your account. Please adjust Max Instances setting.";
            }
	        else 
	        {
    	        $_SESSION["wizard"]["amis"] = $post_amis;
    	        
    	        $roles = $db->GetAll("SELECT * FROM ami_roles WHERE (clientid='0' OR clientid='{$_SESSION['uid']}') AND iscompleted='1'");
    	        foreach ($roles as $role)
    	        {
    	            if (in_array($role["ami_id"], $post_amis))
    	               $display["roles"][] = $role;
    	        }
    	        
    	        $template_name = "app_wizard_step3.tpl";
	        }
	    }
	    
	    if (count($err) != 0)
	    {
	    	$req_step = 2;
	        $skip_error_check = true;
	        $post_domainname = $_SESSION["wizard"]["domainname"];
	    }
	}
    
	if ($req_step == 2)
	{    
	    if (!$Validator->IsDomain($post_domainname))
	    {
	        $err[] = "Invalid domain name";
	        $req_step = 1;
	    }
	    else 
	    {
	        if (stristr($post_domainname, "scalr.net"))
	        {
				$err[] = "You cannot use *.scalr.net as your application";
				$req_step = 1;
	        }
			else
			{
	    	
		    	$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id='{$_SESSION['uid']}'");
	            $num = $db->GetOne("SELECT COUNT(*) FROM farms WHERE clientid='{$_SESSION['uid']}'");   
	            	       
	           if ($num >= $clientinfo['farms_limit'] && $clientinfo['farms_limit'] != 0)
	           {
	               $err[] = "Sorry, you have reached maximum allowed amount of running farms.";
	               $req_step = 1;
	           }
		        
	            if (count($err) == 0 || $skip_error_check)
	            {
	    	        if ($db->GetOne("SELECT * FROM zones WHERE zone=? AND status != ?", 
	    	        	array($post_domainname, ZONE_STATUS::DELETED))
	    	        ){
	    	           $err[] = "Selected domain name already exists in database.";
	    	           $req_step = 1;
	    	        }
	    	        else
	    	        {
	        	        $display["amis"] = $post_amis;
	        	        $display["roles"] = $db->GetAll("SELECT * FROM ami_roles WHERE (clientid='0' OR clientid='{$_SESSION['uid']}') AND iscompleted='1'");
	        	        
	        	        $display["roles_descr"] = $db->GetAll("SELECT ami_id, name, description FROM ami_roles WHERE roletype=? OR (roletype=? and clientid=?)", 
					    	array(ROLE_TYPE::SHARED, ROLE_TYPE::CUSTOM, $_SESSION['clientid'])
					    );
	        	        
	        	        $_SESSION["wizard"]["domainname"] = $post_domainname;
	    	        }
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