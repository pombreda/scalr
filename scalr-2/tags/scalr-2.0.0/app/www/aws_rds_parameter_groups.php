<?php
	
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}	
	
	$display['load_extjs'] = true;	
	$Client = Client::Load($_SESSION['uid']);
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon RDS&nbsp;&raquo;&nbsp;Manage parameter groups");
	
	if ($_POST && $post_with_selected)
	{ 
		if ($post_action == 'delete')
		{
			$AmazonRDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
			
			$AmazonRDSClient->SetRegion($_SESSION['aws_region']);
				
			$i = 0;
			foreach ($post_id as $group_name)
			{
				try
				{				
					$AmazonRDSClient->DeleteDBParameterGroup($group_name);
					$i++;
				}
				catch(Exception $e)
				{
					$err[] = sprintf(_("Can't delete db parameter group %s: %s"), $group_name, $e->getMessage());
				}
			}
			
			if (!$err)
				$okmsg = sprintf(_("%s db parameter group(s) successfully removed"), $i);
			
			UI::Redirect("aws_rds_parameter_groups.php");
		}
	}
	
	require("src/append.inc.php"); 	
	
?>
