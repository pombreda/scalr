<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Application&nbsp;&raquo;&nbsp;Switch");
	
	if ($_SESSION["uid"] != 0)
		$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=? AND clientid='{$_SESSION['uid']}'", array($req_application));
	else
		$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=?", array($req_application));
	  
	if (!$zoneinfo)
		UI::Redirect("sites_view.php");
	
	$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($zoneinfo['farmid']));
	
	if ($_POST)
	{
		if ($farminfo['id'] == $post_new_farmid && $zoneinfo['ami_id'] == $post_new_amiid)
			UI::Redirect("sites_view.php");

		$new_farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($post_new_farmid));
		if (!$new_farminfo || $new_farminfo['clientid'] != $zoneinfo['clientid'])
			UI::Redirect("sites_view.php");
			
		$new_roleinfo = $db->GetRow("SELECT farm_amis.*, ami_roles.name FROM farm_amis INNER JOIN ami_roles ON ami_roles.ami_id = farm_amis.ami_id WHERE farmid=? and farm_amis.ami_id=?", array($post_new_farmid, $post_new_amiid));
		if (!$new_roleinfo)
			UI::Redirect("sites_view.php");
			
		$Logger->info(sprintf(_("Switching application '%s'"), $zoneinfo['zone']));

		$ZoneControler = new DNSZoneControler();
		
		$db->BeginTrans();
		
		try
		{
			$Logger->info(_("Updating zone in database"));
			
			$db->Execute("UPDATE zones SET farmid=?, ami_id=?, role_name=? WHERE id=?", 
				array($post_new_farmid, $post_new_amiid, $new_roleinfo['name'], $zoneinfo['id'])
			);
			
			$vhostinfo = $db->GetRow("SELECT * FROM vhosts WHERE name=? AND farmid=?", 
				array($zoneinfo['zone'], $zoneinfo['farmid'])
			);
			
			if ($vhostinfo)
			{
				$Logger->info(_("Updating vhost in database"));
				
				$db->Execute("UPDATE vhosts SET farmid=?, role_name=? WHERE id=?",
					array($post_new_farmid, $new_roleinfo['name'], $vhostinfo['id'])
				);
			}
			
			$Logger->info(_("Updating DNS records"));
			
			//Update DNS zone
			$db->Execute("DELETE FROM records WHERE zoneid=? AND issystem='1' AND rtype='A'", 
				array($zoneinfo['id'])
			);
			
			$records = array();
			$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state=? AND isactive='1'", array($post_new_farmid, INSTANCE_STATE::RUNNING));
			
			foreach ($instances as $instance)
    		{
    		    $ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($instance['ami_id']));
    		    
    			try
				{
					$DBFarmRole = DBFarmRole::Load($instance['famrid'], $instance['ami_id']);
					$skip_main_a_records = ($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB) == 1) ? true : false;
				}
				catch(Exception $e)
				{
					$Logger->fatal(sprintf("instances_view(73): %s", $e->getMessage()));
					$skip_main_a_records = false;
				}
    		    
    		    $instance_records = DNSZoneControler::GetInstanceDNSRecordsList($instance, $new_roleinfo["name"], $ami_info['alias']);
    		    $records = array_merge($records, $instance_records);
    		}
    		
			foreach ($records as $k=>$v)
			{
				if ($v["rkey"] != '' || $v["rvalue"] != '')
					$db->Execute("REPLACE INTO records SET zoneid=?, `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?, `issystem`=?", array($zoneinfo["id"], $v["rtype"], $v["ttl"], (int)$v["rpriority"], $v["rvalue"], $v["rkey"], $v["issystem"] ? 1 : 0));
			}
    		
			//Send VHOS_RECONFIGURE trap
			if ($vhostinfo)
			{
				$Logger->info(_("Reconfiguring vhost on app and www instances"));
				
				$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=?", array($post_new_farmid));
				foreach ((array)$instances as $instance)
				{
					$alias = $db->GetOne("SELECT alias FROM ami_roles WHERE ami_id=?", array($instance['ami_id']));
					if ($alias != ROLE_ALIAS::APP && $alias != ROLE_ALIAS::WWW)
						continue;
						
					if ($new_roleinfo['alias'] == ROLE_ALIAS::APP && $new_roleinfo['ami_id'] != $instance['ami_id'])
						continue;
					
					$DBInstance = DBInstance::LoadByID($instance['id']);
					$DBInstance->SendMessage(new VhostReconfigureScalrMessage(
						$zoneinfo['zone'], 
						$vhostinfo['issslenabled']
					));
				}
			}
		}
		catch(Exception $e)
		{
			$Logger->error($e->getMessage());
			
			$errmsg = sprintf(_("Cannot switch application. %s"), $e->getMessage());
			$db->RollbackTrans();
		}
		
		if (!$errmsg)
		{
			$db->CommitTrans();
			
			try
			{
				$Logger->info(_("Updating zone on NS servers"));
				$ZoneControler->Update($zoneinfo['id']);
			}
			catch(Exception $e)
			{
				$db->Execute("UPDATE zones SET isobsoleted='1' WHERE id=?", array($zoneinfo['id']));
				$Logger->error($e->getMessage());
			}
			
			$Logger->info("Application successfully switched.");
			
			$okmsg = _("Application successfully switched to another farm/role.");
			UI::Redirect("sites_view.php");
		}
	}
	
	$display['farminfo'] = $farminfo;
	$display['role_name'] = $zoneinfo['role_name'];
	$display['ami_id'] = $zoneinfo['ami_id'];
	$display['application'] = $req_application;
	
	$display['roles'] = $db->GetAll("SELECT farm_amis.*, ami_roles.name FROM farm_amis INNER JOIN ami_roles ON ami_roles.ami_id = farm_amis.ami_id WHERE farmid=?", array($farminfo['id']));

	$display['farms'] = $db->GetAll("SELECT * FROM farms WHERE clientid=? AND status IN(?,?)", array($farminfo['clientid'], FARM_STATUS::RUNNING, FARM_STATUS::TERMINATED));
	foreach ($display['farms'] as &$farm)
		$farm['roles'] = $db->GetAll("SELECT farm_amis.*, ami_roles.name FROM farm_amis INNER JOIN ami_roles ON ami_roles.ami_id = farm_amis.ami_id WHERE farmid=?", array($farm['id']));
	
	require("src/append.inc.php");
?>