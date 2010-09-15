<?php
	
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
		
	$display['load_extjs'] = true;		
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon EC2&nbsp;&raquo;&nbsp;Spot requests&nbsp;&raquo;&nbsp;View spot instances requests");
	
	if ($_POST && $post_with_selected)
	{ 
		if ($post_action == 'delete')
		{			
			$Client = Client::Load($_SESSION['uid']);
			$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region']));
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);	
									
			try
			{				
				$AmazonEC2Client->CancelSpotInstanceRequests( new CancelSpotInstanceRequestsType($post_spotInstanceRequestId));	
				$i = count($post_spotInstanceRequestId);			
			}
			catch(Exception $e)
			{
				$err[] =  $e->getMessage(); // Cannot cancel spot request
				UI::Redirect("aws_ec2_spotrequest_view.php");
			}			
			
			if ($i > 0)
				$okmsg = sprintf(_("%s Selected spot request(s) successfully canceled"), $i);
			
			UI::Redirect("aws_ec2_spotrequest_view.php");
		}
	}
	require("src/append.inc.php"); 	

?>
