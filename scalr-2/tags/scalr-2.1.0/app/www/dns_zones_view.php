<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	$display["title"] = "DNS Zones&nbsp;&raquo;&nbsp;View";
	
	if ($req_clientid)
	{
	    $id = (int)$req_clientid;
	    $display['grid_query_string'] .= "&clientid={$id}";
	}
	
	if ($req_farm_roleid)
	{
	    $id = (int)$req_farm_roleid;
		$display['grid_query_string'] .= "&farm_roleid={$id}";
	}

	if ($req_farmid)
	{
	    $id = (int)$req_farmid;
		$display['grid_query_string'] .= "&farmid={$id}";
	}
	
	require("src/append.inc.php");
?>