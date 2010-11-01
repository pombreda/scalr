<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		Scalr_Session::getInstance()->getAuthToken()->hasAccessEx(Scalr_AuthToken::ACCOUNT_USER);
		
		$AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
			$_SESSION['aws_region'], 
			Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
			Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
		);
		
					
		// Rows
		$aws_response = $AmazonEC2Client->DescribeReservedInstancesOfferings();
				
		$rowz = $aws_response->reservedInstancesOfferingsSet->item;
			
		if ($rowz instanceof stdClass)
			$rowz = array($rowz);
		
		foreach ($rowz as $pv)
		{
			$rowz1[] = $pv;
		}
				
		$response["total"] = count($rowz1);
		
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		
		$rowz = (count($rowz1) > $limit) ? array_slice($rowz1, $start, $limit) : $rowz1;
		
		$response["data"] = array();
		
		// Rows
		foreach ($rowz as $r)
		{
		    $response["data"][] = array(
		    	'id' => $r->reservedInstancesOfferingId,
		    	'instance_type' => $r->instanceType, 
		    	'avail_zone' => $r->availabilityZone, 
		    	'duration' => $r->duration/86400/365,
		     	'fixed_price' => $r->fixedPrice,
		    	'usage_price'  => $r->usagePrice,
		    	'description' => $r->productDescription
		    );
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>