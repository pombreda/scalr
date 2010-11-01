<?php

	require("src/prepend.inc.php"); 
		
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon VPC&nbsp;&raquo;&nbsp;Create Virtual Private Cloud");	
			
	$AmazonVPCClient = AmazonVPC::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonVPCClient->SetAuthKeys(
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
	);
		
	if($_POST)
	{ 
		try
		{			
			if (!$req_cidr)
			{
				$errmsg = "Please enter  CIDR (e.g. 10.0.0.0/24)";
				UI::Redirect("aws_vpc_add.php");
			}			
								
			$res = $AmazonVPCClient->CreateVpc($req_cidr);
			$okmsg = "Virtual Private Cloud (VPC) successfully created";	
			UI::Redirect("aws_vpc_view.php");
		}
		catch(Exception $e)
		{			
			$err[] = $e->getMessage(); //Can't create Virtual Private Cloud (VPC) %s: %s
			UI::Redirect("aws_vpc_view.php");
		}
	}	
			
	require("src/append.inc.php"); 
?>