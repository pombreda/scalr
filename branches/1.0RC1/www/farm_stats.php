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
		$display["roles"] = $db->GetAll("SELECT farm_amis.*, ami_roles.name FROM farm_amis 
			INNER JOIN ami_roles ON ami_roles.ami_id = farm_amis.ami_id 
			WHERE farmid=?", array($farminfo['id'])
		);
		
		array_push($display["roles"], array("name" => "_FARM"));
	}
	else
		$display["roles"][] = array("name" => "{$req_role}");
	
	$display["roles"] = array_reverse($display["roles"]);
	
	
	$watchers = array("cpu", "mem", "la", "net");
	foreach ($display["roles"] as &$role)
	{
		foreach ($watchers as $watcher)
		{
			$filename = APPPATH."/data/{$farminfo['id']}/".strtoupper($watcher)."SNMP/{$role['name']}/{$watcher}.rrd";
			if (!file_exists($filename))
			{
				$role["not_avail"] = true;
				break;
			}
		}
	}
	
	require_once("src/append.inc.php");
?>