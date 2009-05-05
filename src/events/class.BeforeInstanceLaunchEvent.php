<?php
	
	class BeforeInstanceLaunchEvent extends Event 
	{
		public $InstanceInfo;
		
		public $SkipDeferredOperations = true;
		
		public function __construct($InstanceInfo)
		{
			$this->InstanceInfo = $InstanceInfo;
		}
	}
?>