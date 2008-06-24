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
        $RunInstancesType->SetUserData("farmid={$farmid};role={$alias};eventhandlerurl=".CONFIG::$EVENTHANDLER_URL.";hash={$farmhash};s3bucket=FARM-{$farmid}-{$clientinfo['aws_accountid']}");
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