<?
	class BundleTasksManagerProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Bundle tasks manager";
        public $Logger;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = Logger::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
            
            $this->ThreadArgs = $db->GetAll("SELECT id FROM bundle_tasks WHERE status NOT IN (?,?,?)", array(
            	SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS,
            	SERVER_SNAPSHOT_CREATION_STATUS::FAILED,
            	SERVER_SNAPSHOT_CREATION_STATUS::CANCELLED
            ));
                        
            $this->Logger->info("Found ".count($this->ThreadArgs)." bundle tasks.");
        }
        
        public function OnEndForking()
        {
			
        }
        
        public function StartThread($bundle_task_info)
        {
         	$db = Core::GetDBInstance();
         	
         	// Reconfigure observers;
        	Scalr::ReconfigureObservers();
         	
         	$BundleTask = BundleTask::LoadById($bundle_task_info['id']);
         	
        	try
         	{
         		$DBServer = DBServer::LoadByID($BundleTask->serverId);
         	}
         	catch (ServerNotFoundException $e)
         	{
         		if (!$BundleTask->snapshotId)
         		{
         			$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::FAILED;
         			$BundleTask->setDate('finished');
         			$BundleTask->failureReason = sprintf(_("Server '%s' was terminated during snapshot creation process"), $BundleTask->serverId);
         			$BundleTask->Save();
         			return;
         		}
         	}
         	catch (Exception $e)
         	{
         		//$this->Logger->error($e->getMessage());
         	}
         	
         	switch($BundleTask->status)
         	{
         		case SERVER_SNAPSHOT_CREATION_STATUS::PENDING:
         			
		         	//TODO:
         			
         			break;
         			
         		case SERVER_SNAPSHOT_CREATION_STATUS::IN_PROGRESS:
         			
         			PlatformFactory::NewPlatform($BundleTask->platform)->CheckServerSnapshotStatus($BundleTask);
         			
         			break;
         			
         		case SERVER_SNAPSHOT_CREATION_STATUS::REPLACING_SERVERS:
         			
         			$r_farm_roles = array();
         			
         			$BundleTask->Log(sprintf("Bundle task replacement type: %s", $BundleTask->replaceType));
         			
         			if ($BundleTask->replaceType == SERVER_REPLACEMENT_TYPE::REPLACE_FARM)
         			{	
         				$DBFarm = DBFarm::LoadByID($BundleTask->farmId);
		         		$r_farm_roles[] = $DBFarm->GetFarmRoleByRoleID($BundleTask->prototypeRoleId);
         			}
         			elseif ($BundleTask->replaceType == SERVER_REPLACEMENT_TYPE::REPLACE_ALL)
         			{
         				$farm_roles = $db->GetAll("SELECT id FROM farm_roles WHERE role_id=? AND farmid IN (SELECT id FROM farms WHERE clientid=?)", array(
         					$BundleTask->prototypeRoleId,
         					$BundleTask->clientId
         				));
         				foreach ($farm_roles as $farm_role)
         				{
         					try
         					{
         						$r_farm_roles[] = DBFarmRole::LoadByID($farm_role['id']);
         					}
         					catch(Exception $e){}
         				}
         			}
         			
         			$update_farm_dns_zones = array();
         			$completed_roles = 0;
         			foreach ($r_farm_roles as $DBFarmRole)
         			{
	         			$servers = $db->GetAll("SELECT server_id FROM servers WHERE farm_roleid = ? AND role_id=? AND status NOT IN (?,?)", array(
		         			$DBFarmRole->ID, $DBFarmRole->RoleID, SERVER_STATUS::TERMINATED, SERVER_STATUS::PENDING_TERMINATE
		         		));
		         		
		         		$BundleTask->Log(sprintf("Found %s servers that need to be replaced with new ones. Role '%s' (ID: %s), farm '%s' (ID: %s)", 
		         			count($servers), 
		         			$DBFarmRole->GetRoleName(),
		         			$DBFarmRole->ID,
		         			$DBFarm->Name,
		         			$DBFarm->ID
		         		));
		         		
		         		if (count($servers) == 0)
		         		{
		         			$DBFarmRole->RoleID = $DBFarmRole->NewRoleID;
		         			$DBFarmRole->NewRoleID = null;
		         			$DBFarmRole->Save();
		         					         			
		         			$update_farm_dns_zones[$DBFarmRole->FarmID] = 1;
		         			
		         			$completed_roles++;
		         		}
		         		else
		         		{		         			
		         			foreach ($servers as $server)
		         			{
		         				try
		         				{
		         					$DBServer = DBServer::LoadByID($server['server_id']);
		         				}
		         				catch(Exception $e)
		         				{
		         					//TODO:
		         					continue;
		         				}
		         				
		         				if ($DBServer->serverId == $BundleTask->serverId)
		         				{
		         					$DBServer->roleId = $BundleTask->roleId;
		         					$DBServer->Save();
		         					
		         					if ($DBServer->GetFarmObject()->Status == FARM_STATUS::SYNCHRONIZING)
		         					{
		         						PlatformFactory::NewPlatform($DBServer->platform)->TerminateServer($DBServer);
		         						
		         						$db->Execute("UPDATE servers_history SET
											dtterminated	= NOW(),
											terminate_reason	= ?
											WHERE server_id = ?
										", array(
											sprintf("Farm was in 'Synchronizing' state. Server terminated when bundling was completed. Bundle task #%s", $BundleTask->id),
											$DBServer->serverId
										));
		         					}
		         				}
		         				else
		         				{
			         				if (!$db->GetOne("SELECT server_id FROM servers WHERE replace_server_id=? AND status NOT IN (?,?)", 
			         					array($DBServer->serverId, SERVER_STATUS::TERMINATED, SERVER_STATUS::PENDING_TERMINATE)
			         				)) {
			         					$ServerCreateInfo = new ServerCreateInfo($DBFarmRole->Platform, $DBFarmRole, $DBServer->index, $DBFarmRole->NewRoleID);
										$nDBServer = Scalr::LaunchServer($ServerCreateInfo);
										$nDBServer->replaceServerID = $DBServer->serverId;
										
										$nDBServer->Save();
										
										$BundleTask->Log(sprintf(_("Started new server %s to replace server %s"), 
				         					$nDBServer->serverId,
				         					$DBServer->serverId
				         				));
			         				}
		         				} // if serverid != bundletask->serverID
		         			} // foreach server
		         		} // count($servers)
         			}
         			

         			
         			if ($completed_roles == count($r_farm_roles))
         			{
         				$BundleTask->Log(sprintf(_("No servers with old role. Replacement complete. Bundle task complete."), 
         					SERVER_REPLACEMENT_TYPE::NO_REPLACE, SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS
         				));
         				
         				$BundleTask->setDate('finished');
	         			$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS;
	         			$BundleTask->Save();
         			}
         			
         			try {
	         			if (count($update_farm_dns_zones) != 0)
	         			{
	         				foreach ($update_farm_dns_zones as $farm_id => $v)
	         				{
	         					$dnsZones = DBDNSZone::loadByFarmId($farm_id);
	         					foreach ($dnsZones as $dnsZone)
	         					{
	         						if ($dnsZone->status != DNS_ZONE_STATUS::INACTIVE && $dnsZone->status != DNS_ZONE_STATUS::PENDING_DELETE)
	         						{
		         						$dnsZone->updateSystemRecords();
		         						$dnsZone->save();
	         						}
	         					}
	         				}
	         			}
         			}
         			catch(Exception $e)
         			{
         				$this->Logger->fatal("DNS ZONE: {$e->getMessage()}");
         			}
         			
         			break;
         			
         		case SERVER_SNAPSHOT_CREATION_STATUS::CREATING_ROLE:
         			
         			try
         			{
         				if ($BundleTask->replaceType == SERVER_REPLACEMENT_TYPE::REPLACE_ALL)
         				{
	         				$role_id = $db->GetOne("SELECT id FROM roles WHERE name=? AND clientid=?", 
	         					array($BundleTask->roleName, $BundleTask->clientId
	         				));
	         				if ($role_id)
	         				{
	         					if ($DBServer)
	         						$new_role_name = BundleTask::GenerateRoleName($DBServer->GetFarmRoleObject(), $DBServer);
	         					else
	         						$new_role_name = $BundleTask->roleName."-".rand(1000, 9999);
	         					
	         					$db->Execute("UPDATE roles SET name=? WHERE id=?", array(
	         						$new_role_name, $role_id
	         					));
	         					
	         					$BundleTask->Log(sprintf(_("Old role '%s' (ID: %s) renamed to '%s'"), 
		         					$BundleTask->roleName, $role_id, $new_role_name
		         				));
	         				}
         				}
         				
         				$DBRole = DBRole::createFromBundleTask($BundleTask);
	         			
	         			if ($BundleTask->replaceType == SERVER_REPLACEMENT_TYPE::NO_REPLACE)
	         			{
	         				$BundleTask->setDate('finished');
	         				$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS;
	         				
	         				$BundleTask->Log(sprintf(_("Replacement type: %s. Bundle task status: %s"), 
	         					SERVER_REPLACEMENT_TYPE::NO_REPLACE, SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS
	         				));
	         				
	         				try
	         				{
		         				$DBServer = DBServer::LoadByID($BundleTask->serverId);
		         				if ($DBServer->status == SERVER_STATUS::IMPORTING)
		         				{
		         					if ($DBServer->farmId)
		         					{
		         						// Create DBFarmRole object
		         						// TODO: create DBFarm role
		         					}
		         					
		         					//$DBServer->Delete();
		         				}
	         				}
	         				catch(Exception $e)
	         				{
	         					
	         				}
	         			}
	         			else
	         			{
		         			try
	         				{
	         					$BundleTask->Log(sprintf(_("Replacement type: %s. Bundle task status: %s"), 
		         					$BundleTask->replaceType, SERVER_SNAPSHOT_CREATION_STATUS::REPLACING_SERVERS
		         				));
	         					
	         					if ($BundleTask->replaceType == SERVER_REPLACEMENT_TYPE::REPLACE_FARM)
         						{
	         						$DBFarm = DBFarm::LoadByID($BundleTask->farmId);
	         						$DBFarmRole = $DBFarm->GetFarmRoleByRoleID($BundleTask->prototypeRoleId);
	         						
	         						$DBFarmRole->NewRoleID = $BundleTask->roleId;
	         						       						
	         						$DBFarmRole->Save();
         						}
         						else
         						{
         							$farm_roles = $db->GetAll("SELECT id FROM farm_roles WHERE role_id=? AND farmid IN (SELECT id FROM farms WHERE clientid=?)", array(
         								$BundleTask->prototypeRoleId,
         								$BundleTask->clientId
         							));
         							foreach ($farm_roles as $farm_role)
         							{
         								$DBFarmRole = DBFarmRole::LoadByID($farm_role['id']);
         								$DBFarmRole->NewRoleID = $BundleTask->roleId;
		         						
		         						$DBFarmRole->Save();
         							}
         						}
	         					
	         					
	         					$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::REPLACING_SERVERS;
	         				}
	         				catch(Exception $e)
	         				{
	         					$this->Logger->error($e->getMessage());
	         					
	         					$BundleTask->Log(sprintf(_("Server replacement failed: %s"), 
		         					$e->getMessage()
		         				));
	         					
		         				$BundleTask->setDate('finished');
	         					$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS;
	         				}
	         			}
	         			
	         			$BundleTask->Save();
         			}
         			catch(Exception $e)
         			{
         				$this->Logger->error($e->getMessage());
         			}
         			
         			break;
         	}
        }
    }
?>