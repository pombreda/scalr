<?php
	
	class HostDownEvent extends Event
	{
		public $InstanceInfo;
		
		public function __construct($InstanceInfo)
		{
			$this->InstanceInfo = $InstanceInfo;
		}
	}
?>