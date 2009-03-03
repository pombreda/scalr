<?
	require_once('src/prepend.inc.php');
    	    
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	
    $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonEC2Client->SetAuthKeys($_SESSION['aws_private_key'], $_SESSION['aws_certificate']);
    
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
						$errmsg = _("Instance not found");

					if ($db->GetOne("SELECT id FROM farm_ebs WHERE volumeid=?", array($post_volumeId)))
						$errmsg = ("This volume assigned to farm and cannot attach it manually");
						
					if (!$errmsg)
					{
						if ($post_cbtn_2)
						{
							try
							{
								try
								{
									$DBEBSVolume = DBEBSVolume::Load($post_volumeId);
									$DBEBSVolume->Delete();
								}
								catch(Exception $e){}
								
								$DBEBSVolume = new DBEBSVolume($post_volumeId);
								if ($post_attach_on_boot == 1)
								{
									$DBEBSVolume->FarmID = $farminfo['id'];
									$DBEBSVolume->RoleName = $instanceinfo['role_name'];
									$DBEBSVolume->AvailZone = $instanceinfo['avail_zone'];
									$DBEBSVolume->InstanceIndex = $instanceinfo['index'];
									$DBEBSVolume->Region = $instanceinfo['region'];
									$DBEBSVolume->IsManual = 1;
									$DBEBSVolume->Save();
								}
								
								Scalr::AttachEBS2Instance($AmazonEC2Client, $instanceinfo, $farminfo, $DBEBSVolume);
								
								$okmsg = _("EBS volume attachment successfully initialized");
								UI::Redirect("ebs_manage.php");
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
				
				if ($req_volumeId)
				{
					try
					{
						$volumeinfo = $AmazonEC2Client->DescribeVolumes($req_volumeId);
						if ($volumeinfo->volumeSet->item->status != AMAZON_EBS_STATE::AVAILABLE)
							throw new Exception(sprintf(_("Cannot attach volume %s. Volume status: %s"), $req_volumeId, $volumeinfo->volumeSet->item->status));
					}
					catch(Exception $e)
					{
						$errmsg = $e->getMessage();
			    		UI::Redirect("ebs_manage.php");
					}

					$display["instances"] = $db->GetAll("SELECT farm_instances.*, farms.name FROM farm_instances INNER JOIN farms ON farms.id = farm_instances.farmid WHERE avail_zone=? AND state=? AND farms.clientid=?", 
				    	array($volumeinfo->volumeSet->item->availabilityZone, INSTANCE_STATE::RUNNING, $_SESSION['uid'])
				    );
				    
					if (count($display["instances"]) == 0)
				    {
				    	$errmsg = sprintf(_("You don't have running instances in availability zone %s"), $volumeinfo->volumeSet->item->availabilityZone);
				    	UI::Redirect("ebs_manage.php");
				    }
				}
				elseif ($req_instanceID)
				{
					$instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($req_instanceID));
					
					$display['volumes'] = array();
					
					$volumes = $AmazonEC2Client->DescribeVolumes();
					$items = $volumes->volumeSet->item;
					if (!is_array($items))
						$items = array($volumes->volumeSet->item);
						
					foreach ($items as $item)
					{
						if ($item->availabilityZone == $instanceinfo['avail_zone'] && $item->status == AMAZON_EBS_STATE::AVAILABLE)
							array_push($display['volumes'], $item);
					}
					
					if (count($display["volumes"]) == 0)
				    {
				    	$errmsg = sprintf(_("You don't have available EBS volumes in availability zone %s"), $instanceinfo['avail_zone']);
				    	UI::Redirect("ebs_manage.php");
				    }
				}
				else
					UI::Redirect("ebs_manage.php");
				
			    
			    
			    if ($errmsg)
			    	$display["errmsg"] = $errmsg;
			    
				$display["title"] = _("Attach EBS Volume to instance");
				$display["volumeId"] = $req_volumeId;
				$display["iid"] = $req_instanceID;
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
						$okmsg = _("Volume deletion initiated");
						UI::Redirect("ebs_manage.php");
					}
					else
						$errmsg = _("Cannot delete volume");
				}
				catch(Exception $e)
				{
					$errmsg = sprintf(_("Cannot delete volume: %s"), $e->getMessage());
				}
				
				$req_volumeId = false;
				
				break;

			case "snap_delete":
				
				try
				{
					$res = $AmazonEC2Client->DeleteSnapshot($req_snapshotId);

					if ($res->return)
					{
						$okmsg = _("Snapshot successfully removed");
						UI::Redirect("ebs_manage.php");
					}
					else
						$errmsg = _("Cannot remove snapshot");
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
						$r = $AmazonEC2Client->DescribeVolumes($res->volumeId);
						$info = $r->volumeSet->item;
						
						if ($info->attachmentSet->item->instanceId)
						{
							$instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array(
								$info->attachmentSet->item->instanceId
							));
							
							$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array(
								$instanceinfo['farmid']
							));
							
							$comment = sprintf(_("Created on farm '%s', role '%s', instance '%s'"), 
								$farminfo['name'], $instanceinfo['role_name'], $instanceinfo['instance_id']
							);
						}
						else
							$comment = "";
						
						$db->Execute("INSERT INTO ebs_snaps_info SET snapid=?, comment=?, dtcreated=NOW(), region=?",
							array($res->snapshotId, $comment, $_SESSION['aws_region'])
						);
						
						$okmsg = sprintf(_("Snapshot creation initiated. Snapshot ID: %s"), $res->snapshotId);
						UI::Redirect("ebs_manage.php");
					}
					else
						$errmsg = _("Cannot create snapshot");
				}
				catch(Exception $e)
				{
					$errmsg = $e->getMessage();
				}
				
				break;
			
			case "detach":
								
				try
				{
					try
					{
						$DBEBSVolume = DBEBSVolume::Load($req_volumeId);
					}
					catch(Exception $e)
					{
						
					}
					
					if ($_POST)
					{
						$isforce = ($post_force == 1) ? true : false;
												
						$res = $AmazonEC2Client->DetachVolume(new DetachVolumeType($req_volumeId, null, null, $isforce));

						if ($res->volumeId && $res->status == AMAZON_EBS_STATE::DETACHING)
						{
							$okmsg = _("Volume detaching initiated");
							
							if ($DBEBSVolume)
							{
								if ($DBEBSVolume->IsManual && $post_detach_on_boot)
									$DBEBSVolume->Delete();
								elseif ($DBEBSVolume->IsManual == 0)
								{
									$DBEBSVolume->State = FARM_EBS_STATE::AVAILABLE;
									$DBEBSVolume->InstanceID = '';
									$DBEBSVolume->Device = '';
									$DBEBSVolume->Save();
									$okmsg = _("This volume has been attached because you have enabled automatic EBS for this role. It will now be detached, but will be attached again automatically upon next farm/instance start.");
								}
							}
															
							UI::Redirect("ebs_manage.php");
						}
						else
							$errmsg = _("Cannot detach volume");
					}
					
					$display['errmsg'] = $errmsg;
					$display["title"] = _("Detach EBS Volume");
					$display["volumeId"] = $req_volumeId;
					
					if ($DBEBSVolume)
					{
						$display['instanceID'] = $DBEBSVolume->InstanceID;
						$display['attach_on_boot'] = $DBEBSVolume->IsManual;
					}
					
					$Smarty->assign($display);
					$Smarty->display("ebs_detach.tpl");
					exit();
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
		
		try
		{
			$DBEBSVolume = DBEBSVolume::Load($item->volumeId);
			
			if ($DBEBSVolume->FarmID)
			{
				$item->Scalr->FarmID = $DBEBSVolume->FarmID;
			}
				
			if ($DBEBSVolume->RoleName)
				$item->Scalr->RoleName = $DBEBSVolume->RoleName;
				
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
			$item->Scalr->FarmName = $db->GetOne("SELECT name FROM farms WHERE id=?",
				array($item->Scalr->FarmID)
			);
		}
		
		$item->Scalr->AutoSnapshoting = ($db->GetOne("SELECT id FROM autosnap_settings WHERE volumeid=?", array($item->volumeId))) ? true : false;
		
		
		if ($req_farmid)
		{
			if ($req_farmid == $item->Scalr->FarmID)
				$vols[] = $item;
		}
		elseif ($req_arrayid)
		{
			if ($req_arrayid == $item->Scalr->ArrayID)
				$vols[] = $item;
		}
		elseif (!$req_volume_id || $req_volume_id == $item->volumeId)
		{
			$vols[] = $item;
		}
	}
	
	$display["vols"] = $vols;
	
	$Smarty->assign(array("table_title_text" => _("Snapshots"), "reload_action" => "ReloadPage();"));
	$display["snaps_filter"] = $Smarty->fetch("inc/table_title.tpl");
	$display["snaps_paging"] = $Smarty->fetch("inc/table_reload_icon.tpl");
	
	$display["title"] = _("Elastic Block Storage > Manage");
	$display["farmid"] = $req_farmid;
	
	$display["page_data_options"] = array();
	$display["page_data_options_add"] = true;
	
	require_once ("src/append.inc.php");
?>