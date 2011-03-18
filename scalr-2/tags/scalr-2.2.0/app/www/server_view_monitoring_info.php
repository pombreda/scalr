<? 
	require("src/prepend.inc.php"); 
	
	UI::Redirect("/monitoring.php?farmid={$req_farmid}&role={$req_role}&server_index={$req_server_index}");
	
	require("src/append.inc.php"); 
?>
