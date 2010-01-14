<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	// Load Client Object
    $Client = Client::Load($_SESSION['uid']);
    
    $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($req_region)); 
	$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);

	$MonitorInstanceType = new MonitorInstancesType();
    $MonitorInstanceType->AddInstance($req_iid);
	
    if ($req_action == "Disable")
    {	
    	$res = $AmazonEC2Client->UnmonitorInstances($MonitorInstanceType);    	
    	$okmsg = "Disabling Cloudwatch monitoring for instance {$req_iid}. It could take a few minutes.";	
    }
    elseif ($req_action == "Enable")
    {
    	$AmazonEC2Client->MonitorInstances($MonitorInstanceType);
    	$okmsg = "Enabling Cloudwatch monitoring for instance {$req_iid}. It could take a few minutes.";
    }
	
    UI::Redirect("/aws_ec2_instance_info.php?iid={$req_iid}&region={$req_region}");
    
	require("src/append.inc.php"); 
?>