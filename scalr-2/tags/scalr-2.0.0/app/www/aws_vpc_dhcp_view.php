<?php

	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
	
	$display['load_extjs'] = true;
	
	$Client = Client::Load($_SESSION['uid']);
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon VPC&nbsp;&raquo;&nbsp;Amazon DHCP options");
	
	if ($_POST && $post_with_selected)
	{ 
		if ($post_action == 'delete')
		{		
			$AmazonVPCClient = AmazonVPC::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region']));
			$AmazonVPCClient->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);	
					
			foreach ($post_id as $dhcp_id)
			{
				try
				{							
					$AmazonVPCClient->DeleteDhcpOptions($dhcp_id);	
					$i++;			
				}
				catch(Exception $e)
				{
					$err[] = $e->getMessage(); //Can't delete DHCP option %s: %s
					UI::Redirect("aws_vpc_dhcp_view.php");
				}
			}		
			if ($i > 0)
				$okmsg = sprintf(_("%s DHCP options set(s) successfully removed"),$i);
			
			UI::Redirect("aws_vpc_dhcp_view.php");
		}
	}

	require("src/append.inc.php"); 	

?>
