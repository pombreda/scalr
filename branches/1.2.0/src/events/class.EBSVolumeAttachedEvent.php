<?php
	
	class EBSVolumeAttachedEvent extends Event 
	{
		public $DeviceName;
		public $VolumeID;
		
		/**
		 * 
		 * @var DBInstance
		 */
		public $DBInstance;
		
		public function __construct(DBInstance $DBInstance, $DeviceName, $VolumeID)
		{
			parent::__construct();
			
			$this->DBInstance = $DBInstance;
			$this->DeviceName = $DeviceName;
			$this->VolumeID = $VolumeID;
		}
		
		public static function GetScriptingVars()
		{
			return array("device_name" => "DeviceName", "volume_id" => "VolumeID");
		}
	}
?>