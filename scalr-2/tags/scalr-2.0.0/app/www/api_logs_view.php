<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("API log&nbsp;&raquo;&nbsp;View");
	$display['load_extjs'] = true;
    
	if ($req_farmid)
	{
		$farmid = (int)$_REQUEST["farmid"];
		$display["grid_query_string"] = "&farmid={$farmid}";
	}
	
	$display["table_title_text"] = sprintf(_("Current time: %s"), date("M j, Y H:i:s"));
	
	require("src/append.inc.php");
?>