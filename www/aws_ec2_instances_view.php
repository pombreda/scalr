<?php
	
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
		
	$display['load_extjs'] = true;		
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon EC2&nbsp;&raquo;&nbsp;View running spot instances");
	
	if ($_POST && $post_with_selected)
	{ 
		if ($post_action == 'delete')
		{			
			$Client = Client::Load($_SESSION['uid']);
			$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region']));
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);	
			
			try 
    		{      			
    			$response = $AmazonEC2Client->TerminateInstances($req_iid);
    			$i = count($req_iid);
    			if ($response instanceof SoapFault)
    			{
    				$err[] = $response->faultstring;
    			}    			
    		}
    		catch (Exception $e)
    		{
    				$err[] = $e->getMessage(); 
    				UI::Redirect("aws_ec2_instances_view.php");
    		}
				
			if ($i > 0)
				$okmsg = sprintf(_("%s Selected spot instance(s) successfully terminated"), $i);
			
			UI::Redirect("aws_ec2_instances_view.php");
		}
	}
	require("src/append.inc.php"); 	

?>
