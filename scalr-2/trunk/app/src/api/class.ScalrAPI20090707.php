<?php
	
	class ScalrAPI20090707 extends ScalrAPI20090507
	{
		public function Hello()
		{
			$response = $this->CreateInitialResponse();	
			$response->Result = 1;
			return $response;
		}
		
		public function LaunchInstance($FarmRoleID)
		{
			try
			{
				$DBFarmRole = DBFarmRole::LoadByID($FarmRoleID);
				$DBFarm = DBFarm::LoadByID($DBFarmRole->FarmID);
			}
			catch(Exception $e)
			{
				throw new Exception(sprintf("Farm Role ID #%s not found", $FarmRoleID));
			}
			
			$n = $DBFarmRole->GetPendingInstancesCount(); 
			if ($n > 0)
				throw new Exception("There are {$n} pending instances. You cannot launch new instances while you have pending ones.");
			
			if ($DBFarm->ClientID != $this->Client->ID)
				throw new Exception(sprintf("Farm Role ID #%s not found", $FarmRoleID));
			
			$response = $this->CreateInitialResponse();
				
			$max_instances = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES);
			$min_instances = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES);
			
        	if ($max_instances < $min_instances+1)
        		$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES, $max_instances+1);
	
        	$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES, $min_instances+1);
        	
        	//TODO:
        	
			$ServerCreateInfo = new ServerCreateInfo($DBFarmRole->Platform, $DBFarmRole);
			try {
				$DBServer = Scalr::LaunchServer($ServerCreateInfo);
											
				Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($DBFarm->ID, sprintf("Starting new instance (API). ServerID = %s.", $DBServer->serverId)));
			}
			catch(Exception $e){
				Logger::getLogger(LOG_CATEGORY::API)->error($e->getMessage());
			}
            	
            $response->ServerID = $DBServer->serverId;
			return $response;
		}
		
		public function TerminateInstance($FarmID, $InstanceID, $DecreaseMinInstancesSetting = false)
		{
			$farminfo = $this->DB->GetRow("SELECT clientid, region FROM farms WHERE id=? AND clientid=?",
				array($FarmID, $this->Client->ID)
			);
			
			if (!$farminfo)
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
			
			$response = $this->CreateInitialResponse();
				
            $Client = Client::Load($farminfo['clientid']);
            $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region']));
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		
    		$AmazonEC2Client->TerminateInstances(array($InstanceID));
    		
    		if ($DecreaseMinInstancesSetting)
    		{
    			$DBServer = DBServer::LoadByPropertyValue(EC2_SERVER_PROPERTIES::INSTANCE_ID, $InstanceID);
    			
    			$DBFarmRole = $DBServer->GetFarmRoleObject();
    			
    			if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES) > 1)
    			{
	    			$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES, 
	    				$DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES)-1
	    			);
    			}
    		}
    		
    		$response->Result = true;
			return $response;
		}
		
		public function GetFarmDetails($FarmID)
		{
			$response = $this->CreateInitialResponse();
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?",
				array($FarmID, $this->Client->ID)
			);
			
			if (!$farminfo)
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
				
			$response->FarmRoleSet = new stdClass();
			$response->FarmRoleSet->Item = array();
				
			$rows = $this->DB->Execute("SELECT id FROM farm_roles WHERE farmid=?", array($FarmID));
			while ($row = $rows->FetchRow())
			{
				$DBFarmRole = DBFarmRole::LoadByID($row['id']);
				
				$itm = new stdClass();
				$itm->{"ID"} = $DBFarmRole->ID;
				$itm->{"Name"} = $DBFarmRole->GetRoleName();
				$itm->{"Category"} = ROLE_ALIAS::GetTypeByAlias($DBFarmRole->GetRoleAlias());
				$itm->{"AvailabilityZone"} = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_AVAIL_ZONE);
				$itm->{"InstanceType"} = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_INSTANCE_TYPE);
				$itm->{"MinInstances"} = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES);
				$itm->{"MaxInstances"} = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES);
				
				$servers = $this->DB->GetAll("SELECT server_id FROM servers WHERE farm_roleid=?", array($DBFarmRole->ID));
				$itm->{"InstanceSet"} = new stdClass();
				$itm->{"InstanceSet"}->Item = array();
				foreach ($servers as $server)
				{
					$DBServer = DBServer::LoadByID($server['server_id']);
					$iitm = new stdClass();
					$iitm->{"InstanceID"} = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID);
					$iitm->{"ExternalIP"} = $DBServer->remoteIp;
					$iitm->{"InternalIP"} = $DBServer->localIp;
					$iitm->{"State"} = $DBServer->Status;
					$iitm->{"AvailabilityZone"} = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE);
					$iitm->{"Uptime"} = round((time()-strtotime($DBServer->dateAdded))/60, 2); //seconds -> minutes
					
					$itm->{"InstanceSet"}->Item[] = $iitm;					
				}		 
				
				$response->FarmRoleSet->Item[] = $itm; 
			}
			
			return $response;
		}
	}
?>