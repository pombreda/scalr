<?php

	require("src/prepend.inc.php"); 
		
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon VPC&nbsp;&raquo;&nbsp;Create VPN gateway");	
		
	$AmazonVPCClient = AmazonVPC::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonVPCClient->SetAuthKeys(
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
	);
		
	// Get Avail zones
	$AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
		$_SESSION['aws_region'],
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
	);

    $avail_zones_resp = $AmazonEC2Client->DescribeAvailabilityZones();
    $display["avail_zones"] = array();    
   
    // Random assign zone
    array_push($display["avail_zones"], "");
    
    foreach ($avail_zones_resp->availabilityZoneInfo->item as $zone)
    {
    	if (stristr($zone->zoneState,'available'))
    		array_push($display["avail_zones"], (string)$zone->zoneName);
    }
	
	if($_POST)
	{	
		try
		{			
			$AmazonVPCClient->CreateVpnGateway(new CreateVpnGateway($req_type,$req_aws_availability_zone));							
		
			$okmsg = "VPN gateway created successfully";	
			UI::Redirect("aws_vpc_gateways_view.php");
		}
		catch(Exception $e)
		{			
			$err[] = $e->getMessage();//Can't create VPN gateway: %s
			UI::Redirect("aws_vpc_gateways_view.php");
		}
	}	
	
	require("src/append.inc.php"); 

?>