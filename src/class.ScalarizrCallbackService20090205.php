<?php
	
	class ScalarizrCallbackService20090205 extends ScalarizrCallbackService
	{
		public function HostInit()
		{
			$event = new HostInitEvent(
				$this->DBInstance, 
				$this->Request['LocalIP'], 
				$this->GetCallerIPAddress(), 
				base64_decode($this->Request["Based64Pubkey"])
			);
			
			Scalr::FireEvent($this->DBInstance->FarmID, $event);
		}
		
		public function HostUp()
		{
			$event = new HostUpEvent(
				$this->DBInstance, 
				$TODO // TODO:
			);
			
			Scalr::FireEvent($this->DBInstance->FarmID, $event);
		}
	}
?>