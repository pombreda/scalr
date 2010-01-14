<? 
	require("src/prepend.inc.php"); 
		
	if ($_SESSION['uid'] == 0)
		UI::Redirect("/index.php");
	
	$display["title"] = _("Dashboard");
	$display['load_extjs'] = true;
	$display["table_title_text"] = sprintf(_("Current time: %s"), date("M j, Y H:i:s"));
				
	$display['client'] = array(
		'email'	=> $Client->Email
	);
	
	require("src/append.inc.php"); 
?>
