<?php
	require_once('src/prepend.inc.php');

	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	
	if (!$req_iid)
		UI::Redirect("aws_ec2_instances_view.php");
		
	$Client = Client::Load($_SESSION["uid"]);
		
	$display['title'] = 'Spot instance details';
	
	$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region']));
	$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate); 

	$instainceInfo = $AmazonEC2Client->DescribeInstances($req_iid);

	if ($instainceInfo->reservationSet->item->instancesSet->item === null)
	{
		$err[] = ("The requested instance not found");
		UI::Redirect("aws_ec2_instances_view.php");
	}

	$display['instance'] = $instainceInfo->reservationSet->item->instancesSet->item;

	require_once ("src/append.inc.php");
?>