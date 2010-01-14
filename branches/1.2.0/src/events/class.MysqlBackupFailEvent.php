<?php
	
	class MysqlBackupFailEvent extends Event
	{
		/**
		 * 
		 * @var DBInstance
		 */
		public $DBInstance;
		
		public $Operation;
		
		public function __construct(DBInstance $DBInstance, $Operation)
		{
			parent::__construct();
			
			$this->DBInstance = $DBInstance;
			$this->Operation = $Operation;
		}
	}
?>