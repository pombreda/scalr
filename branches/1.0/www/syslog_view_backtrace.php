<? 
	require("src/prepend.inc.php");
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	
	$display["title"] = _("Service log&nbsp;&raquo;&nbsp;Log event backtrace");
	
    if (!$get_logeventid)
	   UI::Redirect("logs_view.php");
		
	$info = $db->GetRow("SELECT * FROM syslog WHERE id=?", array($get_logeventid));
	if (!$info["backtrace"])
	{
		$errmsg = _("There are no backtrace found for selected log event");
		UI::Redirect("logs_view.php");
	}

	$display["backtrace"] = $info["backtrace"];
	
	require("src/append.inc.php");
?>