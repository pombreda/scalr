<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	$display["title"] = "Reserved Instances Offerings";
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	
	if ($req_action == 'purchase')
	{
		$Client = Client::Load($_SESSION['uid']);

		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		
		var_dump($_POST);
	}
	
	require("src/append.inc.php");
?>