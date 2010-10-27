<?php
	
	class ScalrAPI20091006 extends ScalrAPI20090814
	{
		public function RemoveDNSZoneRecord($DomainName, $RecordID)
		{
			$zoneinfo = $this->DB->GetRow("SELECT * FROM zones WHERE zone=?", array($DomainName));
			if (!$zoneinfo || $zoneinfo['clientid'] != $this->Client->ID)
				throw new Exception (sprintf("Zone '%s' not found in database", $DomainName));
			
			$record_info = $this->DB->GetRow("SELECT * FROM records WHERE zoneid=? AND id=?", array($zoneinfo['id'], $RecordID)); 
			if (!$record_info)
				throw new Exception (sprintf("Record ID '%s' for zone '%s' not found in database", $RecordID, $DomainName));
			
			
			if ($record_info['issystem'] == 1 && $zoneinfo['allow_manage_system_records'] != 1)
				throw new Exception (sprintf("Record ID '%s' is system record and cannot be removed"));
			
			$response = $this->CreateInitialResponse();
			
			$this->DB->Execute("DELETE FROM records WHERE id=?", array($RecordID));
			
			$response->Result = 1;
			
			return $response;
		}
		
		/**
		 * Added LA for instances
		 * 
		 * @see app/src/api/ScalrAPI20090707#GetFarmDetails($FarmID)
		 */
		public function GetFarmDetails($FarmID)
		{
			$response = parent::GetFarmDetails($FarmID);
			
			Core::Load("NET/SNMP");
			$SNMP = new SNMP();
			$DBFarm = DBFarm::LoadByID($FarmID);
				
			foreach ($response->FarmRoleSet->Item as &$itm)
			{
				foreach ($itm->InstanceSet->Item as &$item)
				{
					$SNMP->Connect($item->ExternalIP, null, $DBFarm->Hash, null, null, true);
		            $res = $SNMP->Get(".1.3.6.1.4.1.2021.10.1.3.3");
		            if (!$res)
		                $item->LA = _("Unknown");
		            else 
		                $item->LA = number_format((float)$res, 2);
				}
			}
			
			return $response;
		}
	}
?>