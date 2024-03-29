<?php
	class DNSEventObserver extends EventObserver
	{
		public $ObserverName = 'DNS';
		
		function __construct()
		{
			parent::__construct();
			
			$this->DNSZoneController = new DNSZoneControler();
		}
	
		public function OnRebootComplete(RebootCompleteEvent $event)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
						
			$this->AddInstanceToDNS($farminfo, $event->DBInstance);
		}
		
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event)
		{
			$event->DBInstance->ReLoad();
			
			if ($event->DBInstance->IncludeInDNS != 1)
				return;
			
			$DBFarmRole = $event->DBInstance->GetDBFarmRoleObject();
			if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_EXCLUDE_FROM_DNS) != 1)
				return;
				
			try
			{
				$zones = $this->DB->GetAll("SELECT * FROM zones WHERE farmid=? AND status IN (?,?)", 
					array($this->FarmID, ZONE_STATUS::ACTIVE, ZONE_STATUS::PENDING)
				);
				if (count($zones) == 0)
					return;
					
				foreach ($zones as $zone)
				{								
					if (!$zone['id'])
						continue;
											
					$records_attrs = array();
					
					// We must update: 
					// 'int-ROLE_NAME-master IN A INTERNAL_IP'
					// 'ext-ROLE_NAME-master IN A PUBLIC_IP'
					// records
					if ($instanceinfo["isdbmaster"] == 1)
					{
						$records_attrs[] = array("int-{$DBFarmRole->GetRoleName()}-master", $event->DBInstance->InternalIP, 20);
						$records_attrs[] = array("ext-{$DBFarmRole->GetRoleName()}-master", $event->DBInstance->ExternalIP, 20);
						
						if ($DBFarmRole->GetRoleName() != ROLE_ALIAS::MYSQL)
						{
							$records_attrs[] = array("int-mysql-master", $event->DBInstance->InternalIP, 20);
							$records_attrs[] = array("ext-mysql-master", $event->DBInstance->ExternalIP, 20);
						}
					}
										
					// Adding new records to database
					foreach ($records_attrs as $record_attrs)
					{									
						$this->DB->Execute("REPLACE INTO records SET zoneid=?, rtype='A', ttl=?, rvalue=?, rkey=?, issystem='1'",
							array($zone['id'], $record_attrs[2], $record_attrs[1], $record_attrs[0])
						);
					}
					
					// Remove old role-slave and mysql-slave records for this instance
					$this->DB->Execute("DELETE FROM records WHERE zoneid=? AND rtype='A' AND rkey LIKE '%-slave' AND (rvalue=? OR rvalue=?) AND issystem='1'",
						array($zone['id'], $event->DBInstance->InternalIP, $event->DBInstance->ExternalIP)
					);
					
					
					// Update DNS zone on Nameservers
					if (!$this->DNSZoneController->Update($zone["id"]))
						$this->Logger->error(_("Cannot update zone in DNSEventObserver"));
					else
						$this->Logger->info("Instance {$instanceinfo['instance_id']} added to DNS zone '{$zone['zone']}'");
				}
			}
			catch(Exception $e)
			{
				$this->Logger->fatal("DNS zone update failed: ".$e->getMessage());
			}
		}
	
		/**
		 * Public IP address for instance changed
		 *
		 * @param array $instanceinfo
		 * @param string $new_ip_address
		 */
		public function OnIPAddressChanged(IPAddressChangedEvent $event)
		{
			// Get farm info
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			if ($farminfo['status'] != 1)
				return;
				
			$DBFarmRole = DBFarmRole::LoadByID($event->DBInstance->FarmRoleID);
			if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_EXCLUDE_FROM_DNS) != 1)
				$this->AddInstanceToDNS($farminfo, $event->DBInstance);
		}
		
		/**
		 * Farm launched
		 *
		 * @param bool $mark_instances_as_active
		 */
		public function OnFarmLaunched(FarmLaunchedEvent $event)
		{
			// Get list of all zones for current farm
			$zones = $this->DB->GetAll("SELECT * FROM zones WHERE farmid='{$this->FarmID}'");
			if (count($zones) < 1)
				return;
			
			// Foreach zone set status - Active
	        foreach ((array)$zones as $zone)
	        {
	            $this->DNSZoneController->Update($zone["id"]);
	            if ($zone["status"] != ZONE_STATUS::PENDING)
	            	$this->DB->Execute("UPDATE zones SET status=? WHERE id='{$zone['id']}'", array(ZONE_STATUS::ACTIVE));
	        }
		}
		/**
		 * Farm terminated
		 *
		 * @param bool $remove_zone_from_DNS
		 * @param bool $keep_elastic_ips
		 */
		public function OnFarmTerminated(FarmTerminatedEvent $event)
		{
			if (!$event->RemoveZoneFromDNS)
				return;
			
			// Get list of all zones for current farm
			$zones = $this->DB->GetAll("SELECT * FROM zones WHERE farmid='{$this->FarmID}'");
	        foreach ((array)$zones as $zone)
	        {
	            // Remove dynamic A records (pointed to instances)
	        	$this->DB->Execute("DELETE FROM records WHERE rtype='A' AND 
	            	issystem='1' AND 
	            	zoneid='{$zone['id']}'"
	            );	            
	        }
	        
	        // Set status for zones - INACTIVE
	        $this->DB->Execute("UPDATE zones SET status=? WHERE farmid='{$this->FarmID}'", 
	        	array(ZONE_STATUS::INACTIVE)
	        );
		}
		
		/**
		 * Instance sent hostUp event
		 *
		 * @param array $instanceinfo
		 */
		public function OnHostUp(HostUpEvent $event)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));

			$DBFarmRole = DBFarmRole::LoadByID($event->DBInstance->FarmRoleID);
			
			if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_EXCLUDE_FROM_DNS) != 1)
				$this->AddInstanceToDNS($farminfo, $event->DBInstance);
		}
		
		/**
		 * Instance terminated
		 *
		 * @param array $instanceinfo
		 */
		public function OnHostDown(HostDownEvent $event)
		{
			//
			// Remove terminated instance from DNS records
			//
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			if ($farminfo['status'] != FARM_STATUS::RUNNING)
				return;
				
			try
			{
				$zones = $this->DB->GetAll("SELECT DISTINCT(zoneid) FROM records WHERE rvalue='{$event->DBInstance->ExternalIP}' OR rvalue='{$event->DBInstance->InternalIP}' GROUP BY zoneid");
				foreach ($zones as $zone)
				{
					$zoneinfo = $this->DB->GetRow("SELECT * FROM zones WHERE id='{$zone['zoneid']}'");
					if (!$zoneinfo)
						continue;
						
					$this->DB->Execute("DELETE FROM records WHERE 
						rtype='A' AND 
						issystem='1' AND 
						zoneid=? AND 
						(rvalue=? OR rvalue=?)",
						array($zoneinfo['id'], $event->DBInstance->ExternalIP, $event->DBInstance->InternalIP)
					);
					
					//TODO: Check affected rows.
					
					if (!$this->DNSZoneController->Update($zoneinfo["id"]))
						$this->Logger->warn("[FarmID: {$farminfo['id']}] Cannot remove terminated instance '{$event->DBInstance->InstanceID}' ({$event->DBInstance->ExternalIP}) from DNS zone '{$zoneinfo['zone']}'");
					else
						$this->Logger->info("[FarmID: {$farminfo['id']}] Terminated instance '{$event->DBInstance->InstanceID}' (ExtIP: {$event->DBInstance->ExternalIP}, IntIP: {$event->DBInstance->InternalIP}) removed from DNS zone '{$zoneinfo['zone']}'");
				}
			}
			catch(Exception $e)
			{
				Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($farminfo['id'], "Update DNS zone on 'OnHostDown'' event failed: {$e->getMessage()}"));
			}
		}
		
		/**
		 * Add A records to DNS zone for instance
		 *
		 * @param array $farminfo
		 * @param array $instanceinfo
		 */
		private function AddInstanceToDNS($farminfo, DBInstance $DBInstance)
		{
			// Reload instance info
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
				array($DBInstance->ID)
			);
			
			if ($instanceinfo['isactive'] != 1)
				return;
			
			try
			{
				$zones = $this->DB->GetAll("SELECT * FROM zones WHERE farmid='{$farminfo['id']}' AND status IN (?,?)", array(ZONE_STATUS::ACTIVE, ZONE_STATUS::PENDING));
				if (count($zones) == 0)
					return;
					
				foreach ($zones as $zone)
				{								
					if (!$zone['id'])
						continue;
					
					$records_attrs = array();

					$ami_info = $this->DB->GetRow("SELECT * FROM roles WHERE ami_id=?", array($instanceinfo['ami_id']));
					
					$replace = false;
					
					$role_name = $zone["role_name"]; 
					
					if ($instanceinfo["replace_iid"])
					{
						$old_instance_info = $this->DB->GetRow("SELECT role_name FROM farm_instances 
							WHERE instance_id=?", 
							array($instanceinfo["replace_iid"])
						);
						
						if ($old_instance_info['role_name'] == $zone["role_name"])
							$role_name = $instanceinfo['role_name'];
					}
					
					Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($farminfo['id'], "Instance {$instanceinfo['instance_id']}. Is DB Master = {$instanceinfo['isdbmaster']}"));
					
					try
					{
						$DBFarmRole = DBFarmRole::LoadByID($instanceinfo['farm_roleid']);
						$skip_main_a_records = ($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB) == 1) ? true : false;
					}
					catch(Exception $e)
					{
						$this->Logger->fatal(sprintf("DNSEventObserver(275): %s", $e->getMessage()));
						$skip_main_a_records = false;
					}
					
					$instance_records = DNSZoneControler::GetInstanceDNSRecordsList($instanceinfo, $role_name, $ami_info['alias'], $skip_main_a_records);
										
					Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($farminfo['id'], "Adding system A records to zone {$zone['zone']}"));
					
					// Adding new records to database
					foreach ($instance_records as $record_attrs)
					{									
						$this->DB->Execute("REPLACE INTO records SET zoneid='{$zone['id']}', rtype='A', ttl=?, rvalue=?, rkey=?, issystem='1'",
						array($record_attrs['ttl'], $record_attrs['rvalue'], $record_attrs['rkey']));
					}
					
					// Update DNS zone on Nameservers
					if (!$this->DNSZoneController->Update($zone["id"]))
						$this->Logger->error("Cannot update zone in DNSEventObserver");
					else
						$this->Logger->info("Instance {$instanceinfo['instance_id']} added to DNS zone '{$zone['zone']}'");
				}
			}
			catch(Exception $e)
			{
				$this->Logger->fatal("DNS zone update failed: ".$e->getMessage());
			}
		}
	}
?>