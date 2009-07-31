<?php
	
	class RebootCompleteEvent extends Event
	{
		public $InstanceInfo;
		
		public function __construct($InstanceInfo)
		{
			$this->InstanceInfo = $InstanceInfo;
		}
	}
?>