<?php
	
	class HostUpEvent extends Event
	{
		/**
		 * 
		 * @var DBInstance
		 */
		public $DBInstance;
		
		public $ReplUserPass;
		
		public function __construct(DBInstance $DBInstance, $ReplUserPass)
		{
			parent::__construct();
			
			$this->DBInstance = $DBInstance;
			$this->ReplUserPass = $ReplUserPass;
		}
	}
?>