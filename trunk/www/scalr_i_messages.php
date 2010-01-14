<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	try
	{
		$DBFarm = DBFarm::LoadByID($req_farmid);
		if ($DBFarm->ClientID != $_SESSION['uid'] && $_SESSION['uid'] != 0)
			throw new Exception("Farm not found");
			
		$DBInstance = DBInstance::LoadByIID($req_iid);
		if ($DBInstance->FarmID != $DBFarm->ID)
			throw new Exception("Farm not found");
			
		// Load Client Object
    	$Client = Client::Load($DBFarm->ClientID);
	}
	catch(Exception $e)
	{
		UI::Redirect("farms_view.php");
	}
	    
    if ($post_cancel)
		UI::Redirect("instances_view.php?farmid={$farminfo['id']}");
    
	if ($req_action == 'resend')
	{
		$message = $db->GetRow("SELECT * FROM messages WHERE instance_id=? AND messageid=?",array(
			$DBInstance->InstanceID, $req_message
		));
		
		if ($message)
		{
			$db->Execute("UPDATE messages SET isdelivered='0', delivery_attempts='0' WHERE id=?", array($message['id']));
			
			$msg = XMLMessageSerializer::Unserialize($message['message']);
			$DBInstance->SendMessage($msg);
						
			$okmsg = "Message successfully sent";
		}
		
		UI::Redirect("/scalr_i_messages.php?iid={$DBInstance->InstanceID}&farmid={$DBFarm->ID}");
	}
        
	$display["title"] = _("Instances&nbsp;&raquo;&nbsp;Scalr internal messaging (Instance: {$req_iid})");	
	$display["grid_query_string"] = "&farmid={$DBFarm->ID}&iid={$req_iid}";
	
	require("src/append.inc.php");
?>