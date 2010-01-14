<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Shared roles&nbsp;&raquo;&nbsp;View");
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	
	if ($req_task == "add")
	{
		UI::Redirect("shared_roles_edit.php");
	}
	elseif ($req_task == "delete")
	{
	    $info = $db->GetRow("SELECT * FROM roles WHERE ami_id=? AND roletype=?", array($req_ami_id, ROLE_TYPE::SHARED));
	    if ($info)
	    {
	        $db->Execute("DELETE FROM roles WHERE id='{$info['id']}'");
	        $db->Execute("DELETE FROM security_rules WHERE roleid='{$info['id']}'");
	        
	        $okmsg = _("Role successfully unassigned from AMI");
	        UI::Redirect("shared_roles.php");
	    }
	    else 
	       $errmsg = _("Role not found");
	}
	
	$AmazonEC2 = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonEC2->SetAuthKeys(
		APPPATH . "/etc/pk-".CONFIG::$AWS_KEYNAME.".pem", 
		APPPATH . "/etc/cert-".CONFIG::$AWS_KEYNAME.".pem", 
		true
	);
	
	//Paging
	$paging = new SQLPaging();
	$paging->ItemsOnPage = 20;
	
	$sql = "SELECT * FROM roles WHERE roletype='".ROLE_TYPE::SHARED."' AND clientid='0' AND region='".$_SESSION['aws_region']."'";
		
	$paging->SetSQLQuery($sql);
	$paging->ApplyFilter($_POST["filter_q"], array("zone"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");
	
	$display["rows"] = $db->GetAll($paging->SQL);	

	// Generate DescribeImagesType object
	$DescribeImagesType = new DescribeImagesType();
	foreach ($display["rows"] as &$row)
	{
		$DescribeImagesType->imagesSet->item[] = array("imageId" => $row['ami_id']);
	}

	// get information about shared AMIs
	try
	{
		$response = $AmazonEC2->describeImages($DescribeImagesType);
	}
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();
	}
	
	foreach ($display["rows"] as &$row)
	{
		if ($response && $response->imagesSet && $response->imagesSet->item)
		{
			if (!is_array($response->imagesSet->item))
				$response->imagesSet->item = array($response->imagesSet->item);
			
			foreach($response->imagesSet->item as $item)
			{
				if ($item->imageId == $row["ami_id"])
				{
					$row["imageState"] = $item->imageState;
					$row["imageOwnerId"] = $item->imageOwnerId;
					break;
				}
			}
		}
		
		$row["type"] = ROLE_ALIAS::GetTypeByAlias($row['alias']);
		$row['farmsCount'] = $db->GetOne("SELECT COUNT(farmid) FROM farm_roles WHERE ami_id=?", array($row['ami_id']));
	}
	
	$display["page_data_options"] = array();
	
	$display["page_data_options_add"] = true;
	$display["page_data_options_add_querystring"] = "?task=add";
	
	require("src/append.inc.php"); 
	
?>