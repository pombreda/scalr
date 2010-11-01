<? 
	require("src/prepend.inc.php"); 	
	$display["title"] = "DNS zone&nbsp;&raquo;&nbsp;Add";

	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER, Scalr_AuthToken::MODULE_DNS))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	if ($_POST)
	{
		if ($req_step == 1 || !$req_step)
		{
			if ($req_dns_domain_type == 'own')
			{
				$Validator = new Validator();
				if (!$Validator->IsDomain($req_domainname))
					$err[] = _("Invalid domain name");
					
				$domain_chunks = explode(".", $req_domainname);				
				$chk_dmn = '';
				while (count($domain_chunks) > 0)
				{
					$chk_dmn = trim(array_pop($domain_chunks).".{$chk_dmn}", ".");
					if ($db->GetOne("SELECT id FROM dns_zones WHERE zone_name=? AND env_id != ?", 
						array($chk_dmn, Scalr_Session::getInstance()->getEnvironmentId()))
					) {
						if ($chk_dmn == $req_domainname)
							$err[] = sprintf(_("%s already exists on scalr nameservers"), $req_domainname);
						else
							$err[] = sprintf(_("You cannot use %s domain name because top level domain %s does not belong to you"), $req_domainname, $chk_dmn);
					}
				}
				
				if (!$err)
					$domainname = $req_domainname;
			}
			else
			{
				$domainname = Scalr::GenerateUID().".".CONFIG::$DNS_TEST_DOMAIN_NAME;
			}
			
			try
			{
				if ($req_farmid)
				{
					$DBFarm = DBFarm::LoadByID($req_farmid);
					
					if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($DBFarm->EnvID))
						throw new Exception("Farm not found");
	
					$farm_id = $DBFarm->ID;
					
					if ($req_farm_roleid)
					{
						$DBFarmRole = DBFarmRole::LoadByID($req_farm_roleid);
						if ($DBFarmRole->FarmID != $DBFarm->ID)
							throw new Exception(_("Role not found"));
							
						$farm_roleid = $DBFarmRole->ID;
					}
				}
			}
			catch(Exception $e)
			{
				$err[] = $e->getMessage();
			}
			
			if (count($err) == 0)
			{
				$display['step'] = 2;
				$display['domainname'] = $domainname;
				$_SESSION['dns_temp_domain_add'] = array(
					'domainname' => $domainname,
					'farm_roleid'=> $farm_roleid,
					'farm_id'	 => $farm_id
				);
				
				$records = array();
				if (count($display["zone"]["records"]) == 0)
				{
		    		$nss = $db->GetAll("SELECT * FROM nameservers WHERE isbackup='0'");
		            foreach ($nss as $ns)
		            	$records[] = array("id" => "c".rand(10000, 999999), "type" => "NS", "ttl" => 14400, "value" => "{$ns["host"]}.", "name" => "{$display["domainname"]}.", "issystem" => 0);
		                
		            $def_records = $db->GetAll("SELECT * FROM default_records WHERE clientid=?", 
		            	array(Scalr_Session::getInstance()->getClientId())
		            );
		            foreach ($def_records as $record)
		            {
		                $record["name"] = str_replace("%hostname%", "{$display["domainname"]}.", $record["name"]);
		                $record["value"] = str_replace("%hostname%", "{$display["domainname"]}.", $record["value"]);
		            	$records[] = $record;
		            }
		        }
		    		    
				$display["zone"]["records"] = $records;
			}
			else
			{
				$display['step'] = 1;
			}
		}
		elseif ($req_step == 2)
		{						
			$records = array();
			foreach ($post_records as $r)
				if ($r['name'] || $r['value'])
					array_push($records, $r);
					
			$recordsValidation = Scalr_Net_Dns_Zone::validateRecords($records);
			if ($recordsValidation === true)
			{
				$DBDNSZone = DBDNSZone::create(
					$_SESSION['dns_temp_domain_add']['domainname'], 
					$req_zone['soa_refresh'], 
					$req_zone['soa_expire'],
					str_replace('@', '.', $db->GetOne("SELECT email FROM clients WHERE id=?", array(
						Scalr_Session::getInstance()->getClientId()
					))),
					$req_zone['soa_retry']
				);
				
				$DBDNSZone->farmRoleId = (int)$_SESSION['dns_temp_domain_add']['farm_roleid'];
				$DBDNSZone->farmId = (int)$_SESSION['dns_temp_domain_add']['farm_id'];
				$DBDNSZone->clientId = Scalr_Session::getInstance()->getClientId();
				$DBDNSZone->envId = Scalr_Session::getInstance()->getEnvironmentId();
				
				$DBDNSZone->setRecords($records);
				
				$DBDNSZone->save(true);

				$okmsg = _("DNS zone successfully added to database. It could take up to 5 minutes to setup it on NS servers.");
				UI::Redirect("/dns_zones_view.php");
			}
			else
			{
				$err = $recordsValidation;
				$display['step'] = 2;
				$display['domainname'] = $_SESSION['dns_temp_domain_add']['domainname'];
				$display['zone'] = array(
					'records' 		=> $records,
					'soa_refresh'	=> $req_zone['soa_refresh'],
					'soa_expire'	=> $req_zone['soa_expire'],
					'soa_retry'		=> $req_zone['soa_retry'],
				);
			}
		}
	}
	else
	{
		$display['step'] = 1;
		$_SESSION['dns_temp_domain_add'] = null;
	}

	require("src/append.inc.php"); 
?>