<?
	require_once('src/prepend.inc.php');
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
		
	$display["title"] = _("Bundle task > Failure details");
	
	$info = $db->GetRow("SELECT * FROM bundle_tasks WHERE id=? AND env_id=?", 
		array($req_task_id, Scalr_Session::getInstance()->getEnvironmentId())
	);
	if (!$info)
		UI::Redirect("/bundle_tasks.php");
	
	$display['task'] = $info;
	
	require_once ("src/append.inc.php");
?>