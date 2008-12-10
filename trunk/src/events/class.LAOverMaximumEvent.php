<?php
	
	class LAOverMaximumEvent extends Event
	{
		public $RoleInfo; 
		public $LA;
		public $MaxLA;
		
		public function __construct($RoleInfo, $LA, $MaxLA)
		{
			$this->RoleInfo = $RoleInfo;
			$this->LA = $LA;
			$this->MaxLA = $MaxLA;
		}
	}
?>