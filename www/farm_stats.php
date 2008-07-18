<?
    require("src/prepend.inc.php"); 
    
    $display["experimental"] = true;
    
    if ($_SESSION['uid'] == 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));

    if (!$farminfo)
        UI::Redirect("farms_view.php");
        
    if ($farminfo["status"] != 1)
    {
    	$errmsg = "You cannot view statistics for terminated farm";
    	UI::Redirect("farms_view.php");
    }
        
	$display["title"] = "Farm&nbsp;&raquo;&nbsp;Statistics";
	$display["farminfo"] = $farminfo;

	if (!$req_role)
	{
		$display["roles"] = $db->GetAll("SELECT farm_amis.*, CONCAT('Statistics for role: ', ami_roles.name) as vname, ami_roles.name FROM farm_amis 
			INNER JOIN ami_roles ON ami_roles.ami_id = farm_amis.ami_id 
			WHERE farmid=?", array($farminfo['id'])
		);
		
		array_push($display["roles"], array("name" => "_FARM", "vname" => "Farm statistics"));
	}
	else
		$display["roles"][] = array("vname" => "Statistics for role: {$req_role}", "name" => "{$req_role}");
	
	$display["roles"] = array_reverse($display["roles"]);
	
	foreach ($display["roles"] as &$role)
	{
		if (!file_exists(APPPATH."/data/{$farminfo['id']}/graphics/{$role['name']}/cpu.gif") ||
			!file_exists(APPPATH."/data/{$farminfo['id']}/graphics/{$role['name']}/la.gif") ||
			!file_exists(APPPATH."/data/{$farminfo['id']}/graphics/{$role['name']}/mem.gif") ||
			!file_exists(APPPATH."/data/{$farminfo['id']}/graphics/{$role['name']}/net.gif")
		)
		{
			$role["not_avail"] = true;
		}
	}
	
	require_once("src/append.inc.php");
?>