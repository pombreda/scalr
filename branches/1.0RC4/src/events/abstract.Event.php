<?php
	
	abstract class Event
	{
		
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