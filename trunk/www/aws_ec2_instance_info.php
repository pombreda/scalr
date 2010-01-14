<?
	require_once('src/prepend.inc.php');   
	if(!$req_region)
		$req_region = $_SESSION['aws_region'];	
	
	if (!$req_iid || !$req_region)
		UI::Redirect("index.php");
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
		
	try 
	{
		$Client = Client::Load($_SESSION['uid']);
			
		$display['title'] = 'AWS Instance Details';
		
		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($req_region)); 
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
			
		// Rows		
		 
		if($req_iid)
			$aws_response = $AmazonEC2Client->DescribeInstances($req_iid); // show all instances		
		else
    	{
   			$err[] = "Instance id not found. Please, select instances from list";  
   			UI::Redirect("aws_ec2_instances_view.php");
    	}
		$details = $aws_response->reservationSet->item;
		
		if (!is_array($details->groupSet->item))
			$details->groupSet->item = array($details->groupSet->item);
				
		$display['info'] = $details;	
	
					
	}
	catch(Exception $e)
	{
		$err[] = $e->getMessage(); 
    	UI::Redirect("aws_ec2_instances_view.php");
	}
	require_once ("src/append.inc.php");
?>