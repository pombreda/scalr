<?
	require_once('src/prepend.inc.php');
    $display['load_extjs'] = false;	    
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
		
	if (!$req_lb || !$req_iid)
		UI::Redirect("index.php");
	
	$Client = Client::Load($_SESSION["uid"]);
		
	$display['title'] = 'AWS Load balancer > Instance health state';
	
	$AmazonELBClient = AmazonELB::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
	$AmazonELBClient->SetRegion($_SESSION['aws_region']);

	if ($_POST)
	{
		if ($post_cbtn_2)
		{
			try
			{
				$res = $AmazonELBClient->DeregisterInstancesFromLoadBalancer($req_lb, array($req_iid));
				if ($res)
				{
					$okmsg = _("Instance successfully deregistered from the load balancer");
					UI::Redirect("aws_elb_details.php?name={$req_lb}");
				}
			}
			catch(Exception $e)
			{
				$errmsg = $e->getMessage();
			}
		}
	}
	
	$info = $AmazonELBClient->DescribeInstanceHealth($req_lb, array($req_iid));
	
	$display['info'] = $info->DescribeInstanceHealthResult->InstanceStates->member; 

	$display['name'] = htmlspecialchars($req_lb);
	
	require_once ("src/append.inc.php");
?>