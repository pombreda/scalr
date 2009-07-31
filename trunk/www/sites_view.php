<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	$display["title"] = "Applications&nbsp;&raquo;&nbsp;View";
		
	if ($req_farmid)
	{
	    $id = (int)$req_farmid;
	    $display['grid_query_string'] .= "&farmid={$id}";
	}
	
	if ($req_clientid)
	{
	    $id = (int)$req_clientid;
	    $display['grid_query_string'] .= "&clientid={$id}";
	}
	
	if ($req_ami_id)
	    $display['grid_query_string'] .= "&ami_id={$req_ami_id}";
		
	$display["help"] = "This page lists your applications<br /><br />
	<b>Role:</b> Instances of this role are creating domain A records in application DNS zone.<br />
	<b>DNS Zone status</b> can be:<br />
	<span style='margin-left:12px;'>&bull; Active &mdash; Scalr nameservers are serving DNS zone and it is being updated dynamically</span><br />
	<span style='margin-left:12px;'>&bull; Inactive &mdash; Scalr nameservers  are not serving this domain</span><br />
	<span style='margin-left:12px;'>&bull; Pending delete &mdash; DNS zone marked for deletion</span><br />
	<span style='margin-left:12px;'>&bull; Pending create &mdash; DNS zone will be created soon</span><br />
	";
	
	require("src/append.inc.php");
?>