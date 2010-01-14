<? 
	require(dirname(__FILE__)."/../src/prepend.inc.php"); 
	
	header("Content-type: image/gif");
	
	$wus_info = $db->GetRow("SELECT * FROM wus_info WHERE id=?", array($req_id));
	if ($wus_info && crc32($wus_info['company']) == $req_r)
	{
		$dest = dirname(__FILE__)."/../cache/wus_logos/{$wus_info['clientid']}.gif";
		if (file_exists($dest))
		{
			readfile($dest);
			exit();
		}
	}
	
	readfile("images/c_no_logo.gif");
	exit();
?>