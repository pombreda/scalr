<?php
	
	class IPAddressChangedEvent extends Event
	{
		public $InstanceInfo;
		public $NewIPAddress;
		
		public function __construct($InstanceInfo, $NewIPAddress)
		{
			$this->InstanceInfo = $InstanceInfo;
			$this->NewIPAddress = $NewIPAddress;
		}
	}
?>