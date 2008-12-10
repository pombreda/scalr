<?
	class EBSQueueProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Process EBS queues";
        public $Logger;
                
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = LoggerManager::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
                      
            $this->ThreadArgs = array(
            	QUEUE_NAME::EBS_DELETE, 
            	QUEUE_NAME::EBS_MOUNT, 
            	QUEUE_NAME::EBS_STATE_CHECK
            );
        }
        
        public function OnEndForking()
        {
            
        }
        
        public function StartThread($queue_name)
        {        	
        	$this->Logger->info(sprintf("Processing queue: %s", $queue_name));
        	
        	// Reconfigure observers;
        	Scalr::ReconfigureObservers();
            
            // Get DB instance
            $db = Core::GetDBInstance();
            
            $FarmObservers = array();
            
            // Process tasks from EBS status check queue
            while ($Task = TaskQueue::Attach($queue_name)->Peek())
            {
            	$remove_task = false;
            	
            	//TODO: move Max_Fail_attempts to CONFIG
            	if ($Task->FailedAttempts == 5)
            	{
            		$remove_task = true;
            		$this->Logger->error("Task #{$Task->ID} (".serialize($Task).") removed from queue. MaxFailureAttemts limit exceed.");
            	}
            	elseif ($Task->Run())
            	{
            		$remove_task = true;
            	}
            	else
            	{
            		TaskQueue::Attach($queue_name)->IncrementFailureAttemptsCounter();
            	}
            		
            	if ($remove_task)
            		TaskQueue::Attach($queue_name)->Remove($Task);
            }
            // Reset queue
            TaskQueue::Attach($queue_name)->Reset();
        }
    }
?>