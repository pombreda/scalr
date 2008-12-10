<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Execute script");
    
	$farmid = (int)$req_farmid;
	
	$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($farmid));
	
	if ($_SESSION['uid'] != 0 && $_SESSION['uid'] != $farminfo['clientid'])
		UI::Redirect("farms_view.php");
			
	// Get list of roles
	$display['roles'] = $db->GetAll("SELECT farm_amis.*, ami_roles.name FROM farm_amis INNER JOIN 
		ami_roles ON ami_roles.ami_id = farm_amis.ami_id WHERE farmid=?",
		array($farmid)
	);

	// Get list of instances
	$display['instances'] = $db->GetAll("SELECT *, ami_roles.name FROM farm_instances INNER JOIN 
		ami_roles ON ami_roles.ami_id = farm_instances.ami_id WHERE farmid=?",
		array($farmid)
	);
	
	if ($req_iid)
	{
		$iinfo = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($req_iid));
		if ($iinfo['farmid'] == $farminfo['id'])
		{
			$display['target'] = SCRIPTING_TARGET::INSTANCE;
			$display['iid'] = $req_iid;
		}
	}
	
	if (!$display['target'] && $req_ami_id)
	{
		$ami_info = $db->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND ami_id=?", array($farmid, $req_ami_id));
		if ($ami_info)
		{
			$display['target'] = SCRIPTING_TARGET::ROLE;
			$display['ami_id'] = $req_ami_id;
		}
	}
	
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
    		$display['scripts'][] = $script;
    	}
    }
    
    if ($_POST || ($req_script && $req_task == 'execute'))
	{		
		if (!$req_script || $req_task == 'update_event')
		{
			foreach ($display['scripts'] as $script)
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
				
				
			if (count($err) == 0)
			{
				$scriptid = (int)$req_scriptid;
				$ami_id = $display['ami_id'];
				$config = $req_config;
				$event_name = 'CustomEvent-'.date("YmdHi").'-'.rand(1000,9999);
				$target = $display['target'];
				$version = (int)$req_script_version;
				$timeout = (int)$req_scripting_timeout;
				$issync = ($req_issync == 1) ? 1 : 0;
				
				if ($req_task == 'update_event')
				{
					$event_name = $req_script;
					
					$db->Execute("UPDATE farm_role_scripts SET
						scriptid	= ?,
						ami_id		= ?,
						params		= ?,
						target		= ?,
						version		= ?,
						timeout		= ?,
						issync		= ?
					WHERE farmid=? AND event_name=?
					", array(
						$scriptid,
						$ami_id,
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
						ami_id		= ?,
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
						$ami_id,
						serialize($config),
						$event_name,
						$target,
						$version,
						$timeout,
						$issync,
						(isset($post_create_menu_link)) ? 1 : 0
					));
					
					switch($target)
					{
						case SCRIPTING_TARGET::FARM:
							
							$instances = $db->GetAll("SELECT * FROM farm_instances WHERE state IN (?,?) AND farmid=?",
								array(INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING, $farmid)
							);
							
							break;
							
						case SCRIPTING_TARGET::ROLE:
							
							$instances = $db->GetAll("SELECT * FROM farm_instances WHERE state IN (?,?) AND ami_id=? AND farmid=?",
								array(INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING, $ami_id, $farmid)
							);
							
							break;
							
						case SCRIPTING_TARGET::INSTANCE:
							
							$instances = $db->GetAll("SELECT * FROM farm_instances WHERE state IN (?,?) AND instance_id=?",
								array(INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING, $req_iid)
							);
							
							break;
					}
				}
			}
		}
		else
		{
			$info = $db->GetRow("SELECT * FROM farm_role_scripts WHERE event_name=? AND farmid=?", 
				array($req_script, $farminfo['id'])
			);
			if (!$info)
			{
				UI::Redirect("farms_view.php");
			}
			
			$event_name = $info['event_name'];
			
			switch($info['target'])
			{
				case SCRIPTING_TARGET::FARM:
					
					$instances = $db->GetAll("SELECT * FROM farm_instances WHERE state IN (?,?) AND farmid=?",
						array(INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING, $info['farmid'])
					);
					
					break;
					
				case SCRIPTING_TARGET::ROLE:
					
					$instances = $db->GetAll("SELECT * FROM farm_instances WHERE state IN (?,?) AND ami_id=? AND farmid=?",
						array(INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING, $info['ami_id'], $info['farmid'])
					);
					
					break;
			}
		}
			
		//
		// Send Trap
		//
		if (count($instances) > 0)
		{
			$SNMP = new SNMP();
			
			foreach ($instances as $instance)
			{
				$SNMP->Connect($instance['external_ip'], null, $farminfo['hash']);
				$trap = vsprintf(SNMP_TRAP::NOTIFY_EVENT, array(
					"0.0.0.0",
					$db->GetOne("SELECT alias FROM ami_roles WHERE ami_id=?", array($instance['ami_id'])),
					$instance['role_name'],
					$event_name
				));
		        
				$res = $SNMP->SendTrap($trap);
		        $Logger->info("[FarmID: {$farmid}] Sending SNMP Trap notifyEvent ({$trap}) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})");
			}
		}
		
		if (count($err) == 0)
		{
			$okmsg = _("Script execution initialized");
			UI::Redirect("farms_view.php");
		}
	}
    	
	if ($req_task == 'edit')
	{
		$display['title'] = "Edit shortcut";
		$display['task'] = 'edit';
		
		$info = $db->GetRow("SELECT * FROM farm_role_scripts WHERE event_name=? AND farmid=?", 
			array($req_script, $farminfo['id'])
		);
		if (!$info)
		{
			UI::Redirect("farms_view.php");
		}
		
		$display = array_merge($display, $info);
		
		$display['farmid'] = $req_farmid;
		$display["event_name"] = $req_script;
		
		$display['target'] == SCRIPTING_TARGET::FARM;
		
		if ($info['ami_id'])
		{
			$display['target'] == SCRIPTING_TARGET::ROLE;
			$display['ami_id'] = $info['ami_id'];
		}
		
		$display['values'] = json_encode(unserialize($info['params']));
	}
	
	require("src/append.inc.php"); 
	
?>