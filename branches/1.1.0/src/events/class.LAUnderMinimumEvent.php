<?php
	
	class LAUnderMinimumEvent extends Event
	{
		public $RoleInfo; 
		public $LA;
		public $MinLA;
		
		public function __construct($RoleInfo, $LA, $MinLA)
		{
			$this->RoleInfo = $RoleInfo;
			$this->LA = $LA;
			$this->MinLA = $MinLA;
		}
	}
?>