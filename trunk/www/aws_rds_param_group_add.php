<?php
	require("src/prepend.inc.php"); 
		
	if ($_SESSION["uid"] == 0)
	   UI::Redirect("index.php");
	
	$Client = Client::Load($_SESSION['uid']);
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon RDS&nbsp;&raquo;&nbsp;Add  new group");	
		
	$AmazonRDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey); 
		
	
	if($_POST)
	{	
		try
		{			
			if (!$req_newGroupName)
			{
				$errmsg = "Please add parameter group name";
				UI::Redirect("aws_rds_param_group_add.php");
			}
			if (!$req_engine)
			{
				$errmsg = "Please select parameter group engine";
				UI::Redirect("aws_rds_param_group_add.php");
			}
			if (!$req_description)
			{
				$errmsg = "Please add description";
				UI::Redirect("aws_rds_param_group_add.php");
			}
								
			$res = $AmazonRDSClient->CreateDBParameterGroup($req_newGroupName,$req_description,$req_engine);
			$okmsg = "DB parameter group successfully created";	
			UI::Redirect("aws_rds_param_group_edit.php?name={$req_newGroupName}");
		}
		catch(Exception $e)
		{
			
			$err[] = sprintf(_("Cannot create db parameter group %s: %s"), $req_newGroupName, $e->getMessage());
		}
	}	
			
	require("src/append.inc.php"); 
?>