<?php

	require("src/prepend.inc.php"); 
		
	if ($_SESSION["uid"] == 0)
	   UI::Redirect("index.php");
	
	$Client = Client::Load($_SESSION['uid']);
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon VPC&nbsp;&raquo;&nbsp;Create VPN gateway");	
		
	$AmazonVPCClient = AmazonVPC::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonVPCClient->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		
	// Get Avail zones
	$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
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