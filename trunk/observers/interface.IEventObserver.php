<?php

	interface IEventObserver
	{
		public function OnHostUp($instanceinfo);
		
		public function OnHostDown($instanceinfo);
		
		public function OnHostCrash($instanceinfo);
				
		public function OnLAOverMaximum($roleinfo, $LA, $MAX_LA);
		
		public function OnLAUnderMinimum($roleinfo, $LA, $MIN_LA);
		
		public function OnRebundleComplete($roleinfo, $instanceinfo);
		
		public function OnRebundleFailed($instanceinfo);
		
		public function OnRebootBegin($instanceinfo);
		
		public function OnRebootComplete($instanceinfo);
		
		public function OnFarmLaunched();
		
		public function OnFarmTerminated();
	}
?>