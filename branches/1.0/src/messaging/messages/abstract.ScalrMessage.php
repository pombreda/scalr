<?php
	
	abstract class ScalrMessage
	{
		public $MessageID;
		
		/**
		 * Constructor
		 * @return void
		 */
		public function __construct()
		{
			$this->MessageID = uniqid(rand(), true);
		}
		
		/**
		 * Parse and return SNMP trap with all params
		 * @return string $trap
		 */
		public function GetSNMPTrap()
		{
			$reflect = new ReflectionClass($this);
			$trap = $reflect->getConstant("SNMP_TRAP");
			
			preg_match_all("/\{([A-Za-z0-9-]+)\}/", $trap, $matches);

			foreach ($matches[1] as $i=>$var)
				$trap = str_replace($matches[0][$i], $this->{$var}, $trap);
			
			return $trap;
		}
	}
?>