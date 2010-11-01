<? 
	require("src/prepend.inc.php"); 
	
	if ($req_ami_id)
		$req_id = $db->GetOne("SELECT id FROM roles WHERE ami_id=?", array($req_ami_id));
	
	$id = (int)$req_id;
	
	$display['title'] = _("Role information");
	
	// Get role information
	$role_info = $db->GetRow("SELECT * FROM roles WHERE id=?", array($id));
	if (!$role_info)
		UI::Redirect("roles_view.php");
	
	// Check permissions
	if (Scalr_Session::getInstance()->getClientId() != 0 && $role_info['clientid'] != 0)
	{
		if (
			($role_info['origin'] == ROLE_TYPE::SHARED && $role_info['client_id'] != Scalr_Session::getInstance()->getClientId() && $role_info['approval_state'] != APPROVAL_STATE::APPROVED) ||
			($role_info['origin'] == ROLE_TYPE::CUSTOM && $role_info['client_id'] != Scalr_Session::getInstance()->getClientId())
		   )
		{
			UI::Redirect("roles_view.php");	
		}		
	}
		
	if ($role_info['clientid'] != 0)
		$role_info['client'] = $db->GetRow("SELECT * FROM clients WHERE id=?", array($role_info['client_id']));
	
	$display['role'] = $role_info;
	
	
	if ($role_info['origin'] == ROLE_TYPE::SHARED)
	{		
		$entity_name = COMMENTS_OBJECT_TYPE::ROLE;
		$redir_link = "role_info.php?id={$role_info['id']}";
		$object_owner = $role_info['client_id'];
		$object_name = $role_info['name'];
		
		$display["allow_moderation"] = ($role_info['client_id'] != 0) ? true : false;
		if ($display["allow_moderation"])
			$display["approval_state"] = $role_info['approval_state'];
			
		$display["comments_enabled"] = true;
		
	    include ("src/comments.inc.php");
	}
	
	$display["id"] = $id;
	
	require("src/append.inc.php"); 
?>