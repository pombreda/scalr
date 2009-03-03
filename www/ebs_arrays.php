<?
	require_once('src/prepend.inc.php');
    	    
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	else
		$Client = Client::Load($_SESSION["uid"]);
	
    if ($req_task == 'create_array')
    {
    	UI::Redirect("ebs_array_create.php");
    }
    elseif ($req_task == 'snap_delete')
    {
    	$snapinfo = $db->GetRow("SELECT * FROM ebs_array_snaps WHERE id=?", 
    		array($req_snapshotId)
    	);
    	
    	if ($snapinfo['clientid'] != $Client->ID)
    		UI::Redirect("ebs_array_create.php");
    		
    	$ebs_snapshots = $db->GetAll("SELECT * FROM ebs_snaps_info WHERE ebs_array_snapid=?", array($snapinfo['id']));
    	foreach ($ebs_snapshots as $ebs_snapshot)
    	{
    		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($ebs_snapshot['region'])); 
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
    		
    		try
    		{
    			$db->Execute("DELETE FROM ebs_snaps_info WHERE id=?", array($ebs_snapshot['id']));
    			$AmazonEC2Client->DeleteSnapshot($ebs_snapshot['snapid']);
    		}
    		catch(Exception $e){}
    	}
    	
    	$db->Execute("DELETE FROM ebs_array_snaps WHERE id=?", array($req_snapshotId));
    	
    	$okmsg = _("Snapshot successfully removed");
    	UI::Redirect("ebs_arrays.php");
    }
    elseif ($req_task == 'snap_create')
    {
    	$DBEBSArray = DBEBSArray::Load($req_array_id);
    	if ($DBEBSArray->ClientID != $Client->ID)
    		UI::Redirect("ebs_array_create.php");
    		
    	try
    	{
    		$DBEBSArray->CreateSnapshot(sprintf(_("Manual snapshot for '%s' array"), $DBEBSArray->Name));
    	}
    	catch (Exception $e)
    	{
    		$errmsg = $e->getMessage();
    	}

    	$okmsg = _("Snapshot successfully initialized");
    	UI::Redirect("ebs_arrays.php");
    }
    elseif ($req_task == 'delete' || $req_task == 'recreate')
    {
    	$DBEBSArray = DBEBSArray::Load($req_array_id);
    	if ($DBEBSArray->ClientID != $Client->ID)
    		UI::Redirect("ebs_array_create.php");
    		
    	$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBEBSArray->Region)); 
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
				
    	//
    	// Remove Volumes
    	//
    	$volumes = $db->GetAll("SELECT volumeid FROM farm_ebs WHERE ebs_arrayid=?", array($DBEBSArray->ID));
    	foreach ($volumes as $volume)
    	{
    		$DBEBSVolume = DBEBSVolume::Load($volume['volumeid']);
    		
    		try
			{
				TaskQueue::Attach(QUEUE_NAME::EBS_DELETE)->AppendTask(new EBSDeleteTask($DBEBSVolume->VolumeID));
				
				$AmazonEC2Client->DeleteVolume($DBEBSVolume->VolumeID);
			}
			catch(Exception $e)
			{
				$errmsg = sprintf(_("Cannot delete volume: %s"), $e->getMessage());
			}
    	}

    	if (!$errmsg)
    	{
	    	if ($req_task == 'delete')
	    	{
	    		$DBEBSArray->Status = EBS_ARRAY_STATUS::PENDING_DELETE;
	    		$DBEBSArray->Save();
	    		
	    		if (count($err) == 0 && !$errmsg)
		    	{
		    		$okmsg = _("EBS Array removal initialized");
		    		UI::Redirect("ebs_arrays.php");
		    	}
	    	}
	    	if ($req_task == 'recreate')
	    	{    	
		    	//
		    	// Create nw volumes for array
		    	//
		   	 	$volumes_count = ceil($DBEBSArray->Size/1000);
		
		   	 	$size = $DBEBSArray->Size;
		   	 	
		   	 	$DBEBSArray->VolumesCount = $volumes_count;
		    	$DBEBSArray->Status = EBS_ARRAY_STATUS::CREATING_VOLUMES;
		    	$DBEBSArray->IsFSCreated = 0;
		    	    	
		    	$CreateVolumeType = new CreateVolumeType();
		    	$CreateVolumeType->availabilityZone = $DBEBSArray->AvailZone;
		    	
		    	try
		    	{
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
			    			$DBEBSVolume->EBSArrayPart = $i;
			    			$DBEBSVolume->Region = $DBEBSArray->Region;
			    			$DBEBSVolume->IsManual = 1;
			    			$DBEBSVolume->Save();
			    		}
			    	}
		    	}
		    	catch(Exception $e)
		    	{
		    		$DBEBSArray->Status = EBS_ARRAY_STATUS::CORRUPT;
		    		
		    		try
		    		{
		    			foreach ((array)$volumes as $volume)
		    				$AmazonEC2Client->DeleteVolume($volume);
		    		}
		    		catch(Exception $e)
		    		{
		    			
		    		}
		    		
		    		$errmsg = sprintf(_("Cannot create EBS array: %s"), $e->getMessage());
		    	}
		    	
		    	$DBEBSArray->Save();
		    	
		    	if (count($err) == 0 && !$errmsg)
		    	{
		    		$okmsg = _("EBS Array recreation initialized");
		    		UI::Redirect("ebs_arrays.php");
		    	}
	    	}
    	}
    }
		
		
	$display["title"] = _("Elastic Block Storage > Arrays");
	
	$sql = "SELECT * from ebs_arrays WHERE clientid='{$_SESSION['uid']}'";

	if ($req_id)
	{
		$id = (int)$req_id;
		$sql .= " AND id='{$id}'";
	}
	
	$paging = new SQLPaging();
	
	//
	//Paging
	//
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = "ORDER BY name DESC";
	$paging->ApplyFilter($_POST["filter_q"], array("name"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetPagerHTML("inc/table_filter.tpl");;
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");
	
	$Smarty->assign(array("table_title_text" => _("Snapshots"), "reload_action" => "ReloadPage();"));
	$display["snaps_filter"] = $Smarty->fetch("inc/table_title.tpl");
	$display["snaps_paging"] = $Smarty->fetch("inc/table_reload_icon.tpl");
	
	//
	// Rows
	//
	$display["rows"] = $db->GetAll($paging->SQL);
	foreach ($display['rows'] as &$row)
	{
		$row['autosnapshoting'] = $db->GetOne("SELECT id FROM autosnap_settings WHERE arrayid=?", array($row['id']));
	}
	
	$display["page_data_options"] = array();
	$display["page_data_options_add"] = true;
	
	require_once ("src/append.inc.php");
?>