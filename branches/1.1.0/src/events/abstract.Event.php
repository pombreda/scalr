<?php
	
	abstract class Event
	{
		public $SkipDeferredOperations = false;
		
		/**
		 * Returns event name
		 *
		 * @return string
		 */
		public function GetName()
		{
			return str_replace(__CLASS__, "", get_class($this));
		}
	}
?>