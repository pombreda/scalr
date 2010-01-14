<?php
	
	class NewMysqlMasterUpEvent extends Event
	{
		/**
		 * 
		 * @var DBInstance
		 */
		public $DBInstance;
		public $SnapURL;
		
		/**
		 * 
		 * @var DBInstance
		 */
		public $OldMasterInstance;
		
		public function __construct(DBInstance $DBInstance, $SnapURL, DBInstance $OldMasterInstance)
		{
			parent::__construct();
			
			$this->DBInstance = $DBInstance;
			$this->SnapURL = $SnapURL;
			$this->OldMasterInstance = $OldMasterInstance;
		}
	}
?>