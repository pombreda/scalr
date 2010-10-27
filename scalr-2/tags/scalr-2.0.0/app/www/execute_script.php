<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Execute script");   
    
    try
    {
		if($req_target)
		{          
			$farmid = (int)$req_farmid; 			
			$DBFarm = DBFarm::LoadByID($farmid);			
			$target == SCRIPTING_TARGET::FARM;
			
			if ($_SESSION['uid'] != 0 && $_SESSION['uid'] != $DBFarm->ClientID)
				throw new Exception("Specified farm not found");  
		}
		
		if (($req_target == SCRIPTING_TARGET::ROLE ||
		 $req_target == SCRIPTING_TARGET::INSTANCE))
		{  
		
			$DBFarmRole = DBFarmRole::LoadByID($req_farm_roleid);
			
			if ($_SESSION['uid'] != 0 && $_SESSION['uid'] != $DBFarmRole->GetFarmObject()->ClientID)
				throw new Exception("Specified farm role not found");
			
			$target = SCRIPTING_TARGET::ROLE;
			$farm_roleid = $DBFarmRole->ID;
			$farmid = $DBFarmRole->FarmID; 				
		}
		
		if ($req_target == SCRIPTING_TARGET::INSTANCE)
		{ 			
			$DBServer = DBServer::LoadByID($req_server_id);

			if ($_SESSION['uid'] != 0 && $_SESSION['uid'] != $DBServer->clientId)
				throw new Exception("Specified server not found");

			$target 		= SCRIPTING_TARGET::INSTANCE;
			$server_id 		= $req_server_id;			
			$farm_roleid 	= $DBServer->farmRoleId;
			$farmid 		= $DBServer->farmId; 		
		}  	
		
    }
    catch(Exception $e)
	{
		$err[] = $e->getMessage();
		UI::Redirect("/script_templates.php");
	}
      

	if ($farmid)
		$display['farmid'] = $farmid;
		
	if ($farm_roleid)
		$display['farm_roleid'] = $farm_roleid;
		
	if ($server_id)
		$display['server_id'] = $server_id;
	
	if ($req_scriptid)
		$display['scriptid'] = (int)$req_scriptid;
	
	if(!$display['target'])
		$display['target'] = SCRIPTING_TARGET::FARM;
			
	if ($_SESSION['uid'] != 0)
	{
		$filter_sql .= " AND ("; 
			// Show shared roles
			$filter_sql .= " origin='".SCRIPT_ORIGIN_TYPE::SHARED."'";
		
			// Show custom roles
			$filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::CUSTOM."' AND clientid='{$_SESSION['uid']}')";
			
			//Show approved contributed roles
			$filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED."' AND (scripts.approval_state='".APPROVAL_STATE::APPROVED."' OR clientid='{$_SESSION['uid']}'))";
		$filter_sql .= ")";
	}
	
    $sql = "select scripts.*, MAX(script_revisions.dtcreated) as dtupdated from scripts INNER JOIN script_revisions 
    	ON script_revisions.scriptid = scripts.id WHERE 1=1 {$filter_sql} GROUP BY script_revisions.scriptid ORDER BY dtupdated DESC";
		

    // Get list of scripts
    $scripts = $db->GetAll($sql);
    foreach ($scripts as $script)
    {
    	if ($db->GetOne("SELECT COUNT(*) FROM script_revisions WHERE approval_state=? AND scriptid=?", 
    		array(APPROVAL_STATE::APPROVED, $script['id'])) > 0
    	)
    	{       		
    		$approved_scripts[] = $script;
    	}
    }
    
        
    if ($_POST || ($req_script && $req_task == 'execute'))
	{			
		if (!$req_script || $req_task == 'update_event')
		{
			foreach ($approved_scripts as $script)
			{
				if ($script['id'] == $req_scriptid)
				{
					$script_found = true;
					break;
				}
			}			

			if (!$script_found)
				$err[] = _("Script not found");
			
			if (!$db->GetOne("SELECT id FROM script_revisions WHERE scriptid=? AND revision=? AND approval_state=?", 
				array($req_scriptid, $req_script_version, APPROVAL_STATE::APPROVED))
			)
				$err[] = _("Selected version not approved or no longer available.");
			
			if($req_target)
				$target = $req_target;
			
				// shortcuts for servers are not awailable
			if($target == SCRIPTING_TARGET::INSTANCE)
				unset($post_create_menu_link);
						
			if (count($err) == 0)
			{
				$scriptid = (int)$req_scriptid;
				$config = $req_script_args;
				$event_name = 'CustomEvent-'.date("YmdHi").'-'.rand(1000,9999);
				$version = (int)$req_script_version;
				$timeout = (int)$req_timeout;
				$issync = ($req_issync == 1) ? 1 : 0;
				
				if ($req_task == 'update_event')
				{
					$event_name = $req_script;
					
					$db->Execute("UPDATE farm_role_scripts SET
						scriptid	= ?,
						farm_roleid	= ?,
						params		= ?,
						target		= ?,
						version		= ?,
						timeout		= ?,
						issync		= ?
					WHERE farmid=? AND event_name=?
					", array(
						$scriptid,
						$farm_roleid,
						serialize($config),
						$target,
						$version,
						$timeout,
						$issync,
						$farmid, $event_name 
					));
					
					$okmsg = _("Script shortcut successfully updated");
					UI::Redirect("script_shortcuts.php");
				}
				else
				{
					$db->Execute("INSERT INTO farm_role_scripts SET
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
						$scriptid,
						$farmid,
						$farm_roleid,
						serialize($config),
						$event_name,
						$target,
						$version,
						$timeout,
						$issync,
						(isset($post_create_menu_link)) ? 1 : 0
					));
					
					$farm_rolescript_id = $db->Insert_ID();
					
					switch($target)
					{
						case SCRIPTING_TARGET::FARM:
							
							$servers = $db->GetAll("SELECT * FROM servers WHERE status IN (?,?) AND farm_id=?",
								array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $farmid)
							);
							
							break;
							
						case SCRIPTING_TARGET::ROLE:
							
							$servers = $db->GetAll("SELECT * FROM servers WHERE status IN (?,?) AND farm_roleid=?",
								array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $farm_roleid)
							);
							
							break;
							
						case SCRIPTING_TARGET::INSTANCE:
							
							$servers = $db->GetAll("SELECT * FROM servers WHERE status IN (?,?) AND server_id=? AND farm_id=?",
								array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $server_id, $farmid)
							);
							
							break;
					}
				}
			}
		}
		else
		{
			$info = $db->GetRow("SELECT * FROM farm_role_scripts WHERE event_name=? AND farmid=?", 
				array($req_script, $farmid)
			);
			if (!$info)
				UI::Redirect("farms_view.php");
			
			$farm_rolescript_id = $info['id'];
			
			$event_name = $info['event_name'];
			
			switch($info['target'])
			{
				case SCRIPTING_TARGET::FARM:
					
					$servers = $db->GetAll("SELECT * FROM servers WHERE status IN (?,?) AND farm_id=?",
						array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $info['farmid'])
					);
					
					break;
					
				case SCRIPTING_TARGET::ROLE:
					
					$servers = $db->GetAll("SELECT * FROM servers WHERE status IN (?,?) AND farm_roleid=?",
						array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $info['farm_roleid'])
					);
					
					break;
			}
		}
		
		//
		// Send Trap
		//
		if (count($servers) > 0)
		{			
			foreach ($servers as $server)
			{
				$DBServer = DBServer::LoadByID($server['server_id']);
				$message = new Scalr_Messaging_Msg_ExecScript($event_name);
				$message->meta[Scalr_Messaging_MsgMeta::EVENT_ID] = "FRSID-{$farm_rolescript_id}";
				$DBServer->SendMessage($message);
				
				/*
				$DBServer->SendMessage(new EventNoticeScalrMessage(
					$DBServer->remoteIp,
					"FRSID-{$farm_rolescript_id}",
					$DBServer->GetFarmRoleObject()->GetRoleName(),
					$event_name
				));
				*/
			}
		}
		
		if (count($err) == 0)
		{
			$okmsg = _("Script execution request was successfully sent");
			UI::Redirect("farms_view.php");
		}
	}
    	
	if ($req_task == 'edit')
	{	
		$display['title'] = "Edit shortcut";
		$display['task'] = 'edit';
		
		$info = $db->GetRow("SELECT * FROM farm_role_scripts WHERE event_name=? AND farmid=?", 
			array($req_script, $req_farmid)
		);
		
		if (!$info)
			UI::Redirect("farms_view.php");		
		
		$display = array_merge($display, $info);		
		$display['farmid'] 		= (int)$req_farmid;
		$display["event_name"] 	= $req_script;		
		$display['target'] 		== SCRIPTING_TARGET::FARM;
		
		if ($info['farm_roleid'])
		{
			$display['target'] == SCRIPTING_TARGET::ROLE;
			$display['farm_roleid'] = $info['farm_roleid'];
		}
		
		$params = unserialize($info['params']);
		$script_args = array();	
		
    	// form array of script args for javascript
    	foreach ($params as $key => $value)
    	{
    		// add only script unique argumetns    		    	
    		$script_args[$key]['value'] =  $value;
    	}
    	unset($params);
    	
		$display['values']  = json_encode($script_args);
		$display['version'] = $info['version'];
		$display['timeout'] = $info['timeout'];
		$display['issync']  = $info['issync'];
	
	}
	
	require("src/append.inc.php"); 
	
?>