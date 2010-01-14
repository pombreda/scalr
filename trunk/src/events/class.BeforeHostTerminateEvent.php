<?php
	
	class BeforeHostTerminateEvent extends Event 
	{
		/**
		 * 
		 * @var DBInstance
		 */
		public $DBInstance;
		
		public $SkipDeferredOperations = true;
		
		public $ForceTerminate;
		
		public function __construct(DBInstance $DBInstance, $ForceTerminate = true)
		{
			parent::__construct();
			
			$this->DBInstance = $DBInstance;
			$this->ForceTerminate = $ForceTerminate;
		}
	}
?>