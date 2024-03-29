<?php
	$response = array();
	 
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");		
		
		$req_show_all = true;	//
		
		if (isset($req_show_all))
		{
			if ($req_show_all == 'true')
				$_SESSION['sg_show_all'] = true;
			else
				$_SESSION['sg_show_all'] = false;
		}
		
		$Client = Client::Load($_SESSION['uid']);
		
		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region']));		 
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		
		$aws_response = $AmazonEC2Client->DescribeSpotInstanceRequests(); // show requests
		
		// Rows	
		$rows = (array)$aws_response->spotInstanceRequestSet;		
			
		if ($rows["item"] instanceof stdClass)
			$rows["item"] = array($rows["item"]); // convert along subnet record to array
		
		$rowz = array();		
		
		foreach ($rows['item'] as $row)							
				$rowz[]=(array)$row;									
				
		// diplay list limits
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		
		$response['total'] = count($rowz);	
		$rowz = (count($rowz) > $limit) ? array_slice($rowz, $start, $limit) : $rowz;
		
		// descending sorting of requested result
		$response["data"] = array();	
		 		
		if ($req_sort)
		{
			$nrowz = array();
			foreach ($rowz as $row)				
				$nrowz[(string)$row['createTime']] = $row;			
					
			ksort($nrowz);
			
			if ($req_dir == 'DESC')
				$rowz = array_reverse($nrowz);
			else
				$rowz = $nrowz;	
		}
			
		// Rows. Create final rows array for script
		foreach ($rowz as $row)
		{ 	
			$launchSpecification = (array)$row['launchSpecification']; 
		
			$response["data"][] = array(
					"spotInstanceRequestId"	=> (string)$row['spotInstanceRequestId'], // have to call only like "id" for correct script work in template
					"spotPrice"				=> (string)$row['spotPrice'],
					"type"					=> (string)$row['type'],					
					"state"					=> (string)$row['state'],
					"createTime"			=> (string)$row['createTime'],
					"instanceId"	 		=> (string)$row['instanceId'],
					"imageId"	 			=> (string)$launchSpecification['imageId'],	
					"instanceType"	 		=> (string)$launchSpecification['instanceType'],
					"productDescription" 	=> (string)$row['productDescription'],
					"validFrom" 			=> "",
					"validUntil" 			=> ""															
					);
			
			if($row['validFrom'])
				$response["data"][0]["validFrom"] = (string)$row['validFrom'];
				
			if($row['validUntil'])
				$response["data"][0]["validUntil"] = (string)$row['validUntil'];
			
		} 		
		
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}

	print json_encode($response);
?>