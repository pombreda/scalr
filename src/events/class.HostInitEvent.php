<?php
	
	class HostInitEvent extends Event
	{
		public $InstanceInfo;
		public $InternalIP;
		public $ExternalIP;
		public $PublicKey;
		
		public function __construct($InstanceInfo, $InternalIP, $ExternalIP, $PublicKey)
		{
			$this->InstanceInfo = $InstanceInfo;
			$this->InternalIP = $InternalIP;
			$this->ExternalIP = $ExternalIP;
			$this->PublicKey = $PublicKey;
		}
	}
?>