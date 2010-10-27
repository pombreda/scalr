<?
	require_once('src/prepend.inc.php');
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
		
	$display["title"] = _("Bundle task > Failure details");
	
	$info = $db->GetRow("SELECT * FROM bundle_tasks WHERE id=? AND client_id=?", array($req_task_id, $_SESSION['uid']));
	if (!$info)
		UI::Redirect("/bundle_tasks.php");
	
	$display['task'] = $info;
	
	require_once ("src/append.inc.php");
?>