<?php
	
	class RebundleFailedEvent extends Event
	{
		/**
		 * 
		 * @var DBInstance
		 */
		public $DBInstance;
		
		public function __construct(DBInstance $DBInstance)
		{
			parent::__construct();
			
			$this->DBInstance = $DBInstance;
		}
	}
?>