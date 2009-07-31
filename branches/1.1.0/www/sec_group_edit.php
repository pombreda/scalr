<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	   UI::Redirect("index.php");
	
	if (!$req_name || (!stristr($req_name, CONFIG::$SECGROUP_PREFIX) && !$_SESSION['sg_show_all']))
	{
	    $errmsg = "Please select security group from list";
	    UI::Redirect("sec_groups_view.php");
	}
	
	
	$display["title"] = "Security group&nbsp;&raquo;&nbsp;Edit";
	
	$display["group_name"] = $req_name;	
	
	$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
	$AmazonEC2Client->SetAuthKeys($_SESSION["aws_private_key"], $_SESSION["aws_certificate"]);
	
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
		elseif (count($rule->ipRanges->item) > 1)
		{
			foreach ($rule->ipRanges->item as &$ipRange)
			{
				if ($ipRange)
				{
					$r = clone $rule;
					
					$r->ip = $ipRange->cidrIp;
					$r->rule = "{$r->ipProtocol}:{$r->fromPort}:{$r->toPort}:{$ipRange->cidrIp}";
					
					$r->id = md5($r->rule);
					
					if (!$display['rules'][$r->id])
					{
						$display['rules'][$r->id] = $r;
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
		try
	        {
				$role_name = str_replace(CONFIG::$SECGROUP_PREFIX, "", $req_name);
				$db_master_instance = $db->GetRow("SELECT * FROM farm_instances WHERE role_name=? AND isdbmaster='1' AND farmid IN (SELECT id FROM farms WHERE clientid=?)", array(
		        	$role_name, $_SESSION['uid']
		        ));
				if ($db_master_instance)
				{
			        $iinfo = $AmazonEC2Client->DescribeInstances($db_master_instance['instance_id']);
			        $i = $iinfo->reservationSet->item;
			        if ($i)
			        {
			        	foreach ($i->groupSet->item as $item)
			        	{
			        		if ($item->groupId != 'default' && $item->groupId != CONFIG::$MYSQL_STAT_SEC_GROUP)
			        			$db_master_sec_group = $item->groupId;
			        	}
			        }
				}
	        }
	        catch(Exception $e)
	        {
	        	$Logger->fatal("Edit sec group: ".$e->getMessage());
	        }
	        
			if ($new_rules_added)
	        {
				// Set permissions for group
		        $AmazonEC2Client->AuthorizeSecurityGroupIngress($_SESSION['aws_accountid'], $req_name, $IpPermissionSet);
		        
		        if ($db_master_sec_group)       	
	        		$AmazonEC2Client->AuthorizeSecurityGroupIngress($_SESSION['aws_accountid'], $db_master_sec_group, $IpPermissionSet);
	        }
	        
	        if ($remove_rules)
	        {
	        	// Remove removed rules
	        	$AmazonEC2Client->RevokeSecurityGroupIngress($_SESSION['aws_accountid'], $req_name, $RevokeIpPermissionSet);
	        	
	        	if ($db_master_sec_group)
	        		$AmazonEC2Client->RevokeSecurityGroupIngress($_SESSION['aws_accountid'], $db_master_sec_group, $RevokeIpPermissionSet);
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