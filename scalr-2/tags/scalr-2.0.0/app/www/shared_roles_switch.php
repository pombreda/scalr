<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Shared roles&nbsp;&raquo;&nbsp;View");
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
		
	if ($_POST)
	{		
		$db->BeginTrans();
		try
		{
			$db->Execute("UPDATE roles SET ami_id=? WHERE ami_id=?", array($post_new_ami_id, $post_ami_id));
		}
		catch(Exception $e)
		{
			$db->RollbackTrans();
		    throw new ApplicationException($e->getMessage(), E_ERROR);
		}
		
		$db->CommitTrans();
		$okmsg = _("Role successfully switched to new AMI");
		UI::Redirect("shared_roles.php");
	}

	$rinfo = $db->GetRow("SELECT * FROM roles WHERE ami_id=?", array($req_ami_id));
	
	// Filter by our accountid
	$DescribeImagesType = new DescribeImagesType();
	$DescribeImagesType->ownersSet = array("item" => array("owner" => CONFIG::$AWS_ACCOUNTID));


	$AmazonEC2 = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($rinfo['region'])); 
	$AmazonEC2->SetAuthKeys(
		APPPATH . "/etc/pk-".CONFIG::$AWS_KEYNAME.".pem", 
		APPPATH . "/etc/cert-".CONFIG::$AWS_KEYNAME.".pem", 
		true
	);
	
	// Rows
	$response = $AmazonEC2->describeImages($DescribeImagesType);
	$rowz = $response->imagesSet->item;
	$rows = array();
	foreach ($rowz as $pk=>$pv)
	{
		$rowz[$pk]->isPublicStr = $rowz[$pk]->isPublic ? "true" : "false";
		if (!$db->GetOne("SELECT name FROM roles WHERE ami_id=?", $rowz[$pk]->imageId))
			$rows[] = $rowz[$pk];
	}
	
	$display["rows"] = $rows;
	$display["ami_id"] = $req_ami_id;
	
	require("src/append.inc.php"); 
	
?>