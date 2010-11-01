<?php

	require("src/prepend.inc.php"); 
		
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon VPC&nbsp;&raquo;&nbsp;Associate DHCP options");	
		
	$AmazonVPCClient = AmazonVPC::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonVPCClient->SetAuthKeys(
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
	);

	$display["dhcpOptionsId"] = array();

	// Get VPN gateways list
	$aws_response = $AmazonVPCClient->DescribeDhcpOptions();		
		$rows = (array)$aws_response->dhcpOptionsSet;

	if ($rows["item"] instanceof stdClass)
		$rows["item"] = array($rows["item"]); // convert along  record to array		
	
	foreach ($rows['item'] as $row)	
    	array_push($display["dhcpOptionsId"], (string)$row->dhcpOptionsId);
    
	if($_POST)
	{
		try
		{	
			if (!$req_aws_dhcp)
			{
				$errmsg = "Please, select available VPC";
				UI::Redirect("aws_vpc_attach_dhcp.php");
			}
													
			$AmazonVPCClient->AssociateDhcpOptions(new AssociateDhcpOptions($req_id,$req_aws_dhcp));
			
			$okmsg = "DHCP options set associated successfully";	
			UI::Redirect("aws_vpc_view.php");
		}
		catch(Exception $e)
		{			
			$err[] = $e->getMessage(); //Can't associate  DHCP options set %s with VPC %s : %s
			UI::Redirect("aws_vpc_view.php");
		}
	}	
	
	require("src/append.inc.php"); 

?>