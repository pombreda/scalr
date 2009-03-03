<?
	/**
	 * Task for fire deffered event
	 *
	 */
	class FireDeferredEventTask extends Task
	{
		public $EventID;

		/**
		 * Constructor
		 *
		 * @param string $eventid
		 */
		public function __construct($eventid)
		{
			$this->EventID = $eventid;
		}
		
		public function Run()
		{
			$DB = Core::GetDBInstance(null, true);
			
			$event = $DB->GetRow("SELECT * FROM events WHERE id=?", array($this->EventID));
            if ($event)
            {
            	try
            	{
            		LoggerManager::getLogger(__CLASS__)->info(sprintf(_("Fire event %s for farm: %s"), $event['type'], $event['farmid']));
		            	
		            // Fire event
					Scalr::FireDeferredEvent($event['farmid'], $event['type'], $event['message']);
		            $DB->Execute("UPDATE events SET ishandled='1' WHERE id=?", array($event['id']));
            	}
            	catch(Exception $e)
            	{
            		LoggerManager::getLogger(__CLASS__)->fatal(sprintf(_("Cannot fire deferred event: %s"), $e->getMessage()));
            	}
            }
            
            return true;
		}
	}
?>