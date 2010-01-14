<?php
	
	class BeforeInstanceLaunchEvent extends Event 
	{
		/**
		 * 
		 * @var DBInstance
		 */
		public $DBInstance;
		
		public $SkipDeferredOperations = true;
		
		public function __construct(DBInstance $DBInstance)
		{
			parent::__construct();
			
			$this->DBInstance = $DBInstance;
		}
	}
?>