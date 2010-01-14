<?php

	class Scalr_Cronjob_DBQueueEvent extends Scalr_System_Cronjob_MultiProcess_DefaultWorker
    {
        private $logger;
                
        static function getConfig () {
        	return array(
        		"description" => "Process events queue",
				"processPool" => array(
					"size" => 1,
					"daemonize" => true
				),
				"memoryLimit" => 40960, // 40Mb
				"fileName" => __FILE__
        	);
        }
        
    	function __construct()
        {
        	// Get Logger instance
        	$this->logger = LoggerManager::getLogger(__CLASS__);
        }
        
        function startChild ()
        {
        	// Reconfigure observers;
        	Scalr::ReconfigureObservers();

            $this->logger->info("DBQueueEventProcess daemon started");
            
            // Get DB instance
            $db = Core::GetDBInstance();
            
            $taskQueue = TaskQueue::Attach(QUEUE_NAME::DEFERRED_EVENTS);
            while(true)
            {
            	// Process tasks from Deferred event queue
	            while ($Task = $taskQueue->Poll()) {
	            	$Task->Run();
	            }
	            // Reset
	            $taskQueue->Reset();
   
	            // Sleep for 15 seconds
		        sleep(15);
            }
        }
    }
