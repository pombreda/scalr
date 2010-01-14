<?php

	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}	
	
	$display['load_extjs'] = true;	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon EC2&nbsp;&raquo;&nbsp;Datafeed&nbsp;&raquo;&nbsp;Create new datafeed");

	$AmazonS3 = new AmazonS3($Client->AWSAccessKeyID, $Client->AWSAccessKey);
	
	// Get list of all user buckets for datafeed creation
    $buckets = $AmazonS3->ListBuckets();
   	$display["buckets"] = array();
    foreach ($buckets as $bucket)
    {		
    	array_push($display["buckets"], (string)$bucket->Name);
    }	   

	if($_POST)
	{			
		try
		{	
			$Client = Client::Load($_SESSION['uid']);			
			$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 		
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
			
			$AmazonEC2Client->CreateSpotDatafeedSubscription($req_buckets);
			
		}
		catch(Exception $e)
		{		
			//if datafeed is existed, then new datafeed creation just changes bucket's id for that datafeed
			$err[] =  $e->getMessage(); // Cannot create datafeed 
			UI::Redirect("aws_ec2_datafeed_add.php");
		}		
		
		$okmsg = sprintf(_("Datafeed  successfully created"));
		
		UI::Redirect("aws_ec2_datafeed_view.php");
	}
	
	require("src/append.inc.php"); 	

?>