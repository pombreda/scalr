<?php
	
	class MysqlBackupFailEvent extends Event
	{
		public $InstanceInfo;
		public $Operation;
		
		public function __construct($InstanceInfo, $Operation)
		{
			$this->InstanceInfo = $InstanceInfo;
			$this->Operation = $Operation;
		}
	}
?>