<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = _("DNS zone config");
	
	$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=?", array($req_zone));
	if (!$zoneinfo || ($zoneinfo["clientid"] != $_SESSION["uid"] && $_SESSION["uid"] != 0))
	{
		$errmsg = _("DNS zone not found");
		UI::Redirect("sites_view.php");
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
			$db->Execute("UPDATE zones SET axfr_allowed_hosts=?, hosts_list_updated='0' WHERE id=?", 
				array(implode(";", $post_hosts), $zoneinfo['id'])
			);
			
			$okmsg = _("Changes have been saved. They will become active in few minutes.");
			UI::Redirect("dns_zone_config.php?zone={$zoneinfo['zone']}");
		}
	}
	
	$display["zone"] = $zoneinfo["zone"];
	$display["hosts"] = @explode(";", $zoneinfo["axfr_allowed_hosts"]);
		
	require("src/append.inc.php");
?>