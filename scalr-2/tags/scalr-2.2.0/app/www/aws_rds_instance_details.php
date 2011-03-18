<?
	require_once('src/prepend.inc.php');
    $display['load_extjs'] = false;	    
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("/#/dashboard");
	}
		
	if (!$req_name)
		UI::Redirect("/#/dashboard");
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon RDS&nbsp;&raquo;&nbsp;DB Instance Details ({$req_name})");

	$AmazonRDSClient = Scalr_Service_Cloud_Aws::newRds( 
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Rds::ACCESS_KEY),
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Rds::SECRET_KEY),
		$_SESSION['aws_region']
	);

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
	
	$pg = (array)$dbinstance->DBParameterGroups;
	$display['param_groups'] = array();
	
	if (is_array($pg['DBParameterGroup']))
	{
		foreach ($pg['DBParameterGroup'] as $g)
			$display['param_groups'][(string)$g->DBParameterGroupName] = (array)$g;			
	}
	else
		$display['param_groups'] = array((string)$pg['DBParameterGroup']->DBParameterGroupName => (array)$pg['DBParameterGroup']);
		
	if ($dbinstance->PendingModifiedValues->MultiAZ == 'true')
		$dbinstance->PendingModifiedValues->MultiAZ = 'Enable';
	
	if ($dbinstance->PendingModifiedValues->MultiAZ == 'false')
		$dbinstance->PendingModifiedValues->MultiAZ = 'Disable';
		
	if ($dbinstance->PendingModifiedValues)
		$display['pending_values'] = (array)$dbinstance->PendingModifiedValues;
		
	$display['dbinstance'] = $dbinstance; 
		
	require_once ("src/append.inc.php");
?>