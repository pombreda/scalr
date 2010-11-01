<?php

	require("src/prepend.inc.php"); 
		
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon VPC&nbsp;&raquo;&nbsp;Create VPN connection");	
		
	$AmazonVPCClient = AmazonVPC::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonVPCClient->SetAuthKeys(
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
	);


	$display["vpnGatewayId"] = array();
	$display["customerGatewayId"] = array();
	
	
	// Get VPN gateways list
	$aws_response = $AmazonVPCClient->DescribeVpnGateways();		
		$rows = (array)$aws_response->vpnGatewaySet;
		
		if ($rows["item"] instanceof stdClass)
			$rows["item"] = array($rows["item"]); // convert along  record to array		
						
		foreach ($rows['item'] as $row)
		{
    		if (stristr($row->state,'available'))    		
    			array_push($display["vpnGatewayId"], (string)$row->vpnGatewayId);
    	}
				
	// Get Customer gateways list
	$aws_response = $AmazonVPCClient->DescribeCustomerGateways();		
		$rows = (array)$aws_response->customerGatewaySet;
		
		if ($rows["item"] instanceof stdClass)
			$rows["item"] = array($rows["item"]); 		
					
		foreach ($rows['item'] as $row)					
		{
    		if (stristr($row->state,'available'))    		
    			array_push($display["customerGatewayId"], (string)$row->customerGatewayId);
    	}
	
	if($_POST)
	{	
		try
		{	
			if (!$req_aws_vpc_customer_gateways)
			{
				$errmsg = "Please create customer gateway first";
				UI::Redirect("aws_vpc_gateways_view.php");
			}		
			if (!$req_aws_vpc_vpn_gateways)
			{
				$errmsg = "Please create VPN gateway first";
			UI::Redirect("aws_vpc_gateways_view.php");
			}
								
			$AmazonVPCClient->CreateVpnConnection(new CreateVpnConnection($req_aws_vpc_customer_gateways,$req_aws_vpc_vpn_gateways,"ipsec.1"));
			$okmsg = "Vpn connection created successfully";	
			UI::Redirect("aws_vpc_gateways_view.php");
		}
		catch(Exception $e)
		{
			$err[] = $e->getMessage();//Can't create VPN connection with %s and %s gateways: %s
			UI::Redirect("aws_vpc_gateways_view.php");
		}
	}	
	
	require("src/append.inc.php"); 

?>