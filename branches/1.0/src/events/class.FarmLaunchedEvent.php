<?php
	
	class FarmLaunchedEvent extends Event 
	{
		public $MarkInstancesAsActive;
		
		public function __construct($MarkInstancesAsActive)
		{
			$this->MarkInstancesAsActive = $MarkInstancesAsActive;
		}
	}
?>