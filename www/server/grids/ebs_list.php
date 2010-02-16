<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		if ($_SESSION["uid"] == 0)
			throw new Exception(_("Requested page cannot be viewed from the admin account"));
		
		$Client = Client::Load($_SESSION['uid']);
		
		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		
		// Rows
		$aws_response = $AmazonEC2Client->DescribeVolumes();
		
		$rowz = $aws_response->volumeSet->item;
		
		if ($rowz instanceof stdClass)
			$rowz = array($rowz);
				
		foreach ($rowz as $pk=>$pv)
		{
			if ($pv->attachmentSet && $pv->attachmentSet->item)
				$pv->attachmentSet = $pv->attachmentSet->item;
			
			$item = $pv;
			
			try
			{
				$DBEBSVolume = DBEBSVolume::Load($item->volumeId);
				
				if ($DBEBSVolume->FarmID)
				{
					$item->Scalr->FarmID = $DBEBSVolume->FarmID;
				}
					
				if ($DBEBSVolume->FarmRoleID)
				{
					try
					{
						$DBFarmRole = DBFarmRole::LoadByID($DBEBSVolume->FarmRoleID);
						$item->Scalr->RoleName = $DBFarmRole->GetRoleName(); 
					}
					catch(Exception $e) {}
				}
					
				if ($DBEBSVolume->EBSArrayID)
				{
					$item->Scalr->ArrayID = $DBEBSVolume->EBSArrayID;
					$item->Scalr->ArrayPartNo = $DBEBSVolume->EBSArrayPart;
				}
			}
			catch (Exception $e)
			{
				
			}	
	
			if (!$item->Scalr->FarmID)
			{
				$item->Scalr->FarmID = $db->GetOne("SELECT farmid FROM farm_instances WHERE instance_id=?", 
					array($pv->attachmentSet->instanceId)
				);
			}
			
			if ($item->Scalr->ArrayID)
			{
				$item->Scalr->ArrayName = $db->GetOne("SELECT name FROM ebs_arrays WHERE id=?",
					array($item->Scalr->ArrayID)
				); 
			}
			
			if ($item->Scalr->FarmID)
			{
				$DBFarm = DBFarm::LoadByID($item->Scalr->FarmID);
				
				$item->Scalr->FarmName = $DBFarm->Name;
				
				$item->Scalr->MySQLMasterVolume = ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_MASTER_EBS_VOLUME_ID) == $item->volumeId) ? true : false;
			}
			else
			{
				$farmid = $db->GetRow("SELECT farmid FROM farm_settings WHERE `name`=? AND `value`=?", 
					array(DBFarm::SETTING_MYSQL_MASTER_EBS_VOLUME_ID, $item->volumeId)
				);
				
				if ($farmid)
				{
					try
					{
						$DBFarm = DBFarm::LoadByID($farmid);
						
						if ($DBFarm->ClientID == $_SESSION["uid"])
						{
							$item->Scalr->FarmID = $DBFarm->ID;
							$item->Scalr->FarmName = $DBFarm->Name;
							$item->Scalr->MySQLMasterVolume = true;
						}
					}
					catch(Exception $e){}
				}
			}
			

			$item->Scalr->AutoSnapshoting = ($db->GetOne("SELECT id FROM autosnap_settings WHERE objectid=? AND object_type=?",
					 array($item->volumeId,AUTOSNAPSHOT_TYPE::EBSSnap))) ? true : false;
			
			///
			/// Generate sort key
			///
			if ($item->Scalr->ArrayID)
				$sort_key = "{$item->Scalr->ArrayID}_{$item->Scalr->ArrayName}_{$item->Scalr->ArrayPartNo}";
			elseif ($item->Scalr->FarmID)
				$sort_key = "{$item->Scalr->FarmID}_{$item->Scalr->RoleName}_{$item->volumeId}";
			else
				$sort_key = "{$item->volumeId}";
			
			//////////////////////
			
			if ($req_farmid)
			{
				if ($req_farmid == $item->Scalr->FarmID)
				{
					$vols[$sort_key] = $item;
				}
			}
			elseif ($req_arrayid)
			{
				if ($req_arrayid == $item->Scalr->ArrayID)
				{
					$vols[$sort_key] = $item;
				}
			}
			elseif (!$req_volume_id || $req_volume_id == $item->volumeId)
			{
				$vols[$sort_key] = $item;
			}
		}
		
		ksort($vols);
	
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		
		$response['total'] = count($vols);
		
		$vols = (count($vols) > $limit) ? array_slice($vols, $start, $limit) : $vols;
		
		foreach ($vols as $vol)
		{
			$row = array(
				'farmid'	=> $vol->Scalr->FarmID,
				'arrayid'	=> $vol->Scalr->ArrayID, 
				'farm_name'	=> $vol->Scalr->FarmName, 
				'role_name'	=> $vol->Scalr->RoleName, 
				'mysql_master_volume' => $vol->Scalr->MySQLMasterVolume, 
				'array_name'=> $vol->Scalr->ArrayName,
				'array_part_no'	=> $vol->Scalr->ArrayPartNo,
				'volume_id'	=> $vol->volumeId, 
				'size'	=> $vol->size, 
				'snapshot_id' => $vol->snapshotId, 
				'avail_zone' => $vol->availabilityZone, 
				'status' => $vol->status, 
				'attachment_status' => $vol->attachmentSet->status,
				'device'	=> $vol->attachmentSet->device,
				'instance_id' => $vol->attachmentSet->instanceId,
				'auto_snap' => $vol->Scalr->AutoSnapshoting
			);
			
			$response['data'][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>