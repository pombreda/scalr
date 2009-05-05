<?php
	
	class ScalarizrCallbackService20090205 extends ScalarizrCallbackService
	{
		public function HostInit()
		{
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?", array($this->DBInstance->ID));
			
			$event = new HostInitEvent(
				$instanceinfo, 
				$this->Request['LocalIP'], 
				$this->GetCallerIPAddress(), 
				base64_decode($this->Request["Based64Pubkey"])
			);
			
			Scalr::FireEvent($this->DBInstance->FarmID, $event);
		}
		
		public function HostUp()
		{
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?", array($this->DBInstance->ID));
			
			$event = new HostUpEvent(
				$instanceinfo, 
				$TODO // TODO:
			);
			
			Scalr::FireEvent($this->DBInstance->FarmID, $event);
		}
	}
?>