<? 
	require("src/prepend.inc.php"); 
	
	if ($req_ami_id)
		$req_id = $db->GetOne("SELECT id FROM ami_roles WHERE ami_id=?", array($req_ami_id));
	
	$id = (int)$req_id;
	
	$display['title'] = _("Role information");
	
	// Get role information
	$role_info = $db->GetRow("SELECT * FROM ami_roles WHERE id=?", array($id));
	if (!$role_info)
		UI::Redirect("client_roles_view.php");
	
	// Check permissions
	if ($_SESSION['uid'] != 0 && $role_info['clientid'] != 0)
	{
		if (
			($role_info['roletype'] == ROLE_TYPE::SHARED && $role_info['clientid'] != $_SESSION['uid'] && $role_info['approval_state'] != APPROVAL_STATE::APPROVED) ||
			($role_info['roletype'] == ROLE_TYPE::CUSTOM && $role_info['clientid'] != $_SESSION['uid'])
		   )
		{
			UI::Redirect("client_roles_view.php");	
		}		
	}
		
	$role_info['type'] = ROLE_ALIAS::GetTypeByAlias($role_info["alias"]);
		
	if ($role_info['clientid'] != 0)
		$role_info['client'] = $db->GetRow("SELECT * FROM clients WHERE id=?", array($role_info['clientid']));
	
	$display['role'] = $role_info;
	
	
	if ($role_info['roletype'] == ROLE_TYPE::SHARED)
	{		
		$entity_name = COMMENTS_OBJECT_TYPE::ROLE;
		$redir_link = "role_info.php?id={$role_info['id']}";
		$object_owner = $role_info['clientid'];
		$object_name = $role_info['name'];
		
		$display["allow_moderation"] = ($role_info['clientid'] != 0) ? true : false;
		if ($display["allow_moderation"])
			$display["approval_state"] = $role_info['approval_state'];
			
		$display["comments_enabled"] = true;
		
	    include ("src/comments.inc.php");
	}
	
	$display["id"] = $id;
	
	require("src/append.inc.php"); 
?>