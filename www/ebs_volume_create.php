<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
		
    $AmazonEC2Client = new AmazonEC2($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);
	
    if ($_POST)
    {
    	if (!$post_ctype == 1)
    	{
    		$post_size = (int)$post_size;
    		if ($post_size < 1 || $post_size > 1024)
    			$err[] = "You must select snapshot or set volume size (1 to 1024 GB)";
    	}
    	
    	if (count($err) == 0)
    	{
	    	$CreateVolumeType = new CreateVolumeType();
	    	if ($post_ctype == 2)
	    		$CreateVolumeType->snapshotId = $post_snapId;
	    	else
	    		 $CreateVolumeType->size = $post_size;
	    		 
	    	$CreateVolumeType->availabilityZone = $post_availZone;
	    	
	    	try
	    	{
	    		$AmazonEC2Client->CreateVolume($CreateVolumeType);
	    	}
	    	catch(Exception $e)
	    	{
	    		$err[] = $e->getMessage();
	    	}
    	}
    	
    	if (count($err) == 0)
    	{
    		$okmsg = "Volume creation initialized";
    		UI::Redirect("ebs_manage.php");
    	}
    }
    
	$response = $AmazonEC2Client->DescribeSnapshots();

	$rowz = $response->snapshotSet->item;
		
	if ($rowz instanceof stdClass)
		$rowz = array($rowz);
			
	foreach ($rowz as $pk=>$pv)
	{		
		if ($pv->status == 'completed')
			$display['snapshots'][] = $pv->snapshotId;
	}
	
	// Get Avail zones
    $avail_zones_resp = $AmazonEC2Client->DescribeAvailabilityZones();
    $display["avail_zones"] = array();
    
    foreach ($avail_zones_resp->availabilityZoneInfo->item as $zone)
    {
    	if ($zone->zoneState == 'available')
    		array_push($display["avail_zones"], (string)$zone->zoneName);
    }
	
    $display["title"] = "Elastic block storage > Create new volume";
    
    $display["instances"] = $db->GetAll("SELECT farm_instances.*, farms.name FROM farm_instances INNER JOIN farms ON farms.id = farm_instances.farmid WHERE state=? AND farms.clientid=?", 
    	array(INSTANCE_STATE::RUNNING, $_SESSION['uid'])
    );
    
	require("src/append.inc.php"); 
?>