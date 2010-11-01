<? 
	require("src/prepend.inc.php"); 
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	if ($post_cancel)
	{
        if ($req_arrayID)
        	UI::Redirect("ebs_arrays.php");
        else
			UI::Redirect("ebs_manage.php");
	}
	
	$AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
		$req_region,
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
		Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
	);

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
		$info = $db->GetRow("SELECT * FROM autosnap_settings WHERE volumeid=? AND env_id=?", 
			array($req_volumeId, Scalr_Session::getInstance()->getEnvironmentId())
		);
		
		$display["title"] = _("Auto-snapshots settings for volume '{$req_volumeId}'");
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
						env_id		= ?,
						volumeid	= ?,
						period		= ?,
						rotate		= ?,
						region		= ?,
						arrayid		= ?
					", array(
						Scalr_Session::getInstance()->getClientId(),
						Scalr_Session::getInstance()->getEnvironmentId(),
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
					WHERE env_id = ? AND (volumeid = ? OR arrayid=?)
					", array(
						$post_period,
						$post_rotate,
						Scalr_Session::getInstance()->getEnvironmentId(),
						$req_volumeId,
						$req_array_id
					));
					
					$okmsg = sprintf(_("Auto-snapshot settings successfully updated"), $req_volumeId);
				}

				if ($req_volumeId)
					UI::Redirect("ebs_manage.php");
			}
		}
		else
		{
			if ($req_volumeId)
				$db->Execute("DELETE FROM autosnap_settings WHERE volumeid=? AND env_id=?", 
					array($req_volumeId, Scalr_Session::getInstance()->getEnvironmentId())
				);
				
			$okmsg = sprintf(_("Auto-snapshots successfully disabled"));
			
			if ($req_volumeId)
				UI::Redirect("ebs_manage.php");
		}
	}
	
	
	$display['auto_snap'] = $info;
	$display['visible'] = ($info) ? "" : "none";
	$display["region"] = $req_region;
	        
	require("src/append.inc.php"); 
?>