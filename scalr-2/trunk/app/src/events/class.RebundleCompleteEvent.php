<?php
	
	class RebundleCompleteEvent extends Event
	{
		public $SnapshotID;
		public $BundleTaskID;
		
		/**
		 * 
		 * @var DBInstance
		 */
		public $DBServer;
		
		public function __construct(DBServer $DBServer, $SnapshotID, $BundleTaskID)
		{
			parent::__construct();
			
			$this->DBServer = $DBServer;
			$this->SnapshotID = $SnapshotID;
			$this->BundleTaskID = $BundleTaskID;
		}
	}
?>