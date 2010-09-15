<?
	require_once('src/prepend.inc.php');
	$display['load_extjs'] = true;
	
	set_time_limit(3600);
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
	
	if ($req_action == 'cancel')
	{
		try {
			$task = BundleTask::LoadById($req_task_id);
			
			if ($task->clientId != $_SESSION['uid'])
				throw new Exception("Task not found");
			
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