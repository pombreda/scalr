<?
	/**
	 * Task for DNS zone creation
	 *
	 */
	class CreateDNSZoneTask extends Task
	{
		public $ZoneID;
		
		function __construct($zoneid)
		{
			$this->ZoneID = $zoneid;
		}
	}
?>