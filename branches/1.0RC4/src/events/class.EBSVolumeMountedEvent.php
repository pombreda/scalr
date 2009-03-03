<?php
	
	class EBSVolumeMountedEvent extends Event 
	{
		public $Mountpoint;
		public $InstanceInfo;
		
		public $SkipDeferredOperations = true;
		
		public function __construct($InstanceInfo, $Mountpoint)
		{
			$this->InstanceInfo = $InstanceInfo;
			$this->Mountpoint = $Mountpoint;
		}
	}
?>