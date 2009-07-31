<?
	/**
	 * Task for DNS zone deletion
	 *
	 */
	class DeleteDNSZoneTask extends Task
	{
		public $ZoneID;
		
		function __construct($zoneid)
		{
			$this->ZoneID = $zoneid;
		}
	}
?>