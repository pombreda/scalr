<?
	require_once('src/prepend.inc.php');
    $display['load_extjs'] = true;	    
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	
	if ($req_farmid)
	{
		$display['grid_query_string'] .= "&farmid={$req_farmid}";
	}
	
	$Client = Client::Load($_SESSION['uid']);
	
	if ($req_action == 'remove')
	{
		try
		{
			$AmazonELBClient = AmazonELB::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
			$AmazonELBClient->DeleteLoadBalancer($req_name);
			
			$okmsg = _("Load balancer successfully removed");
			UI::Redirect('/aws_elb.php');
		}
		catch(Exception $e)
		{
			$errmsg = sprintf(_('Cannot remove load balancer: %s'), $e->getMessage());
		}
	}
		
	$display['title'] = 'Elastic Load Balancers';
		
	require_once ("src/append.inc.php");
?>