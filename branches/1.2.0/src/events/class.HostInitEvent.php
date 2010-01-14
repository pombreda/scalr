<?php
	
	class HostInitEvent extends Event
	{
		/**
		 * 
		 * @var DBInstance
		 */
		public $DBInstance;
		
		public $InternalIP;
		public $ExternalIP;
		public $PublicKey;
		
		public function __construct(DBInstance $DBInstance, $InternalIP, $ExternalIP, $PublicKey)
		{
			parent::__construct();
			
			$this->DBInstance = $DBInstance;
			$this->InternalIP = $InternalIP;
			$this->ExternalIP = $ExternalIP;
			$this->PublicKey = $PublicKey;
		}
	}
?>