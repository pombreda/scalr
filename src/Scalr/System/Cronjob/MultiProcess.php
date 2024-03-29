<?php

class Scalr_System_Cronjob_MultiProcess extends Scalr_System_Cronjob 
		implements Scalr_System_Ipc_Worker {

	/**
	 * @var Scalr_System_Ipc_ProcessPool
	 */
	protected $processPool;
	
	/**
	 * @var Scalr_System_Cronjob_MultiProcess_Worker
	 */
	protected $worker;
	
	/**
	 * @var Scalr_System_Ipc_Shm
	 */
	protected $shm;
	
	protected $shmKey;
	
	private $workerFileLastMtime;
	
	private $logger;
	
	static function getConfig () {
		return Scalr_Util_Arrays::mergeReplaceRecursive(parent::getConfig(), array(
			"processPool" => array(
				"size" => 3
			)
		));
	}
	
	function __construct ($config=array()) {
		parent::__construct($config);
		$this->logger = LoggerManager::getLogger(__CLASS__);
		if (!$this->jobName) {		
			$this->jobName = strtolower(get_class($this->worker ? $this->worker : $this));
		}
	}
	
	function startForking ($workQueue) {
		if ($this->worker) {
			$this->worker->startForking($workQueue);
			$this->worker->enqueueWork($workQueue);
		}
	}

	function endForking () {
		try {
			$this->processPool->workQueue->delete();
		} catch (Exception $ignore) {
		}
		
		if ($this->worker) {
			try {
				$this->worker->endForking();
			} catch (Exception $workerEx) {
			}
		}
		
		try {
			$this->shm->delete();
		} catch (Exception $ignore) {
		}
		
		if (isset($workerEx)) {
			throw $workerEx;
		}
	}
	
	function childForked ($pid) {
		if ($this->worker) {
			$this->worker->childForked($pid);
		}
	}

	function startChild () {
		if ($this->worker) {
			$this->worker->startChild();
		}
	}

	function handleWork ($message) {
		if ($this->worker) {
			$this->worker->handleWork($message);
		}
	}

	function endChild () {
		if ($this->worker) {
			$this->worker->endChild();
		}
	}
	
	function terminate () {
		if ($this->worker) {
			$this->worker->terminate();
		}
	}
	
	protected function poolIsRunning ($pid) {
		if ($pid < 1) {
			return false;
		}
		
		$os = Scalr_System_OS::getInstance();
		try {
			$status = $os->getProcessStatus($pid);
			return "php" == strtolower($status["Name"]) && posix_kill($pid, 0);
		} catch (Scalr_System_Exception $e) {
			return false;
		}
	} 
	
	protected function init () {
		// Init process pool
		$poolConfig = $this->config["processPool"];
		$poolConfig["worker"] = $this;
		$poolConfig["name"] = $this->jobName;
		if (!is_object($poolConfig["workQueue"])) {
			if (!$poolConfig["workQueue"]) {
				$poolConfig["workQueue"] = array();
			}
			if ($poolConfig["daemonize"]) {
				$poolConfig["workQueue"]["blocking"] = true;
			}
			$poolConfig["workQueue"]["name"] = "scalr.system.cronjob.multiprocess.workQueue-{$this->jobName}";
		}
		$this->processPool = new Scalr_System_Ipc_ProcessPool($poolConfig);
		$this->processPool->addListener($this);
		
		// Init shared memory segment
		$this->shm = new Scalr_System_Ipc_Shm(array(
			"name" => "scalr.system.cronjob.multiprocess.shm-{$this->jobName}"
		));
		
		if ($this->config["fileName"]) {
			if (file_exists($this->config["fileName"])) { 
				$this->workerFileLastMtime = filemtime($this->config["fileName"]);
			} else {
				$this->logger->warn(sprintf("Filename %s doesn't exists. Ignore 'fileName' configuration option", 
						$this->config["fileName"]));
			}
		}
	}
	
	function run ($options=array()) {
		$this->init($options);

		if ($poolPid = $this->shm->get(0)) {
			$this->logger->debug("Get process pool pid from shm. pid: '{$poolPid}'");			
		} else {
			$poolPid = 0;
		}
		
		if ($this->poolIsRunning($poolPid)) {
			$this->logger->warn(sprintf("Cronjob '%s' is already running (pid: %d)", $this->jobName, $poolPid));
			if ($this->worker) {
				$this->logger->info("Enqueue work...");
				$this->worker->enqueueWork($this->processPool->workQueue);
			}
			return;
		}
		
		$this->processPool->start();
	}
	
	protected function checkMemoryLimit () {
		if ($this->config["memoryLimit"]) {
			// Check allocated memory
			$os = Scalr_System_OS::getInstance();
			$this->logger->debug("Get process childs");
			$gpids = $os->getProcessChilds($this->processPool->getPid());
			
			// Get resident memory size for each process in group 
			$memory = 0;
			foreach ($gpids as $pid) {
				$memory += $os->getMemoryUsage($pid, Scalr_System_OS::MEM_RES);
			}
			$memory += $os->getMemoryUsage(posix_getpid(), Scalr_System_OS::MEM_RES);
			
			$this->logger->debug("Memory usage: {$memory} Kb. Memory limit: {$this->config["memoryLimit"]} Kb");
			
			if ($memory > $this->config["memoryLimit"]) {
				$this->logger->warn(sprintf("Cronjob allocates %d Kb. Maximum %d Kb is allowed by configuration", 
						$memory, $this->config["memoryLimit"]));
						
				// Terminate cronjob
				posix_kill(posix_getpid(), SIGTERM);
				
				return 0;
			}
		}
		
		return 1;
	}

	function onReady ($pool) {
		$this->logger->debug("Store process pool (pid: ".$this->processPool->getPid().") in shm");
		$this->shm->put(0, $this->processPool->getPid());
	}
	
	function onSignal ($pool, $signal) {
		switch ($signal) {
			case SIGALRM:
				if ($this->config["fileName"] && $this->workerFileLastMtime) {
					clearstatcache();
					if ($this->workerFileLastMtime < filemtime($this->config["fileName"])) {
						$this->logger->warn("Cronjob worker file was modified");
						posix_kill(posix_getpid(), SIGTERM);
					}
				}
				$this->checkMemoryLimit();
				break;
		}
	}
}