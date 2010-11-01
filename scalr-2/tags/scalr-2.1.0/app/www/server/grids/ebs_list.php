<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		Scalr_Session::getInstance()->getAuthToken()->hasAccessEx(Scalr_AuthToken::ACCOUNT_USER);
		
		$AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
			$_SESSION['aws_region'], 
			Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY), 
			Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
		);
		
		// Rows
		$aws_response = $AmazonEC2Client->DescribeVolumes();
		
		$rowz = $aws_response->volumeSet->item;
		
		if ($rowz instanceof stdClass)
			$rowz = array($rowz);

		$vols = array();
		foreach ($rowz as $pk=>$pv)
		{
			if ($pv->attachmentSet && $pv->attachmentSet->item)
				$pv->attachmentSet = $pv->attachmentSet->item;
			
			$item = array(
				'volume_id'	=> $pv->volumeId, 
				'size'	=> $pv->size, 
				'snapshot_id' => $pv->snapshotId, 
				'avail_zone' => $pv->availabilityZone, 
				'status' => $pv->status, 
				'attachment_status' => $pv->attachmentSet->status,
				'device'	=> $pv->attachmentSet->device,
				'instance_id' => $pv->attachmentSet->instanceId,
			);
			
			$sort_key = "{$pv->volumeId}";
			
			try
			{
				$DBEBSVolume = DBEBSVolume::loadByVolumeId($pv->volumeId);

				$item['auto_snaps'] = ($db->GetOne("SELECT id FROM autosnap_settings WHERE objectid=? AND object_type=?",
					 array($pv->volumeId, AUTOSNAPSHOT_TYPE::EBSSnap))) ? true : false;
					
				$sort_key = "{$DBEBSVolume->farmId}_{$DBEBSVolume->farmRoleId}_{$pv->volumeId}";
				
				$item['farm_id'] = $DBEBSVolume->farmId;
				$item['farm_roleid'] = $DBEBSVolume->farmRoleId;
				$item['server_index'] = $DBEBSVolume->serverIndex;
				$item['server_id'] = $DBEBSVolume->serverId;
				$item['mount_status'] = $DBEBSVolume->mountStatus;
				$item['farm_name'] = DBFarm::LoadByID($DBEBSVolume->farmId)->Name;
				$item['role_name'] = DBFarmRole::LoadByID($DBEBSVolume->farmRoleId)->GetRoleObject()->name;
			}
			catch(Exception $e)
			{
				//TODO:
			}
			
			//////////////////////
			
			if ($req_farmid)
			{
				if ($req_farmid == $item['farm_id'])
					$vols[$sort_key] = $item;
			}
			elseif (!$req_volume_id || $req_volume_id == $item['volume_id'])
				$vols[$sort_key] = $item;
		}
		
		ksort($vols);
	
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		
		$response['total'] = count($vols);
		
		$vols = (count($vols) > $limit) ? array_slice($vols, $start, $limit) : $vols;
		
		$response['data'] = array_values($vols);
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>