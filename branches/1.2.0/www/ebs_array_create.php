<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
		
	$Client = Client::Load($_SESSION['uid']);
		
	$display["avail_zones"] = array();
	$zone_regions = array();
	
	foreach (AWSRegions::GetList() as $region)
	{
		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region)); 
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		
		$avail_zones_resp = $AmazonEC2Client->DescribeAvailabilityZones();
	        
	    foreach ($avail_zones_resp->availabilityZoneInfo->item as $zone)
	    {
	    	if (stristr($zone->zoneState,'available'))
	    	{
	    		array_push($display["avail_zones"], (string)$zone->zoneName);
	    		$zone_regions[(string)$zone->zoneName] = $region;
	    	}
	    }
	}
	
    if ($_POST)
    {
    	if ($post_cancel)
        	UI::Redirect("ebs_manage.php");
    	
    	if ($post_ctype == 1)
    	{
    		$post_size = (int)$post_size;
    		if ($post_size < 1001)
    			$err[] = _("You must select snapshot or set array size (from 1001 GB)");
    			
    		if ($post_size > 10000)
    			$err[] = _("EBS array size cannot be larger than 10000 GB");
    	}
    	
    	$Validator = new Validator();
    	
    	if (strlen($post_name) < 3 || !$Validator->IsAlphaNumeric($post_name))
    		$err[] = _("Array name must be alphanumeric string and must be greather than 3 chars");
    	elseif ($db->GetOne("SELECT id FROM ebs_arrays WHERE name=? AND clientid=?", array($post_name, $_SESSION['uid'])))
    		$err[] = _("Specified name already used by another EBS array.");
    		
    	$region = $zone_regions[$post_availZone];
    	if (!$region)
    		$err[] = _("Availability zone not avaiable.");
    	
    	$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region)); 
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
    		
    	if (count($err) == 0)
    	{
	    	$DBEBSArray = new DBEBSArray($post_name);
    		
    		$CreateVolumeType = new CreateVolumeType();
    		$CreateVolumeType->availabilityZone = $post_availZone;
    		
    		try
    		{
    			$DBEBSArray->ClientID = $Client->ID;
    			$DBEBSArray->Status = EBS_ARRAY_STATUS::CREATING_VOLUMES;
    			$DBEBSArray->IsFSCreated = 0;
    			$DBEBSArray->Region = $region;
    			$DBEBSArray->AvailZone = $CreateVolumeType->availabilityZone;
    			
	    		switch($post_ctype)
		    	{
		    		case "1": // Create empty array
		    			
		    			$volumes_count = ceil($post_size/1000);
		    			$size = $post_size;
		    			
		    			$DBEBSArray->Size = $size;
		    			$DBEBSArray->VolumesCount = $volumes_count;
						$DBEBSArray->Save();
		    			
		    			for($i = 1; $i <= $volumes_count; $i++)
		    			{
		    				$volume_size = ($size > 1000) ? 1000 : $size;
		    				$size -= $volume_size;
		    				
	  		    		    $CreateVolumeType->size = $volume_size;
	
				    		$res = $AmazonEC2Client->CreateVolume($CreateVolumeType);
				    		if ($res->volumeId)
				    		{
				    			$volumes[] = $res->volumeId;
				    			
				    			$DBEBSVolume = new DBEBSVolume($res->volumeId);
				    			$DBEBSVolume->AvailZone = $res->availabilityZone;
				    			$DBEBSVolume->EBSArrayID = $DBEBSArray->ID;
				    			$DBEBSVolume->Region = $DBEBSArray->Region;
				    			$DBEBSVolume->EBSArrayPart = $i;
				    			$DBEBSVolume->IsManual = 1;
				    			$DBEBSVolume->Save();
				    		}
		    			}
		    			
		    			break;
		    			
		    		case "2": // Create array from spapshots
		    			
		    			$snapinfo = $db->GetRow("SELECT * FROM ebs_array_snaps WHERE id=?", array($req_snapId));
		    			if (!$snapinfo || $snapinfo['clientid'] != $Client->ID)
		    				throw new Exception(_("Snapshot not found"));
		    			
		    			if ($snapinfo['status'] != EBS_ARRAY_SNAP_STATUS::COMPLETED)
		    				throw new Exception(_("Cannot create array from specified snapshot"));
		    				
		    			$DBEBSArray->VolumesCount = $snapinfo['ebs_snaps_count'];
		    			$DBEBSArray->Save();
		    			
		    			$snapshots = $db->GetAll("SELECT * FROM ebs_snaps_info WHERE ebs_array_snapid=?", array($req_snapId));
		    			foreach ($snapshots as $i=>$snapshot)
		    			{
		    				$CreateVolumeType->snapshotId = $snapshot['snapid'];
		    				
		    				$res = $AmazonEC2Client->CreateVolume($CreateVolumeType);
				    		if ($res->volumeId)
				    		{
				    			$volumes[] = $res->volumeId;
				    			
				    			$DBEBSVolume = new DBEBSVolume($res->volumeId);
				    			$DBEBSVolume->AvailZone = $res->availabilityZone;
				    			$DBEBSVolume->EBSArrayID = $DBEBSArray->ID;
				    			$DBEBSVolume->Region = $DBEBSArray->Region;
				    			$DBEBSVolume->EBSArrayPart = $i+1;
				    			$DBEBSVolume->IsManual = 1;
				    			$DBEBSVolume->Save();
				    		}
		    			}
		    				
		    			break;
		    	}
    		}
    		catch(Exception $e)
    		{
    			$DBEBSArray->Delete();
    			
    			try
    			{
    				foreach ((array)$volumes as $volume)
    					$AmazonEC2Client->DeleteVolume($volume);
    			}
    			catch(Exception $e)
    			{
    				//
    			}
    			
    			$errmsg = sprintf(_("Cannot create EBS array: %s"), $e->getMessage());
    		}
    	}
    	
    	if (count($err) == 0 && !$errmsg)
    	{
    		$okmsg = _("EBS Array creation initialized");
    		UI::Redirect("ebs_arrays.php");
    	}
    }
    
	$display['snapshots'] = $db->GetAll("SELECT * FROM ebs_array_snaps WHERE clientid=? AND status=?", array($Client->ID, EBS_ARRAY_SNAP_STATUS::COMPLETED));
	
    $display["title"] = _("Elastic Block Storage > Create new array");
    
    $display["snapId"] = ($req_snapid) ? $req_snapid : $req_snapId;
    
    $display["instances"] = $db->GetAll("SELECT farm_instances.*, farms.name FROM farm_instances INNER JOIN farms ON farms.id = farm_instances.farmid WHERE state=? AND farms.clientid=?", 
    	array(INSTANCE_STATE::RUNNING, $_SESSION['uid'])
    );
    
	require("src/append.inc.php"); 
?>