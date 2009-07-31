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
		
		if ($req_volumeid)
	    {
	    	try
	    	{
	    		$DBEBSVolume = DBEBSVolume::Load($req_volumeid);    			
	    		$region = $DBEBSVolume->Region;
	    	}
	    	catch(Exception $e)
	    	{
	    		$region = $_SESSION['aws_region'];
	    	}
	    }
	    else
	    	$region = $_SESSION['aws_region'];	
	        
	    $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region));
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
	    
	    // Rows
		$aws_response = $AmazonEC2Client->DescribeSnapshots();
	
		$rowz = $aws_response->snapshotSet->item;
		
		if ($rowz instanceof stdClass)
			$rowz = array($rowz);
				
		foreach ($rowz as $pk=>$pv)
		{		
			$pv->startTime = date("Y-m-d H:i:s", strtotime($pv->startTime));
			$item = $pv;	
			
			$info = $db->GetRow("SELECT * FROM ebs_snaps_info WHERE snapid=?", array(
				$item->snapshotId
			));
			
			$item->comment = $info['comment'];
			$item->is_array_snapshot = ($info['arraysnapshotid'] > 0) ? true : false;
			
			$item->progress = (int)preg_replace("/[^0-9]+/", "", $item->progress);
			
			$item->free = 100 - $item->progress;
			
			$item->bar_begin = ($item->progress == 0) ? "empty" : "filled";
	    	$item->bar_end = ($item->free != 0) ? "empty" : "filled";
	    	
	    	$item->used_percent_width = round(120/100*$item->progress, 2);
	    	$item->free_percent_width = round(120/100*$item->free, 2);
	
	    	if ($req_volumeid)
	    	{
	    		if ($req_volumeid == $item->volumeId)
	    		{
	    			$snaps[$item->snapshotId] = $item;
	    		}
	    		
	    		$display["snaps_header"] = sprintf(_("Snapshots for %s"), $item->volumeId); 
	    	}
	    	elseif (!$req_snapid || $req_snapid == $item->snapshotId)
	    	{
	    		$snaps[$item->snapshotId] = $item;
	    	}
		}
	
		ksort($snaps);
		
		$response['total'] = count($snaps);
		
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		
		$snaps = (count($snaps) > $limit) ? array_slice($snaps, $start, $limit) : $snaps;
		
		foreach ($snaps as $snap)
		{
			$row = array(
				'snap_id'			=> $snap->snapshotId,
				'volume_id'			=> $snap->volumeId,
				'status'			=> $snap->status,
				'time'				=> $snap->startTime,
				'comment'			=> $snap->comment,
				'is_array_snapshot'	=> $snap->is_array_snapshot,
				'progress'			=> $snap->progress
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