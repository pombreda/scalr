<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon RDS&nbsp;&raquo;&nbsp;Launch new DB Instance");
		
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	
	$Client = Client::Load($_SESSION['uid']);
	$AmazonRDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
	
	if ($_POST)
	{
		$_POST['PreferredMaintenanceWindow'] = "{$_POST['pmw1']['ddd']}:{$_POST['pmw1']['hh']}:{$_POST['pmw1']['mm']}-{$_POST['pmw2']['ddd']}:{$_POST['pmw2']['hh']}:{$_POST['pmw2']['mm']}";
		$_POST['PreferredBackupWindow'] = "{$_POST['pbw1']['hh']}:{$_POST['pbw1']['mm']}-{$_POST['pbw2']['hh']}:{$_POST['pbw2']['mm']}";
		
		try
		{		
			if (!$post_snapshot)
			{
				$AmazonRDSClient->CreateDBInstance(
					$_POST['DBInstanceIdentifier'],
					$_POST['AllocatedStorage'],
					$_POST['DBInstanceClass'],
					$_POST['Engine'],				
					$_POST['MasterUsername'],
					$_POST['MasterUserPassword'],
					$_POST['Port'],
					$_POST['DBName'],
					$_POST['DBParameterGroup'],
					$_POST['DBSecurityGroups'],
					$_POST['AvailabilityZone'] ? $_POST['AvailabilityZone'] : null,
					$_POST['PreferredMaintenanceWindow'],
					$_POST['BackupRetentionPeriod'],
					$_POST['PreferredBackupWindow']
				);
			}
			else
			{
				$AmazonRDSClient->RestoreDBInstanceFromDBSnapshot(
					$_POST['snapshot'], 
					$_POST['DBInstanceIdentifier'],
					$_POST['DBInstanceClass'],
					$_POST['Port'],
					$_POST['AvailabilityZone'] ? $_POST['AvailabilityZone'] : null
				);
			}
		}
		catch(Exception $e)
		{
			$err[] = $e->getMessage();
			$display['POST'] = $_POST;
		}
		
		if (count($err) == 0)
		{
			$okmsg = _("DB instance successfully launched");
			UI::Redirect("aws_rds_instances_view.php");
		}
	}
	
	if ($req_snapshot)
		$display['snapshot'] = $req_snapshot; 
	
	if (!$req_snapshot)
	{
		//
		// Load DB parameter groups
		//
		$DBParameterGroups = $AmazonRDSClient->DescribeDBParameterGroups();
		$groups = (array)$DBParameterGroups->DescribeDBParameterGroupsResult->DBParameterGroups;
		$groups = $groups['DBParameterGroup'];	
		if ($groups)
		{
			if (!is_array($groups))
				$groups = array($groups);
				
			foreach ((array)$groups as $group)
				$display['DBParameterGroups'][] = $group;
		}
	
	
		//
		// Load DB security groups
		//
		$DescribeDBSecurityGroups = $AmazonRDSClient->DescribeDBSecurityGroups();
		$sgroups = (array)$DescribeDBSecurityGroups->DescribeDBSecurityGroupsResult->DBSecurityGroups;
		$sgroups = $sgroups['DBSecurityGroup'];
		if ($sgroups)
		{
			if (!is_array($sgroups))
				$sgroups = array($sgroups);
				
			foreach ((array)$sgroups as $sgroup)
				$display['DBSecurityGroups'][] = $sgroup;
		}
	}
	
	//
	// Load avail zones
	//
	//TODO:
	$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL('us-east-1')); 
	$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
                    
    // Get Avail zones
    $avail_zones_resp = $AmazonEC2Client->DescribeAvailabilityZones();
    $display["avail_zones"] = array();
    
    // Random assign zone
    array_push($display["avail_zones"], "");
    
    foreach ($avail_zones_resp->availabilityZoneInfo->item as $zone)
    {
    	if (stristr($zone->zoneState,'available'))
    		array_push($display["avail_zones"], (string)$zone->zoneName);
    }
	
	require("src/append.inc.php"); 
?>