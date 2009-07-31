<?php
	
	class RebundleCompleteEvent extends Event
	{
		public $AMIID;
		public $InstanceInfo;
		
		public function __construct($InstanceInfo, $AMIID)
		{
			$this->InstanceInfo = $InstanceInfo;
			$this->AMIID = $AMIID;
		}
	}
?>