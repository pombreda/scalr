<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "DNS zone&nbsp;&raquo;&nbsp;Edit";

	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER, Scalr_AuthToken::MODULE_DNS))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	try
	{
		$DBDNSZone = DBDNSZone::loadById($req_zone_id);
		if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($DBDNSZone->envId))
			throw new Exception('Not found');
	}
	catch(Exception $e)
	{
		$errmsg = _("DNS zone not found");
		UI::Redirect("dns_zones_view.php");
	}
	
	if ($_POST)
	{					
		$records = array();
		foreach ($post_records as $r)
			if ($r['name'] || $r['value'])
				array_push($records, $r);
				
		$recordsValidation = Scalr_Net_Dns_Zone::validateRecords($records);
		if ($recordsValidation === true)
		{	
			$DBDNSZone->soaRefresh = $req_zone['soa_refresh'];
			$DBDNSZone->soaExpire = $req_zone['soa_expire'];
			$DBDNSZone->soaRetry = $req_zone['soa_retry'];
			
			$DBDNSZone->setRecords($records);
			
			$DBDNSZone->save();

			$okmsg = _("DNS zone successfully updated. It could take up to 5 minutes to update it on NS servers.");
			UI::Redirect("/dns_zones_view.php");
		}
		else
		{
			$err = $recordsValidation;
			$display['step'] = 2;
			$display['zone'] = array(
				'records' 		=> $records,
				'soa_refresh'	=> $req_zone['soa_refresh'],
				'soa_expire'	=> $req_zone['soa_expire'],
				'soa_retry'		=> $req_zone['soa_retry'],
			);
		}
	}

	$display['domainname'] = $DBDNSZone->zoneName;
	
	if (!$display['zone'])
	{
		$display['zone'] = array(
			'soa_refresh' 	=> $DBDNSZone->soaRefresh,
			'soa_expire'	=> $DBDNSZone->soaExpire,
			'soa_retry'		=> $DBDNSZone->soaRetry,
			'allow_manage_system_records'	=> $DBDNSZone->allowManageSystemRecords,
			'records'		=> $DBDNSZone->getRecords()
		);
	}
	
	$display['edit'] = true;
	$template_name = 'dns_zone_add.tpl';
	
	require("src/append.inc.php"); 
?>