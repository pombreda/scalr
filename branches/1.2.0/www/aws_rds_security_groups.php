<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
        
	$display['load_extjs'] = true;
	
	$Client = Client::Load($_SESSION['uid']);
	                        
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon RDS&nbsp;&raquo;&nbsp;Manage security groups");
	
	if ($_POST && $post_with_selected)
	{
		if ($post_action == 'delete')
		{
			$AmazonRDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
			
			foreach ($post_id as $group_name)
			{
				try
				{
					$AmazonRDSClient->DeleteDBSecurityGroup($group_name);
					$i++;
				}
				catch(Exception $e)
				{
					$err[] = sprintf(_("Cannot delete db security group %s: %s"), $group_name, $e->getMessage());
				}
			}
			
			if ($i > 0)
				$okmsg = sprintf(_("%s db secutity group(s) successfully removed"), $i);
				
			UI::Redirect("aws_rds_security_groups.php");
		}
	}
	
	require("src/append.inc.php"); 	
?>