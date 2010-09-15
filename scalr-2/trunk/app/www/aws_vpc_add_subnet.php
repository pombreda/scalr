<?php

	require("src/prepend.inc.php"); 
		
	if ($_SESSION["uid"] == 0)
	   UI::Redirect("index.php");
	
	$Client = Client::Load($_SESSION['uid']);
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon VPC&nbsp;&raquo;&nbsp;Create Subnet");	
		
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
			if (!$req_id)
			{
				$errmsg = "Please select Virtual Private Cloud to create subnet";
				UI::Redirect("aws_vpc_view.php");
			}		
			if (!$req_subnet)
			{
				$errmsg = "Please enter  CIDR (e.g. 10.0.0.0/24)";
			UI::Redirect("aws_vpc_add_subnet.php");
			}
								
			$AmazonVPCClient->CreateSubnet(new CreateSubnet($req_id,$req_subnet,$req_aws_availability_zone));
			$okmsg = "Subnet created successfully";	
			UI::Redirect("aws_vpc_subnets_view.php");
		}
		catch(Exception $e)
		{			
			$err[] = $e->getMessage(); //Can't create Subnet in this cloud: 
			UI::Redirect("aws_vpc_view.php");
		}
	}	
	
	require("src/append.inc.php"); 

?>