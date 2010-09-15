<? 
	require("src/prepend.inc.php"); 
		
	if ($req_action)
	{
		try
		{
			$DBServer = DBServer::LoadByID($req_server_id);
			
			if ($req_action == 'include_in_dns')
			{	
				$DBServer->SetProperty(SERVER_PROPERTIES::EXCLUDE_FROM_DNS, 0);
				
				$zones = DBDNSZone::loadByFarmId($DBServer->farmId);
				foreach ($zones as $DBDNSZone)
				{
					$DBDNSZone->updateSystemRecords($DBServer->serverId);
					$DBDNSZone->save();
				}
				
				$okmsg = _("Server successfully added to DNS");
			}
			elseif ($req_action == 'exclude_from_dns')
			{
				$DBServer->SetProperty(SERVER_PROPERTIES::EXCLUDE_FROM_DNS, 1);
				
				$zones = DBDNSZone::loadByFarmId($DBServer->farmId);
				foreach ($zones as $DBDNSZone)
				{
					$DBDNSZone->updateSystemRecords($DBServer->serverId);
					$DBDNSZone->save();
				}
				
				$okmsg = _("Server successfully removed from DNS");
			}
			elseif  ($req_action == 'cancel')
			{
				$bt_id = $db->GetOne("SELECT id FROM bundle_tasks WHERE server_id=? AND 
					prototype_role_id='0' AND status NOT IN (?,?,?)", array(
					$DBServer->serverId,
					SERVER_SNAPSHOT_CREATION_STATUS::FAILED,
					SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS,
					SERVER_SNAPSHOT_CREATION_STATUS::CANCELLED
				));
				if ($bt_id)
				{
					$BundleTask = BundleTask::LoadById($bt_id);
					$BundleTask->SnapshotCreationFailed("Server was cancelled before snapshot was created.");
				}
				
				$DBServer->Delete();
				$okmsg = _("Server successfully cancelled and removed from database.");
			}
		}
		catch(Exception $e)
		{
			$err[] = $e->getMessage();
		}
		
		UI::Redirect("/servers_view.php?server_id={$req_server_id}");
	}
	
	require("src/append.inc.php"); 
	
?>