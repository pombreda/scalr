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
			$roleid = $db->GetOne("SELECT farm_roleid FROM farm_role_settings WHERE name=? AND value=?",
			array(
				DBFarmRole::SETTING_BALANCING_NAME,
				$req_name
			));
									
			$AmazonELBClient = AmazonELB::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
			$AmazonELBClient->SetRegion($_SESSION['aws_region']);
			$AmazonELBClient->DeleteLoadBalancer($req_name);
			
			if ($roleid)
			{
				$DBFarmRole = DBFarmRole::LoadByID($roleid);
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB, 0);
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_HOSTNAME, "");
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_NAME, "");
			}
			
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