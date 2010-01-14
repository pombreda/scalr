<?php

	class Scalr_Cronjob_DNSZoneListUpdate extends Scalr_System_Cronjob
    {
        static function getConfig () {
        	return array(
        		"description" => "Remove locks and update named.conf"
        	);
        }
        
        private $logger;
        
        private $db;
        
        private $crypto;
        
        private $nameservers;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->logger = LoggerManager::getLogger(__CLASS__);
        	$this->crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
        	$this->db = Core::GetDBInstance();
        }
        
        function run ($options)
        {
            $Shell = ShellFactory::GetShellInstance();
            
            // Remove old locks
            $timeout = CONFIG::$ZONE_LOCK_WAIT_RETRIES*(CONFIG::$ZONE_LOCK_WAIT_TIMEOUT/1000000)+10;
            $this->db->Execute("UPDATE zones SET islocked='0' WHERE dtlocked < ? AND islocked='1'", array(time()-$timeout));
                                    
			// Update allowed hosts for zones
			try
			{
				$uzones = $this->db->GetAll("SELECT * FROM zones WHERE hosts_list_updated='0' AND status=?", array(ZONE_STATUS::ACTIVE));
				foreach ($uzones as $uzone)
				{
					foreach ($this->getNameservers() as $host=>$nameserver)
	            	{
	            		$this->logger->info("Updating list of allowed hosts for '{$uzone["zone"]}' on '{$host}'");
	            		
	            		$allowed_hosts = ($uzone['axfr_allowed_hosts']) ? $uzone['axfr_allowed_hosts'] : "none"; 
	            		
	            		$nameserver->UpdateZoneDirectives($uzone['zone'], $allowed_hosts);
	            		$reload_bind = true;
	            	}
	            	
	            	$this->db->Execute("UPDATE zones SET hosts_list_updated='1' WHERE id=?", array($uzone['id']));
				}
			}
			catch(Exception $e)
			{
				$this->logger->fatal($e->getMessage());
			}
			
			while ($Task = TaskQueue::Attach(QUEUE_NAME::CREATE_DNS_ZONE)->Poll())
	        {
	        	try
	        	{
		        	$zone = $this->db->GetRow("SELECT * FROM zones WHERE id=?", array($Task->ZoneID));
		        	if ($zone["status"] != ZONE_STATUS::PENDING)
		        		continue;
		        		
					$zone_add_failed = false;
					
		        	foreach ($this->getNameservers() as $host=>$nameserver)
	            	{
	            		$this->logger->info("Adding zone '{$zone["zone"]}' to '{$host}'");
	            			
	            		$add_status = $nameserver->AddZone($zone["zone"]);
						
						if (!$add_status)
						{
							$this->logger->fatal("Cannot add zone to named.conf on '{$host}'");
							foreach ($GLOBALS["warnings"] as $warn)
	                            $this->logger->error("{$warn}");
	                            
	                        $zone_add_failed = true;
	            			break;
						}
	            	}
	            	
		        	// If zone successfully added to nameservers - update db
	            	if (!$zone_add_failed)
	            	{
	            		$this->logger->info("Zone '{$zone["zone"]}' successfully added to nameservers");
	            		
	            		$farmstatus = $this->db->GetOne("SELECT status FROM farms WHERE id='{$zone['farmid']}'");
	            		$zonestatus = ($farmstatus == 1) ? ZONE_STATUS::ACTIVE : ZONE_STATUS::INACTIVE;
	            		
	            		$reload_bind = true;
	            		
	            		$this->db->Execute("UPDATE zones SET status=? WHERE id=?", array($zonestatus, $zone['id']));
	            	}
	            	else
	            		TaskQueue::Attach(QUEUE_NAME::CREATE_DNS_ZONE)->AppendTask($Task);
	        	}
	        	catch(Exception $e)
	        	{
	        		$this->logger->error(sprintf(_("Cannot create DNZ zone %s: %s"), $zone['name'], $e->getMessage()));
	        		TaskQueue::Attach(QUEUE_NAME::CREATE_DNS_ZONE)->AppendTask($Task);
	        	}
	        }
			
	        while ($Task = TaskQueue::Attach(QUEUE_NAME::DELETE_DNS_ZONE)->Poll())
	        {	        	
	        	$zone = $this->db->GetRow("SELECT * FROM zones WHERE id=?", array($Task->ZoneID));
	        	if ($zone["status"] != ZONE_STATUS::DELETED)
	        		continue;
	        		
	        	$zone_remove_failed = false;
	        	
	        	foreach ($this->getNameservers() as $host=>$nameserver)
            	{
            		$remove_status = $nameserver->DeleteZone($zone["zone"], false);
					if(!$remove_status)
					{
						$this->logger->fatal("Cannot remove zone from named.conf on '{$host}'");
						
						$zone_remove_failed = true;
            			break;
					}         		
            	} // foreach nameservers
            	
	        	// If zone successfully deleted from nameservers - update db
            	if (!$zone_remove_failed)
            	{
            		$this->logger->info("DNS zone '{$zone["zone"]}' deleted from database!");
            		
            		$reload_bind = true;
            		
           			$this->db->Execute("DELETE from zones WHERE id=?", array($zone['id']));
   					$this->db->Execute("DELETE from records WHERE zoneid=?", array($zone['id']));
   					$this->db->Execute("DELETE from vhosts WHERE name=?", array($zone['zone']));
   					
            		try
    				{
						$DBFarm = DBFarm::LoadByID($zone['farmid']);
						$instances = $DBFarm->GetInstancesByFilter();						
						foreach ((array)$instances as $DBInstance)
						{
							if (!in_array($DBInstance->State, array(INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING)))
								continue;
							
							$alias = $DBInstance->GetDBFarmRoleObject()->GetRoleAlias();
							if ($alias != ROLE_ALIAS::APP && $alias != ROLE_ALIAS::WWW)
								continue;
							
							$DBInstance->SendMessage(new VhostReconfigureScalrMessage());
						}
    				}
    				catch(Exception $e)
    				{
    					$this->logger->fatal("Cannot remove virtualhost: {$e->getMessage()}");
    				}
   					
            	}
            	else
            		TaskQueue::Attach(QUEUE_NAME::DELETE_DNS_ZONE)->AppendTask($Task);
	        }

	        if ($reload_bind)
	        {
	            // run rndc reload
	            foreach ($this->getNameservers() as $host=>$nameserver)
	            {
	            	$this->logger->info("Reloading bind on '{$host}'!");
	            	$res = $nameserver->ReloadRndc();
	            	$this->logger->info("RNDC reload result: {$res}");
	            }
	        }
        }
        
        private function getNameservers()
        {
        	$cpwd = $this->crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../../etc/.passwd"));
        	
        	if (!$this->nameservers)
        	{
	        	$this->nameservers = array();
	        	foreach((array)$this->db->GetAll("SELECT * FROM nameservers WHERE isproxy='0'") as $ns)
				{
					if ($ns["host"]!='')
					{
					    $this->nameservers[$ns["host"]] = new RemoteBIND($ns["ipaddress"], 
							$ns["port"],
							array("type" => "password", "login" => $ns["username"], "password" => $this->crypto->Decrypt($ns["password"], $cpwd)),
							$ns["rndc_path"],
							$ns["namedconf_path"],
							$ns["named_path"], 
							CONFIG::$NAMEDCONFTPL
						  );
					}
				}
        	}
			
			return $this->nameservers;
        }
    }
