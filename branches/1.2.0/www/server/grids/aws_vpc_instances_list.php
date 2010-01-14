<?php
	$response = array();

	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");		
		
		$req_show_all = true;	
		
		if (isset($req_show_all))
		{
			if ($req_show_all == 'true')
				$_SESSION['sg_show_all'] = true;
			else
				$_SESSION['sg_show_all'] = false;
		}
		
		$Client = Client::Load($_SESSION['uid']);	
		
		$AmazonVPCClient = AmazonVPC::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
		$AmazonVPCClient->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		
		// Rows	
		$aws_response = $AmazonVPCClient->DescribeVpcs();		
		$rows = (array)$aws_response->vpcSet;	
		
		$rowz = array();
		foreach ($rows as $row)
			$rowz[]=(array)$row;
		 
		// diplay list limits
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		
		$response['total'] = count($rowz);	
		$rowz = (count($rowz) > $limit) ? array_slice($rowz, $start, $limit) : $rowz;
		
		// descending sorting of request result
		$response["data"] = array();	
		$req_sort = 1;
		if ($req_sort)
		{
			$nrowz = array();
			foreach ($rowz as $row)				
				$nrowz[(string)$row['vpcId']] = $row;			
					
			ksort($nrowz);
			
			if ($req_dir == 'DESC')
				$rowz = array_reverse($nrowz);
			else
				$rowz = $nrowz;	
		}		
	
		// Rows. Create final rows array for script
		foreach ($rowz as $row)
		{ 	
			$response["data"][] = array(
					"id"			=> (string)$row['vpcId'], // have to call only like "id" for correct script work in template
					"state"			=> (string)$row['state'],
					"cidrBlock"		=> (string)$row['cidrBlock'],
					"dhcpOptionsId"	=> (string)$row['dhcpOptionsId']
					);				
		}	
	
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}

	print json_encode($response);
?>