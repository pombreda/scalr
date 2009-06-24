<?
	class ScalingAlgo
	{
		const UPSCALE = 'UpScalingNeeded';
		const DOWNSCALE = 'DownScalingNeeded';
		const NOOP = 'NoActionNeeded';
				
		protected $Properties = array();
		
		function __construct()
		{
			
		}
		
		/**
		 * Set properties list from database
		 * @param array $properties
		 * @return void
		 */
		function SetProperties($properties)
		{
			$this->Properties = $properties;
		}
		
		/**
		 * Get specified property by name
		 * @param string $name
		 * @return void
		 */
		function GetProperty($name)
		{
			return $this->Properties[$name];
		}
		
		/**
		 * Returns all algo properties
		 * @return array
		 */
		function GetProperties()
		{
			return $this->Properties;
		}
	}
?>