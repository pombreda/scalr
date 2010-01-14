<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		if (isset($req_show_all))
		{
			if ($req_show_all == 'true')
				$_SESSION['sg_show_all'] = true;
			else
				$_SESSION['sg_show_all'] = false;
		}
		
		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
		$AmazonEC2Client->SetAuthKeys($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);
		
	
		// Rows
		$aws_response = $AmazonEC2Client->DescribeSecurityGroups();
		
		$rows = $aws_response->securityGroupInfo->item;
		foreach ($rows as $row)
		{
			if ($req_query)
			{
				if (!stristr($row->groupName, $req_query))
					continue;
			}
			
			// Show only scalr security groups
			if (stristr($row->groupName, CONFIG::$SECGROUP_PREFIX) || $_SESSION['sg_show_all'])
				$rowz[] = $row;
		}
		
		if ($rowz instanceof stdClass)
			$rowz = array($rowz);
			
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		
		$response['total'] = count($rowz);
		
		$rowz = (count($rowz) > $limit) ? array_slice($rowz, $start, $limit) : $rowz;
		
		$response["data"] = array();
		
		if ($req_sort)
		{
			$nrowz = array();
			foreach ($rowz as $row)
			{
				$nrowz[$row->groupName] = $row;
			}
			
			ksort($nrowz);
			
			if ($req_dir == 'DESC')
				$rowz = array_reverse($nrowz);
			else
				$rowz = $nrowz;
		}
		
		// Rows
		foreach ($rowz as $row)
		{
		    $response["data"][] = array(
		    	"name"			=> $row->groupName,
		    	"description"	=> $row->groupDescription,
		    	"id"			=> $row->groupName
		    );
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>