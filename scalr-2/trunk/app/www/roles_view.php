<?
	require_once('src/prepend.inc.php');
	$display['load_extjs'] = true;
	
	set_time_limit(3600);
	
	if ($req_clientid)
	{
	    $id = (int)$req_clientid;
	    $display['grid_query_string'] .= "&clientid={$clientid}";
	}
	   
	if ($req_type)
	{
		$type = preg_replace("/[^A-Za-z]+/", "", $req_type);
	    $display['grid_query_string'] .= "&type={$type}";
	}
	
	if ($req_id)
	{
		$id = (int)$req_id;
	    $display['grid_query_string'] .= "&id={$id}";
	}
	
	// Post actions
	if ($_POST && ($post_with_selected || $post_confirm))
	{
		if ($post_action == "delete" && count($post_id) != 0)
		{			
			if (!$post_confirm)
			{
				foreach($post_id as $roleid)
				{
					$info = $db->GetRow("SELECT * FROM roles WHERE id=? AND clientid=?", array($roleid, $_SESSION['uid']));
					$display['roles'][$info['id']] = $info;
				}
				$display['title'] = 'Roles removal confirmation';
				
				$Smarty->assign($display);
				$Smarty->display("role_remove_confirmation.tpl");
				exit();
			}
			else
			{
				// Delete users
				$i = 0;			
				foreach ((array)$post_id as $v)
				{
					try
					{
						$DBRole = DBRole::loadById($v);						
						if (($_SESSION['uid'] != 0 && $_SESSION['uid'] != $DBRole->clientId) || $DBRole->clientId == 0)
							continue;
					}
					catch(Exception $e)
					{
						continue;
					}

					if (!$DBRole->isUsed())
					{
						try {
							$DBRole->remove($post_remove_image[$DBRole->id]);
							$i++;
						}
						catch(Exception $e)
						{
							$err[] = sprintf(_("Cannot remove role %s: %s"), $DBRole->name, $e->getMessage());
						}
						
					}
					else
						$err[] = sprintf(_("Role %s cannot be removed. It's being used on your farms."), $DBRole->name);
				}
				
				if (count($err) == 0)
				{
				     $okmsg = sprintf(_("%s roles deleted"), $i);
				     UI::Redirect("roles_view.php");
				}
			}
		}
	}
	
	$display["title"] = _("Roles > View");
	
	require_once ("src/append.inc.php");
?>