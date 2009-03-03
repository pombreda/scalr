<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = _("Script shortcuts");
	
	if (isset($post_cancel))
		UI::Redirect("script_templates.php");
	
	if ($_POST)
	{
		foreach ($_POST['delete'] as $farm_scriptid)
		{
			$db->Execute("DELETE FROM farm_role_scripts WHERE farmid IN (SELECT id FROM farms WHERE clientid='{$_SESSION['uid']}') AND id=? AND ismenuitem='1'",
				array($farm_scriptid)
			);
		}
		
		$okmsg = _("Selected shortcuts successfully removed");
		UI::Redirect("script_shortcuts.php");
	}
		
	$paging = new SQLPaging();
		
    $sql = "select farm_role_scripts.*, scripts.name as scriptname from farm_role_scripts 
    INNER JOIN scripts ON scripts.id = farm_role_scripts.scriptid
    WHERE ismenuitem='1' AND farmid IN (SELECT id FROM farms WHERE clientid='{$_SESSION['uid']}')";
    
	$paging->SetSQLQuery($sql);
	$paging->ApplyFilter($_POST["filter_q"], array("scripts.name"));
	$paging->AdditionalSQL = "ORDER BY id DESC";	
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");

	// Rows
	$display["rows"] = $db->GetAll($paging->SQL);	
	foreach ($display["rows"] as &$row)
	{
		$row['farmname'] = $db->GetOne("SELECT name FROM farms WHERE id=?", array($row['farmid']));
		if ($row['ami_id'])
			$row['rolename'] = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($row['ami_id']));
	}
	
	$display["page_data_options"] = array(
		array("name" => _("Delete"), "action" => "delete"),
	);
		
	require("src/append.inc.php");
?>