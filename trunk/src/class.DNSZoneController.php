<?
    Core::Load("NET/API/BIND");
	Core::Load("NET/DNS/DNSZoneParser");    

	/**
     * @name DNSZoneControler
     * @package    APP
     * @version 1.0
     * @author Igor Savchenko
     */
    class DNSZoneControler
    {
        /**
         * Returns array of all DNS records needed for selected instance 
         */
    	public static function GetInstanceDNSRecordsList($instance, $role_name, $role_alias, $skip_main_a_record = false)
        {
        	// Zone pointed to instance role: add '@ IN A' record 
        	//TODO: For future use:
        	$skip_main_a_record = false;
        	
        	if ($instance["role_name"] == $role_name && !$skip_main_a_record)
    	    {
    			$records[] = array("rtype" => "A", "ttl" => CONFIG::$DYNAMIC_A_REC_TTL, "rvalue" => $instance["external_ip"], "rkey" => "@", "issystem" => 1);
    	    }
    	    
    	    // if instance is db master add 'int-%ROLENAME%-master' and 'ext-%ROLENAME%-master' records
    	    if ($instance["isdbmaster"] == 1)
			{
				$records[] = array("rtype" => "A", "rkey" => "int-{$instance["role_name"]}-master", "rvalue" => $instance["internal_ip"], "ttl" => 20, "issystem" => 1);
				$records[] = array("rtype" => "A", "rkey" => "ext-{$instance["role_name"]}-master", "rvalue" => $instance["external_ip"], "ttl" => 20, "issystem" => 1);
			}
			// else add 'int-%ROLENAME%-slave' and 'ext-%ROLENAME%-slave' records
			elseif ($role_alias == ROLE_ALIAS::MYSQL)
			{
				$records[] = array("rtype" => "A", "rkey" => "int-{$instance["role_name"]}-slave", "rvalue" => $instance["internal_ip"], "ttl" => 20, "issystem" => 1);
				$records[] = array("rtype" => "A", "rkey" => "ext-{$instance["role_name"]}-slave", "rvalue" => $instance["external_ip"], "ttl" => 20, "issystem" => 1);
			}

			// if role name != mysql Add mysql related records with role_name = mysql.
    		if ($role_alias == ROLE_ALIAS::MYSQL && $instance['role_name'] != ROLE_ALIAS::MYSQL)
			{
				$records[] = array("rtype" => "A", "rkey" => "int-mysql", "rvalue" => $instance["internal_ip"], "ttl" => 20, "issystem" => 1);
				$records[] = array("rtype" => "A", "rkey" => "ext-mysql", "rvalue" => $instance["external_ip"], "ttl" => 20, "issystem" => 1);
				
				if ($instance["isdbmaster"] == 1)
				{
					$records[] = array("rtype" => "A", "rkey" => "int-mysql-master", "rvalue" => $instance["internal_ip"], "ttl" => 20, "issystem" => 1);
					$records[] = array("rtype" => "A", "rkey" => "ext-mysql-master", "rvalue" => $instance["external_ip"], "ttl" => 20, "issystem" => 1);
				}
				else
				{
					$records[] = array("rtype" => "A", "rkey" => "int-mysql-slave", "rvalue" => $instance["internal_ip"], "ttl" => 20, "issystem" => 1);
					$records[] = array("rtype" => "A", "rkey" => "ext-mysql-slave", "rvalue" => $instance["external_ip"], "ttl" => 20, "issystem" => 1);
				}
			}
			
			// Add int-%ROLENAME% and ext-%ROLE_NAME% records.
			$records[] = array("rtype" => "A", "rkey" => "int-{$instance["role_name"]}", "rvalue" => $instance["internal_ip"], "ttl" => 20, "issystem" => 1);
			$records[] = array("rtype" => "A", "rkey" => "ext-{$instance["role_name"]}", "rvalue" => $instance["external_ip"], "ttl" => 20, "issystem" => 1);
			
			return $records;
        }
    	
    	function __construct()
        {
            $this->DB = Core::GetDBInstance(null, true);
            $this->Crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
            $this->Logger = LoggerManager::getLogger(__CLASS__);
        }
        
        function Delete($zoneid)
        {
            // Check zone
        	$zoneinfo = $this->DB->GetRow("SELECT * FROM zones WHERE id=?", array($zoneid));            
			if (!$zoneinfo)
			{
                $this->Logger->warn(sprintf(_("Zone with zoneid %s not found."), $zoneid));
			    return false;
			}
			
			// Mark zone as pending delete;
			$this->DB->Execute("UPDATE zones SET status=? WHERE id=?", array(ZONE_STATUS::DELETED, $zoneid));
			
			// Add zone deletion task to queue
			TaskQueue::Attach(QUEUE_NAME::DELETE_DNS_ZONE)->AppendTask(new DeleteDNSZoneTask($zoneid));
			
			return true;
        }
                
        function Update($zoneid)
        {
			$cpwd = $this->Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
            			
            $zoneinfo = $this->DB->GetRow("SELECT * FROM zones WHERE id=?", array($zoneid));            
			if (!$zoneinfo || !$zoneinfo["zone"])
			{
                $this->Logger->warn(sprintf(_("Zone with zoneid %s not found."), $zoneid));
			    return false;
			}
					
			$this->Logger->info(sprintf(_("Updating %s DNS zone"), $zoneinfo['zone']));
			
			// Check zone lock
			$retry = 1;
			while ($zoneinfo["islocked"] != 0)
			{
				$this->Logger->warn(sprintf(_("Zone '%s' is locked. Waiting. Retry: %s"), $zoneinfo['zone'], $retry));
				
				// Check retries count
				if ($retry > CONFIG::$ZONE_LOCK_WAIT_RETRIES)
				{
					$this->DB->Execute("UPDATE zones SET isobsoleted='1' WHERE id=?", array($zoneid));
					$this->Logger->warn(sprintf(_("Zone '%s' lock wait timeout."), $zoneinfo['zone']));
					throw new Exception(sprintf("Zone %s is locked by %s. I wait for %s seconds %s times and then gave up.",
						$zoneinfo['zone'], APPCONTEXT::GetContextName($zoneinfo['lockedby']), round(CONFIG::$ZONE_LOCK_WAIT_TIMEOUT/1000000, 2), CONFIG::$ZONE_LOCK_WAIT_RETRIES)
					);
				}
				
				// Increment retries
				$retry++;
				
				// Sleep
				usleep(CONFIG::$ZONE_LOCK_WAIT_TIMEOUT);
				
				// Get new zone info
				$zoneinfo = $this->DB->GetRow("SELECT * FROM zones WHERE id=?", array($zoneid));
			}
			
			// Add lock for zone
			$this->Logger->debug(sprintf(_("Add lock for '%s' zone."), $zoneinfo['zone']));
			$this->DB->Execute("UPDATE zones SET islocked='1', dtlocked=?, lockedby=? WHERE id=?", array(time(), CONTEXTS::$APPCONTEXT, $zoneid));
			
            $GLOBALS["warnings"] = array();
            
            try
            {
				$this->Zone = new DNSZone($zoneinfo["zone"]);
				
				$serial = AbstractDNSZone::RaiseSerial($zoneinfo["soa_serial"]);
				
				$this->DB->Execute("UPDATE zones SET soa_serial='{$serial}' WHERE id='{$zoneinfo['id']}'");
								
	            $SOA = new SOADNSRecord(
	            	$zoneinfo["zone"], 
	            	CONFIG::$DEF_SOA_PARENT, 
	            	CONFIG::$DEF_SOA_OWNER, 
	            	CONFIG::$DEF_SOA_TTL, 
	            	$serial,
	            	$zoneinfo["soa_refresh"],
	            	$zoneinfo["soa_retry"],
	            	$zoneinfo["soa_expire"],
	            	$zoneinfo["min_ttl"]
	            );
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

	        					case "TXT":
	        							$record = new TXTDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"]);
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

	        					case "SRV":
	        							$record = new SRVDNSRecord($record["rkey"], $record["rvalue"], $record["ttl"], $record["rpriority"], $record["rweight"], $record["rport"]);
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
					
		            foreach((array)$this->DB->GetAll("SELECT * FROM nameservers WHERE isproxy='0'") as $ns)
					{
						if ($ns["host"]!='')
						{
						   $Bind = new RemoteBIND(  $ns["ipaddress"], 
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
				$this->Logger->debug(sprintf(_("Exception thrown during zone update: %s. Unlocking zone %s."), $e->getMessage(), $zoneid));
            	// Remove lock
            	$this->DB->Execute("UPDATE zones SET islocked='0' WHERE id=?", array($zoneid));
            	// Throw exception again
            	throw $e;
            }
			
			// Remove lock
			$this->Logger->debug(sprintf(_("Unlocking zone %s"), $zoneid));
			$this->DB->Execute("UPDATE zones SET islocked='0' WHERE id=?", array($zoneid));
			
			return $retval;
        }
    }

?>