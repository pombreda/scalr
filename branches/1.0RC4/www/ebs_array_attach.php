<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
		
	$Client = Client::Load($_SESSION['uid']);
		
    try
    {
		$DBEBSArray = DBEBSArray::Load($req_array_id);
		if ($DBEBSArray->ClientID != $Client->ID)
			throw new Exception("");
    }
    catch(Exception $e)
    {
    	UI::Redirect("ebs_arrays.php");
    }
	
    $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBEBSArray->Region)); 
	$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
    
    if ($DBEBSArray->Status != EBS_ARRAY_STATUS::AVAILABLE)
    {
    	$errmsg = _("You cannot attach non available array");
    	UI::Redirect("ebs_arrays.php");
    }
    
    $display['array_id'] = $DBEBSArray->ID;
    $display['array_name'] = $DBEBSArray->Name;
    
    if ($_POST)
    {
    	if ($post_cancel)
        	UI::Redirect("ebs_arrays.php");
    	
        $instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($post_inststanceId));
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($instanceinfo['farmid'], $Client->ID));
        	
        if (!$instanceinfo || !$farminfo)
        {
        	$errmsg = _("Instance is no longer available");
        	UI::Redirect("ebs_arrays.php");
        }
        
        if ($instanceinfo['state'] != INSTANCE_STATE::RUNNING)
        {
        	$errmsg = _("You cannot attach array to non running instance");
        	UI::Redirect("ebs_arrays.php");
        }
        
        if ($farminfo['status'] != FARM_STATUS::RUNNING)
        {
        	$errmsg = _("You cannot attach array to instance on terminated farm");
        	UI::Redirect("ebs_arrays.php");
        }
        
        $volumes = $db->GetAll("SELECT volumeid FROM farm_ebs WHERE ebs_arrayid=?", array($DBEBSArray->ID));
        
        Logger::getLogger("EBSArrayAttach")->info("Attaching array '%s' to instance '%s'. Volumes count: %s", array(
        	$DBEBSArray->Name, $instanceinfo['instance_id'], count($volumes) 
        ));
            	
        $AWSVolumes = $AmazonEC2Client->DescribeVolumes();
        $AWSVolumes = $AWSVolumes->volumeSet->item;
        
    	foreach ($volumes as $volume)
    	{
    		$DBEBSVolume = DBEBSVolume::Load($volume['volumeid']);
    		
    		try
    		{
	    		//
	    		// Check Volume;
	    		//
	    		$Volume = false;
	    		foreach ($AWSVolumes as $AWSVolume)
	    		{
	    			if ($AWSVolume->volumeId == $DBEBSVolume->VolumeID)
	    			{
	    				$Volume = $AWSVolume;
	    				break;
	    			}
	    		}
	    		
	    		// If volume not found on EC2 - mark array as corrupt
	    		if (!$Volume)
	    		{
	    			$DBEBSArray->Status = EBS_ARRAY_STATUS::CORRUPT;
	    			$DBEBSArray->Save();
	    			
	    			$DBEBSVolume->Delete();
	    			
	    			throw new Exception(sprintf(_("Volume %s not found"), $DBEBSVolume->VolumeID));
	    		}
    			
	    		// Check volume status.
	    		if ($Volume->status != AMAZON_EBS_STATE::AVAILABLE)
	    		{
	    			throw new Exception(sprintf(_("Volume '%s' status '%s' disallows attaching"), $DBEBSVolume->VolumeID, $Volume->status));
	    		}
    			
    			// Try to attach EBS volume
    			Scalr::AttachEBS2Instance($AmazonEC2Client, $instanceinfo, $farminfo, $DBEBSVolume);
    			$attached_volumes[] = $DBEBSVolume;
    		}
    		catch(Exception $e)
    		{
    			$errmsg = sprintf(_("Cannot use volume %s for array %s: %s"),
    				$DBEBSVolume->VolumeID,
    				$DBEBSArray->Name,
    				$e->getMessage()
    			);
    			
    			Logger::getLogger("EBSArrayAttach")->error($errmsg);
    			
    			foreach ($attached_volumes as $DBEBSVolume)
    			{
    				try
    				{
    					$DetachVolumeType = new DetachVolumeType($DBEBSVolume->VolumeID);
    					$AmazonEC2Client->DetachVolume($DetachVolumeType);
    					
    					$DBEBSVolume->State = FARM_EBS_STATE::AVAILABLE;
    					$DBEBSVolume->InstanceID = "";
    					$DBEBSVolume->Device = "";
    					
    					$this->DB->Execute("UPDATE farm_ebs SET state=?, instance_id='', device='' WHERE volumeid=?", 
							array(FARM_EBS_STATE::AVAILABLE, $volumeid)
						);
    				}
    				catch(Exception $e)
    				{
    					Logger::getLogger("EBSArrayAttach")->error($e->getMessage());	
    				}
    			}
    			    			
    			break;
    		}
    	}
    	
    	if (!$errmsg)
    	{
    		$DBEBSArray->Status = EBS_ARRAY_STATUS::ATTACHING_VOLUMES;
    		$DBEBSArray->InstanceID = $instanceinfo['instance_id'];
    		$DBEBSArray->InstanceIndex = $instanceinfo['index'];
    		$DBEBSArray->Mountpoint = $post_mountpoint;
			$DBEBSArray->FarmID = $instanceinfo['farmid'];    		
    		
    		if ($post_attach_on_boot)
    		{
    			$DBEBSArray->AttachOnBoot = 1;
    			$DBEBSArray->RoleName = $instanceinfo['role_name'];
    		}
    		else
    		{
    			$DBEBSArray->AttachOnBoot = 0;
    			$DBEBSArray->RoleName = "";
    		}
    		
    		$DBEBSArray->Save();
    		
    		$okmsg = _("Array attaching initialized.");
    	}
    	
    	UI::Redirect("ebs_arrays.php");
    }
    
    $instances = $db->GetAll("SELECT farm_instances.*, farms.name FROM farm_instances INNER JOIN farms ON farms.id = farm_instances.farmid WHERE avail_zone=? AND state=? AND farms.clientid=?", 
    	array($DBEBSArray->AvailZone, INSTANCE_STATE::RUNNING, $Client->ID)
    );
    foreach ($instances as $instance)
    {
    	if (!$db->GetOne("SELECT id FROM ebs_arrays WHERE instance_id=?", array($instance['instance_id'])))
    		$display['instances'][] = $instance;
    }
    
    if (count($display["instances"]) == 0)
    {
    	$errmsg = sprintf(_("You don't have running instances in availability zone %s"), $DBEBSArray->AvailZone);
		UI::Redirect("ebs_arrays.php");
    }
		
    $display["title"] = _("Elastic Block Storage > Attach array to instance");
    
    
	require("src/append.inc.php");
?>