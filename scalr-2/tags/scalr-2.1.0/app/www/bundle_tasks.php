<?
	require_once('src/prepend.inc.php');
	$display['load_extjs'] = true;
	
	set_time_limit(3600);
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	if ($req_action == 'cancel')
	{
		try {
			$task = BundleTask::LoadById($req_task_id);
			
			if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($task->envId))
			{
				$errmsg = _("You have no permissions for viewing requested page");
				UI::Redirect("/index.php");
			}
			
			if ($task->status != SERVER_SNAPSHOT_CREATION_STATUS::IN_PROGRESS)
				throw new Exception("Incorrect status for cancelling");
				
		} catch (Exception $e) {
			UI::Redirect("/bandle_tasks.php");
		}
		
		$task->SnapshotCreationFailed("Cancelled by client");
		
		$okmsg = _("Bundle task successfully cancelled");
		UI::Redirect("/bundle_tasks.php");
	}
	
	if ($req_clientid)
	{
	    $id = (int)$req_clientid;
	    $display['grid_query_string'] .= "&clientid={$clientid}";
	}
	   
	if ($req_type)
	{
		$type = preg_replace("/[^A-Za-z]+/", "", $req_type);
	    $display['grid_query_string'] .= "&type={$type}";
	}
	
	$display["title"] = _("Bundle tasks > View");
	
	require_once ("src/append.inc.php");
?>