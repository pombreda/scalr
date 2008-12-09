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
    	$errmsg = "You cannot view statistics for terminated farm";
    	UI::Redirect("farms_view.php");
    }
        
	$display["title"] = "Farm&nbsp;&raquo;&nbsp;Statistics";
	$display["farminfo"] = $farminfo;

	$display["roles"] = $db->GetAll("SELECT farm_amis.*, ami_roles.name FROM farm_amis 
		INNER JOIN ami_roles ON ami_roles.ami_id = farm_amis.ami_id 
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
			if (CONFIG::$RRD_GRAPH_STORAGE_TYPE == RRD_STORAGE_TYPE::AMAZON_S3)
			{
				$expires = time()+720;
				$s3_path = "/".CONFIG::$RRD_GRAPH_STORAGE_PATH."/{$req_farmid}/{$role['name']}_{$watcher}.daily.gif";
				
				$signature = urlencode(base64_encode(hash_hmac("SHA1", "GET\n\n\n{$expires}\n{$s3_path}", CONFIG::$AWS_ACCESSKEY, 1)));
				$query_string = "?AWSAccessKeyId=".CONFIG::$AWS_ACCESSKEY_ID."&Expires={$expires}&Signature={$signature}";
			}
			
			$url = str_replace(array("%fid%","%rn%","%wn%"), array($req_farmid, $role['name'], $watcher), CONFIG::$RRD_STATS_URL);
			$role["images"][$watcher]['url'] = "{$url}daily.gif{$query_string}";
		}
		
		if ($role["id"] == "frm1")
			$display["tabs_list"][$role["id"]] = "Entire farm";
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