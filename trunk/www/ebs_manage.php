<?
	require_once('src/prepend.inc.php');
    	    
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
	
    $AmazonEC2Client = new AmazonEC2($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);

    if ($req_task)
	{
		switch($req_task)
		{
			case "attach":
				
				if ($_POST)
				{
					$instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($post_inststanceId));
					$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($instanceinfo['farmid']));
					
					if ($farminfo['clientid'] != $_SESSION['uid'])
					{
						$errmsg = "Instance not found";
					}
					else
					{
						if ($post_cbtn_2)
						{
							try
							{
								$AttachVolumeType = new AttachVolumeType();
								$AttachVolumeType->instanceId = $post_inststanceId;
								$AttachVolumeType->volumeId = $post_volumeId;
								
								try
								{
									$SNMP = new SNMP();
									$SNMP->Connect($instanceinfo['external_ip'], 161, $farminfo['hash'], false, false, true);
									$result = $SNMP->GetTree("UCD-DISKIO-MIB::diskIODevice");
									
									$map = array(
										"a", "b", "c", "d", "e", "f", "g", "h", "i", "j", 
										"k", "l", "m", "n", "o", "p", "q", "r", "s", "t", 
										"u", "v", "w", "x", "y", "z"
									);
									
									$map_used = array();
									
									foreach ($result as $disk)
									{
										if (preg_match("/^sd([a-z])[0-9]*$/", $disk, $matches))
											array_push($map_used, $matches[1]);
									}
									
									$device_l = false;
									while (count($map) != 0 && (in_array($device_l, $map_used) || $device_l == false))
										$device_l = array_shift($map);
										
									if (!$device_l)
										$errmsg = "There is not available device letter on instance for attaching EBS";
								}
								catch(Exception $e)
								{
									$errmsg = $e->getMessage();
								}
																
								if (!$errmsg)
								{
									$AttachVolumeType->device = "/dev/sd{$device_l}";
									$res = $AmazonEC2Client->AttachVolume($AttachVolumeType);
									
									if ($res->status == 'attaching')
									{
										$okmsg = "Volume successfully attached to instance";
										UI::Redirect("ebs_manage.php");
									}
									else
										$errmsg = "Cannot attach volume at this time. Please try again later.";
								}
							}
							catch(Exception $e)
							{
								$errmsg = $e->getMessage();
							}
						}
						else
							UI::Redirect("ebs_manage.php");
					}
				}

				$display["instances"] = $db->GetAll("SELECT farm_instances.*, farms.name FROM farm_instances INNER JOIN farms ON farms.id = farm_instances.farmid WHERE state=? AND farms.clientid=?", 
			    	array(INSTANCE_STATE::RUNNING, $_SESSION['uid'])
			    );
				
			    if (count($display["instances"]) == 0)
			    {
			    	$errmsg = "You don't have running instances";
			    	UI::Redirect("ebs_manage.php");
			    }
			    
			    if ($errmsg)
			    	$display["errmsg"] = $errmsg;
			    
				$display["title"] = "Attach Elastic block storage to instance";
				$display["volumeId"] = $req_volumeId;
				$Smarty->assign($display);
				$Smarty->display("ebs_attach.tpl");
				exit();
				
				break;
				
			case "create_volume":
				
				UI::Redirect("ebs_volume_create.php?snapid={$req_snapid}");
				
				break;
			
			case "delete_volume":
				
				try
				{
					$res = $AmazonEC2Client->DeleteVolume($req_volumeId);
					if ($res->return)
					{
						$okmsg = "Volume deletion initiated";
						UI::Redirect("ebs_manage.php");
					}
					else
						$errmsg = "Cannot delete volume";
				}
				catch(Exception $e)
				{
					$errmsg = $e->getMessage();
				}
				
				break;
				
			case "snap_create":
				
				try
				{
					$res = $AmazonEC2Client->CreateSnapshot($req_volumeId);

					if ($res->snapshotId)
					{
						$okmsg = "Snapshot creation initiated. Snapshot ID: {$res->snapshotId}";
						UI::Redirect("ebs_manage.php");
					}
					else
						$errmsg = "Cannot create snapshot";
				}
				catch(Exception $e)
				{
					$errmsg = $e->getMessage();
				}
				
				break;
			
			case "detach":
				
				try
				{
					$res = $AmazonEC2Client->DetachVolume(new DetachVolumeType($req_volumeId));

					if ($res->volumeId && $res->status == 'detaching')
					{
						$okmsg = "Volume detaching initiated";
						UI::Redirect("ebs_manage.php");
					}
					else
						$errmsg = "Cannot detach volume";
				}
				catch(Exception $e)
				{
					$errmsg = $e->getMessage();
				}
				
				break;
		}
	}
    
	if ($req_farmid)
		$req_view = false;
	
	if ($req_view)
		$display["view"] = $req_view;
	
	// Rows
	$response = $AmazonEC2Client->DescribeVolumes();
	
	$rowz = $response->volumeSet->item;
	
	if ($rowz instanceof stdClass)
		$rowz = array($rowz);
			
	foreach ($rowz as $pk=>$pv)
	{
		if ($pv->attachmentSet && $pv->attachmentSet->item)
			$pv->attachmentSet = $pv->attachmentSet->item;
		
		$item = $pv;
		
		$item->farmId = $db->GetOne("SELECT farmid FROM farm_instances WHERE instance_id=?", 
			array($pv->attachmentSet->instanceId)
		);
		
		if ($item->farmId)
		{
			$item->farmName = $db->GetOne("SELECT name FROM farms WHERE id=?",
				array($item->farmId)
			);
		}
		
		if ($req_farmid)
		{
			if ($req_farmid == $item->farmId)
				$vols[] = $item;
		}
		elseif (!$req_volume_id || $req_volume_id == $item->volumeId)
		{
			$vols[] = $item;
		}
	}
	
	$display["vols"] = $vols;
	
	// Rows
	$response = $AmazonEC2Client->DescribeSnapshots();

	$rowz = $response->snapshotSet->item;
		
	if ($rowz instanceof stdClass)
		$rowz = array($rowz);
			
	foreach ($rowz as $pk=>$pv)
	{		
		$pv->startTime = date("Y-m-d H:i:s", strtotime($pv->startTime));
		$item = $pv;	
		
		$item->progress = (int)preg_replace("/[^0-9]+/", "", $item->progress);
		
		$item->free = 100 - $item->progress;
		
		$item->bar_begin = ($item->progress == 0) ? "empty" : "filled";
    	$item->bar_end = ($item->free != 0) ? "empty" : "filled";
    	
    	$item->used_percent_width = round(190/100*$item->progress, 2);
    	$item->free_percent_width = round(190/100*$item->free, 2);

    	if ($req_volumeId)
    	{
    		if ($req_volumeId == $item->volumeId)
    		{
    			$snaps[] = $item;
    		}
    		
    		$display["snaps_header"] = "Snapshots for {$item->volumeId}"; 
    	}
    	elseif (!$req_snap_id || $req_snap_id == $item->snapshotId)
    	{
			$snaps[] = $item;
    	}
	}
	
	$display["snaps"] = $snaps;
	$display["title"] = "Elastic Block Storage > Manage";
	$display["farmid"] = $req_farmid;
	
	$display["page_data_options"] = array();
	$display["page_data_options_add"] = true;
	
	require_once ("src/append.inc.php");
?>