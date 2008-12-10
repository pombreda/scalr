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
		
	foreach ($rules as $rule)
	{
		if ($rule->groups->item  && !is_array($rule->groups->item))
			$rule->groups->item = array($rule->groups->item);
		
		if (count($rule->groups->item) > 0)
		{
			foreach ($rule->groups->item as &$group)
			{
				if ($group)
				{
					$r = clone $rule;
					$r->ip = '0.0.0.0/0';
					$r->rule = "user:{$group->userId}:{$group->groupName}:0.0.0.0/0";
					$r->userId = $group->userId;
					$r->type = 'user';
					$r->groupname = $group->groupName;
					$r->id = md5($r->rule);
					
					if (!$group_rules[$r->id])
					{
						$display['ug_rules'][$r->id] = $r;
						$group_rules[$r->id] = $r;
					}
				}
			}
		}
		else
		{
			$rule->ip = $rule->ipRanges->item->cidrIp;
			$rule->rule = "{$rule->ipProtocol}:{$rule->fromPort}:{$rule->toPort}:{$rule->ip}";
			$rule->id = md5($rule->rule);
			
			$display['rules'][$rule->id] = $rule;
			$group_rules[$rule->id] = $rule;
		}
		
		
	}
	
	if ($_POST)
	{	    		
		$IpPermissionSet = new IpPermissionSetType();
	    $exists_rules = array();
		foreach ((array)$post_rules as $rule)
        {
			if (!$group_rules[md5($rule)] && $rule)
			{
        		$group_rule = explode(":", $rule);
        		
        		if ($group_rule[0] != 'user')
					$IpPermissionSet->AddItem($group_rule[0], $group_rule[1], $group_rule[2], null, array($group_rule[3]));
        		else
        		{
        			$IpPermissionSet->AddItem("tcp", 1, 65535, array('userId' => $group_rule[1], 'groupName' => $group_rule[2]), null);
        			$IpPermissionSet->AddItem("udp", 1, 65535, array('userId' => $group_rule[1], 'groupName' => $group_rule[2]), null);
        			$IpPermissionSet->AddItem("icmp", -1, -1,  array('userId' => $group_rule[1], 'groupName' => $group_rule[2]), null);
        		}

				$new_rules_added = true;
			}
						
			$exists_rules[md5($rule)] = true;
		}
		
		$RevokeIpPermissionSet = new IpPermissionSetType();
		foreach ($group_rules as $rule_hash=>$rule)
		{
			if (!$exists_rules[$rule_hash])
			{
				if ($rule->type != 'user')
				{
					$RevokeIpPermissionSet->AddItem($rule->ipProtocol, $rule->fromPort, $rule->toPort, null, array($rule->ip));
				}
				else
				{
					$RevokeIpPermissionSet->AddItem("tcp", 1, 65535, array('userId' => $rule->userId, 'groupName' => $rule->groupname), null);
					$RevokeIpPermissionSet->AddItem("udp", 1, 65535, array('userId' => $rule->userId, 'groupName' => $rule->groupname), null);
					$RevokeIpPermissionSet->AddItem("icmp", -1, -1, array('userId' => $rule->userId, 'groupName' => $rule->groupname), null);
				}
								
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
	
	require("src/append.inc.php"); 
?>