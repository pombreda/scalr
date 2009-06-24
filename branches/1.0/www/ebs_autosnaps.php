<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
		
	$Client = Client::Load($_SESSION['uid']);
	
	if ($post_cancel)
	{
        if ($req_arrayID)
        	UI::Redirect("ebs_arrays.php");
        else
			UI::Redirect("ebs_manage.php");
	}
	
	$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($req_region)); 
	$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);

	if ($req_volumeId)
	{
		try
		{
			$res = $AmazonEC2Client->DescribeVolumes($req_volumeId);
		}
		catch(Exception $e)
		{
			$errmsg = $e->getMessage();
			UI::Redirect("ebs_manage.php");
		}
		
		$display["volumeId"] = $req_volumeId;
		$info = $db->GetRow("SELECT * FROM autosnap_settings WHERE volumeid=? AND clientid=?", array($req_volumeId, $Client->ID));
		
		$display["title"] = _("Auto-snapshots settings for volume '{$req_volumeId}'");
	}
	elseif ($req_array_id)
	{
		$DBEBSArray = DBEBSArray::Load($req_array_id);
		if ($DBEBSArray->ClientID != $_SESSION['uid'])
			UI::Redirect("ebs_arrays.php");
		
		$display["array_id"] = $req_array_id;
		$info = $db->GetRow("SELECT * FROM autosnap_settings WHERE arrayid=? AND clientid=?", array($req_array_id, $Client->ID));
		$display["title"] = _("Auto-snapshots settings for EBS array '{$DBEBSArray->Name}'");
	}
	
	if ($_POST)
	{
		if ($post_enable == 1)
		{
			
			if (!$err)
			{
				if (!$info)
				{
					$db->Execute("INSERT INTO autosnap_settings SET
						clientid 	= ?,
						volumeid	= ?,
						period		= ?,
						rotate		= ?,
						region		= ?,
						arrayid		= ?
					", array(
						$Client->ID,
						($req_volumeId) ? $req_volumeId : 0,
						$post_period,
						$post_rotate,
						$req_region,
						($req_array_id) ? $req_array_id : 0
					));
					
					$okmsg = sprintf(_("Auto-snapshots successfully enabled"), $req_volumeId);
				}
				else
				{
					$db->Execute("UPDATE autosnap_settings SET
						period		= ?,
						rotate		= ?
					WHERE clientid = ? AND (volumeid = ? OR arrayid=?)
					", array(
						$post_period,
						$post_rotate,
						$Client->ID,
						$req_volumeId,
						$req_array_id
					));
					
					$okmsg = sprintf(_("Auto-snapshot settings successfully updated"), $req_volumeId);
				}

				if ($req_volumeId)
					UI::Redirect("ebs_manage.php");
				else
					UI::Redirect("ebs_arrays.php");
			}
		}
		else
		{
			$db->Execute("DELETE FROM autosnap_settings WHERE (volumeid=? OR arrayid=?) AND clientid=?", array($req_volumeId, $req_array_id, $Client->ID));
			$okmsg = sprintf(_("Auto-snapshots successfully disabled"));
			
			if ($req_volumeId)
				UI::Redirect("ebs_manage.php");
			else
				UI::Redirect("ebs_arrays.php");
		}
	}
	
	
	$display['auto_snap'] = $info;
	$display['visible'] = ($info) ? "" : "none";
	$display["region"] = $req_region;
	        
	require("src/append.inc.php"); 
?>