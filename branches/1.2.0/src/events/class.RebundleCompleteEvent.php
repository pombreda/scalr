<?php
	
	class RebundleCompleteEvent extends Event
	{
		public $AMIID;
		
		/**
		 * 
		 * @var DBInstance
		 */
		public $DBInstance;
		
		public function __construct(DBInstance $DBInstance, $AMIID)
		{
			parent::__construct();
			
			$this->DBInstance = $DBInstance;
			$this->AMIID = $AMIID;
		}
	}
?>