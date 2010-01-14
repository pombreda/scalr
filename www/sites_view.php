<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	$display["title"] = "Websites&nbsp;&raquo;&nbsp;View";
		
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
		
	$display["help"] = "This page lists your websites<br /><br />
	<b>Role:</b> Instances of this role are creating domain A records in application DNS zone.<br />
	<b>DNS Zone status</b> can be:<br />
	<span style='margin-left:12px;'>&bull; Active &mdash; Scalr nameservers are serving DNS zone and it is being updated dynamically</span><br />
	<span style='margin-left:12px;'>&bull; Inactive &mdash; Scalr nameservers  are not serving this domain</span><br />
	<span style='margin-left:12px;'>&bull; Pending delete &mdash; DNS zone marked for deletion</span><br />
	<span style='margin-left:12px;'>&bull; Pending create &mdash; DNS zone will be created soon</span><br />
	";
		
	if ($_SESSION["uid"] != 0)
	{
	   $sql = "select zone,id,ami_id,role_name,status,farmid FROM zones WHERE clientid='{$_SESSION['uid']}'";

		if ($req_farmid)
		{
		    $id = (int)$req_farmid;
		    $sql .= " AND farmid='{$id}'";
		}
		
		if ($req_clientid)
		{
		    $id = (int)$req_clientid;
		    $sql .= " AND clientid='{$id}'";
		}
		
		if ($req_ami_id)
		{
		    $ami_id = $db->qstr($req_ami_id);	    
		    $sql .= " AND ami_id={$ami_id}";
		}
		
		$zones = $db->GetAll($sql);
		$zones_str = "";
		foreach ($zones as $zone)
		{
			@exec("whois {$zone['zone']}", &$o, $t);
			if ($t == 0)
			{
				$s = implode("\n", $o);
				preg_match_all("/(ns([0-9]+).scalr.net)/si", $s, $m);
				if ($m[2][0]+$m[2][1] == 3 && !$m[2][2])
				{
					$zones_str .= "{$zone['zone']},";
				}
			}
		}
		
		if ($zones_str)
		{
			$zones_str = trim($zones_str, ",");
			$display['warnmsg'] = "The following domain name(s): {$zones_str} haven't ns3.scalr.net or/and ns4.scalr.net in their nameservers list. Pleasee add them for better failover.";
		}
	}
	
	if ($_SESSION["uid"] != 0)
	{
	   $sql = "select zone,id,ami_id,role_name,status,farmid FROM zones WHERE clientid='{$_SESSION['uid']}'";

		if ($req_farmid)
		{
		    $id = (int)$req_farmid;
		    $sql .= " AND farmid='{$id}'";
		}
		
		if ($req_clientid)
		{
		    $id = (int)$req_clientid;
		    $sql .= " AND clientid='{$id}'";
		}
		
		if ($req_ami_id)
		{
		    $ami_id = $db->qstr($req_ami_id);	    
		    $sql .= " AND ami_id={$ami_id}";
		}
		
		$zones = $db->GetAll($sql);
		$zones_str = "";
		foreach ($zones as $zone)
		{
			@exec("whois {$zone['zone']}", &$o, $t);
			if ($t == 0)
			{
				$s = implode("\n", $o);
				preg_match_all("/(ns([0-9]+).scalr.net)/si", $s, $m);
				if ($m[2][0]+$m[2][1] == 3 && !$m[2][2])
				{
					$zones_str .= "{$zone['zone']},";
				}
			}
		}
		
		if ($zones_str)
		{
			$zones_str = trim($zones_str, ",");
			$display['warnmsg'] = "The following domain name(s): {$zones_str} haven't ns3.scalr.net or/and ns4.scalr.net in their nameservers list. Pleasee add them for better failover.";
		}
	}
	
	require("src/append.inc.php");
?>