<?php
	
	class ScalrAPI20090507 extends ScalrAPICore
	{
		
		public function TerminateFarm($FarmID, $KeepEBS, $KeepEIP, $KeepDNSZone)
		{
			$response = $this->CreateInitialResponse();
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?",
				array($FarmID, $this->Client->ID)
			);
			
			if (!$farminfo)
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
				
			if ($farminfo['status'] != FARM_STATUS::RUNNING)
				throw new Exception(sprintf("Farm already terminated", $FarmID));
			
			$event = new FarmTerminatedEvent(
				(($KeepDNSZone) ? 0 : 1), 
				(($KeepEIP) ? 1 : 0),
				true,
				(($KeepEBS) ? 1 : 0)
			);
			Scalr::FireEvent($FarmID, $event);
			
			$response->Result = true;
			return $response;
		}
		
		public function LaunchFarm($FarmID)
		{				
			$response = $this->CreateInitialResponse();
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?",
				array($FarmID, $this->Client->ID)
			);
			
			if (!$farminfo)
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
				
			if ($farminfo['status'] == FARM_STATUS::RUNNING)
				throw new Exception(sprintf("Farm already running", $FarmID));
			
			Scalr::FireEvent($FarmID, new FarmLaunchedEvent(1));
			
			$response->Result = true;
			return $response;
		}
		
		public function ListRoles($Region, $AmiID = null, $Name = null, $Prefix = null)
		{			
			$response = $this->CreateInitialResponse();
			$response->RoleSet = new stdClass();
			$response->RoleSet->Item = array();
			
			$sql = "SELECT * FROM roles WHERE iscompleted='1' AND (clientid='0' OR clientid='{$this->Client->ID}') AND region={$this->DB->qstr($Region)}";
			
			if ($AmiID)
				$sql .= " AND ami_id={$this->DB->qstr($AmiID)}";
				
			if ($Name)
				$sql .= " AND name={$this->DB->qstr($Name)}";
			
			if ($Prefix)
				$sql .= " AND name LIKE{$this->DB->qstr("%{$Prefix}%")}";
				
			$rows = $this->DB->Execute($sql);
			while ($row = $rows->FetchRow())
			{
				if ($row['clientid'] == 0)
					$row["client_name"] = "Scalr";
				else
					$row["client_name"] = $this->DB->GetOne("SELECT fullname FROM clients WHERE id='{$row['clientid']}'");
					
				if (!$row["client_name"])
					$row["client_name"] = "";
					
				$itm = new stdClass();
				$itm->{"Name"} = $row['name'];
				$itm->{"Owner"} = $row["client_name"];
				$itm->{"Category"} = ROLE_ALIAS::GetTypeByAlias($row['alias']);
				$itm->{"AmiID"} = $row['ami_id'];
				$itm->{"Architecture"} = $row['architecture'];
				$itm->{"BuildDate"} = $row['dtbuilt'];
				
				$response->RoleSet->Item[] = $itm; 
			}
			
			return $response;
		}
				
		/**
		 * 
		 * @return object
		 */
		public function GetFarmStats($FarmID, $Date = null)
		{			
			$response = $this->CreateInitialResponse();
			$response->StatisticsSet = new stdClass();
			$response->StatisticsSet->Item = array();
			
			preg_match("/([0-9]{2})\-([0-9]{4})/", $Date, $m);
			if ($m[1] && $m[2])
				$filter_sql = " AND month='{$m[1]}' AND year='{$m[2]}'";
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?",
				array($FarmID, $this->Client->ID)
			);
			
			if (!$farminfo)
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
				
			$rows = $this->DB->Execute("SELECT *, bw_out/1024 as bw_out, bw_in/1024 as bw_in FROM farm_stats WHERE farmid=? {$filter_sql} ORDER BY id DESC", 
				array($FarmID)
			);
			while ($row = $rows->FetchRow())
			{
				$itm = new stdClass();
				$itm->Month = $row['month'];
				$itm->Year = $row['year'];
				$itm->Statistics = new stdClass();
				$itm->Statistics->{"BandwidthIn"} = round($row["bw_in"], 2);
				$itm->Statistics->{"BandwidthOut"} = round($row["bw_out"], 2);
				$itm->Statistics->{"BandwidthTotal"} = (int)($row["bw_out"]+$row["bw_in"]);
				
				$Reflect = new ReflectionClass("INSTANCE_FLAVOR");
				foreach ($Reflect->getConstants() as $n=>$v)
				{
					$field = str_replace(".", "_", $v);
					$itm->Statistics->{"{$v}Usage"} = round($row[$field]/60/60, 1);
				}
					
				$response->StatisticsSet->Item[] = $itm;
			}
			
			return $response;
		}
		
		/**
		 * 
		 * @return object
		 * @todo: More checks and better validation
		 */
		public function ExecuteScript($ScriptID, $Timeout, $Async, $FarmID, $FarmRoleID = null, $InstanceID = null)
		{			
			$response = $this->CreateInitialResponse();
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?",
				array($FarmID, $this->Client->ID)
			);
			
			if (!$farminfo)
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
			
			/* * */			
			if ($InstanceID && !$FarmRoleID)
			{
				$DBInstance = DBInstance::LoadByIID($InstanceID);
				$FarmRoleID = $DBInstance->FarmRoleID;
			}
			
			$config = array(); //TODO:	
			$scriptid = (int)$ScriptID;
			if ($InstanceID)
				$target = SCRIPTING_TARGET::INSTANCE;
			else if ($RoleName)
				$target = SCRIPTING_TARGET::ROLE;
			else
				$target = SCRIPTING_TARGET::FARM;
			$event_name = 'APIEvent-'.date("YmdHi").'-'.rand(1000,9999);
			$version = 'latest';
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
					
					$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE state IN (?,?) AND farmid=?",
						array(INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING, $farmid)
					);
					
					break;
					
				case SCRIPTING_TARGET::ROLE:
					
					$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE state IN (?,?) AND farm_roleid=?",
						array(INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING, $FarmRoleID)
					);
					
					break;
					
				case SCRIPTING_TARGET::INSTANCE:
					
					$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE state IN (?,?) AND instance_id=? AND farmid=?",
						array(INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING, $InstanceID, $farmid)
					);
					
					break;
			}
			
			//
			// Send Trap
			//
			if (count($instances) > 0)
			{			
				foreach ($instances as $instance)
				{
					$DBInstance = DBInstance::LoadByID($instance['id']);
					$DBInstance->SendMessage(new EventNoticeScalrMessage(
						$instance['internal_ip'],
						"FRSID-{$farm_rolescript_id}",
						$instance['role_name'],
						$event_name
					));
				}
			}
			
			
			$response->Result = true;
			return $response;
		}
		
		public function ListFarms()
		{
			$response = $this->CreateInitialResponse();
			$response->FarmSet = new stdClass();
			$response->FarmSet->Item = array();
			
			$farms = $this->DB->Execute("SELECT * FROM farms WHERE clientid=?", array($this->Client->ID));
			while ($farm = $farms->FetchRow())
			{
				$itm = new stdClass();
				$itm->{"ID"} = $farm['id'];
				$itm->{"Name"} = $farm['name'];
				$itm->{"Region"} = $farm['region'];
				$itm->{"Status"} = $farm['status'];
				
				$response->FarmSet->Item[] = $itm; 
			}
			
			return $response;
		}
		
		public function ListScripts()
		{
			$response = $this->CreateInitialResponse();
			$response->ScriptSet = new stdClass();
			$response->ScriptSet->Item = array();
			
			$filter_sql .= " AND ("; 
				// Show shared roles
				$filter_sql .= " origin='".SCRIPT_ORIGIN_TYPE::SHARED."'";
			
				// Show custom roles
				$filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::CUSTOM."' AND clientid='{$this->Client->ID}')";
				
				//Show approved contributed roles
				$filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED."' AND (scripts.approval_state='".APPROVAL_STATE::APPROVED."' OR clientid='{$this->Client->ID}'))";
			$filter_sql .= ")";
			
		    $sql = "SELECT 
		    			scripts.id, 
		    			scripts.name, 
		    			scripts.description, 
		    			scripts.origin,
		    			scripts.clientid,
		    			scripts.approval_state,
		    			MAX(script_revisions.dtcreated) as dtupdated, MAX(script_revisions.revision) AS version FROM scripts 
		    		INNER JOIN script_revisions ON script_revisions.scriptid = scripts.id 
		    		WHERE 1=1 {$filter_sql}";

		    $sql .= " GROUP BY script_revisions.scriptid";
		    
		    $rows = $this->DB->Execute($sql);
		    
		    while ($row = $rows->FetchRow())
		    {
		    	$itm = new stdClass();
				$itm->{"ID"} = $row['id'];
				$itm->{"Name"} = $row['name'];
				$itm->{"Description"} = $row['description'];
				$itm->{"LatestRevision"} = $row['version'];
				
				$response->ScriptSet->Item[] = $itm; 	
		    }
		    
		    return $response;
		}
		
		public function ListApplications()
		{
			$response = $this->CreateInitialResponse();
			$response->ApplicationSet = new stdClass();
			$response->ApplicationSet->Item = array();
			
			$rows = $this->DB->Execute("SELECT * FROM zones WHERE clientid=?", array($this->Client->ID));
			while ($row = $rows->FetchRow())
			{
				$itm = new stdClass();
				$itm->{"DomainName"} = $row['zone'];
				$itm->{"FarmID"} = $row['farmid'];
				$itm->{"FarmRole"} = $row['role_name'];
				$itm->{"Status"} = $row['status'];
				$itm->{"IPSet"} = new stdClass();
				$itm->{"IPSet"}->item = array();
				if ($row['status'] == ZONE_STATUS::ACTIVE)
				{
					$instances = $this->DB->GetAll("SELECT rvalue FROM records WHERE zoneid=? AND rtype=? AND rkey=? AND issystem=?",
						array($row['id'], 'A', '@', 1)
					);
					
					foreach ($instances as $instance)
					{
						$itm_ip = new stdClass();
						$itm_ip->IPAddress = $instance['rvalue'];
						$itm->{"IPSet"}->Item[] = $itm_ip;
					}
				}
				
				$response->ApplicationSet->Item[] = $itm; 	
		    }
		    
		    return $response;
		}
	}
?>