<?php

	class AWSRegions
	{
		const US_EAST_1 = 'us-east-1';
		const EU_WEST_1 = 'eu-west-1';
		
		private static $Regions = array(
			"us-east-1"	=> "https://us-east-1.ec2.amazonaws.com",
			"eu-west-1"	=> "https://eu-west-1.ec2.amazonaws.com"
		);
		
		public static function GetList()
		{
			return array_keys(self::$Regions);
		}
		
		public static function GetAPIURL($region)
		{
			if (self::$Regions[$region])
				return self::$Regions[$region];
			else
				throw new Exception(sprintf(_("Region %s not supported by Scalr"), $region)); 
		}
	}
	
?>