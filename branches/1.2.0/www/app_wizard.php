<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = _("Website creation wizard");
    $Validator = new Validator();
    
    $Client = Client::Load($_SESSION['uid']);

    if ($_SESSION['uid'] == 0)
    {
    	$errmsg = _("Requested page cannot be viewed from admin account");
    	UI::Redirect("index.php");
    }
    
    if ($req_step == 4)
    {
        if (!$_SESSION['wizard'] || !$_SESSION['wizard']["domainname"] || count($_SESSION['wizard']['amis']) == 0)
        {
        	$errmsg = _("Your session has been expired.<br>Please start again.");
        	UI::Redirect("app_wizard.php");
        }
    	
    	if ($db->GetOne("SELECT id FROM zones WHERE zone=? AND status != ?", 
        	array($_SESSION['wizard']["domainname"], ZONE_STATUS::DELETED))
        ){
        	$errmsg = sprintf(_("'%s' domain already exists in database."), $_SESSION['wizard']["domainname"]);
        	UI::Redirect("app_wizard.php");
        }
    	
    	$_SESSION["wizard"]["dnsami"] = $post_dnsami;
        	                            
        $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION["wizard"]["region"]));
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
                
        $db->BeginTrans();
        
        //
        // Add farm information to database
        //
        $farmname = preg_replace("/[^A-Za-z0-9]+/", "", $_SESSION['wizard']["domainname"]);
                
        $farmhash = $Crypto->Sault(14);
        $db->Execute("INSERT INTO farms SET status='0', name=?, clientid=?, hash=?, dtadded=NOW(), region=?", 
        	array($farmname, $_SESSION['uid'], $farmhash, $_SESSION["wizard"]["region"])
        );
        $farmid = $db->Insert_ID();
        
        $bucket_name = "farm-{$farmid}-{$Client->AWSAccountID}";
        
        $DBFarm = new DBFarm($farmid);
		$DBFarm->SetSetting(DBFarm::SETTING_AWS_S3_BUCKET_NAME, $bucket_name);
        
        //
        // Create FARM KeyPair
        //
        try
        {
            $key_name = "FARM-{$farmid}";
            $result = $AmazonEC2Client->CreateKeyPair($key_name);
            if ($result->keyMaterial)
            {
                $DBFarm = new DBFarm($farmid);
        		$DBFarm->SetSetting(DBFarm::SETTING_AWS_PRIVATE_KEY, $result->keyMaterial);
        		$DBFarm->SetSetting(DBFarm::SETTING_AWS_KEYPAIR_NAME, $key_name);
            }
            else 
                throw new Exception(_("Cannot create key pair for farm."), E_ERROR);
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
	            $roleinfo = $db->GetRow("SELECT * FROM roles WHERE ami_id='{$ami_id}'");
	            
	            $itype = ($roleinfo["architecture"] == INSTANCE_ARCHITECTURE::I386) ? I386_TYPE::M1_SMALL : X86_64_TYPE::M1_LARGE;
	            
	            $db->Execute("INSERT INTO farm_roles SET 
							farmid=?, ami_id=?, reboot_timeout=?, launch_timeout=?, status_timeout = ?
                            ", array( 
                        		$farmid, 
                        		$ami_id, 
	                            300,
                            	2400,
                         		20
						));

				$roleid = $db->Insert_ID();
				
				$DBFarmRole = new DBFarmRole($roleid);
				$DBFarmRole->FarmID = $farmid;
				$DBFarmRole->AMIID = $ami_id;
						
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES, 1);
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES, 1);
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_POLLING_INTERVAL, 2);
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_AWS_INSTANCE_TYPE, $itype);
				
				if ($roleinfo['default_minLA'])
					$DBFarmRole->SetSetting(LAScalingAlgo::PROPERTY_MIN_LA, $roleinfo['default_minLA']);
					
				if ($roleinfo['default_maxLA'])
					$DBFarmRole->SetSetting(LAScalingAlgo::PROPERTY_MAX_LA, $roleinfo['default_maxLA']);
				
				
				if ($roleinfo['alias'] == ROLE_ALIAS::MYSQL)
				{
					if (!$DBFarm)
						$DBFarm = new DBFarm($farmid);
	    	
					$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_BCP_ENABLED, $farm_mysql_make_backup);
					$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_BCP_EVERY, $farm_mysql_make_backup_every);	                
					$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_BUNDLE_ENABLED, $farm_mysql_bundle);
					$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_BUNDLE_EVERY, $farm_mysql_bundle_every);
					$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_DATA_STORAGE_ENGINE, 'LVM');	                
					$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_EBS_VOLUME_SIZE, $farm_mysql_ebs_size);
					
				}
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
               $AmazonS3->CreateBucket($bucket_name, $_SESSION["wizard"]["region"]);
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
	        $roleinfo = $db->GetRow("SELECT * FROM roles WHERE ami_id='{$_SESSION['wizard']["dnsami"]}'");
        	
	        $records = array();
			$nss = $db->GetAll("SELECT * FROM nameservers WHERE isbackup='0'");
			foreach ($nss as $ns)
			{
	            $issystem = ($ns['isproxy'] == 1) ? 0 : 1;
				$records[] = array("rtype" => "NS", "ttl" => 14400, "rvalue" => "{$ns["host"]}.", "rkey" => "{$_SESSION['wizard']["domainname"]}.", "issystem" => $issystem);
			}
			
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
			
			TaskQueue::Attach(QUEUE_NAME::CREATE_DNS_ZONE)->AppendTask(new CreateDNSZoneTask($zoneid));
			
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
			    $err[] = sprintf(_("Cannot add new DNS zone: %s"), Core::GetLastWarning());
			}
        }
        catch(Exception $e)
        {
        	$Logger->warn(_("Exception thrown during zone update. Cron will repair this zone."));
        }
		
		$okmsg = _("Application successfully created.");
		UI::Redirect("farms_control.php?farmid={$farmid}&new=1&iswiz=1");
    }
    
	if ($req_step == 3)
	{
	    if (count($post_amis) == 0)
	    {
	        $err[] = _("You must select at least one role");
	    }
	    else 
	    {
	        $used_slots = $db->GetOne("SELECT SUM(value) FROM farm_role_settings WHERE name=? 
	        	AND farm_roleid IN (SELECT id FROM farm_roles WHERE farmid IN (SELECT id FROM farms WHERE clientid=?))",
	        	array(DBFarmRole::SETTING_SCALING_MAX_INSTANCES, $_SESSION["uid"])
	        );
	    	
	    	$total_max_count = count($post_amis)*2;
            $client_max_instances = $Client->GetSettingValue(CLIENT_SETTINGS::MAX_INSTANCES_LIMIT);
            $i_limit = $client_max_instances ? $client_max_instances : CONFIG::$CLIENT_MAX_INSTANCES;
            
            if ($used_slots+$total_max_count > $i_limit)
                $err[] = sprintf(_("You cannot launch more than %s instances on your account. Please adjust Max Instances setting."), $i_limit);
	        else 
	        {
    	        $_SESSION["wizard"]["amis"] = $post_amis;
    	        
    	        $roles = $db->GetAll("SELECT * FROM roles WHERE (clientid='0' OR clientid='{$_SESSION['uid']}') AND iscompleted='1'");
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
	        $err[] = _("Invalid domain name");
	        $req_step = 1;
	    }
	    else 
	    {
	        if (stristr($post_domainname, "scalr.net"))
	        {
				$err[] = _("You cannot use *.scalr.net as your application");
				$req_step = 1;
	        }
			else
			{
	    	
		       $num = $db->GetOne("SELECT COUNT(*) FROM farms WHERE clientid='{$_SESSION['uid']}'");   
	            	       
	           if ($num >= $Client->FarmsLimit && $Client->FarmsLimit != 0)
	           {
	               $err[] = _("Sorry, you have reached maximum allowed amount of running farms.");
	               $req_step = 1;
	           }
		        
	            if (count($err) == 0 || $skip_error_check)
	            {
	    	        if ($db->GetOne("SELECT * FROM zones WHERE zone=? AND status != ?", 
	    	        	array($post_domainname, ZONE_STATUS::DELETED))
	    	        ){
	    	           $err[] = _("Selected domain name already exists in database.");
	    	           $req_step = 1;
	    	        }
	    	        else
	    	        {
	        	        $display["amis"] = $post_amis;
	        	        
	        	        $display["roles_descr"] = $db->GetAll("SELECT ami_id, name, description FROM roles WHERE roletype=? OR (roletype=? and clientid=?)", 
					    	array(ROLE_TYPE::SHARED, ROLE_TYPE::CUSTOM, $_SESSION['uid'])
					    );
	        	        
					    $_SESSION["wizard"]["region"] = $post_region;
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
        $display['region'] = $_POST["region"];
    }
	
	require("src/append.inc.php"); 
?>