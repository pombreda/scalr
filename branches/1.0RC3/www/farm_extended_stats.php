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
        
	$display["title"] = "Farm&nbsp;&raquo;&nbsp;Extended statistics";
	$display["farminfo"] = $farminfo;
	
	$Reflect = new ReflectionClass("GRAPH_TYPE");
    $types = $Reflect->getConstants();
    foreach($types as $type)
    {
    	if (CONFIG::$RRD_GRAPH_STORAGE_TYPE == RRD_STORAGE_TYPE::AMAZON_S3)
		{
			$expires = time()+720;
			$s3_path = "/".CONFIG::$RRD_GRAPH_STORAGE_PATH."/{$farminfo['id']}/{$get_role}_{$get_watcher}.{$type}.gif";
			
			$signature = urlencode(base64_encode(hash_hmac("SHA1", "GET\n\n\n{$expires}\n{$s3_path}", CONFIG::$AWS_ACCESSKEY, 1)));
			$query_string = "?AWSAccessKeyId=".CONFIG::$AWS_ACCESSKEY_ID."&Expires={$expires}&Signature={$signature}";
		}
		
		$url = str_replace(array("%fid%","%rn%","%wn%"), array($farminfo['id'], $get_role, $get_watcher), CONFIG::$RRD_STATS_URL);
		$display["images"][$type] = "{$url}{$type}.gif{$query_string}";	
    }
	
	require_once("src/append.inc.php");
?>