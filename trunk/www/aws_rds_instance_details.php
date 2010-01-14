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
		
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon RDS&nbsp;&raquo;&nbsp;DB Instance Details ({$req_name})");
	
	$AmazonRDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
	//$AmazonELBClient->SetRegion($_SESSION['aws_region']);
	

	$info = $AmazonRDSClient->DescribeDBInstances($req_name);
	$dbinstance = $info->DescribeDBInstancesResult->DBInstances->DBInstance;
	$dbinstance->InstanceCreateTime = date("M j, Y H:i:s", strtotime($dbinstance->InstanceCreateTime));
			
	$sg = (array)$dbinstance->DBSecurityGroups;
	$display['sec_groups'] = array();
	if (is_array($sg['DBSecurityGroup']))
	{
		foreach ($sg['DBSecurityGroup'] as $g)
			$display['sec_groups'][(string)$g->DBSecurityGroupName] = (array)$g;
			
	}
	else
		$display['sec_groups'] = array((string)$sg['DBSecurityGroup']->DBSecurityGroupName => (array)$sg['DBSecurityGroup']);		
	
	if ($dbinstance->PendingModifiedValues)
		$display['pending_values'] = (array)$dbinstance->PendingModifiedValues;
	
	$display['dbinstance'] = $dbinstance; 
		
	require_once ("src/append.inc.php");
?>