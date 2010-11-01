<?
	require_once('src/prepend.inc.php');
    $display['load_extjs'] = true;	    
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
    $AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
		$_SESSION['aws_region'],
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
	);
    
    if ($req_task)
	{
		switch($req_task)
		{
			case "attach":
				
				try
				{
					$DBEBSVolume = DBEBSVolume::loadByVolumeId($req_volumeId);
					if ($DBEBSVolume->isManual == 0)
					{
						$s['role_name'] = $db->GetOne("SELECT name FROM roles WHERE id=?", array($s['role_id']));
						$s['farm_name'] = $db->GetOne("SELECT name FROM farms WHERE id=?", array($s['farm_id']));
						
						$errmsg = sprintf(_("This volume was automatically created for role '%s' on farm '%s' and cannot be re-attahced manually."),
							$db->GetOne("SELECT name FROM roles INNER JOIN farm_roles ON farm_roles.role_id = roles.id WHERE farm_roles.id=?", array($DBEBSVolume->farmRoleId)),
							$db->GetOne("SELECT name FROM farms WHERE id=?", array($DBEBSVolume->farmId))
						);
						UI::Redirect("/ebs_manage.php");
					}
				}
				catch(Exception $e)
				{
					
				}
				
				if ($_POST)
				{
					try
					{
						$r = $AmazonEC2Client->DescribeVolumes($req_volumeId);
						$info = $r->volumeSet->item;
						
						$DBServer = DBServer::LoadByID($req_serverId);
					}
					catch(Exception $e)
					{
						$err[] = $e->getMessage();
					}

					if ($info && $DBServer)
					{
						try
						{
							$AttachVolumeType = new AttachVolumeType(
								$req_volumeId, 
								$DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID), 
								$DBServer->GetFreeDeviceName()
							);
							
							$res = $AmazonEC2Client->AttachVolume($AttachVolumeType);
						}
						catch(Exception $e)
						{
							$err[] = $e->getMessage();
						}
					}
					
					if ($req_attach_on_boot && count($err) == 0)
					{
						$DBEBSVolume = new DBEBSVolume();
						$DBEBSVolume->attachmentStatus = EC2_EBS_ATTACH_STATUS::ATTACHING;
						$DBEBSVolume->isManual = true;
						$DBEBSVolume->volumeId = $req_volumeId;
						$DBEBSVolume->ec2AvailZone = $info->availabilityZone;
						$DBEBSVolume->ec2Region = $_SESSION['aws_region'];
						$DBEBSVolume->farmId = $DBServer->farmId;
						$DBEBSVolume->farmRoleId = $DBServer->farmRoleId;
						$DBEBSVolume->serverId = $DBServer->serverId;
						$DBEBSVolume->serverIndex = $DBServer->index;
						$DBEBSVolume->size = $info->size;
						$DBEBSVolume->snapId = $info->snapshotId;
						$DBEBSVolume->mount = ($req_mount == 1) ? true: false;
						$DBEBSVolume->mountPoint = $req_mountpoint;
						$DBEBSVolume->mountStatus = ($req_mount == 1) ? EC2_EBS_MOUNT_STATUS::AWAITING_ATTACHMENT : EC2_EBS_MOUNT_STATUS::NOT_MOUNTED;
						$DBEBSVolume->clientId = Scalr_Session::getInstance()->getClientId();
						$DBEBSVolume->envId = Scalr_Session::getInstance()->getEnvironmentId();
						
						
						$DBEBSVolume->Save();
					}

					if (count($err) == 0)
					{
						$okmsg = _("Volume successfully attached");
						UI::Redirect("/ebs_manage.php");
					}
				}
				
				$display['servers'] = $db->GetAll("SELECT * FROM servers WHERE env_id=? AND platform=? AND status=?", array(
					Scalr_Session::getInstance()->getEnvironmentId(), SERVER_PLATFORMS::EC2, SERVER_STATUS::RUNNING
				));
				foreach ($display['servers'] as &$s)
				{
					$s['role_name'] = $db->GetOne("SELECT name FROM roles WHERE id=?", array($s['role_id']));
					$s['farm_name'] = $db->GetOne("SELECT name FROM farms WHERE id=?", array($s['farm_id']));
				}
				
				$display['err'] = $err;
				$display['errmsg'] = $errmsg;
				$display["title"] = _("Attach EBS Volume");
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
						$db->Execute("DELETE FROM ec2_ebs WHERE volume_id=?", array($req_volumeId));
						
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
							try {
								$DBServer = DBServer::LoadByPropertyValue(
									EC2_SERVER_PROPERTIES::INSTANCE_ID, 
									(string)$info->attachmentSet->item->instanceId
								);
								
								$DBFarm = $DBServer->GetFarmObject();
							}
							catch(Exception $e){}
							
							if ($DBServer && $DBFarm)
							{
								$comment = sprintf(_("Created on farm '%s', server '%s' (Instance ID: %s)"), 
									$DBFarm->Name, $DBServer->serverId, (string)$info->attachmentSet->item->instanceId
								);
							}
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
						$DBEBSVolume = DBEBSVolume::loadByVolumeId($req_volumeId);
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
								if ($DBEBSVolume->isManual && $post_detach_on_boot)
								{
									$DBEBSVolume->delete();
								}
								elseif ($DBEBSVolume->isManual == 0)
								{
									$DBEBSVolume->attachmentStatus = EC2_EBS_ATTACH_STATUS::AVAILABLE;
									$DBEBSVolume->mountStatus = EC2_EBS_MOUNT_STATUS::NOT_MOUNTED;
									$DBEBSVolume->serverId = '';
									$DBEBSVolume->deviceName = '';
									$DBEBSVolume->save();
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
						$display['serverId'] = $DBEBSVolume->serverId;
						$display['attach_on_boot'] = $DBEBSVolume->isManual;
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

	$display['warnmsg'] = _("You should never manually re-assign EBS volumes that are auto-assigned by Scalr.");
	
	if ($req_farmid)
		$display['grid_query_string'] .= "&farmid={$req_farmid}";

	if ($req_arrayid)
		$display['grid_query_string'] .= "&arrayid={$req_arrayid}";
		
	if ($req_volume_id)
		$display['grid_query_string'] .= "&volume_id={$req_volume_id}";
	
	$display["title"] = _("Elastic Block Storage > Manage");
	
	require_once ("src/append.inc.php");
?>