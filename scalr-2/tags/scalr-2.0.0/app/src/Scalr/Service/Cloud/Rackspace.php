<?php
  
	class Scalr_Service_Cloud_Rackspace
	{
		public static function newRackspaceCS($user, $key)
		{
			return new Scalr_Service_Cloud_RackspaceCS($user, $key);
		}
	}
?>
