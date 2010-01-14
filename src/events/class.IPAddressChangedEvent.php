<?php
	
	class IPAddressChangedEvent extends Event
	{
		/**
		 * 
		 * @var DBInstance
		 */
		public $DBInstance;
		
		public $NewIPAddress;
		
		public function __construct(DBInstance $DBInstance, $NewIPAddress)
		{
			parent::__construct();
			
			$this->DBInstance = $DBInstance;
			$this->NewIPAddress = $NewIPAddress;
		}
		
		public static function GetScriptingVars()
		{
			return array("new_ip_address" => "NewIPAddress");
		}
	}
?>