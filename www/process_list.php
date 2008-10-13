<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] != 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION["uid"]));
    else
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));

    $instance = $db->GetRow("SELECT * FROM farm_instances WHERE farmid=? AND instance_id=?",
    	array($farminfo['id'], $req_iid)
    );    
    
	if (!$instance || !$farminfo)
	{
		$errmsg = "Instance not running.";
		UI::Redirect("instances_view.php?farmid={$req_farmid}");
	}
    
    $display["title"] = "Running processes on instance {$instance['instance_id']} ({$instance['external_ip']})";
    
    $display["iid"] = $instance['instance_id'];
    
	require("src/append.inc.php"); 
?>