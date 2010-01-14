<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		if ($_SESSION["uid"] == 0)
			throw new Exception(_("Requested page cannot be viewed from admin account"));
		else
			$clientid = $_SESSION['uid'];
			
		$region = $_SESSION['aws_region'];
		
		// Load Client Object
	    $Client = Client::Load($clientid);
	    
	    $AmazonRDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
	    			
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