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
    
    $statuses = array(EBS_ARRAY_STATUS::AVAILABLE, EBS_ARRAY_STATUS::CORRUPT, EBS_ARRAY_STATUS::DETACHING_VOLUMES);
    if (in_array($DBEBSArray->Status, $statuses))
    {
    	$errmsg = sprintf(_("You cannot detach array with status %s"), $DBEBSArray->Status);
    	UI::Redirect("ebs_arrays.php");
    }
    
    $display['array_id'] = $DBEBSArray->ID;
    $display['array_name'] = $DBEBSArray->Name;
    $display['instance_id'] = $DBEBSArray->InstanceID;
    $display['attach_on_boot'] = $DBEBSArray->AttachOnBoot;
    
    if ($_POST)
    {
    	if ($post_cancel)
        	UI::Redirect("ebs_arrays.php");
    	
        $isforce = ($post_force == 1) ? true : false;
        	
        $volumes = $db->GetAll("SELECT volumeid FROM farm_ebs WHERE ebs_arrayid=?", array($DBEBSArray->ID));
		foreach ($volumes as $volume)        
		{
			$DBEBSVolume = DBEBSVolume::Load($volume['volumeid']);
			
			try
			{
				$res = $AmazonEC2Client->DetachVolume(new DetachVolumeType($DBEBSVolume->VolumeID, null, null, $isforce));
	
				if ($res->volumeId && $res->status == AMAZON_EBS_STATE::DETACHING)
				{
					$DBEBSVolume->State = FARM_EBS_STATE::DETACHING;
					$DBEBSVolume->InstanceID = '';
					$DBEBSVolume->Device = '';
					$DBEBSVolume->Save();
				}
			}
			catch(Exception $e)
			{
				$err[] = sprintf(_("Cannot detach volume %s. %s"), $DBEBSVolume->VolumeID, $e->getMessage());
			}
		}
        
		$DBEBSArray->AttachOnBoot = ($post_detach_on_boot) ? 0 : $DBEBSArray->AttachOnBoot;
		if (!$DBEBSArray->AttachOnBoot)
		{
			$DBEBSArray->FarmID = null;
			$DBEBSArray->InstanceIndex = null;
			$DBEBSArray->FarmRoleID = null;
		}
		$DBEBSArray->Status = EBS_ARRAY_STATUS::DETACHING_VOLUMES;
		$DBEBSArray->Save();
		
		$okmsg = _("Array detaching initialized");
		UI::Redirect("ebs_arrays.php");
    }
    
    $display["title"] = _("Elastic Block Storage > Detach array from instance");
    
	require("src/append.inc.php");
?>