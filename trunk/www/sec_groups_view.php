<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
        
    $AmazonEC2Client = new AmazonEC2($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);
                        
	$display["title"] = "Roles&nbsp;&raquo;&nbsp;Security groups";
		
	//Paging
	$paging = new Paging();
	$paging->ItemsOnPage = 20;

	// Rows
	$response = $AmazonEC2Client->DescribeSecurityGroups();
	
	$rows = $response->securityGroupInfo->item;
	foreach ($rows as $row)
	{
		// Show only scalr security groups
		if (stristr($row->groupName, CONFIG::$SECGROUP_PREFIX))
			$rowz[] = $row;
	}
	
	if ($rowz instanceof stdClass)
		$rowz = array($rowz);
		
	$paging->Total = count($rowz);
	
	$paging->ParseHTML();
	
	$display["rows"] = (count($rowz) > CONFIG::$PAGING_ITEMS) ? array_slice($rowz, ($paging->PageNo-1) * CONFIG::$PAGING_ITEMS, CONFIG::$PAGING_ITEMS) : $rowz;
	
	$display["paging"] = $paging->GetHTML("inc/paging.tpl");
	
	$display["page_data_options"] = false;
	$display["page_data_options_add"] = false;
	
	require("src/append.inc.php"); 
	
?>