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
	
	$display["help"] = "This page lists your DNS zones<br /><br />
	<b>Role:</b> Instances of this role are creating domain A records in DNS zone.<br />
	<b>DNS Zone status</b> can be:<br />
	<span style='margin-left:12px;'>&bull; Active &mdash; Scalr nameservers are serving DNS zone and it is being updated dynamically</span><br />
	<span style='margin-left:12px;'>&bull; Inactive &mdash; Scalr nameservers  are not serving this domain</span><br />
	<span style='margin-left:12px;'>&bull; Pending delete &mdash; DNS zone marked for deletion</span><br />
	<span style='margin-left:12px;'>&bull; Pending create &mdash; DNS zone will be created soon</span><br />
	<span style='margin-left:12px;'>&bull; Pending update &mdash; DNS zone has changes that not been saved on nameservers yet</span><br />
	";
	
	require("src/append.inc.php");
?>