<?php
		
	interface IScalingAlgo
	{
		public function MakeDecision(DBFarmRole $DBFarmRole);
		
		/**
		 * Must return a DataForm object that will be used to draw a configuration form for this scaling algo.
		 * @return DataForm object
		 */
		public static function GetConfigurationForm($clientid = null);
		
		public static function ValidateConfiguration(array &$config, DBFarmRole $DBFarmRole);
		
		public static function GetAlgoDescription();
	}
?>