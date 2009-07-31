<?
	require_once('src/prepend.inc.php');
    $display['load_extjs'] = false;	    
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
		
	if (!$req_name)
		UI::Redirect("index.php");
	
	$Client = Client::Load($_SESSION["uid"]);
		
	$display['title'] = 'Elastic Load Balancer details';
	
	$AmazonELBClient = AmazonELB::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);

	$info = $AmazonELBClient->DescribeLoadBalancers(array($req_name));
	$elb = $info->DescribeLoadBalancersResult->LoadBalancerDescriptions->member;
	
	
	if (!is_array($elb->Instances->member))
		$elb->Instances->member = array($elb->Instances->member);
		
	if (!is_array($elb->AvailabilityZones->member))
		$elb->AvailabilityZones->member = array($elb->AvailabilityZones->member);
		
	if (!is_array($elb->Listeners->member))
		$elb->AvailabilityZones->member = array($elb->AvailabilityZones->member);
	
	$display['elb'] = $elb;
	
	require_once ("src/append.inc.php");
?>