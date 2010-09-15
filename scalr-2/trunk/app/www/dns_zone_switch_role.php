<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("DNS zone&nbsp;&raquo;&nbsp;Switch to new Farm / Role");
	
	try
	{
		$DBDNSZone = DBDNSZone::loadById($req_zone_id);
		if ($DBDNSZone->clientId != $_SESSION['uid'] && $_SESSION['uid'] != 0)
			throw new Exception('Not found');
	}
	catch(Exception $e)
	{
		$errmsg = _("DNS zone not found");
		UI::Redirect("dns_zones_view.php");
	}
	
	if ($_POST)
	{
		try
		{
			if ($req_farmid)
			{
				$DBFarm = DBFarm::LoadByID($req_farmid);
				if ($DBFarm->ClientID != $_SESSION['uid'])
					throw new Exception(_("Farm not found"));
					
				$DBDNSZone->farmId = $DBFarm->ID;
			}
			
			if ($req_farm_roleid)
			{
				$DBFarmRole = DBFarmRole::LoadByID($req_farm_roleid);
				if ($DBFarmRole->FarmID != $DBFarm->ID)
					throw new Exception(_("Role not found"));
					
				$DBDNSZone->farmRoleId = $DBFarmRole->ID;
			}
		}
		catch(Exception $e)
		{
			$err[] = $e->getMessage();
		}
		
		if (count($err) == 0)
		{
			$DBDNSZone->updateSystemRecords();
			$DBDNSZone->save();
			
			$okmsg = 'DNS zone successfully switched to new farm / role.';
			UI::Redirect('/dns_zones_view.php');
		}
	}
	
	if ($DBDNSZone->farmRoleId)
	{
		$DBFarmRole = DBFarmRole::LoadByID($DBDNSZone->farmRoleId);
		$display['role_name'] = $DBFarmRole->GetRoleName();
		$display['farm_name'] = $DBFarmRole->GetFarmObject()->Name;
	}
	
	$display['zone_name'] = $DBDNSZone->zoneName;
	$display['zone_id']	= $DBDNSZone->id;
	
	require("src/append.inc.php");
?>