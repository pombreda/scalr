<?
    class SNMPWatcher
    {
        /**
         * ADODB Instance
         *
         * @var ADODBConnection
         */
    	protected $DB;
    	
    	/**
    	 * SNMPTree
    	 *
    	 * @var SNMPTree
    	 */
        protected $SNMPTree;
        
        protected $Community;
        
        protected $WatchersCache = array();
        
        /**
         * Constructor
         *
         */
        function __construct($community, $path)
        {
            $this->DB = Core::GetDBInstance();
        	
            $this->SNMPTree = Core::GetInstance("SNMPTree");
				
            $this->Community = $community;
            
            $this->DataPath = $path;
        }
        
        public function Connect($host)
        {
        	$this->SNMPTree->Connect($host, 161, $this->Community, 5, 3);
        }
        
        public function RetreiveData($watcher_name)
        {
        	if (!$this->WatchersCache[$watcher_name])
        		$this->WatchersCache[$watcher_name] = new ReflectionClass("{$watcher_name}Watcher");
        		
        	$Watcher = $this->WatchersCache[$watcher_name]->newInstance($this->SNMPTree);
        	
        	return $Watcher->RetreiveData();
        }
        
        public function UpdateRRDDatabase($watcher_name, $data, $name)
        {
        	if (!$this->WatchersCache[$watcher_name])
        		$this->WatchersCache[$watcher_name] = new ReflectionClass("{$watcher_name}Watcher");
        		
        	$Watcher = $this->WatchersCache[$watcher_name]->newInstance(null, $this->DataPath);
        	
        	return $Watcher->UpdateRRDDatabase($name, $data);
        }
        
        public function PlotGraphic($watcher_name, $name)
        {
        	if (!$this->WatchersCache[$watcher_name])
        		$this->WatchersCache[$watcher_name] = new ReflectionClass("{$watcher_name}Watcher");
        		
        	$Watcher = $this->WatchersCache[$watcher_name]->newInstance(null, $this->DataPath);
        	
        	return $Watcher->PlotGraphic($name);
        }
    }
?>