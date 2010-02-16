<? 
	require("src/prepend.inc.php");
	
    if ($_SESSION['uid'] == 0)
    {
    	$errmsg = _("Requested page cannot be viewed from admin account");
    	UI::Redirect("index.php");
    }

	////////////
	// EDIT TASK
	////////////
	
    if($req_task == 'edit')
    {
    	$display["title"] = _("Edit task");    
    	
    	try
    	{
    		$taskId = (int)$req_id;
    		
	    	if($taskId && $taskId > 0)	    	
	    		$taskInfo = $db->GetRow("SELECT * from scheduler_tasks WHERE id = ?",array($taskId));	    	    		
	    	else	    	 	    		
	    		throw new Exception(_("Task #{$taskId} not found"));	    		    		  
	    	
	    	if ($_SESSION['uid'] && $_SESSION['uid'] != $taskInfo['client_id'])
				UI::Redirect("farms_view.php");
	    		
			// display the using farm, role or instance			
			$DBFarm = null;
			switch($taskInfo['target_type'])
			{
				case SCRIPTING_TARGET::FARM:

					$DBFarm = DBFarm::LoadByID($taskInfo['target_id']);
				
					$display['farminfo'] = array(
						'id' => $DBFarm->ID, 
						'name' => $DBFarm->Name, 
						'clientid' => $DBFarm->ClientID
					);
					break;					
									
				case SCRIPTING_TARGET::ROLE: 

					$DBFarmRole = DBFarmRole::LoadByID($taskInfo['target_id']);
					$DBFarm = $DBFarmRole->GetFarmObject();
					
					$display['roleinfo']['ami_id'] = $DBFarmRole->AMIID;
					$display['roleinfo']['farmid'] = $DBFarmRole->FarmID;
					$display['farminfo']['name'] = $DBFarm->Name;

					break;
					
				case SCRIPTING_TARGET::INSTANCE:
					
					$roleInstance = explode(":",$taskInfo['target_id']);
					$DBFarmRole = DBFarmRole::LoadByID($roleInstance[0]);	
							
					$display['roleinfo']['ami_id'] = $DBFarmRole->AMIID;
					$display['roleinfo']['farmid'] = $DBFarmRole->FarmID;

					$DBFarm = $DBFarmRole->GetFarmObject();					
					$display['farminfo']['name'] = $DBFarm->Name;
								
					$DBInstance = DBInstance::LoadByFarmRoleIDAndIndex($roleInstance[0],$roleInstance[1]);
					
				 	$display['instanceInfo']['instance_id'] = $DBInstance->InstanceID;
				 	$display['instanceInfo']['external_ip'] = $DBInstance->ExternalIP;
				 	$display['instanceInfo']['id'] = $DBInstance->ID;
			
					break;			
			}			
			
    	}
    	catch(Exception $e)
    	{
    		$err[] = $e->getMessage();
    		UI::Redirect("/scheduler.php");	
    	}
	    	
    	$task_config = unserialize($taskInfo['task_config']);
		
		// if task is script then get id, version(revision) and issync args
    	if($task_config['script_id'] > 0)
    	{	
			$display['scriptid'] = $task_config['script_id'];
			$display['issync']	 = $task_config['issync'];
			$display['version'] = $task_config['revision'];
			$display['timeout'] = $task_config['timeout'];						
    	}

    	if($taskInfo['task_type'] == SCHEDULE_TASK_TYPE::TERMINATE_FARM)
    	{
    		$display['deleteDNS'] = $task_config['deleteDNS'];
    		$display['keep_elastic_ips'] = $task_config['keep_elastic_ips'];
    		$display['keep_ebs'] = $task_config['keep_ebs'];  
    	}
    	
    	$i = 0;
    	$script_args = array();
    	
    	// form array of script args for javascript
    	foreach ($task_config as $key => $value)
    	{    		
    		$script_args[$i]['fieldname'] = $key;
    		$script_args[$i]['value'] =  $value;
    		$i++;
    	}  
    	unset($i);   
    	
    	$display['task_type'] = $taskInfo["task_type"];
    	$display['taskinfo'] = $taskInfo;
    	$display["script_args"] = json_encode($script_args);	
    }
    ///////////////////
	// CREATE NEW TASK
	///////////////////
	
    elseif($req_task == 'create')
    {    	
    	$display["title"] = _("Create new task");    	    
		$farmid = (int)$req_farmid;
				
		// farms list selection	
		$display['farms'] = $db->GetAll(
			"SELECT `name`, id FROM farms WHERE clientid=?",
			array($_SESSION['uid'])
		);	

		// farmid > 0 - means that $req_farmid is not a string, but number
		if ($farmid && $farmid > 0)
		{  
			// get client's farm by id		
			$farminfo = $db->GetRow("SELECT id,name,clientid FROM farms WHERE id=?", 
				array($farmid)
			);			
						
			if ($_SESSION['uid'] && $_SESSION['uid'] != $farminfo['clientid'])
				UI::Redirect("farms_view.php");
				
			$display['farminfo'] =  $farminfo;
		}
		else			
			// first DB selection. get all farms
			$farmid = $display['farms'][0]['id'];
		
	    // for correct javascript code send empty variable
		$display["script_args"] = json_encode("");

		// default terminate parameters
		$display['deleteDNS'] 			= 1;
    	$display['keep_elastic_ips'] 	= 1;
    	$display['keep_ebs'] 			= 1; 
   }
   else 
   		UI::Redirect("/scheduler.php");
   
   //////////////////////////////////
   // CONTINUE TO SHOW TASK EDIT MENU
   //////////////////////////////////
   
	if ($req_scriptid)
		$display['scriptid'] = (int)$req_scriptid;
	
	// Get list of roles
	$display['roles'] = $db->GetAll("SELECT farm_roles.*, roles.name 
		FROM farm_roles 
		INNER JOIN roles ON roles.ami_id = farm_roles.ami_id WHERE farmid=?",
		array($farmid)
	);
	
	// Get list of instances
	$display['instances'] = $db->GetAll("SELECT id, farmid, instance_id, state, ami_id, internal_ip, external_ip,role_name,farm_roleid
		FROM farm_instances WHERE farmid = ?  AND `state` = ?",
		array($farmid, INSTANCE_STATE::RUNNING)
	);
	
	try 
	{			
		if ($req_iid)
		{ 	
			$DBInstance = DBInstance::LoadByID($req_iid);
			
			if ($DBInstance->FarmID == $farminfo['id'])
			{
				$display['target'] = SCRIPTING_TARGET::INSTANCE;
				$display['iid'] = $DBInstance->InstanceID;
			}
		}
		
		if (!$display['target'] && $req_farm_roleid)
		{

			$DBFarmRole = DBFarmRole::LoadByID($req_farm_roleid);

			$display['target'] = SCRIPTING_TARGET::ROLE;
				$display['ami_id'] = $DBFarmRole->AMIID;
		}	
	
		if (!$display['target'] && $req_ami_id)
		{
			$ami_info = $db->GetRow("SELECT * FROM farm_roles WHERE farmid=? AND ami_id=?", 
				array($farmid, (int)$req_ami_id)
			);
			
			if ($ami_info)
			{
				$display['target'] = SCRIPTING_TARGET::ROLE;
				$display['ami_id'] = $req_ami_id;
			}
		}
	}
	catch(Exception $e)
	{
		$err[]  = $e->getMessage();
		UI::Redirect("/scheduler.php");	
	}
	
	if(!$display['target'])
		$display['target'] = SCRIPTING_TARGET::FARM;
			
	if ($_SESSION['uid'] != 0)
	{
		$script_filter_sql .= " AND ("; 
			// Show shared roles
			$script_filter_sql .= " origin='".SCRIPT_ORIGIN_TYPE::SHARED."'";
		
			// Show custom roles
			$script_filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::CUSTOM."' 
					AND clientid='{$_SESSION['uid']}')";
			
			//Show approved contributed roles
			$script_filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED."' 
					AND (scripts.approval_state='".APPROVAL_STATE::APPROVED."' 
					OR clientid='{$_SESSION['uid']}'))";
		$script_filter_sql .= ")";
	}
	
    $sql = "select scripts.*, MAX(script_revisions.dtcreated) as dtupdated from scripts INNER JOIN script_revisions 
    	ON script_revisions.scriptid = scripts.id WHERE 1=1 {$script_filter_sql} GROUP BY script_revisions.scriptid ORDER BY dtupdated DESC";
	   
    // Get list of scripts
    $scripts = $db->GetAll($sql);
    foreach ($scripts as $script)
    {
    	if ($db->GetOne("SELECT COUNT(*) FROM script_revisions WHERE approval_state=? AND scriptid=?", 
    		array(APPROVAL_STATE::APPROVED, $script['id'])) > 0
    	)    	
    	$display['scripts'][] = $script;    	
    }
	
    if ($_POST)
	{	
		if($req_task == 'create')
		{
			try
			{				
				if($req_task_type == SCHEDULE_TASK_TYPE::SCRIPT_EXEC)
				{				
						switch($req_target_type)
						{
							case SCRIPTING_TARGET::FARM:
								$DBFarm = DBFarm::LoadByID($req_farm_target);
								$target_id = $DBFarm->ID;					
								
								$target_object_clientid = $DBFarm->ClientID;																					
								break;
									
							case SCRIPTING_TARGET::ROLE:
								$DBFarmRole = DBFarmRole::LoadByID($req_farm_roleid);
								$target_id = $DBFarmRole->ID;
								
								$target_object_clientid = $DBFarmRole->GetFarmObject()->ClientID;
								break;
									
							case SCRIPTING_TARGET::INSTANCE:
								$DBInstance = DBInstance::LoadByID($req_iid);
								$target_id = "{$DBInstance->FarmRoleID}:{$DBInstance->Index}";
								
								$target_object_clientid = $DBInstance->GetDBFarmObject()->ClientID;
								break;	
																										
							default: 
								throw new Exception();
								break;
						}		
						
				}
				elseif( $req_task_type == SCHEDULE_TASK_TYPE::TERMINATE_FARM || 
						$req_task_type == SCHEDULE_TASK_TYPE::LAUNCH_FARM)
				{
					$DBFarm = DBFarm::LoadByID($req_farm_target);
					
					$target_object_clientid = $DBFarm->ClientID;
					
					$target_id = $DBFarm->ID;	
				}
				else				
					UI::Redirect("/scheduler.php");
								
				if (($_SESSION['uid'] && $target_object_clientid != $_SESSION['uid']) || $target_id == 0)
					throw new Exception(_("Specified target not found, please select correct target object"));
									
			}
			catch (Exception $e)
			{			
				$err[] = $e->getMessage();
				UI::Redirect("/scheduler.php");	
			}				
		}
		
		// form error message for entering data correction
		$exception = false;		
		$Validator = new Validator();			
	
		// check entering data
		try
		{			
			if(!$Validator->IsAlphaNumeric($req_task_name))
				$err[] = _("Task name contains invalid symbols or empty");				
					
			if (!$req_startDateTime)			
				$err[] = _("Start date has incorrect date format");			
			
			if (!$req_endDateTime)			
				$err[] = _("End date has incorrect date format");				
			
			if (!$Validator->IsNumeric($req_restart_timeout))			
				$err[] = _("Restart timeout is not a numeric variable");				
			
			if (!$Validator->IsNumeric($req_order_index))			
				$err[] = _("Priority is not a numeric variable");
					
			if(!$Validator->IsNumeric($req_timeout) && $req_task_type == SCHEDULE_TASK_TYPE::SCRIPT_EXEC)		
				$err[] = _("Timeout is not a numeric variable");
				
			if($err)
				throw new Exception();		

			// check correct  date  and time				
			// new task start date can't be older then current date
			if($req_task == 'create')
			{				
				if(CompareDateTime($req_startDateTime) < 0)				
					$err[] = _("Start time must be later or equal to the current date and time");							
			}
			
			if(CompareDateTime($req_endDateTime) < 1)				
					$err[] = _("End time must be later than current date and time");	
					
			if(CompareDateTime($req_startDateTime,$req_endDateTime) != -1 && $req_restart_timeout != 0) 			
				$err[] = _("End time must be later than start time");			
			
			if($err)
				throw new Exception();	
				
			// add/update database 				
			$redirectUrl = "/scheduler.php";
			
			$req_config = array();			
			$info = $db->GetRow("SELECT * FROM scripts WHERE id = ? {$script_filter_sql}", array($req_scriptid));
			
			if(!$info)			
				throw new Exception(_("Script {$req_scriptid} not found")); 			
			
			// Rewrite
				
			if($req_scriptid && $req_task_type == SCHEDULE_TASK_TYPE::SCRIPT_EXEC)			
			{
				$req_config['script_id']= (int)$req_scriptid;				
				$req_config['revision'] = (int)$req_script_version;
				$req_config['issync'] 	= $req_issync;
				$req_config['timeout'] 	= (int)$req_timeout;
			}
		
			if($req_task_type == SCHEDULE_TASK_TYPE::TERMINATE_FARM)	
			{					
				$req_config['deleteDNS'] = ($req_deleteDNS) ? 1 : 0;
				$req_config['keep_elastic_ips'] = $req_keep_elastic_ips;
				$req_config['keep_ebs'] = $req_keep_ebs; 			
			}	
			
			if ($req_task == 'edit')
			{	
				// update scheduler's record
				$redirectUrl = "/schedule_task_add.php?&task={$req_task}&id={$taskId}";						
						
				$db->Execute("UPDATE scheduler_tasks SET
					task_name = ?,
					task_type = ?,
					start_time_date = ?,
					end_time_date = ?,
					last_start_time = ?,
					restart_every = ?,
					task_config = ?,
					order_index = ?,
					status = ?
					WHERE id = ? AND client_id = ?",
				array(
					$req_task_name,
					$req_task_type,
					$req_startDateTime,			// time 
					$req_endDateTime,
					null,
					(int)$req_restart_timeout,
					($req_config) ? serialize($req_config) : null,
					(int)$req_order_index,
					TASK_STATUS::ACTIVE,
					$taskId,						
					$_SESSION['uid']						
					)
				);
			
				$okmsg =_("Task {$req_task_name} successfully updated");									
			}
		
			elseif($req_task == 'create')
			{				
				$redirectUrl = "/schedule_task_add.php?&farmid={$req_farmid}&task={$req_task}";
				
				// add  new scheduler's record				
				$db->Execute("INSERT INTO scheduler_tasks SET
					task_name = ?,
					task_type = ?,
					target_id = ?,
					target_type =?,
					start_time_date = ?,
					end_time_date = ?,
					last_start_time = ?,
					restart_every = ?,
					task_config = ?,
					order_index = ?,
					status = ?,				
					client_id = ?",
				array($req_task_name,
					$req_task_type,
					$target_id,
					$req_target_type,
					$req_startDateTime,			// time 
					$req_endDateTime,
					null,
					(int)$req_restart_timeout,
					($req_config)? serialize($req_config):null,
					$req_order_index,
					TASK_STATUS::ACTIVE,						
					$_SESSION['uid']
					)
				);
			
				$okmsg =_("New task successfully added");			
			}
		}
		catch(Exception $e)
		{					
			if(!$err)
				$err[] = $e->getMessage(); // to get message from DB exceptions
			
			if($req_task == 'create')
				$redirectUrl = "farmid={$req_farmid}&task={$req_task}";
			elseif($req_task == 'edit')
				$redirectUrl = "id={$taskId}&task={$req_task}";
				
			UI::Redirect("scheduler_task_add.php?{$redirectUrl}");	
		}
		
		UI::Redirect("/scheduler.php");	
	}
	
	$display['taskTypes'] = SCHEDULE_TASK_TYPE::GetAll();
	$display['id'] = (int)$taskId; 
	$display['task'] = $req_task;
	$display['farmid'] = (int)$req_farmid;	
	$display['type'] = $req_task_type;
	
	//to show correct task type(javascript), then select another farm to create 
    if($req_task_type == SCHEDULE_TASK_TYPE::LAUNCH_FARM ||
    	$req_task_type == SCHEDULE_TASK_TYPE::TERMINATE_FARM ||
    	$req_task_type == SCHEDULE_TASK_TYPE::SCRIPT_EXEC)
    {   	
    	$display['task_type'] = $req_task_type;
    }
	
	require("src/append.inc.php"); 
	
	function CompareDateTime($date1,$date2 = null)
	{	
		// compatere 2 dates 
		// if date1 later then date 2  returns 1;
		// if date2 later  then date 1  returns -1;
		// if date1 equal to date 2 returns 0;
		// if date2 is null function compateres date1 with current time (as date2 variable)

		$checkingDate1 = strtotime($date1);	
		
		if($date2)	
			$checkingDate2 = strtotime($date2); // get compareing time	
		else		
			$checkingDate2 = time(); // get current time
			
			
		if($checkingDate1 > $checkingDate2)		 // e.g. selected date later then current date
			return 1;
		elseif($checkingDate1 < $checkingDate2)  // e.g. end date is later then start date or current date is la
			return -1;
		else
			return 0;
		
	}

