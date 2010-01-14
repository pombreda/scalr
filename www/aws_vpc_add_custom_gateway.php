<?php

	require("src/prepend.inc.php"); 
		
	if ($_SESSION["uid"] == 0)
	   UI::Redirect("index.php");
	
	$Client = Client::Load($_SESSION['uid']);
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon VPC&nbsp;&raquo;&nbsp;Create custom gateway");	
		
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
			if (!$req_ipAddress)
			{
				$errmsg = "Please enter IP address (e.g. 10.0.0.0/24)";
				UI::Redirect("aws_vpc_add_custom_gateway.php");
			}
			if (!$req_bgpAsn)
				{
					$errmsg = "Please enter Autonomous System Number (BGP ASN)";
					UI::Redirect("aws_vpc_add_custom_gateway.php");
				}
								
			$AmazonVPCClient->CreateCustomerGateway(new CreateCustomerGateway($req_type,$req_ipAddress,$req_bgpAsn));
			$okmsg = "Customer gateway created successfully";	
			UI::Redirect("aws_vpc_gateways_view.php");
		}
		catch(Exception $e)
		{			
			$err[] = sprintf(_("Cannot create customer gateway: %s"), $e->getMessage());
			UI::Redirect("aws_vpc_gateways_view.php");
		}
	}	
	
	require("src/append.inc.php"); 

?>