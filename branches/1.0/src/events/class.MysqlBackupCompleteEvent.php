<?php
	
	class MysqlBackupCompleteEvent extends Event
	{
		public $InstanceInfo;
		public $Operation;
		public $SnapshotInfo;
		
		public function __construct($InstanceInfo, $Operation, $SnapInfo)
		{
			$this->InstanceInfo = $InstanceInfo;
			$this->Operation = $Operation;
			$this->SnapshotInfo = $SnapInfo;
		}
	}
?>