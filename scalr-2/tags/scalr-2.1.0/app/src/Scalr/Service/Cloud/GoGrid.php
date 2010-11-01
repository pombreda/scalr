<?php
	class Scalr_Service_Cloud_GoGrid
	{
		public static function newGoGrid($apiKey, $secretKey)
		{
			return new Scalr_Cloud_Service_GoGridCH($apiKey, $secretKey);
		}
	} 
?>
