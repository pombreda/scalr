<?
	if ($_SESSION["okmsg"] || $okmsg)
	{
		$display["okmsg"] = $_SESSION["okmsg"] ? $_SESSION["okmsg"] : $okmsg;
		$_SESSION["okmsg"] = null;
	}
	elseif ($_SESSION["errmsg"] || $errmsg)
	{
		$display["errmsg"] = $_SESSION["errmsg"] ? $_SESSION["errmsg"] : $errmsg;
		$_SESSION["errmsg"] = null;
	}
	elseif ($_SESSION["mess"] || $mess)
	{
	    $display["mess"] = $_SESSION["mess"] ? $_SESSION["mess"] : $mess;
	    $_SESSION["mess"] = null;
	}
	
	if ($_SESSION["err"])
	{
	    $err = $_SESSION["err"];
	    $_SESSION["err"] = null;
	}
	
	if (is_array($err))
	{
		$display["errmsg"] = $errmsg ? $errmsg : "The following errors occured:";
		$display["err"] = $err;
	}

	
	$Smarty->assign($display);
	
	if (!$template_name)
	   $template_name = NOW.".tpl";
	
    $Smarty->display($template_name);
?>
