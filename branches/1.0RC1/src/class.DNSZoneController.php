<?
    Core::Load("NET/API/BIND");
	Core::Load("NET/DNS/DNSZoneParser");    

    class DNSZoneControler
    {
        function __construct()
        {
            $this->DB = Core::GetDBInstance(null, true);
            $this->Crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
            $this->Logger = LoggerManager::getLogger(__CLASS__);
        }
        
        function Delete($zoneid)
        {
            //$cpwd = $this->Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
            
            $zoneinfo = $this->DB->GetRow("SELECT * FROM zones WHERE id=?", array($zoneid));            
			if (!$zoneinfo)
			{
                $this->Logger->warn("Zone with zoneid {$zoneid} not found.");
			    return false;
			}
			
			// Mark zone as pending delete;
			$this->DB->Execute("UPDATE zones SET status=? WHERE id=?", array(ZONE_STATUS::DELETED, $zoneid));
			
			return true;
        }
                
        function Update($zoneid)
        {
			$cpwd = $this->Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
            			
            $zoneinfo = $this->DB->GetRow("SELECT * FROM zones WHERE id=?", array($zoneid));            
			if (!$zoneinfo || !$zoneinfo["zone"])
			{
                $this->Logger->warn("Zone with zoneid {$zoneid} not found.");
			    return false;
			}
					
			$this->Logger->debug("Updating '{$zoneinfo['zone']}' DNS zone");
			
			// Check zone lock
			$retry = 1;
			while ($zoneinfo["islocked"] != 0)
			{
				$this->Logger->warn("Zone '{$zoneinfo['zone']}' is locked. Waiting.. Retry: {$retry}");
				
				// Check retries count
				if ($retry > CONFIG::$ZONE_LOCK_WAIT_RETRIES)
				{
					$this->Logger->warn("Zone '{$zoneinfo['zone']}' lock wait timeout.");
					throw new Exception(sprintf("Zone %s is locked by %s. I wait for %s seconds %s times and then gave up.",
						$zoneinfo['zone'], APPCONTEXT::GetContextName($zoneinfo['lockedby']), round(CONFIG::$ZONE_LOCK_WAIT_TIMEOUT/1000000, 2), CONFIG::$ZONE_LOCK_WAIT_RETRIES));
				}
				
				// Increment retries
				$retry++;
				
				// Sleep
				usleep(CONFIG::$ZONE_LOCK_WAIT_TIMEOUT);
				
				// Get new zone info
				$zoneinfo = $this->DB->GetRow("SELECT * FROM zones WHERE id=?", array($zoneid));
			}
			
			// Add lock for zone
			$this->Logger->debug("Add lock for '{$zoneinfo['zone']}' zone.");
			$this->DB->Execute("UPDATE zones SET islocked='1', dtlocked=?, lockedby=? WHERE id=?", array(time(), CONTEXTS::$APPCONTEXT, $zoneid));
			
            $GLOBALS["warnings"] = array();
            
            try
            {
				$this->Zone = new DNSZone($zoneinfo["zone"]);
				
				$serial = AbstractDNSZone::RaiseSerial($zoneinfo["soa_serial"]);
				
				$this->DB->Execute("UPDATE zones SET soa_serial='{$serial}' WHERE id='{$zoneinfo['id']}'");
				
	            $SOA = new SOADNSRecord($zoneinfo["zone"], CONFIG::$DEF_SOA_PARENT, CONFIG::$DEF_SOA_OWNER, false, $serial);
				if (!$SOA->__toString())				        
				    $error = true;
				else 
				{
	                $this->Zone->AddRecord($SOA);
	
	                $records = $this->DB->GetAll("SELECT * FROM records WHERE zoneid={$zoneid}");
	                
					foreach ($records as $k=>$record)
					{
						if ($record["rkey"] != '' && $record["rvalue"] != '')
						{   					
	    					switch($record["rtype"])
	        				{
	        					case "A":
	        							$record = new ADNSRecord($record["rkey"], $record["rvalue"], $record["ttl"]);
	        							$this->Zone->AddRecord($record);
	        						break;
	        						
	        					case "NS":
	        							$record = new NSDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"]);        							
	        							$this->Zone->AddRecord($record);
	        						break;
	        						
	        					case "CNAME":
	        							$record = new CNAMEDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"]);
	        							$this->Zone->AddRecord($record);
	        						break;
	        						
	        					case "MX":
	        							$record = new MXDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"], $record["rpriority"]);
	        							$this->Zone->AddRecord($record);
	        						break;
	        				}
						}
					}
				}
	            
	            $zonecontent = $this->Zone->__toString();
	            
			    if (Core::HasWarnings())
			    {
				    $this->Logger->fatal(sprintf(_("'%s' DNS zone generation failed."), $zoneinfo["zone"]));
				    foreach ($GLOBALS["warnings"] as $warn)
				        $this->Logger->error($warn);
				        
				    $retval = false;
				}
				else
				{
					$retval = true;
					
		            foreach((array)$this->DB->GetAll("SELECT * FROM nameservers") as $ns)
					{
						if ($ns["host"]!='')
						{
						   $Bind = new RemoteBIND($ns["host"], 
													$ns["port"],
													array("type" => "password", "login" => $ns["username"], "password" => $this->Crypto->Decrypt($ns["password"], $cpwd)),
													$ns["rndc_path"],
													$ns["namedconf_path"],
													$ns["named_path"], 
													CONFIG::$NAMEDCONFTPL
												  );
						    
							$dosave = false;
							
							// Save zone File
							$status = $Bind->SaveZoneFile($zoneinfo["zone"], $zonecontent);
							
							// Reload rndc
							$Bind->ReloadRndc();
							
							if (!$status)
							{
								foreach ($GLOBALS["warnings"] as $warn)
		                            $this->Logger->error("{$warn}");
		                            
		                        $retval = false;
		                        break;
							}
							
							unset($Bind);
						}
					}
				}
            }
            catch(Exception $e)
            {
				$this->Logger->debug("Exception thrown during zone update: {$e->getMessage()}. Unlocking zone {$zoneid}.");
            	// Remove lock
            	$this->DB->Execute("UPDATE zones SET islocked='0' WHERE id=?", array($zoneid));
            	// Throw exception again
            	throw $e;
            }
			
			// Remove lock
			$this->Logger->debug("Unlocking zone {$zoneid}");
			$this->DB->Execute("UPDATE zones SET islocked='0' WHERE id=?", array($zoneid));
			
			return $retval;
        }
    }

?>