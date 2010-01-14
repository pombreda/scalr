<?php

	require("src/prepend.inc.php"); 
		
	if ($_SESSION["uid"] == 0)
	   UI::Redirect("index.php");
	
	$Client = Client::Load($_SESSION['uid']);
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon VPC&nbsp;&raquo;&nbsp;Create Virtual Private Cloud");	
		
	$AmazonVPCClient = AmazonVPC::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonVPCClient->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		
	
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
			$err[] = $e->getMessage(); //Cannot create Virtual Private Cloud (VPC) %s: %s
			UI::Redirect("aws_vpc_vierw.php");
		}
	}	
			
	require("src/append.inc.php"); 
?>