<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = _("DNS zone config");
	
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
		$Validator = new Validator();
		
		foreach ($post_hosts as $host)
		{
			$chunks = explode("/", $host);
			$ip_chunks = explode(".", $chunks[0]);
			if (!$Validator->IsIPAddress($chunks[0]) || ($chunks[1] && !$Validator->IsNumeric($chunks[1])) || count($chunks) > 2 || count($ip_chunks) != 4)
				$err[] = sprintf(_("'%s' is not valid IP address or CIDR"), $host);
		}
		
		if (count($err) == 0)
		{
			$hosts = implode(";", $post_hosts);
			if ($hosts != $DBDNSZone->axfrAllowedHosts)
			{
				$DBDNSZone->axfrAllowedHosts = implode(";", $post_hosts);
				$DBDNSZone->isZoneConfigModified = 1;
			}
			
			$DBDNSZone->allowManageSystemRecords = ($post_allow_manage_system_records == 1) ? '1' : '0';
			$DBDNSZone->save();
			
			$okmsg = _("Changes have been saved. They will become active in few minutes.");
			UI::Redirect("dns_zone_settings.php?zone_id={$DBDNSZone->id}");
		}
	}
	
	$display["zone"] = $DBDNSZone->zoneName;
	$display['zone_id'] = $DBDNSZone->id;
	$display['allow_manage_system_records'] = $DBDNSZone->allowManageSystemRecords;
	$display["hosts"] = @explode(";", $DBDNSZone->axfrAllowedHosts);
		
	require("src/append.inc.php");
?>