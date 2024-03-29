<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	   UI::Redirect("index.php");
		
	$Client = Client::Load($_SESSION['uid']);
	
	$display["title"] = _("Tools&nbsp;&raquo;&nbsp;Amazon Web Services&nbsp;&raquo;&nbsp;Amazon RDS&nbsp;&raquo;&nbsp;Security groups&nbsp;&raquo;&nbsp;Add new");
	$display["add"] = true;
	$template_name = "aws_rds_sec_group_edit.tpl";
		
	
	$AmazonRDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey); 
		
	if ($_POST)
	{	    		
	    $exists_rules = array();
	    $add_rules = array();
		foreach ((array)$post_rules as $rule)
        {
        	$group_rule = explode(":", $rule);
        	
        	if ($group_rule[0] == 'iprange')
				$add_rules[] = array('type' => 'iprange', 'iprange' => $group_rule[1]);
        	else
        		$add_rules[] = array('type' => 'user', 'user' => $group_rule[1], 'group' => $group_rule[2]);

			$new_rules_added = true;
						
			$exists_rules[md5($rule)] = true;
		}
			
		
		try
		{	        
			$AmazonRDSClient->CreateDBSecurityGroup($req_name, $req_description);
			
			if ($new_rules_added)
	        {
				foreach ($add_rules as $r)
				{
		        	// Set permissions for group
		        	if ($r['type'] == 'iprange')
			        	$AmazonRDSClient->AuthorizeDBSecurityGroupIngress($req_name, $r['iprange']);
			        else
			        	$AmazonRDSClient->AuthorizeDBSecurityGroupIngress($req_name, null, $r['group'], $r['user']);
				}
	        }
	        	        
			$okmsg = "DB security group successfully added";	        
	        UI::Redirect("aws_rds_security_groups.php");
		}
		catch(Exception $e)
		{
			$errmsg = $e->getMessage();
		}
	}
	
	require("src/append.inc.php"); 
?>