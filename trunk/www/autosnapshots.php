<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
		
	// select autosnapshot type by params
	
	if ($req_volumeId)
	{
		$objectId =  $req_volumeId;
		$object_type = AUTOSNAPSHOT_TYPE::EBSSnap;
	}
	elseif ($req_array_id)
	{
		$objectId =  $req_array_id;
		$object_type = AUTOSNAPSHOT_TYPE::EBSArraySnap;
	}
	elseif ($req_name)
	{
		$objectId =  $req_name;
		$object_type = AUTOSNAPSHOT_TYPE::RDSSnap;
	}
	
	if ($post_cancel)
	{
       switch($object_type)
		{
			case AUTOSNAPSHOT_TYPE::EBSSnap: 		UI::Redirect("ebs_manage.php"); 			break;
			case AUTOSNAPSHOT_TYPE::EBSArraySnap:	UI::Redirect("ebs_arrays.php");				break;
			case AUTOSNAPSHOT_TYPE::RDSSnap:		UI::Redirect("aws_rds_instances_view.php");	break;
			default: break;				
		}
	}
	
	$Client = Client::Load($_SESSION['uid']);
	
	switch($object_type)
	{
		case AUTOSNAPSHOT_TYPE::EBSSnap: 
			{
				// checks correctness of  EBS instance volume
				try
				{
					$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($req_region)); 
					$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
					
					$AmazonEC2Client->DescribeVolumes($objectId);
				}
				catch(Exception $e)
				{
					$errmsg = $e->getMessage();
					UI::Redirect("ebs_manage.php");
				}				
				$display["volumeId"] = $objectId;
				$display["title"] = _("Auto-snapshots settings for volume '{$objectId}'");	
				break;	
			}
		case AUTOSNAPSHOT_TYPE::EBSArraySnap: 
			{
				// checks correctness of  EBSArray IDs
				$DBEBSArray = DBEBSArray::Load($objectId);
				
				if ($DBEBSArray->ClientID != $_SESSION['uid'])
					UI::Redirect("ebs_arrays.php");
				
				$display["array_id"] = $objectId;
				$display["title"] = _("Auto-snapshots settings for EBS array '{$DBEBSArray->Name}'");
				break;		
			}
		case AUTOSNAPSHOT_TYPE::RDSSnap: 
			{
				// checks correctness of  RDS instance name
				try
				{
					$AmazonRDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
					$AmazonRDSClient->DescribeDBInstances($req_name);
				}
				catch(Exception $e)
				{
					$errmsg = $e->getMessage();
					UI::Redirect("aws_rds_instances_view.php");
				}
				
				$display["title"] = _("Auto-snapshots settings for RDS instance '{$objectId}'");
				break;		
			}
			
	}
	// look for existing settings in DB by current objectid and type
	$info = $db->GetRow("SELECT * FROM autosnap_settings WHERE 
		objectid = ? AND 
		object_type = ? AND 
		clientid = ?", 
	array(
		$objectId,
		$object_type,
	 	$Client->ID
	));
		
	$redirect = false;
	
	if ($_POST)
	{	
		// if we change settings...
		if ($post_enable == 1)
		{	
			if (!$err)
			{
				if (!$info)
				{			
					// add new settings record		
					$db->Execute("INSERT INTO autosnap_settings SET
						clientid 	= ?,						
						period		= ?,
						rotate		= ?,
						region		= ?,
						objectid	= ?,
						object_type	= ?
					", array(
						$Client->ID,						
						$post_period,
						$post_rotate,
						$req_region,
						$objectId,						
						$object_type
					));
					
					$okmsg = sprintf(_("Auto-snapshots successfully enabled for %s"), $objectId);
				}
				else
				{		
					// or update old settings record			
					$db->Execute("UPDATE autosnap_settings SET
						period		= ?,
						rotate		= ?
						WHERE clientid = ? AND objectid = ? AND object_type = ?
					", array(
						$post_period,
						$post_rotate,
						$Client->ID,
						$objectId,
						$object_type
					));
								
					$okmsg = sprintf(_("Auto-snapshot settings successfully updated for %s"), $objectId);
				}
				
				// redirect by type to the  previos page
				$redirect = true;
			}
		}
		else
		{			
			// if we don't want to continue  using settings for this instance (or volume)
			$db->Execute("DELETE FROM autosnap_settings WHERE 
				objectid=? AND 
				object_type=? AND 
				clientid=?",
			 array(
			 	$objectId,
			 	$object_type,
			 	$Client->ID)
			);			
				
			$okmsg = sprintf(_("Auto-snapshots successfully disabled"));
			
			$redirect = true;
		}
		
		if($redirect)	
			switch($object_type)
				{
					case AUTOSNAPSHOT_TYPE::EBSSnap: 		UI::Redirect("ebs_manage.php"); 			break;
					case AUTOSNAPSHOT_TYPE::EBSArraySnap:	UI::Redirect("ebs_arrays.php");				break;
					case AUTOSNAPSHOT_TYPE::RDSSnap:		UI::Redirect("aws_rds_instances_view.php");	break;	
					default: break;				
				}
	}
	
	$display['auto_snap'] = $info;
	$display['visible'] = ($info) ? "" : "none";
	$display["region"] = $req_region;
	        
	require("src/append.inc.php"); 
?>