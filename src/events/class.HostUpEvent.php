<?php
	
	class HostUpEvent extends Event
	{
		public $InstanceInfo;
		public $ReplUserPass;
		
		public function __construct($InstanceInfo, $ReplUserPass)
		{
			$this->InstanceInfo = $InstanceInfo;
			$this->ReplUserPass = $ReplUserPass;
		}
	}
?>