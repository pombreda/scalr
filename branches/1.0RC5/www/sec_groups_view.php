<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
        
    $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonEC2Client->SetAuthKeys($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);
                        
	$display["title"] = "Roles&nbsp;&raquo;&nbsp;Security groups";
		
	if ($_POST)
	{
		if ($post_action == 'delete')
		{
			foreach ($post_delete as $group_name)
			{
				try
				{
					$AmazonEC2Client->DeleteSecurityGroup($group_name);
					$i++;
				}
				catch(Exception $e)
				{
					$err[] = sprintf(_("Cannot delete group %s: %s"), $group_name, $e->getMessage());
				}
			}
			
			if ($i > 0)
				$okmsg = sprintf(_("%s secutity group(s) successfully removed"), $i);
				
			UI::Redirect("sec_groups_view.php");
		}
	}
	
	if (isset($req_show_all))
	{
		if ($req_show_all == 'true')
			$_SESSION['sg_show_all'] = true;
		else
			$_SESSION['sg_show_all'] = false;
	}
	
	//Paging
	$paging = new Paging();
	$paging->ItemsOnPage = 20;

	// Rows
	$response = $AmazonEC2Client->DescribeSecurityGroups();
	
	$rows = $response->securityGroupInfo->item;
	foreach ($rows as $row)
	{
		// Show only scalr security groups
		if (stristr($row->groupName, CONFIG::$SECGROUP_PREFIX) || $_SESSION['sg_show_all'])
			$rowz[] = $row;
	}
	
	if ($rowz instanceof stdClass)
		$rowz = array($rowz);
		
	$paging->Total = count($rowz);
	
	$paging->ParseHTML();
	
	$display["rows"] = (count($rowz) > CONFIG::$PAGING_ITEMS) ? array_slice($rowz, ($paging->PageNo-1) * CONFIG::$PAGING_ITEMS, CONFIG::$PAGING_ITEMS) : $rowz;
	
	$display["paging"] = $paging->GetHTML("inc/paging.tpl");
	
	$display['filter'] = $Smarty->fetch("inc/sec_groups_filter.tpl");
	
	$display["page_data_options"] = array(
		array("name" => _("Delete"), "action" => "delete"),
	);
	$display["page_data_options_add"] = false;
	
	require("src/append.inc.php"); 
	
?>