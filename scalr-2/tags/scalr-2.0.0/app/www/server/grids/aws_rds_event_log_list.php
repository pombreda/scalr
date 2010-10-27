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
		$AmazonRDSClient->SetRegion($_SESSION['aws_region']);
	    
	    $res = $AmazonRDSClient->DescribeEvents($req_name, $req_type);
	    
	    $events = (array)$res->DescribeEventsResult->Events;
	    $response['success'] = '';
		$response['error'] = '';
	    $response['data'] = array();
		
	    if (!is_array($events['Event']))
	    	$events['Event'] = array($events['Event']);
	    		    	
	    foreach ($events['Event'] as $event)
	    {
	    	if ($event->Message)
	    	{
		    	$response['data'][] = array(
		    		'message'	=> (string)$event->Message,
		    		'time'	=> date("M j, Y H:i:s", strtotime((string)$event->Date)),
		    		'source'	=> (string)$event->SourceIdentifier,
		    		'type'		=> (string)$event->SourceType
		    	);
	    	}	
	    }
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>