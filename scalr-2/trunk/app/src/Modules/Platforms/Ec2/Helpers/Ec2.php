<?php

	class Modules_Platforms_Ec2_Helpers_Ec2
	{   		
		
		public static function farmValidateRoleSettings($settings, $rolename)
		{
			
		}
		
		public static function farmUpdateRoleSettings(DBFarmRole $DBFarmRole, $oldSettings, $newSettings)
		{
			
		}
		
		/**
		* // Creates a list of Amazon's security groups  
		* 
		*/
		public static function loadSecurityGroups()
		{  
		 
			$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 			
			$Client = Client::Load($_SESSION['uid']);   
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);			 	 
		       			
			$securityGroupSet = $AmazonEC2Client->DescribeSecurityGroups();
			
			$i = 0;			
			foreach($securityGroupSet->securityGroupInfo->item as $sgroup)			
			{  	 				
				$securityGroupNamesSet[$i]['name'] = (string)$sgroup->groupName;	
			    $i++;
			}
			
			return $securityGroupNamesSet;		
		}
	}

?>