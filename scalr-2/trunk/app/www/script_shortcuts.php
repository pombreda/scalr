<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	$display["title"] = _("Script shortcuts");
	
	if (isset($post_cancel))
		UI::Redirect("script_templates.php");
	
	if ($_POST && $post_with_selected)
	{
		foreach ($_POST['id'] as $farm_scriptid)
		{
			$db->Execute("DELETE FROM farm_role_scripts WHERE farmid IN (SELECT id FROM farms WHERE clientid=?) AND id=? AND ismenuitem='1'",
				array(Scalr_Session::getInstance()->getClientId(), $farm_scriptid)
			);
		}
		
		$okmsg = _("Selected shortcuts successfully removed");
		UI::Redirect("script_shortcuts.php");
	}
		
	require("src/append.inc.php");
?>