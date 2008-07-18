<?
	class DBQueueEventProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Process events queue";
        public $Logger;
        public $IsDaemon = true;
        private $DaemonMemoryLimit = 20; // in megabytes 
                
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = LoggerManager::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance(null, true);
            
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
            //
            // Attach observers
            //
        	Scalr::AttachObserver(new MailEventObserver());
        	Scalr::AttachObserver(new RESTEventObserver());
        	
        	//
            // Create pid file
            //
            @file_put_contents(CACHEPATH."/".__CLASS__.".Daemon.pid", posix_getpid());
        	
        	// Get memory usage on start
        	$memory_usage = $this->GetMemoryUsage();
            $this->Logger->info("DBQueueEventProcess daemon started. Memory usage: {$memory_usage}M");
            
            // Get DB instance
            $db = Core::GetDBInstance(null, true);
            
            $FarmObservers = array();
            
            while(true)
            {
	            // Get events list
            	$events = $db->Execute("SELECT * FROM events WHERE ishandled='0'");
	            while ($event = $events->FetchRow())
	            {
	            	$this->Logger->info("Fire event {$event['type']} for farm: {$event['farmid']}");
	            	
	            	// Fire event
	            	Scalr::FireEvent($event['farmid'], $event['type'], $event['message']);
	            	
	            	$db->Execute("UPDATE events SET ishandled='1' WHERE id=?", array($event['id']));
	            }
	            
	            // Cleaning
	            unset($current_memory_usage);
	            unset($event);
	            unset($events);
	            
	            // Check memory usage
	            $current_memory_usage = $this->GetMemoryUsage()-$memory_usage;
	            if ($current_memory_usage > $this->DaemonMemoryLimit)
	            {
	            	$this->Logger->warn("DBQueueEventProcess daemon reached memory limit {$this->DaemonMemoryLimit}M, Used:{$current_memory_usage}M");
	            	$this->Logger->warn("Restart daemon.");
	            	exit();
	            }
	            
	            // Sleep for 5 seconds
	            sleep(5);
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