<?
	require_once('src/prepend.inc.php');
    	    
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
	
		
    $AmazonEC2Client = new AmazonEC2($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);
		
	$paging = new SQLPaging();
	$paging->ItemsOnPage = 20;

	// Rows
	$response = $AmazonEC2Client->DescribeAddresses();
			
	$rowz = $response->addressesSet->item;
		
	if ($rowz instanceof stdClass)
		$rowz = array($rowz);
	
	foreach ($rowz as $pk=>$pv)
	{
		$doadd = true;
		$item = $pv;
		$info = $db->GetRow("SELECT * FROM elastic_ips WHERE ipaddress=?", array($pv->publicIp));
		if ($info)
		{
			$item->dbInfo = $info;
			$item->farmId = $info['farmid'];
		}
		else
		{
			$dbinstance = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($item->instanceId));
			if ($dbinstance)
			{
				$item->dbInstance = $dbinstance;
				$item->farmId = $dbinstance['farmid'];
			}
		}
		
		if ($item->farmId)
			$item->farmName = $db->GetOne("SELECT name FROM farms WHERE id=?", array($item->farmId));
		
		// Filter by farm id
		if ($req_farmid)
		{
			if ($item->farmId != $req_farmid)
				$doadd = false;
			else
				$paging->AddURLFilter("farmid", $req_farmid);
		}
		
		// Filter by role
		if ($req_role)
		{
			if ($item->dbInstance['role_name'] != $req_role && $item->dbInfo['role_name'])
				$doadd = false;
			else
				$paging->AddURLFilter("role", $req_role);
		}
		
		if ($doadd)
			$rowz1[] = $item;
	}
	
	$rowz = $rowz1;
	
	$paging->Total = count($rowz); 
	
	$paging->ParseHTML();
	
	$display["rows"] = (count($rowz) > CONFIG::$PAGING_ITEMS) ? array_slice($rowz, ($paging->PageNo-1) * CONFIG::$PAGING_ITEMS, CONFIG::$PAGING_ITEMS) : $rowz;
	$display["paging"] = $paging->GetHTML("inc/paging.tpl");
	$display["title"] = "Elastic IPs > Manage";
	$display["farmid"] = $req_farmid;
	
	$display["page_data_options"] = array();
	
	require_once ("src/append.inc.php");
?>