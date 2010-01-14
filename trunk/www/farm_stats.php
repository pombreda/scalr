<?
    require("src/prepend.inc.php"); 
        
    if ($_SESSION['uid'] == 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));

    if (!$farminfo)
        UI::Redirect("farms_view.php");
        
    if ($farminfo["status"] != 1)
    {
    	$errmsg = _("You cannot view statistics for terminated farm");
    	UI::Redirect("farms_view.php");
    }
        
	$display["title"] = _("Farm&nbsp;&raquo;&nbsp;Statistics");
	$display["farminfo"] = $farminfo;

	$display["roles"] = $db->GetAll("SELECT farm_roles.*, roles.name FROM farm_roles 
		INNER JOIN roles ON roles.ami_id = farm_roles.ami_id 
		WHERE farmid=?", array($farminfo['id'])
	);
		
	array_push($display["roles"], array("name" => "_FARM", "id" => "frm1"));

	$display["roles"] = array_reverse($display["roles"]);
	
	
	$watchers = array("MEMSNMP", "CPUSNMP", "NETSNMP", "LASNMP");
	foreach ($display["roles"] as &$role)
	{
		if ($role['name'] == $req_role)
			$selected_role = $role['id'];
		
		foreach ($watchers as $watcher)
		{
			$role["images"][$watcher]['params'] = array(
				"farmid"	=> $req_farmid, 
				"role_name" => $role['name'], 
				"watcher"	=> $watcher,
				"type"		=> 'daily',
				"farmid"	=> $farminfo['id']
			);
			
			$role["images"][$watcher]['hash'] = md5(implode("", $role["images"][$watcher]['params']));
		}
		
		if ($role["id"] == "frm1")
			$display["tabs_list"][$role["id"]] = _("Entire farm");
		else
			$display["tabs_list"][$role["id"]] = $role["name"];
	}
	
	/**
     * Tabs
     */
	if (!$req_role)
		$display["selected_tab"] = "frm1";
	else
		$display["selected_tab"] = $selected_role;
	
	require_once("src/append.inc.php");
?>