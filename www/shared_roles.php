<? 
	require("src/prepend.inc.php"); 
	$display["title"] = "Shared roles&nbsp;&raquo;&nbsp;View";
	
	if ($_SESSION["uid"] != 0)
	   CoreUtils::Redirect("index.php");
	
	if ($req_task == "delete")
	{
	    $info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=? AND roletype='SHARED'", $req_ami_id);
	    if ($info)
	    {
	        $db->Execute("DELETE FROM ami_roles WHERE id='{$info['id']}'");
	        $db->Execute("DELETE FROM security_rules WHERE roleid='{$info['id']}'");
	        
	        $okmsg = "Role successfully unassigned from AMI";
	        CoreUtils::Redirect("shared_roles.php");
	    }
	    else 
	       $errmsg = "Role not found";
	}
	
	define("CF_PAGING_ITEMS", 20);

	//Paging
	$paging = new Paging();
	$paging->ItemsOnPage = 20;
	
	// Filter by our accountid
	$DescribeImagesType = new DescribeImagesType();
	$DescribeImagesType->ownersSet = array("item" => array("owner" => CF_AWS_ACCOUNTID));


	$AmazonEC2 = new AmazonEC2(
            APPPATH . "/etc/pk-{$cfg['aws_keyname']}.pem", 
            APPPATH . "/etc/cert-{$cfg['aws_keyname']}.pem");
	// Rows
	$response = $AmazonEC2->describeImages($DescribeImagesType);
	$rowz = $response->imagesSet->item;
	$rows = array();
	foreach ($rowz as $pk=>$pv)
	{
		$rowz[$pk]->isPublicStr = $rowz[$pk]->isPublic ? "true" : "false";
		$rowz[$pk]->roleName = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", $rowz[$pk]->imageId);
		
		if ($rowz[$pk]->roleName)
		{
            if ($db->GetOne("SELECT roletype FROM ami_roles WHERE ami_id=?", $rowz[$pk]->imageId) == "SHARED")
                $rows[] = $rowz[$pk];
		}
		else 
            $rows[] = $rowz[$pk];
	}
	
	$paging->Total = count($rows); 
	
	$paging->ParseHTML();
	// Slice 
	$display["rows"] = (count($rows) > CF_PAGING_ITEMS) ? array_slice($rows, $paging->PageNo * CF_PAGING_ITEMS, CF_PAGING_ITEMS) : $rows;
	
	#$display["filter"] = $paging->GetFilterHTML("$tplpath/table_filter.tpl");
	$display["paging"] = $paging->GetHTML("paging.tpl");
	
	$display["page_data_options"] = array(
	//array("action" => "launch", "name" => "Launch new instance")
	);
	
	$display["page_data_options_add"] = false;
	//$display["form_action"] = "amis_view_farm.php";
	
	require("src/append.inc.php"); 
	
?>