<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Applications&nbsp;&raquo;&nbsp;Add / Edit";
	
	if ($_SESSION["_POST"] && $post_vhost_page)
	{
		$Validator = new Validator();
		
		if (!$Validator->IsNotEmpty($post_document_root_dir))
			$err[] = _("Document root required");
			
		if (!$Validator->IsNotEmpty($post_logs_dir))
			$err[] = _("Logs directory required");
			
		if (!$Validator->IsNotEmpty($post_server_admin))
			$err[] = _("Server admin required");
		
		if ($post_issslenabled == 1)
		{
			if (!$_FILES['ssl_cert']['size'])
				$err[] = _("Certificate file required for SSL");
				
			if (!$_FILES['ssl_pk']['size'])
				$err[] = _("Private key file required for SSL");
		}
			
		if (count($err) == 0)
		{
			$_SESSION["vhost_settings"] = $_POST;
			$_SESSION["vhost_settings"]["ssl_cert"] = "";
			$_SESSION["vhost_settings"]["ssl_pkey"] = "";
			$_SESSION["vhost_settings"]["issslenabled"] = 0;
			
			$_SESSION["vhost_settings"]["issslenabled"] = ($post_issslenabled) ? 1 : 0;
			if ($_SESSION["vhost_settings"]["issslenabled"])
			{
				$_SESSION["vhost_settings"]["ssl_cert"] = @file_get_contents($_FILES['ssl_cert']['tmp_name']);
				$_SESSION["vhost_settings"]["ssl_pkey"] = @file_get_contents($_FILES['ssl_pk']['tmp_name']);
			}
		}
		
		$_POST = $_SESSION["_POST"];
		$_SESSION["_POST"] = false;
		
		@extract($_POST, EXTR_PREFIX_ALL, "post");
		@extract($_POST, EXTR_PREFIX_ALL, "req");
	}
	
	if ($_POST) 
	{
		$ZoneControler = new DNSZoneControler();
	    		
		if ($post_ezone && $post_formadded)	
		{
		    if ($_SESSION["uid"] != 0)
    		  $zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=? AND clientid='{$_SESSION['uid']}'", array($post_ezone));
    		else
    		  $zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=?", array($post_ezone));
    		  
    		if (!$zoneinfo)
		      UI::Redirect("sites_view.php");
		    
		    $err = array();
		    
		    $db->BeginTrans();
		    
		    $post_zone['soa_owner'] = trim(str_replace("@", ".", $post_zone['soa_owner']), ".");
		    if ($post_zone['soa_owner'] == "")
		    	$post_zone['soa_owner'] = CONFIG::$DEF_SOA_OWNER;
		    
		    $post_zone['soa_expire'] = ((int)$post_zone['soa_expire'] == 0) ? 3600000 : (int)$post_zone['soa_expire'];
		     
		    try
			{
			    $db->Execute("UPDATE zones SET soa_expire = ?, soa_owner = ? WHERE id=?",
			    	array($post_zone['soa_expire'], $post_zone['soa_owner'], $zoneinfo['id'])
			    );
				
				foreach ((array)$post_zone["records"] as $k=>$v)
				{
					if ($v["rkey"] != '' || $v["rvalue"] != '')
					{
						//
						// Validate Record
						//
						$GLOBALS['warnings'] = array();
						
						$reflection = new ReflectionClass("{$v['rtype']}DNSRecord");
						if ($v['rtype'] == 'MX')
							$c = $reflection->newInstance($v["rkey"], $v["rvalue"], $v["ttl"], $v["rpriority"]);
						elseif($v['rtype'] == 'SRV')
							$c = $reflection->newInstance($v["rkey"], $v["rvalue"], $v["ttl"], $v["rpriority"], $v["rweight"], $v["rport"]);
						else
							$c = $reflection->newInstance($v["rkey"], $v["rvalue"], $v["ttl"]);
						
						if ($c->__toString() == "")
						{
							$err = array_merge($GLOBALS['warnings'], $err);
						}
						else
							$db->Execute("UPDATE records SET `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?, `rweight`=?, `rport`=? WHERE id=?", array($v["rtype"], $v["ttl"], (int)$v["rpriority"], $v["rvalue"], $v["rkey"], (int)$v["rweight"], (int)$v["rport"], $k));
					}
					else
						$db->Execute("DELETE FROM records WHERE id=?", array($k));
				}
				
				foreach ((array)$post_add as $k=>$v)
				{
					if ($v["rkey"] != '' || $v["rvalue"] != '')
					{
						//
						// Validate Record
						//
						$GLOBALS['warnings'] = array();
						
						$reflection = new ReflectionClass("{$v['rtype']}DNSRecord");
						if ($v['rtype'] == 'MX')
							$c = $reflection->newInstance($v["rkey"], $v["rvalue"], $v["ttl"], $v["rpriority"]);
						elseif($v['rtype'] == 'SRV')
							$c = $reflection->newInstance($v["rkey"], $v["rvalue"], $v["ttl"], $v["rpriority"], $v["rweight"], $v["rport"]);
						else
							$c = $reflection->newInstance($v["rkey"], $v["rvalue"], $v["ttl"]);
						
						if ($c->__toString() == "")
							$err = array_merge($GLOBALS['warnings'], $err);
						else
							$db->Execute("INSERT INTO records SET 
								zoneid=?, 
								`rtype`=?, 
								`ttl`=?, 
								`rpriority`=?, 
								`rvalue`=?, 
								`rkey`=?, 
								`rweight`=?, 
								`rport`=?", 
							array(
								$zoneinfo["id"], 
								$v["rtype"], 
								$v["ttl"], 
								(int)$v["rpriority"], 
								$v["rvalue"], 
								$v["rkey"], 
								(int)$v["rweight"], 
								(int)$v["rport"]
							));
					}
				}
								
				if (count($err) == 0)
				{
					$db->CommitTrans();
					
					try
					{
						if (!$ZoneControler->Update($zoneinfo["id"]))
						{
							$errmsg = _("Cannot update DNS zone: ");
						    $err = $GLOBALS['warnings'];
						}
					}
					catch(Exception $e)
					{
						$errmsg = $e->getMessage();
					}
				}
				else
				{
					$db->RollbackTrans();
					$errmsg = _("Cannot update DNS zone");
				}
			}
			catch(Exception $e)
			{
				if (!$errmsg) 
					$errmsg = $e->getMessage();
			}
			
			if (!$errmsg)
			{
				$okmsg = _("Zone successfully updated");
				UI::Redirect("sites_view.php");	
			}
		}
		elseif ($post_domainname && $post_formadded)
		{		
			if ($_SESSION["uid"] == 0)
				UI::Redirect("index.php");
		    
			if (stristr($post_domainname, "scalr.net"))
				$err[] = _("You cannot use *.scalr.net as your application");
				
		    $status = false;
			$post_hostname = $post_domainname;
			
			$roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($post_ami_id));
			
			if ($roleinfo['alias'] == ROLE_ALIAS::APP || $roleinfo['alias'] == ROLE_ALIAS::WWW)
			{
				try
				{					
					$db->Execute("REPLACE INTO vhosts SET
							name				= ?,
							document_root_dir	= ?,
							server_admin		= ?,
							issslenabled		= ?,
							farmid				= ?,
							logs_dir			= ?,
							ssl_cert			= ?,
							ssl_pkey			= ?,
							aliases				= ?,
							role_name		 	= ?
						", 
						array($post_domainname, $_SESSION["vhost_settings"]["document_root_dir"], 
							$_SESSION["vhost_settings"]["server_admin"], $_SESSION["vhost_settings"]["issslenabled"], $post_farmid, 
							$_SESSION["vhost_settings"]["logs_dir"], 
							$_SESSION["vhost_settings"]["ssl_cert"], $_SESSION["vhost_settings"]["ssl_pkey"], 
							$_SESSION["vhost_settings"]["aliases"],
							$roleinfo['name']
						)
					);
					
					$zone_ami_info = $roleinfo;
					
					$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($post_farmid));
					$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=?", array($post_farmid));
					foreach ((array)$instances as $instance)
					{
						$alias = $db->GetOne("SELECT alias FROM ami_roles WHERE ami_id=?", array($instance['ami_id']));
						if ($alias != ROLE_ALIAS::APP && $alias != ROLE_ALIAS::WWW)
							continue;
							
						if ($zone_ami_info['alias'] == ROLE_ALIAS::APP && $zone_ami_info['ami_id'] != $instance['ami_id'])
							continue;
						
						$DBInstance = DBInstance::LoadByID($instance['id']);
						$DBInstance->SendMessage(new VhostReconfigureScalrMessage(
							$post_domainname, 
							$_SESSION["vhost_settings"]["issslenabled"]
						));
					}
				}
				catch(Exception $e)
				{
					$Logger->fatal($e->getMessage());
				}
			}
			
		    $records = array();
			$nss = $db->GetAll("SELECT * FROM nameservers WHERE isproxy='0' AND isbackup='0'");
			foreach ($nss as $ns)
			{
				$records[] = array("rtype" => "NS", "ttl" => 14400, "rvalue" => "{$ns["host"]}.", "rkey" => "{$post_domainname}.", "issystem" => 1);
			}

			$def_records = $db->GetAll("SELECT * FROM default_records WHERE clientid='{$_SESSION['uid']}'");
            foreach ($def_records as $record)
            {
                $record["rkey"] = str_replace("%hostname%", "{$post_domainname}.", $record["rkey"]);
                $record["rvalue"] = str_replace("%hostname%", "{$post_domainname}.", $record["rvalue"]);
            	$records[] = $record;
            }
            
			$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state=? AND isactive='1'", array($post_farmid, INSTANCE_STATE::RUNNING));
    		foreach ($instances as $instance)
    		{
    		    $ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($instance['ami_id']));

    		    $DBFarmRole = DBFarmRole::Load($instance['farmid'], $instance['ami_id']);
    		    if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_EXCLUDE_FROM_DNS) != 1)
    		    {
    		    	$instance_records = DNSZoneControler::GetInstanceDNSRecordsList($instance, $roleinfo["name"], $ami_info['alias']);
    		    	$records = array_merge($records, $instance_records);
    		    }
    		}
    		    
    		$records = array_merge($records, (array)$post_add);
    		$records = array_merge($records, (array)$_POST['zone']['records']);

    		$err = array();
    		
    		foreach($records as $k=>$v)
    		{
    			if (!$v['rtype'])
    				continue;
    				
    			$GLOBALS['warnings'] = array();
    			
    			if ($v["rkey"] != '' || $v["rvalue"] != '')
    			{
	    			$reflection = new ReflectionClass("{$v['rtype']}DNSRecord");
					if ($v['rtype'] == 'MX')
						$c = $reflection->newInstance($v["rkey"], $v["rvalue"], $v["ttl"], $v["rpriority"]);
					elseif($v['rtype'] == 'SRV')
						$c = $reflection->newInstance($v["rkey"], $v["rvalue"], $v["ttl"], $v["rpriority"], $v["rweight"], $v["rport"]);
					else
						$c = $reflection->newInstance($v["rkey"], $v["rvalue"], $v["ttl"]);
					
					if ($c->__toString() == "")
						$err = array_merge($GLOBALS['warnings'], $err);
    			}
    		}
    		    		
    		if (count($err) == 0)
    		{	    		
	    		$post_zone['soa_owner'] = trim(str_replace("@", ".", $post_zone['soa_owner']), ".");
			    if ($post_zone['soa_owner'] == "")
			    	$post_zone['soa_owner'] = CONFIG::$DEF_SOA_OWNER;
    			
			    $post_zone['soa_expire'] = ((int)$post_zone['soa_expire'] == 0) ? CONFIG::$DEF_SOA_EXPIRE : (int)$post_zone['soa_expire'];
			    	
    			$db->Execute("replace into zones (`zone`, `soa_owner`, `soa_ttl`, `soa_parent`, 
    			`soa_serial`, `soa_refresh`, `soa_retry`, `soa_expire`, `min_ttl`, `dtupdated`, 
    			`farmid`, `ami_id`, `clientid`, `role_name`, `status`)
				values (?,?,?,?,?,?,?,?,?,NOW(), ?, ?, ?, ?, ?)", 
				array(
					$post_domainname, 
					$post_zone['soa_owner'], 
					CONFIG::$DEF_SOA_TTL, 
					CONFIG::$DEF_SOA_PARENT, 
					date("Ymd")."01",
					CONFIG::$DEF_SOA_REFRESH,
					CONFIG::$DEF_SOA_RETRY, 
					$post_zone['soa_expire'],
					CONFIG::$DEF_SOA_MINTTL, 
					$post_farmid, 
					$post_ami_id, 
					$_SESSION["uid"], 
					$roleinfo['name'], 
					ZONE_STATUS::PENDING)
				);
				$zoneid = $db->Insert_ID();
				
				$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id='{$zoneid}'");
	    		
				foreach ($records as $k=>$v)
				{
					if ($v["rkey"] != '' || $v["rvalue"] != '')
						$db->Execute("REPLACE INTO records SET 
							zoneid=?, 
							`rtype`=?, 
							`ttl`=?, 
							`rpriority`=?, 
							`rvalue`=?, 
							`rkey`=?, 
							`issystem`=?, 
							`rweight`=?, 
							`rport`=?", 
						array(
							$zoneinfo["id"], 
							$v["rtype"],
							$v["ttl"], 
							(int)$v["rpriority"], 
							$v["rvalue"], 
							$v["rkey"], 
							($v["issystem"] ? 1 : 0), 
							(int)$v["rweight"], 
							(int)$v["rport"]
						));
				}

				try
				{
					$status = $ZoneControler->Update($zoneid);
				}
				catch(Exception $e)
				{
					 $error = $e->getMessage;
				}
				
				if ($error || !$status)
				{
				    $errmsg = $error ? $error : Core::GetLastWarning() ? Core::GetLastWarning() : "";
					$errmsg = "Cannot add new DNS zone. {$errmsg}";
				    
				    $db->Execute("DELETE FROM zones WHERE id='{$zoneid}'");
				    $db->Execute("DELETE FROM records WHERE zoneid='{$zoneid}'");
				}
				else 
				{
					$_SESSION['_POST'] = null;
					$_SESSION["vhost_settings"] = null;
					
					TaskQueue::Attach(QUEUE_NAME::CREATE_DNS_ZONE)->AppendTask(new CreateDNSZoneTask($zoneid));
					
					$okmsg = "Application succesfuly created. DNS zone for {$post_domainname} will be created in a few minutes. Until then, {$post_domainname} will not be resolving.";
				    UI::Redirect("sites_view.php");
				}
    		}
		}
	}
	
	if ($post_ezone || $req_ezone)
	{
		$zone = ($post_ezone) ? $post_ezone : $req_ezone;
		
		if ($_SESSION["uid"] != 0)
		  $zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=? AND clientid='{$_SESSION['uid']}'", array($zone));
		else
		  $zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=?", array($zone));
		  
		if (!$zoneinfo)
			UI::Redirect("sites_view.php");
		
		$records = $db->GetAll("SELECT * FROM records WHERE zoneid='{$zoneinfo["id"]}' ORDER BY rtype ASC, issystem DESC");
		
		$display["zone"] = $zoneinfo;
		$display["zone"]["records"] = $records;
		$display["domainname"] = $display["zone"]["zone"];
	}
	else
	{
		$display = array_merge($display, $_POST);
		$display["zone"] = $_POST;
		
		if ($post_domainname)
		{
			$db_chk = $db->GetRow("SELECT * FROM zones WHERE zone=?", array($post_domainname));
			if (!$db_chk["id"])
			{
				if (stristr($post_domainname, "scalr.net"))
				{
					$errmsg = _("You cannot use *.scalr.net as your application");
					$display["domainname"] = false;
				}
				else
					$display["domainname"] = $post_domainname;
			}	
			else
			{
				$display["domainname"] = false;
				$errmsg = sprintf(_("DNS zone for %s already exists"), $post_domainname);
			}
		}
		
		$display["ami_id"] = $post_ami_id;
		
		$roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($post_ami_id));
		
		if (($roleinfo['alias'] == ROLE_ALIAS::APP || $roleinfo['alias'] == ROLE_ALIAS::WWW) && !$_SESSION["vhost_settings"])
		{
			$template_name = "vhost.tpl";
			$display["vhost"]["name"] = $post_domainname;
			
			$Client = Client::Load($_SESSION['uid']);
			
			$display["vhost"]["server_admin"] = $Client->Email;
		
			$display["vhost"]["document_root_dir"] = CONFIG::$APACHE_DOCROOT_DIR;
			$display["vhost"]["logs_dir"] = CONFIG::$APACHE_LOGS_DIR;
			$display["button2_name"] = "Next";
			
			$display["can_use_ssl"] = !(bool)$db->GetOne("SELECT id FROM vhosts WHERE issslenabled='1' AND farmid=? AND name!=? AND role_name!=?",
				array($zoneinfo['farmid'], $post_domainname, $roleinfo['name'])
			);
			
			$_SESSION['_POST'] = $_POST;
		}
		
		if ($req_farmid)
		{
    		if ($_POST['createtype'] == 2)
    			UI::Redirect("app_wizard.php");
			
			if ($_SESSION['uid'] != 0)
                $display["farm"] = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));
    		else 
    		    $display["farm"] = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));  
    		
    		if (!$display["farm"])
    		{
    		    $errmsg = _("Farm not found");
                UI::Redirect("farms_view.php");
    		}
		}
		else 
		{
		    $template_name = "site_add_step1.tpl";
		    $display["farms"] = $db->GetAll("SELECT * FROM farms WHERE clientid=?", array($_SESSION['uid']));
		    
		    if (count($display["farms"]) == 0)
		    	UI::Redirect("app_wizard.php");
		}
		
		$records = array();
		if (count($display["zone"]["records"]) == 0)
		{
    		$roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($post_ami_id));
    		
			$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state=? AND isactive='1'", array($display["farm"]["id"], INSTANCE_STATE::RUNNING));
    		foreach ($instances as $instance)
    		{
    			$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($instance['ami_id']));
    			
    			$DBFarmRole = DBFarmRole::Load($instance['farmid'], $instance['ami_id']);
    		    if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_EXCLUDE_FROM_DNS) != 1)
    		    {    			
    				$instance_records = DNSZoneControler::GetInstanceDNSRecordsList($instance, $roleinfo["name"], $ami_info['alias']);
    				$records = array_merge($records, $instance_records);
    		    }
    		}
    		    
            $nss = $db->GetAll("SELECT * FROM nameservers WHERE isbackup='0'");
            foreach ($nss as $ns)
            {
            	$issystem = ($ns['isproxy'] == 1) ? 0 : 1;
            	$records[] = array("id" => "c".rand(10000, 999999), "rtype" => "NS", "ttl" => 14400, "rvalue" => "{$ns["host"]}.", "rkey" => "{$display["domainname"]}.", "issystem" => $issystem);
            }
                
                
            $def_records = $db->GetAll("SELECT * FROM default_records WHERE clientid='{$_SESSION['uid']}'");
            foreach ($def_records as $record)
            {
                $record["rkey"] = str_replace("%hostname%", "{$display["domainname"]}.", $record["rkey"]);
                $record["rvalue"] = str_replace("%hostname%", "{$display["domainname"]}.", $record["rvalue"]);
            	$records[] = $record;
            }
        }
    		    
		$display["zone"]["records"] = $records;
		
		$display["roles"] = $db->GetAll("SELECT * FROM ami_roles WHERE iscompleted='1' AND ami_id IN (SELECT ami_id FROM farm_amis WHERE farmid=?)", array($req_farmid));
	}
	
	$display["add"] = array(1, 2, 3, 4, 5);
	$display["def_sn"] = date("Ymd")."01";
	$display["ezone"] = ($_GET["ezone"]) ? $_GET["ezone"] : $_POST["ezone"];
	$display["def_soa_owner"] = CONFIG::$DEF_SOA_OWNER;
	$display["def_soa_parent"] = CONFIG::$DEF_SOA_PARENT;

	if ($display["ezone"])
		$display["help"] = sprintf(_("Scalr nameservers support <a href=\"http://en.wikipedia.org/wiki/DNS_zone_transfer\" target=\"_blank\">DNS zone transfers</a>. If you want to deploy your own backup DNS, click <a href=\"dns_zone_config.php?zone=%s\">here</a> to configure IP adresses of your DNS servers."), $display["ezone"]);
		
	require("src/append.inc.php"); 
?>