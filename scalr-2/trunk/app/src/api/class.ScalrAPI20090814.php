<?php
	
	class ScalrAPI20090814 extends ScalrAPI20090804
	{
		
		/**
		 * Returns events
		 * @param $FarmID
		 * @param $StartFrom
		 * @param $RecordsLimit
		 * @return Object 
		 * 
		 */
		public function GetEvents($FarmID, $StartFrom = 0, $RecordsLimit = 20)
		{
			$farminfo = $this->DB->GetRow("SELECT clientid, region FROM farms WHERE id=? AND clientid=?",
				array($FarmID, $this->Client->ID)
			);
			
			if (!$farminfo)
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
				
			$sql = "SELECT * FROM events WHERE farmid='{$FarmID}'";
				
			$total = $this->DB->GetOne(preg_replace('/\*/', 'COUNT(*)', $sql, 1));
		
			$sql .= " ORDER BY id DESC";
			
			$start = $StartFrom ? (int) $StartFrom : 0;
			$limit = $RecordsLimit ? (int) $RecordsLimit : 20;
			$sql .= " LIMIT {$start}, {$limit}";
			
			$response = $this->CreateInitialResponse();
			$response->TotalRecords = $total;
			$response->StartFrom = $start;
			$response->RecordsLimit = $limit;
			$response->EventSet = new stdClass();
			$response->EventSet->Item = array();
			
			$rows = $this->DB->Execute($sql);
			while ($row = $rows->FetchRow())
			{
				$itm = new stdClass();
				$itm->ID = $row['event_id'];
				$itm->Type = $row['type'];
				$itm->Timestamp = strtotime($row['dtadded']);
				$itm->Message = $row['short_message'];
				
				$response->EventSet->Item[] = $itm;
			}
			
			return $response;
		}
		
		/**
		 * Returns logs
		 * @param $FarmID
		 * @param $InsatnceID
		 * @param $StartFrom
		 * @param $RecordsLimit
		 * @return Object 
		 * 
		 */
		public function GetLogs($FarmID, $InstanceID = null, $StartFrom = 0, $RecordsLimit = 20)
		{
			$farminfo = $this->DB->GetRow("SELECT clientid, region FROM farms WHERE id=? AND clientid=?",
				array($FarmID, $this->Client->ID)
			);
			
			if (!$farminfo)
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
				
			$sql = "SELECT * FROM logentries WHERE farmid='{$FarmID}'";
			if ($InstanceID)
				$sql .= " AND serverid=".$this->DB->qstr($InstanceID);
				
			$total = $this->DB->GetOne(preg_replace('/\*/', 'COUNT(*)', $sql, 1));
		
			$sql .= " ORDER BY id DESC";
			
			$start = $StartFrom ? (int) $StartFrom : 0;
			$limit = $RecordsLimit ? (int) $RecordsLimit : 20;
			$sql .= " LIMIT {$start}, {$limit}";
			
			$response = $this->CreateInitialResponse();
			$response->TotalRecords = $total;
			$response->StartFrom = $start;
			$response->RecordsLimit = $limit;
			$response->LogSet = new stdClass();
			$response->LogSet->Item = array();
			
			$rows = $this->DB->Execute($sql);
			while ($row = $rows->FetchRow())
			{
				$itm = new stdClass();
				$itm->InstanceID = $row['serverid'];
				$itm->Message = $row['message'];
				$itm->Severity = $row['severity'];
				$itm->Timestamp = $row['time'];
				$itm->Source = $row['source'];
				
				$response->LogSet->Item[] = $itm;
			}
			
			return $response;
		}
		
		/**
		 * Reboots specified instance
		 * @param $FarmID
		 * @param $InstanceID
		 * @return Object
		 */
		public function RebootInstance($FarmID, $InstanceID)
		{
			$farminfo = $this->DB->GetRow("SELECT clientid, region FROM farms WHERE id=? AND clientid=?",
				array($FarmID, $this->Client->ID)
			);
			
			if (!$farminfo)
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
			
			$response = $this->CreateInitialResponse();				
            $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region']));
			$AmazonEC2Client->SetAuthKeys($this->Client->AWSPrivateKey, $this->Client->AWSCertificate);
    		$AmazonEC2Client->RebootInstances(array($InstanceID));
    		
    		$response->Result = true;
			return $response;
		}
		
		/**
		 * Returns script details (Available revisions and config arguments)
		 * @param $ScriptID
		 * @return Object
		 */
		public function GetScriptDetails($ScriptID)
		{
			$script_info = $this->DB->GetRow("SELECT * FROM scripts WHERE id=?", array($ScriptID));
			if (!$script_info)
				throw new Exception(sprintf("Script ID: %s not found in our database (1)", $ScriptID));
				
			if ($script_info['origin'] == SCRIPT_ORIGIN_TYPE::CUSTOM && $this->Client->ID != $script_info['clientid'])
				throw new Exception(sprintf("Script ID: %s not found in our database (2)", $ScriptID));
				
			if ($script_info['origin'] == SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED && $script_info['approval_state'] != APPROVAL_STATE::APPROVED)
				throw new Exception(sprintf("Script ID: %s not found in our database (3)", $ScriptID));
			
			$response = $this->CreateInitialResponse();
				
			$response->ScriptID = $ScriptID;
			$response->ScriptRevisionSet = new stdClass();
			$response->ScriptRevisionSet->Item = array();
			
			$revisions = $this->DB->GetAll("SELECT * FROM script_revisions WHERE scriptid=?", array($ScriptID));
			foreach ($revisions as $revision)
			{
				$itm = new stdClass();
				$itm->{"Revision"} = $revision['revision'];
				$itm->{"Date"} = $revision['dtcreated'];
				$itm->{"ConfigVariables"} = new stdClass();
				$itm->{"ConfigVariables"}->Item = array();
				
				$text = preg_replace('/(\\\%)/si', '$$scalr$$', $revision['script']);
				preg_match_all("/\%([^\%\s]+)\%/si", $text, $matches);
				$vars = $matches[1];
				$data = array();
			    foreach ($vars as $var)
			    {
			    	if (!in_array($var, array_keys(CONFIG::$SCRIPT_BUILTIN_VARIABLES)))
			    	{
			    		$ditm = new stdClass;
			    		$ditm->Name = $var;
			    		$itm->{"ConfigVariables"}->Item[] = $ditm;
			    	}
			    }
				
				$response->ScriptRevisionSet->Item[] = $itm;
			}
			
			return $response;
		}
		
		/**
		 * Execute script with revision number and config
		 * @return object
		 * @todo: Revision, ConfigVariables
		 */
		public function ExecuteScript($ScriptID, $Timeout, $Async, $FarmID, $FarmRoleID = null, $InstanceID = null, $Revision = null, array $ConfigVariables = null)
		{			
			if (!$ConfigVariables)
				return parent::ExecuteScript($ScriptID, $Timeout, $Async, $FarmID, $FarmRoleID, $InstanceID);
				
			$response = $this->CreateInitialResponse();
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?",
				array($FarmID, $this->Client->ID)
			);
			
			if (!$farminfo)
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
			
			if (!$Revision)
				$Revision = 'latest';
				
			/* * */			
			if ($InstanceID && !$FarmRoleID)
			{
				$DBServer = DBServer::LoadByPropertyValue(EC2_SERVER_PROPERTIES::INSTANCE_ID, $InstanceID);
				$FarmRoleID = $DBServer->farmRoleId;
			}
			
			$config = $ConfigVariables;
			$scriptid = (int)$ScriptID;
			if ($InstanceID)
				$target = SCRIPTING_TARGET::INSTANCE;
			else if ($RoleName)
				$target = SCRIPTING_TARGET::ROLE;
			else
				$target = SCRIPTING_TARGET::FARM;
			$event_name = 'APIEvent-'.date("YmdHi").'-'.rand(1000,9999);
			$version = $Revision;
			$farmid = (int)$FarmID;
			$timeout = (int)$Timeout;
			$issync = ($Async == 1) ? 0 : 1;
			
			$this->DB->Execute("INSERT INTO farm_role_scripts SET
				scriptid	= ?,
				farmid		= ?,
				farm_roleid	= ?,
				params		= ?,
				event_name	= ?,
				target		= ?,
				version		= ?,
				timeout		= ?,
				issync		= ?,
				ismenuitem	= ?
			", array(
				$scriptid, $farmid, $FarmRoleID, serialize($config), $event_name, $target, $version, $timeout, $issync, 0
			));
			
			$farm_rolescript_id = $this->DB->Insert_ID();
			
			switch($target)
			{
				case SCRIPTING_TARGET::FARM:
					
					$servers = $this->DB->GetAll("SELECT server_id FROM servers WHERE status IN (?,?) AND farmid=?",
						array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $farmid)
					);
					
					break;
					
				case SCRIPTING_TARGET::ROLE:
					
					$servers = $this->DB->GetAll("SELECT server_id FROM servers WHERE status IN (?,?) AND farm_roleid=?",
						array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $FarmRoleID)
					);
					
					break;
					
				case SCRIPTING_TARGET::INSTANCE:
					
					$servers = $this->DB->GetAll("SELECT server_id FROM servers WHERE status IN (?,?) AND server_id=? AND farmid=?",
						array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $DBServer->serverId, $farmid)
					);
					
					break;
			}
			
			//
			// Send Trap
			//
			if (count($servers) > 0)
			{			
				foreach ($servers as $server)
				{
					$DBServer = DBServer::LoadByID($server['server_id']);
					
					$msg = new Scalr_Messaging_Msg_ExecScript($event_name);
					$msg->meta[Scalr_Messaging_MsgMeta::EVENT_ID] = "FRSID-{$farm_rolescript_id}";
					$DBServer->SendMessage($msg, true);
				}
			}
			
			
			$response->Result = true;
			return $response;
		}
	}
?>