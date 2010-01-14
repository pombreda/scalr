<?php
	
	class EBSVolumeMountedEvent extends Event 
	{
		public $Mountpoint;
		public $VolumeID;
		
		/**
		 * 
		 * @var DBInstance
		 */
		public $DBInstance;
		
		public function __construct(DBInstance $DBInstance, $Mountpoint, $VolumeID)
		{
			parent::__construct();
			
			$this->DBInstance = $DBInstance;
			$this->Mountpoint = $Mountpoint;
			$this->VolumeID = $VolumeID;
		}
		
		public static function GetScriptingVars()
		{
			return array("mountpoint" => "Mountpoint", "volume_id" => "VolumeID");
		}
	}
?>