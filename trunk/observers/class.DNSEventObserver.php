<?php
	class DNSEventObserver extends EventObserver
	{
		public $ObserverName = 'DNS';
		
		function __construct()
		{
			parent::__construct();
			
			$this->DNSZoneController = new DNSZoneControler();
		}

		public function OnNewMysqlMasterUp($instanceinfo, $snapurl)
		{
			// Reload instance info
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
				array($instanceinfo['id'])
			);
			
			if ($instanceinfo['isactive'] != 1)
				return;
			
			try
			{
				$zones = $this->DB->GetAll("SELECT * FROM zones WHERE farmid='{$farminfo['id']}' AND status IN (?,?)", array(ZONE_STATUS::ACTIVE, ZONE_STATUS::PENDING));
				if (count($zones) == 0)
					return;
					
				$ami_info = $this->DB->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($instanceinfo['ami_id']));
					
				foreach ($zones as $zone)
				{								
					if (!$zone['id'])
						continue;
					
					$records_attrs = array();
					
					// If instance is mysql master we must add: 
					// 'int-ROLE_NAME-master IN A INTERNAL_IP'
					// 'ext-ROLE_NAME-master IN A PUBLIC_IP'
					// records
					if ($instanceinfo["isdbmaster"] == 1)
					{
						$records_attrs[] = array("int-{$instanceinfo['role_name']}-master", $instanceinfo["internal_ip"], 20);
						$records_attrs[] = array("ext-{$instanceinfo['role_name']}-master", $instanceinfo['external_ip'], 20);
						
						if ($instanceinfo["role_name"] != ROLE_ALIAS::MYSQL)
						{
							$records_attrs[] = array("int-mysql-master", $instanceinfo["internal_ip"], 20);
							$records_attrs[] = array("ext-mysql-master", $instanceinfo['external_ip'], 20);
						}
					}
										
					// Adding new records to database
					foreach ($records_attrs as $record_attrs)
					{									
						$this->DB->Execute("REPLACE INTO records SET zoneid='{$zone['id']}', rtype='A', ttl=?, rvalue=?, rkey=?, issystem='1'",
						array($record_attrs[2], $record_attrs[1], $record_attrs[0]));
					}
					
					// Update DNS zone on Nameservers
					if (!$this->DNSZoneController->Update($zone["id"]))
						$this->Logger->error("Cannot update zone in DNSEventObserver");
					else
						$this->Logger->debug("Instance {$instanceinfo['instance_id']} added to DNS zone '{$zone['zone']}'");
				}
			}
			catch(Exception $e)
			{
				$this->Logger->fatal("DNS zone update failed: ".$e->getMessage());
			}
		}
		
		/**
		 * Host crashed
		 *
		 * @param array $instanceinfo
		 */
		public function OnHostCrash($instanceinfo)
		{
			$this->OnHostDown($instanceinfo);
		}
	
		/**
		 * Public IP address for instance changed
		 *
		 * @param array $instanceinfo
		 * @param string $new_ip_address
		 */
		public function OnIPAddressChanged($instanceinfo, $new_ip_address)
		{
			// Get farm info
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			if ($farminfo['status'] != 1)
				return;
				
			// Update zones
			$this->AddInstanceToDNS($farminfo, $instanceinfo);
		}
		
		/**
		 * Farm launched
		 *
		 * @param bool $mark_instances_as_active
		 */
		public function OnFarmLaunched($mark_instances_as_active)
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
		public function OnFarmTerminated($remove_zone_from_DNS, $keep_elastic_ips, $term_on_sync_fail)
		{
			if (!$remove_zone_from_DNS)
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
	            
	            // Delete zone from nameservers
	            $this->DNSZoneController->Delete($zone["id"]);
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
		public function OnHostUp($instanceinfo)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
						
			$this->AddInstanceToDNS($farminfo, $instanceinfo);
		}
		
		/**
		 * Instance terminated
		 *
		 * @param array $instanceinfo
		 */
		public function OnHostDown($instanceinfo)
		{
			//
			// Remove terminated instance from DNS records
			//
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			if ($farminfo['status'] != 1)
				return;
				
			try
			{
				$zones = $this->DB->GetAll("SELECT DISTINCT(zoneid) FROM records WHERE rvalue='{$instanceinfo['external_ip']}' OR rvalue='{$instanceinfo['internal_ip']}' GROUP BY zoneid");
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
						array($zoneinfo['id'], $instanceinfo['external_ip'], $instanceinfo['internal_ip'])
					);
					if (!$this->DNSZoneController->Update($zoneinfo["id"]))
						$this->Logger->warn("[FarmID: {$farminfo['id']}] Cannot remove terminated instance '{$instanceinfo['instance_id']}' ({$instanceinfo['external_ip']}) from DNS zone '{$zoneinfo['zone']}'");
					else
						$this->Logger->debug("[FarmID: {$farminfo['id']}] Terminated instance '{$instanceinfo['instance_id']}' (ExtIP: {$instanceinfo['external_ip']}, IntIP: {$instanceinfo['internal_ip']}) removed from DNS zone '{$zoneinfo['zone']}'");
				}
			}
			catch(Exception $e)
			{
				$this->Logger->warn(new FarmLogMessage($farminfo['id'], "Update DNS zone on 'OnHostDown'' event failed: {$e->getMessage()}"));
			}
		}
		
		/**
		 * Add A records to DNS zone for instance
		 *
		 * @param array $farminfo
		 * @param array $instanceinfo
		 */
		private function AddInstanceToDNS($farminfo, $instanceinfo)
		{
			// Reload instance info
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
				array($instanceinfo['id'])
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

					$ami_info = $this->DB->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($instanceinfo['ami_id']));
					
					$replace = false;
					if ($instanceinfo["replace_iid"])
					{
						$old_instance_info = $this->DB->GetRow("SELECT role_name FROM farm_instances 
							WHERE instance_id=?", 
							array($instanceinfo["replace_iid"])
						);
						
						if ($old_instance_info['role_name'] == $zone["role_name"])
							$replace = true;
					}
					
					// Add main '@ IN A PUBLIC_IP' record
					if ($zone["role_name"] == $instanceinfo['role_name'] || $replace)
					{
						$records_attrs[] = array("@", $instanceinfo['external_ip'], CONFIG::$DYNAMIC_A_REC_TTL);
						$this->Logger->info(new FarmLogMessage($farminfo['id'], "Adding '@ IN A {$instanceinfo['external_ip']}' to zone {$zone['zone']} pointed to role '{$zone["role_name"]}'"));
					}
					
					// If instance is mysql master we must add: 
					// 'int-ROLE_NAME-master IN A INTERNAL_IP'
					// 'ext-ROLE_NAME-master IN A PUBLIC_IP'
					// records
					if ($instanceinfo["isdbmaster"] == 1)
					{
						$records_attrs[] = array("int-{$instanceinfo['role_name']}-master", $instanceinfo["internal_ip"], 20);
						$records_attrs[] = array("ext-{$instanceinfo['role_name']}-master", $instanceinfo['external_ip'], 20);
					}
						
					// Add: 
					// 'int-ROLE_NAME IN A INTERNAL_IP'
					// 'ext-ROLE_NAME IN A PUBLIC_IP'
					// records
					$records_attrs[] = array("int-{$instanceinfo['role_name']}", $instanceinfo["internal_ip"], 20);
					$records_attrs[] = array("ext-{$instanceinfo['role_name']}", $instanceinfo['external_ip'], 20);

					if ($ami_info && $ami_info['alias'] == ROLE_ALIAS::MYSQL && $instanceinfo['role_name'] != ROLE_ALIAS::MYSQL)
					{
						$records_attrs[] = array("int-mysql", $instanceinfo["internal_ip"], 20);
						$records_attrs[] = array("ext-mysql", $instanceinfo['external_ip'], 20);
						if ($instanceinfo["isdbmaster"] == 1)
						{
							$records_attrs[] = array("int-mysql-master", $instanceinfo["internal_ip"], 20);
							$records_attrs[] = array("ext-mysql-master", $instanceinfo['external_ip'], 20);
						}
					}
					
					$this->Logger->info(new FarmLogMessage($farminfo['id'], "Adding ext-* and int-* to zone {$zone['zone']}"));
					
					// Adding new records to database
					foreach ($records_attrs as $record_attrs)
					{									
						$this->DB->Execute("REPLACE INTO records SET zoneid='{$zone['id']}', rtype='A', ttl=?, rvalue=?, rkey=?, issystem='1'",
						array($record_attrs[2], $record_attrs[1], $record_attrs[0]));
					}
					
					// Update DNS zone on Nameservers
					if (!$this->DNSZoneController->Update($zone["id"]))
						$this->Logger->error("Cannot update zone in DNSEventObserver");
					else
						$this->Logger->debug("Instance {$instanceinfo['instance_id']} added to DNS zone '{$zone['zone']}'");
				}
			}
			catch(Exception $e)
			{
				$this->Logger->fatal("DNS zone update failed: ".$e->getMessage());
			}
		}
	}
?>