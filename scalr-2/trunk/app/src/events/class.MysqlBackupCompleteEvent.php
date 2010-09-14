<?php
	
	class MysqlBackupCompleteEvent extends Event
	{
		/**
		 * 
		 * @var DBServer
		 */
		public $DBServer;
		
		public $Operation;
		public $SnapshotInfo;
		
		public $snapshotId;
		public $logFile;
		public $logPos;
		
		public function __construct(DBServer $DBServer, $Operation, $SnapInfo)
		{
			parent::__construct();
			
			$this->DBServer = $DBServer;
			$this->Operation = $Operation;
			$this->SnapshotInfo = $SnapInfo;
		}
	}
?>