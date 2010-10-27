<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
        
	$display['load_extjs'] = true;
	
    $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonEC2Client->SetAuthKeys($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);
                        
	$display["title"] = "Roles&nbsp;&raquo;&nbsp;Security groups";
	
	if ($_POST && $post_with_selected)
	{
		if ($post_action == 'delete')
		{
			$i = 0;
			foreach ($post_id as $group_name)
			{
				try
				{
					$AmazonEC2Client->DeleteSecurityGroup($group_name);
					$i++;
				}
				catch(Exception $e)
				{
					$err[] = sprintf(_("Cannot delete group %s: %s"), $group_name, $e->getMessage());
				}
			}
			
			if ($i > 0)
				$okmsg = sprintf(_("%s security group(s) successfully removed"), $i);
				
			UI::Redirect("sec_groups_view.php");
		}
	}
	
	require("src/append.inc.php"); 	
?>