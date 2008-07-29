<?
	class DNSMaintenanceProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "DNS Maintenance poller";
        public $Logger;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = LoggerManager::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance(null, true);
            
            $this->Logger->info("Fetching completed farms...");
            
            $this->ThreadArgs = $db->GetAll("SELECT farms.*, clients.isactive FROM farms INNER JOIN clients ON clients.id = farms.clientid WHERE farms.status='1' AND clients.isactive='1'");
                        
            $this->Logger->info("Found ".count($this->ThreadArgs)." farms.");
        }
        
        public function OnEndForking()
        {
            
        }
        
        public function StartThread($farminfo)
        {
            $db = Core::GetDBInstance(null, true);
            $SNMP = new SNMP();
            
            //
            // Check farm status
            //
            if ($db->GetOne("SELECT status FROM farms WHERE id=?", array($farminfo["id"])) != 1)
            {
            	$this->Logger->error("[FarmID: {$farminfo['id']}] Farm terminated by client.");
            	return;
            }
            
            $DNSZoneController = new DNSZoneControler();
              
            //
            // Start DNS Zone maintenance
            //
            $this->Logger->info("[FarmID: {$farminfo['id']}] Checking DNS zones");
            try
            {
				// Check zomby records
				$this->Logger->info("[FarmID: {$farminfo['id']}] Checking zomby records");
				
            	$records = $db->GetAll("SELECT * FROM records WHERE rtype='A' AND 
            		issystem='1' AND 
            		zoneid IN (SELECT id FROM zones WHERE farmid = '{$farminfo['id']}')"
            	);
	            $malformed_zones = array();
	            if ($records && count($records) > 0)
	            {
		            foreach ($records as $record)
		            {
		            	$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id='{$record['zoneid']}'");
		            	
		            	if (!$db->GetOne("SELECT id FROM farm_instances 
		            		WHERE farmid='{$farminfo['id']}' AND 
		            		(external_ip = '{$record['rvalue']}' OR 
		            		internal_ip = '{$record['rvalue']}') AND isactive='1'")
		            	) {
		            		$this->Logger->warn("[FarmID: {$farminfo['id']}] Found zomby record: '{$record['rkey']} {$record['ttl']} IN A {$record['rvalue']}'");
		            		$malformed_zones[$record['zoneid']] = 1;
		            		$db->Execute("DELETE FROM records WHERE id='{$record['id']}'");
		            	}
		            	
		            	if ($record["rkey"] == "@")
		            	{
		            		$instance_rolename = $db->GetOne("SELECT role_name FROM farm_instances 
		            			WHERE external_ip=? AND farmid=? AND isactive='1'",
		            		array($record["rvalue"], $zoneinfo["farmid"]));
		            		
		            		if (!$instance_rolename || $instance_rolename != $zoneinfo["role_name"])
		            		{
		            			$malformed_zones[$record['zoneid']] = 1;
		            			$db->Execute("DELETE FROM records WHERE id='{$record['id']}'");
		            		}
		            	}
		            }
	            }
	            
	            $nss = $db->GetAll("SELECT * FROM nameservers WHERE isproxy='0'");
				
	            // Check malformed zones
	            $zones = $db->GetAll("SELECT * FROM zones WHERE farmid='{$farminfo['id']}'");
	            foreach ($zones as $zone)
	            {
	            	// Check for NS records
	            	$this->Logger->info("[FarmID: {$farminfo['id']}] Checking for malformed NS records");
	            	if (count($nss) != $db->GetOne("SELECT COUNT(*) FROM records WHERE rtype='NS' AND zoneid='{$zone['id']}'"))
	            	{
	            		$this->Logger->warn("[FarmID: {$farminfo['id']}] Outdated NS records for zone '{$zone['zone']}'");
	            		
	            		$db->Execute("DELETE FROM records WHERE rtype='NS' AND zoneid='{$zone['id']}'");
	            		foreach ($nss as $ns)
	            			$db->Execute("REPLACE INTO records SET zoneid=?, `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?, `issystem`=?", array(
	            				$zone['id'], "NS", 14400, null, "{$ns["host"]}.", "{$zone['zone']}.", 1
	            			));
	            		$malformed_zones[$zone['id']] = 1;
	            	}
	            	
	            	// Check for A records
	            	$this->Logger->info("[FarmID: {$farminfo['id']}] Checking for malformed A records");
	            	$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid='{$farminfo['id']}' AND state='Running' AND isactive='1'");
	            	foreach ($instances as $instance)
	            	{
	            		if ($instance["role_name"] == $zone["role_name"])
	            		{
	            			// Check A records for external IP
	            			if (!$db->GetOne("SELECT id FROM records WHERE rtype='A' AND rkey='@' AND issystem='1' AND rvalue='{$instance["external_ip"]}' AND zoneid='{$zone['id']}'"))
	            			{
	            				$this->Logger->warn("[FarmID: {$farminfo['id']}] Outdated A records for external IP: {$instance["external_ip"]} ({$zone['zone']})");
	            				
	            				// Missed A record, add it
	            				$db->Execute("REPLACE INTO records SET zoneid=?, `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?, `issystem`=?", array(
		            				$zone['id'], "A", 90, null, "{$instance["external_ip"]}", "@", 1
		            			));
		            			
		            			$malformed_zones[$zone['id']] = 1;
	            			}
	            		}
	            		
	            		// Check A records for internal IP
	            		if (!$db->GetOne("SELECT id FROM records WHERE rtype='A' AND issystem='1' AND rvalue='{$instance["internal_ip"]}' AND zoneid='{$zone['id']}'"))
            			{
            				$this->Logger->warn("[FarmID: {$farminfo['id']}] Outdated A records for internal IP: {$instance["internal_ip"]} ({$zone['zone']})");
            				
            				$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$instance['ami_id']}'");
            				
            				// Missed A record, add it
            				$db->Execute("REPLACE INTO records SET zoneid=?, `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?, `issystem`=?", array(
	            				$zone['id'], "A", 20, null, "{$instance["internal_ip"]}", "int-{$ami_info['name']}", 1
	            			));
	            			
	            			
	            			if ($ami_info['alias'] == 'mysql' && $instance['isdbmaster'] == 1)
	            			{
	            				$db->Execute("REPLACE INTO records SET zoneid=?, `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?, `issystem`=?", array(
		            				$zone['id'], "A", 20, null, "{$instance["internal_ip"]}", "int-{$ami_info['name']}-master", 1
		            			));	
	            			}
	            			
	            			$malformed_zones[$zone['id']] = 1;
            			}
            			
            			// Check A records for external IP (ext-)
	            		if (!$db->GetOne("SELECT id FROM records WHERE rtype='A' AND issystem='1' AND rvalue='{$instance["external_ip"]}' AND zoneid='{$zone['id']}' AND rkey != '@'"))
            			{
            				$this->Logger->warn("[FarmID: {$farminfo['id']}] Outdated A records for external IP: {$instance["external_ip"]} ({$zone['zone']})");
            				
            				$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$instance['ami_id']}'");
            				
            				// Missed A record, add it
            				$db->Execute("REPLACE INTO records SET zoneid=?, `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?, `issystem`=?", array(
	            				$zone['id'], "A", 20, null, "{$instance["external_ip"]}", "ext-{$ami_info['name']}", 1
	            			));
	            			
	            			
	            			if ($ami_info['alias'] == 'mysql' && $instance['isdbmaster'] == 1)
	            			{
	            				$db->Execute("REPLACE INTO records SET zoneid=?, `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?, `issystem`=?", array(
		            				$zone['id'], "A", 20, null, "{$instance["external_ip"]}", "ext-{$ami_info['name']}-master", 1
		            			));	
	            			}
	            			
	            			$malformed_zones[$zone['id']] = 1;
            			}
	            	}
	            }
	            
	            // Set more retries for locked zone for maintenance process
	            CONFIG::$ZONE_LOCK_WAIT_RETRIES = 10;
	            
	            if (count($malformed_zones) > 0)
	            {
	            	foreach(array_keys($malformed_zones) as $malformed_zoneid)
	            	{
	            		$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id='{$malformed_zoneid}'");
	            		$this->Logger->info("[FarmID: {$farminfo['id']}] Fixing malformed zone (ID: {$malformed_zoneid}, Name: {$zoneinfo['zone']})");
	            		$DNSZoneController->Update($malformed_zoneid);
	            	}
	            }
            }
            catch(Exception $e)
            {
            	$this->Logger->fatal("[FarmID: {$farminfo['id']}]".$e->getMessage());
            }
            $this->Logger->info("[FarmID: {$farminfo['id']}] DNS zones check complete");
            
            //
            // End DNS Zone maintenance
            //
        }
    }
?>