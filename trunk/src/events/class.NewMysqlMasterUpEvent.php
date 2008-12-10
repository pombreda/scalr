<?php
	
	class NewMysqlMasterUpEvent extends Event
	{
		public $InstanceInfo;
		public $SnapURL;
		
		public function __construct($InstanceInfo, $SnapURL)
		{
			$this->InstanceInfo = $InstanceInfo;
			$this->SnapURL = $SnapURL;
		}
	}
?>