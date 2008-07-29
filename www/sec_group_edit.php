<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	   UI::Redirect("index.php");
	
	if (!$req_name || !stristr($req_name, CONFIG::$SECGROUP_PREFIX))
	{
	    $errmsg = "Please select security group from list";
	    UI::Redirect("sec_group_view.php");
	}
	
	
	$display["title"] = "Security group&nbsp;&raquo;&nbsp;Edit";
	
	$display["group_name"] = $req_name;
	
	$AmazonEC2Client = new AmazonEC2($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);
	
	// Rows
	try
	{
		$response = $AmazonEC2Client->DescribeSecurityGroups($req_name);
		
		$group = $response->securityGroupInfo->item;
		if ($group && $group instanceof stdClass)
		{
			$rules = $group->ipPermissions->item;
			
			if ($rules instanceof stdClass)
				$rules = array($rules);
		}
	}
	catch(Exception $e)
	{
		$errmsg = $e->getMessage();
		UI::Redirect("sec_group_view.php");
	}

	$group_rules = array();
	
	foreach ($rules as &$rule)
	{
		$rule->ip = $rule->ipRanges->item->cidrIp;
		$rule->rule = "{$rule->ipProtocol}:{$rule->fromPort}:{$rule->toPort}:{$rule->ip}";
		$rule->id = md5($rule->rule);
		
		$group_rules[$rule->id] = $rule;
	}
	
	if ($_POST)
	{	    		
		$IpPermissionSet = new IpPermissionSetType();
	    $exists_rules = array();
		foreach ((array)$post_rules as $rule)
        {
			if (!$group_rules[md5($rule)])
			{
        		$group_rule = explode(":", $rule);
				$IpPermissionSet->AddItem($group_rule[0], $group_rule[1], $group_rule[2], null, array($group_rule[3]));
				$new_rules_added = true;
			}
			
			$exists_rules[md5($rule)] = true;
		}
		
		$RevokeIpPermissionSet = new IpPermissionSetType();
		foreach ($group_rules as $rule_hash=>$rule)
		{
			if (!$exists_rules[$rule_hash])
			{
				$RevokeIpPermissionSet->AddItem($rule->ipProtocol, $rule->fromPort, $rule->toPort, null, array($rule->ip));
				$remove_rules = true;
			}
		}
		
		try
		{
	        if ($new_rules_added)
	        {
				// Set permissions for group
		        $AmazonEC2Client->AuthorizeSecurityGroupIngress($_SESSION['aws_accountid'], $req_name, $IpPermissionSet);
	        }
	        
	        if ($remove_rules)
	        {
	        	// Remove removed rules
	        	$AmazonEC2Client->RevokeSecurityGroupIngress($_SESSION['aws_accountid'], $req_name, $RevokeIpPermissionSet);
	        }
	        
			$okmsg = "Security group successfully updated";	        
	        UI::Redirect("sec_groups_view.php");
		}
		catch(Exception $e)
		{
			$errmsg = $e->getMessage();
		}
	}
			
	$display["rules"] = $rules;
	
	require("src/append.inc.php"); 
?>