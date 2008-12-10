<?php
	
	class RebundleFailedEvent extends Event
	{
		public $InstanceInfo;
		
		public function __construct($InstanceInfo)
		{
			$this->InstanceInfo = $InstanceInfo;
		}
	}
?>