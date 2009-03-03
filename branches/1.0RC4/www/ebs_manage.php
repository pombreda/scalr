<?
	require_once('src/prepend.inc.php');
    	    
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
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
						$errmsg = _("Instance not found");
					}
					else
					{
						if ($post_cbtn_2)
						{
							try
							{
								Scalr::AttachEBS2Instance($AmazonEC2Client, $instanceinfo, $farminfo, $post_volumeId);
								
								$okmsg = _("EBS attachment successfully initialized");
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

				$display["instances"] = $db->GetAll("SELECT farm_instances.*, farms.name FROM farm_instances INNER JOIN farms ON farms.id = farm_instances.farmid WHERE state=? AND farms.clientid=?", 
			    	array(INSTANCE_STATE::RUNNING, $_SESSION['uid'])
			    );
				
			    if (count($display["instances"]) == 0)
			    {
			    	$errmsg = _("You don't have running instances");
			    	UI::Redirect("ebs_manage.php");
			    }
			    
			    if ($errmsg)
			    	$display["errmsg"] = $errmsg;
			    
				$display["title"] = _("Attach Elastic block storage to instance");
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
						
						$instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array(
							$info->attachmentSet->item->instanceId
						));
						
						$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array(
							$instanceinfo['farmid']
						));
						
						$comment = sprintf(_("Created on farm '%s', role '%s', instance '%s'"), 
							$farminfo['name'], $instanceinfo['role_name'], $instanceinfo['instance_id']
						);
						
						$db->Execute("INSERT INTO ebs_snaps_info SET snapid=?, comment=?, dtcreated=NOW()",
							array($res->snapshotId, $comment)
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
					$res = $AmazonEC2Client->DetachVolume(new DetachVolumeType($req_volumeId));

					if ($res->volumeId && $res->status == AMAZON_EBS_STATE::DETACHING)
					{
						$okmsg = _("Volume detaching initiated");
						UI::Redirect("ebs_manage.php");
					}
					else
						$errmsg = _("Cannot detach volume");
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
		
		$info = $db->GetRow("SELECT * FROM farm_ebs WHERE volumeid=?", array($item->volumeId));
		
		if ($info)
		{
			$item->farmId = $info["farmid"];
			$item->roleName = $info["role_name"];
		}
		else
		{
			$item->farmId = $db->GetOne("SELECT farmid FROM farm_instances WHERE instance_id=?", 
				array($pv->attachmentSet->instanceId)
			);
		}
		
				
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
	
	$Smarty->assign(array("table_title_text" => _("Snapshots"), "reload_action" => "ReloadPage();"));
	$display["snaps_filter"] = $Smarty->fetch("inc/table_title.tpl");
	$display["snaps_paging"] = $Smarty->fetch("inc/table_reload_icon.tpl");
	
	$display["title"] = _("Elastic Block Storage > Manage");
	$display["farmid"] = $req_farmid;
	
	$display["page_data_options"] = array();
	$display["page_data_options_add"] = true;
	
	require_once ("src/append.inc.php");
?>