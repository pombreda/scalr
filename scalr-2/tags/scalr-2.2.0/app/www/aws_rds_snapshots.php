<?

	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;

	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("/#/dashboard");
	}
    
    if ($post_cancel)
		UI::Redirect(basename(__FILE__)."?farmid={$farminfo['id']}");
    	
	if ($req_action)
	{
		$AmazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$_SESSION['aws_region']
		);
		
		if ($req_action == 'create')
		{
			try
			{
				$snap_id = "scalr-manual-".dechex(microtime(true)*10000).rand(0,9);
				
				$AmazonRDSClient->CreateDBSnapshot($snap_id, $req_name);
				$db->Execute("INSERT INTO rds_snaps_info SET snapid=?, comment=?, dtcreated=NOW(), region=?",
							array($snap_id, "manual RDS instance snapshot", $_SESSION['aws_region']));
			}
			catch(Exception $e)
			{
				$err[] = sprintf(_("Can't create db snapshot: %s"), $e->getMessage());
			}
			
			if (count($err) == 0)
			{
				$okmsg = sprintf(_("DB snapshot '%s' successfully create"), $snap_id);
				UI::Redirect("aws_rds_snapshots.php?name={$req_name}");
			}
		}
		elseif ($req_action == 'delete' && $post_with_selected)
		{
			$i = 0;
			foreach ($post_id as $snap_name)
			{
				try
				{
					$AmazonRDSClient->DeleteDBSnapshot($snap_name);
					$db->Execute("DELETE FROM rds_snaps_info WHERE snapid=? ",array($snap_name));
					$i++;
				}
				catch(Exception $e)
				{
					$err[] = sprintf(_("Can't delete db snapshot %s: %s"), $group_name, $e->getMessage());
				}
			}
			
			if ($i > 0)
				$okmsg = sprintf(_("%s db snapshot(s) successfully removed"), $i);
				
			UI::Redirect("aws_rds_snapshots.php?name={$req_name}");
		}
	}	
	
    if ($req_name)
		$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon RDS&nbsp;&raquo;&nbsp;Snapshots (DBInstance: {$req_name})");
	else
		$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon RDS&nbsp;&raquo;&nbsp;Snapshots");
			
		
	$display["grid_query_string"] = "&name={$req_name}";
		
	require("src/append.inc.php");
?>