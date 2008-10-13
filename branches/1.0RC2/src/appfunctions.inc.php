<?
	function RunInstance($AmazonEC2Client, $sec_group, $farmid, $role, $farmhash, $ami, $dbmaster = false, $active = true)
    {
        $db = Core::GetDBInstance();
        
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id='{$farmid}'");
        $clientinfo = $db->GetRow("SELECT * FROM clients WHERE id='{$farminfo["clientid"]}'");
        
        $ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$ami}'");
        
        $alias = $ami_info["alias"];
        $role_name = $ami_info["name"];
                
        $farm_role_info = $db->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND ami_id=?", array($farmid, $ami));
        if ($farm_role_info)
        	$i_type = $farm_role_info["instance_type"];
        else
        	$i_type = $db->GetOne("SELECT instance_type FROM ami_roles WHERE ami_id='{$ami}'");

       	//
       	// Check Security group - start
       	//	    
       	
        // get security group list for client
	    $client_security_groups = $AmazonEC2Client->DescribeSecurityGroups();
        if (!$client_security_groups)
           throw new Exception("Cannot describe security groups for client.");
                
        $client_security_groups = $client_security_groups->securityGroupInfo->item;
        if ($client_security_groups instanceof stdClass)
        	$client_security_groups = array($client_security_groups);  
        
        // Check security groups
        $addSecGroup = true;
        foreach ($client_security_groups as $group)
        {
            // Group exist. No need to add new
            if (strtolower($group->groupName) == strtolower($sec_group))
            {
        	    $addSecGroup = false;
                break;
            }
        }
        	
    	if ($addSecGroup)
	    {
			$res = $AmazonEC2Client->CreateSecurityGroup($sec_group, $name);
			if (!$res)
				throw new Exception("Cannot create security group", E_USER_ERROR);	                        
                           
			// Get permission rules for group
            $group_rules = $db->GetAll("SELECT * FROM security_rules WHERE roleid=(SELECT id FROM ami_roles WHERE name='{$alias}')");	                        
            $IpPermissionSet = new IpPermissionSetType();
            foreach ($group_rules as $rule)
            {
            	$group_rule = explode(":", $rule["rule"]);
                $IpPermissionSet->AddItem($group_rule[0], $group_rule[1], $group_rule[2], null, array($group_rule[3]));
            }

            // Create security group
            $AmazonEC2Client->AuthorizeSecurityGroupIngress($clientinfo['aws_accountid'], $sec_group, $IpPermissionSet);
	    }
        //
        // Check Security group - end
        //
        	
        	
        $RunInstancesType = new RunInstancesType();
        $RunInstancesType->imageId = $ami;
        $RunInstancesType->minCount = 1;
        $RunInstancesType->maxCount = 1;
        $RunInstancesType->AddSecurityGroup("default");
        $RunInstancesType->AddSecurityGroup($sec_group);
        
        if ($farm_role_info["avail_zone"])
        	$RunInstancesType->SetAvailabilityZone($farm_role_info["avail_zone"]);
        
        $RunInstancesType->additionalInfo = "";
        $RunInstancesType->keyName = "FARM-{$farmid}";
        $RunInstancesType->SetUserData("farmid={$farmid};role={$alias};eventhandlerurl=".CONFIG::$EVENTHANDLER_URL.";hash={$farmhash};s3bucket=FARM-{$farmid}-{$clientinfo['aws_accountid']};realrolename={$role_name};httpproto=".CONFIG::$HTTP_PROTO);
        $RunInstancesType->instanceType = $i_type;
                
        $result = $AmazonEC2Client->RunInstances($RunInstancesType);
        
        if ($result->instancesSet)
        {
            $isdbmaster = ($dbmaster) ? '1' : '0';
        	$isactive = ($active) ? '1' : '0';
            
        	$instace_id = $result->instancesSet->item->instanceId;
	        $db->Execute("INSERT INTO 
	        							farm_instances 
	        					  SET 
	        					  		farmid=?, 
	        					  		instance_id=?, 
	        					  		ami_id=?, 
	        					  		dtadded=NOW(), 
	        					  		isdbmaster=?,
	        					  		isactive = ?,
	        					  		role_name = ?
	        			 ", array($farmid, $instace_id, $ami, $isdbmaster, $isactive, $role_name));
        }
        else 
        {
            LoggerManager::getLogger('RunInstance')->fatal($result->faultstring);
            return false;
        }
        
        return $instace_id;
    }	
?>