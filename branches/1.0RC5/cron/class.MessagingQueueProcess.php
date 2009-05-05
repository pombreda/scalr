<?
	class MessagingQueueProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Manage messages queues";
        public $Logger;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = LoggerManager::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
            
            // Check undelivered messges and try to send them again.
            $messages = $db->Execute("SELECT * FROM messages WHERE isdelivered='0' AND UNIX_TIMESTAMP(dtlastdeliveryattempt)+delivery_attempts*120 < UNIX_TIMESTAMP(NOW())");
            
            while ($message = $messages->FetchRow())
            {
				try
				{
					$DBInstance = DBInstance::LoadByIID($message['instance_id']);
					$version = $DBInstance->GetScalarizrVersion();
					
					if ($message['delivery_attempts'] >= 3)
					{
						$db->Execute("UPDATE messages SET isdelivered='3' WHERE id=?", array($message['id']));
					}
					else
					{
						// Only 0.2-68 or greater version support this feature.
						if ($DBInstance->IsSupported("0.2-68"))
						{					
							$msg = XMLMessageSerializer::Unserialize($message['message']);
							$DBInstance->SendMessage($msg);
						}
						else
						{
							$db->Execute("UPDATE messages SET isdelivered='2' WHERE id=?", array($message['id']));
						}
					}
				}
				catch(Exception $e)
				{
					
				}
            }
        }
        
        public function OnEndForking()
        {
            
        }
        
        public function StartThread($farminfo)
        {
            
        }
    }
?>