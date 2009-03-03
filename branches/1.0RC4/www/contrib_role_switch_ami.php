<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Switch role to new AMI");

	$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($req_ami_id));
	if ($ami_info['roletype'] != ROLE_TYPE::SHARED || $ami_info['approval_state'] != APPROVAL_STATE::PENDING)
		UI::Redirect("client_roles_view.php");
	
	if ($_SESSION['uid'] != 0 && $_SESSION['uid'] != $ami_info['clientid'])
		UI::Redirect("client_roles_view.php");
	
	if ($_POST)
	{
		$new_ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($req_new_ami_id));
		if ($new_ami_info['roletype'] != ROLE_TYPE::CUSTOM)
			UI::Redirect("client_roles_view.php");
		
		if ($_SESSION['uid'] != 0 && $_SESSION['uid'] != $new_ami_info['clientid'])
			UI::Redirect("client_roles_view.php");
			
		$chk = $db->GetOne("SELECT id FROM farm_amis WHERE ami_id=?", array($new_ami_info['ami_id']));
		if ($chk)
			UI::Redirect("client_roles_view.php");
			
		if ($post_confirm == 1)
		{
			$db->BeginTrans();
			try
			{
				$db->Execute("UPDATE ami_roles SET ami_id=? WHERE id=?", array($new_ami_info['ami_id'], $ami_info['id']));
				$db->Execute("DELETE FROM ami_roles WHERE id=?", array($new_ami_info['id']));
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
		   	 	throw new ApplicationException($e->getMessage(), E_ERROR);
			}
		
			$db->CommitTrans();
			
			UI::Redirect("role_info.php?id={$ami_info['id']}");
		}
		
		$display['new_ami_id'] = $req_new_ami_id;
		$display['new_role_name'] = $ami_info['name'];
		$display['old_role_name'] = $new_ami_info['name'];
	}
	   
	$rows = $db->GetAll("SELECT * FROM ami_roles WHERE clientid=? AND roletype=? AND iscompleted='1'", 
		array($ami_info['clientid'], ROLE_TYPE::CUSTOM)
	);

	foreach ($rows as $row)
	{
		$chk = $db->GetOne("SELECT id FROM farm_amis WHERE ami_id=?", array($row['ami_id']));
		if (!$chk)
			$display["rows"][] = $row;
	}
	
	$display["ami_id"] = $req_ami_id;
	
	require("src/append.inc.php"); 
	
?>