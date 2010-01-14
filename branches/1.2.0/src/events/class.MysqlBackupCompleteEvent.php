<?php
	
	class MysqlBackupCompleteEvent extends Event
	{
		/**
		 * 
		 * @var DBInstance
		 */
		public $DBInstance;
		
		public $Operation;
		public $SnapshotInfo;
		
		public function __construct(DBInstance $DBInstance, $Operation, $SnapInfo)
		{
			parent::__construct();
			
			$this->DBInstance = $DBInstance;
			$this->Operation = $Operation;
			$this->SnapshotInfo = $SnapInfo;
		}
	}
?>