<?php
	
	class ScalrAPI20090804 extends ScalrAPI20090707
	{
		public function ListDNSZones()
		{
			return parent::ListApplications();
		}
		
		public function AddDNSZoneRecord($DomainName, $Type, $TTL, $Key, $Value, $Priority = 0, $Weight = 0, $Port = 0)
		{
			$zoneinfo = $this->DB->GetRow("SELECT * FROM dns_zones WHERE zone_name=?", array($DomainName));
			if (!$zoneinfo || $zoneinfo['clientid'] != $this->Client->ID)
				throw new Exception (sprintf("Zone '%s' not found in database", $DomainName));
				
			if (!in_array($Type, array("A", "MX", "CNAME", "NS", "TXT", "SRV")))
				throw new Exception (sprintf("Unknown record type '%s'", $Type));
						
			////////
			$reflection = new ReflectionClass("{$v['rtype']}DNSRecord");
			if ($v['rtype'] == 'MX')
				$c = $reflection->newInstance($v["rkey"], $v["rvalue"], $v["ttl"], $v["rpriority"]);
			elseif($v['rtype'] == 'SRV')
				$c = $reflection->newInstance($v["rkey"], $v["rvalue"], $v["ttl"], $v["rpriority"], $v["rweight"], $v["rport"]);
			else
				$c = $reflection->newInstance($v["rkey"], $v["rvalue"], $v["ttl"]);
			
			if ($c->__toString() == "")
				throw new Exception(array_shift($GLOBALS['warnings']));
			else
				$this->DB->Execute("INSERT INTO dns_zone_records SET 
					`zone_id`=?, 
					`type`=?, 
					`ttl`=?, 
					`priority`=?, 
					`value`=?, 
					`name`=?, 
					`weight`=?, 
					`port`=?", 
				array(
					$zoneinfo["id"], 
					$Type, 
					$TTL, 
					(int)$Priority, 
					$Value, 
					$Key, 
					(int)$Weight, 
					(int)$Port
				));
			
			$response = $this->CreateInitialResponse();
			$response->Result = 1;
			
			return $response;
		}
		
		public function RemoveDNSZoneRecord($DomainName, $RecordID)
		{
			$zoneinfo = $this->DB->GetRow("SELECT * FROM dns_zones WHERE zone_name=?", array($DomainName));
			if (!$zoneinfo || $zoneinfo['client_id'] != $this->Client->ID)
				throw new Exception (sprintf("Zone '%s' not found in database", $DomainName));
				
			$record_info = $this->DB->GetRow("SELECT * FROM dns_zone_records WHERE zone_id=? AND id=?", array($zoneinfo['id'], $RecordID)); 
			if (!$record_info)
				throw new Exception (sprintf("Record ID '%s' for zone '%s' not found in database", $RecordID, $DomainName));
			
			if ($record_info['issystem'] == 1)
				throw new Exception (sprintf("Record ID '%s' is system record and cannot be removed"));
				
			$response = $this->CreateInitialResponse();
				
			$this->DB->Execute("DELETE FROM dns_zone_records WHERE id=?", array($RecordID));
			
			$response->Result = 1;
			
			return $response;
		}
		
		public function ListDNSZoneRecords($DomainName)
		{
			$zoneinfo = $this->DB->GetRow("SELECT * FROM dns_zones WHERE zone_name=?", array($DomainName));
			if (!$zoneinfo || $zoneinfo['client_id'] != $this->Client->ID)
				throw new Exception (sprintf("Zone '%s' not found in database", $DomainName));
			
			$response = $this->CreateInitialResponse();
				
			$response->ZoneRecordSet = new stdClass();
			$response->ZoneRecordSet->Item = array();
			
			$records = $this->DB->GetAll("SELECT * FROM dns_zone_records WHERE zone_id=?", array($zoneinfo['id']));
			foreach ($records as $record)
			{
				$itm = new stdClass();
				$itm->{"ID"} = $record['id'];
				$itm->{"Type"} = $record['type'];
				$itm->{"TTL"} = $record['ttl'];
				$itm->{"Priority"} = $record['priority'];
				$itm->{"Key"} = $record['name'];
				$itm->{"Value"} = $record['value'];
				$itm->{"Weight"} = $record['weight'];
				$itm->{"Port"} = $record['port'];
				$itm->{"IsSystem"} = $record['issystem'];
				
				$response->ZoneRecordSet->Item[] = $itm;
			}
			
			return $response;
		}
		
		
	}
?>