<? 
	require("src/prepend.inc.php"); 
	$display["title"] = "Shared roles&nbsp;&raquo;&nbsp;View";
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	
	if ($req_task == "add")
	{
		UI::Redirect("shared_roles_edit.php");
	}
	elseif ($req_task == "delete")
	{
	    $info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=? AND roletype='SHARED'", $req_ami_id);
	    if ($info)
	    {
	        $db->Execute("DELETE FROM ami_roles WHERE id='{$info['id']}'");
	        $db->Execute("DELETE FROM security_rules WHERE roleid='{$info['id']}'");
	        
	        $okmsg = "Role successfully unassigned from AMI";
	        UI::Redirect("shared_roles.php");
	    }
	    else 
	       $errmsg = "Role not found";
	}
	
	$AmazonEC2 = new AmazonEC2(
		APPPATH . "/etc/pk-".CONFIG::$AWS_KEYNAME.".pem", 
		APPPATH . "/etc/cert-".CONFIG::$AWS_KEYNAME.".pem", true
	);
	
	//Paging
	$paging = new SQLPaging();
	$paging->ItemsOnPage = 20;
	
	$sql = "SELECT * FROM ami_roles WHERE roletype='SHARED'";
	
	
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
		
		$row['farmsCount'] = $db->GetOne("SELECT COUNT(farmid) FROM farm_amis WHERE ami_id=?", array($row['ami_id']));
	}
	
	$display["page_data_options"] = array();
	
	$display["page_data_options_add"] = true;
	$display["page_data_options_add_querystring"] = "?task=add";
	
	require("src/append.inc.php"); 
	
?>