<?
	function RunInstance($AmazonEC2Client, $sec_group, $farmid, $role, $farmhash, $ami)
    {
        $db = Core::GetDBInstance();
        
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id='{$farmid}'");
        $clientinfo = $db->GetRow("SELECT * FROM clients WHERE id='{$farminfo["clientid"]}'");
        
        $alias = $db->GetOne("SELECT alias FROM ami_roles WHERE ami_id='{$ami}'");
        
        $RunInstancesType = new RunInstancesType();
        $RunInstancesType->imageId = $ami;
        $RunInstancesType->minCount = 1;
        $RunInstancesType->maxCount = 1;
        $RunInstancesType->AddSecurityGroup("default");
        $RunInstancesType->AddSecurityGroup($sec_group);
        $RunInstancesType->additionalInfo = "";
        $RunInstancesType->keyName = "FARM-{$farmid}";
        $RunInstancesType->SetUserData("farmid={$farmid};role={$alias};eventhandlerurl=".CF_EVENTHANDLER_URL.";hash={$farmhash};s3bucket=FARM-{$farmid}-{$clientinfo['aws_accountid']}");
        $RunInstancesType->instanceType = "m1.small";
                
        $result = $AmazonEC2Client->RunInstances($RunInstancesType);
        
        if ($result->instancesSet)
        {
            $instace_id = $result->instancesSet->item->instanceId;
	        $db->Execute("INSERT INTO farm_instances SET farmid=?, instance_id=?, ami_id=?, dtadded=NOW()", array($farmid, $instace_id, $ami));
        }
        else 
        {
            Log::Log($result->faultstring, E_ERROR);
            return false;
        }
        
        return $instace_id;
    }	
?>