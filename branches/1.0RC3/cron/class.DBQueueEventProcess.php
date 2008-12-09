<?
	class DBQueueEventProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Process events queue";
        public $Logger;
        public $IsDaemon = true;
        private $DaemonMtime;
        private $DaemonMemoryLimit = 20; // in megabytes 
                
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = LoggerManager::getLogger(__CLASS__);
        	
        	$this->DaemonMtime = @filemtime(__FILE__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
            
            // Get pid of running daemon
            $pid = @file_get_contents(CACHEPATH."/".__CLASS__.".Daemon.pid");
            
            $this->Logger->info("Current daemon process PID: {$pid}");
            
            // Check is daemon already running or not
            if ($pid)
            {
	            $Shell = ShellFactory::GetShellInstance();
	            // Set terminal width
	            putenv("COLUMNS=200");
	            
	            // Execute command
            	$ps = $Shell->QueryRaw("ps ax -o pid,ppid,command | grep ' 1' | grep {$pid} | grep -v 'ps x' | grep DBQueueEvent");
            	
            	$this->Logger->info("Shell->QueryRaw(): {$ps}");
            	
            	if ($ps)
            	{
            		// daemon already running
            		$this->Logger->info("Daemon running. All ok!");
            		return true;
            	}
            }
            
            $this->ThreadArgs = array(1);
        }
        
        public function OnEndForking()
        {
            
        }
        
        public function StartThread($data)
        {        	
        	// Reconfigure observers;
        	Scalr::ReconfigureObservers();
        	
        	//
            // Create pid file
            //
            @file_put_contents(CACHEPATH."/".__CLASS__.".Daemon.pid", posix_getpid());
        	
        	// Get memory usage on start
        	$memory_usage = $this->GetMemoryUsage();
            $this->Logger->info("DBQueueEventProcess daemon started. Memory usage: {$memory_usage}M");
            
            // Get DB instance
            $db = Core::GetDBInstance();
            
            $FarmObservers = array();
            
            while(true)
            {
	            // Get event task from task queue
	            while ($Task = TaskQueue::Attach(QUEUE_NAME::DEFERRED_EVENTS)->Poll())
	            {
	            	$event = $db->GetRow("SELECT * FROM events WHERE id=?", array($Task->EventID));
	            	
	            	if ($event)
	            	{
		            	try
		            	{
		            		// Log
		            		$this->Logger->info("Fire event {$event['type']} for farm: {$event['farmid']}");
				            	
				            // Fire event
							Scalr::FireDeferredEvent($event['farmid'], $event['type'], $event['message']);
				            	
				            $db->Execute("UPDATE events SET ishandled='1' WHERE id=?", array($event['id']));
		            	}
		            	catch(Exception $e)
		            	{
		            		$this->Logger->fatal("Cannot fire deferred event: {$e->getMessage()}");
		            	}
	            	}
		            
		            
		            // Cleaning
		            unset($current_memory_usage);
		            unset($event);
		            
		            // Check memory usage
		            $current_memory_usage = $this->GetMemoryUsage()-$memory_usage;
		            if ($current_memory_usage > $this->DaemonMemoryLimit)
		            {
		            	$this->Logger->warn("DBQueueEventProcess daemon reached memory limit {$this->DaemonMemoryLimit}M, Used:{$current_memory_usage}M");
		            	$this->Logger->warn("Restart daemon.");
		            	exit();
		            }
	            }
	            
	            // Sleep for 15 seconds
		        sleep(15);
		        
		        // Clear stat file cache
		        clearstatcache();
		       
		        // Check daemon file for modifications.
		        if ($this->DaemonMtime && $this->DaemonMtime < @filemtime(__FILE__))
		        {
		        	$this->Logger->warn(__FILE__." - updated. Exiting for daemon reload.");
		        	exit();
		        }
            }
        }
        
        /**
         * Return current memory usage by process
         *
         * @return float
         */
        private function GetMemoryUsage()
        {
        	return round(memory_get_usage(true)/1024/1024, 2);
        }
    }
?>