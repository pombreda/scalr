<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	try
	{
		$DBServer = DBServer::LoadByID($req_server_id);
		if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($DBServer->envId))
			throw new Exception("Server not found");
			
	}
	catch(Exception $e)
	{
		UI::Redirect("servers_view.php");
	}
	    
    if ($post_cancel)
		UI::Redirect("servers_view.php");
    
	if ($req_action == 'resend')
	{
		$message = $db->GetRow("SELECT * FROM messages WHERE server_id=? AND messageid=?",array(
			$DBServer->serverId, $req_message
		));
		
		if ($message)
		{
			$db->Execute("UPDATE messages SET status=?, handle_attempts='0' WHERE id=?", array(MESSAGE_STATUS::PENDING, $message['id']));
			
			$serializer = new Scalr_Messaging_XmlSerializer();
			
			$msg = $serializer->unserialize($message['message']);
			$DBServer->SendMessage($msg);
						
			$okmsg = _("Message successfully sent");
		}
		
		UI::Redirect("/scalr_i_messages.php?server_id={$DBServer->serverId}");
	}
        
	$display["title"] = _("Servers&nbsp;&raquo;&nbsp;Scalr internal messaging (Server: {$DBServer->serverId})");	
	$display["grid_query_string"] = "&server_id={$DBServer->serverId}";
	
	require("src/append.inc.php");
?>