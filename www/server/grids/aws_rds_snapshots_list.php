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
		
		$AmazonRDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);		
			
		// Rows
		$aws_response = $AmazonRDSClient->DescribeDBSnapshots($req_name);	
		$res = (array)$aws_response->DescribeDBSnapshotsResult->DBSnapshots;

		if (!is_array($res['DBSnapshot']))
		{
			if ($res['DBSnapshot'])
				$rowz = array($res['DBSnapshot']);
			else
				$rowz = array();
		}
		else
			$rowz = $res['DBSnapshot'];

		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		
		$response['total'] = count($rowz);
		
		$rowz = (count($rowz) > $limit) ? array_slice($rowz, $start, $limit) : $rowz;		

		$response["data"] = array();		
		
		foreach ($rowz as $row)
		{
		    $response["data"][] = array(
		    	"dtcreated"		=> date("M j, Y H:i:s", strtotime((string)$row->SnapshotCreateTime)),
		    	"port"			=> (string)$row->Port,
		    	"status"		=> (string)$row->Status,
		    	"engine"		=> (string)$row->Engine,
		    	"avail_zone"	=> (string)$row->AvailabilityZone,
		    	"idtcreated"	=> date("M j, Y H:i:s", strtotime((string)$row->InstanceCreateTime)),
		    	"storage"		=> (string)$row->AllocatedStorage,
		    	"name"			=> (string)$row->DBSnapshotIdentifier,
		    	"id"			=> (string)$row->DBSnapshotIdentifier,
		    );
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>