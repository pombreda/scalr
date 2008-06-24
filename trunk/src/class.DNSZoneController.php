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
            $cpwd = $this->Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
            
            $zoneinfo = $this->DB->GetRow("SELECT * FROM zones WHERE id=?", array($zoneid));            
			if (!$zoneinfo)
			{
                $this->Logger->warn("Zone with zoneid {$zoneid} not found.");
			    return false;
			}
			
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
					$Bind->SetBackup(true);
					$status = $Bind->DeleteZone($zoneinfo["zone"]);
					
					if (!$status)
					{
                        foreach ($GLOBALS["warnings"] as $warn)
                            $this->Logger->warn($warn);
                            
                        return false;
					}
				}
			}
			
			return $retval;
        }
                
        function Update($zoneid)
        {
			$cpwd = $this->Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
            
            $zoneinfo = $this->DB->GetRow("SELECT * FROM zones WHERE id=?", array($zoneid));            
			if (!$zoneinfo)
			{
                $this->Logger->warn("Zone with zoneid {$zoneid} not found.");
			    return false;
			}
			
            $GLOBALS["warnings"] = array();
            
			$this->Zone = new DNSZone($zoneinfo["zone"]);
			
            $SOA = new SOADNSRecord($zoneinfo["zone"], CONFIG::$DEF_SOA_PARENT, CONFIG::$DEF_SOA_OWNER, false, $zoneinfo["soa_serial"]);
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
			    $this->Logger->fatal(sprintf(_("Generating DNS zone for '%s'... Failed!"), $zoneinfo["zone"]));
			    foreach ($GLOBALS["warnings"] as $warn)
			        $this->Logger->error($warn);
			        
			    return false;
			}
			
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
					$status = $Bind->SaveZone($zoneinfo["zone"], $zonecontent);
					
					if (!$status)
					{
                        foreach ($GLOBALS["warnings"] as $warn)
                            $this->Logger->error("{$warn}");
                            
                        return false;
					}
					
					unset($Bind);
				}
			}
			
			return $retval;
        }
    }

?>