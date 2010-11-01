<?php
	
	require("src/prepend.inc.php"); 	
	$display['load_extjs'] = true;

	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon VPC&nbsp;&raquo;&nbsp;Amazon VPC Subnets");
		
	if ($_POST && $post_with_selected)
	{ 
		if ($post_action == 'delete')
		{			
			$AmazonVPCClient = AmazonVPC::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region']));
			$AmazonVPCClient->SetAuthKeys(
				Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
				Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
			);			
			
			foreach ($post_id as $subnet_id)
			{
				try
				{				
					$AmazonVPCClient->DeleteSubnet($subnet_id);		
					$i++;		
				}
				catch(Exception $e)
				{
					$err[] = $e->getMessage(); // Can't delete VPC Subnet %s: %s
					UI::Redirect("aws_vpc_subnets_view.php");
				}
			}
			
			if ($i > 0)
				$okmsg = sprintf(_("%s Subnet(s) successfully removed"), $i);
			
			UI::Redirect("aws_vpc_subnets_view.php");
		}
	}
	
	require("src/append.inc.php"); 	

?>
