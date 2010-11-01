<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		Scalr_Session::getInstance()->getAuthToken()->hasAccessEx(Scalr_AuthToken::ACCOUNT_USER);
		
		// Load Client Object
		$AmazonRDSClient = Scalr_Service_Cloud_Aws::newRds( 
			Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Rds::ACCESS_KEY),
			Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Rds::SECRET_KEY),
			$_SESSION['aws_region']
		);

		// Rows
		$aws_response = $AmazonRDSClient->DescribeDBInstances();
		$instances = (array)$aws_response->DescribeDBInstancesResult->DBInstances;
		$instances = $instances['DBInstance'];
		if ($instances)
			if (!is_array($instances))
				$instances = array($instances);

				
		// Custom properties
		foreach ($instances as $pk=>$pv)
		{
			$instance = array(
				'engine'	=> (string)$pv->Engine,
				'status'	=> (string)$pv->DBInstanceStatus,
				'hostname'	=> (string)$pv->Endpoint->Address,
				'port'		=> (string)$pv->Endpoint->Port,
				'name'		=> (string)$pv->DBInstanceIdentifier,
				'username'	=> (string)$pv->MasterUsername,
				'type'		=> (string)$pv->DBInstanceClass,
				'storage'	=> (string)$pv->AllocatedStorage,
				'dtadded'	=> date("M j, Y H:i:s", strtotime((string)$pv->InstanceCreateTime)),
				'avail_zone'=> (string)$pv->AvailabilityZone
			);			
			
			$rows[] = $instance;
		}
		
		$response["total"] = count($rows);
		
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		
		$rowz = (count($rowz1) > $limit) ? array_slice($rows, $start, $limit) : $rows;
		
		$response["data"] = array();
		
		foreach ($rowz as $row)
		{
			$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>