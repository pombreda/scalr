<?php

/**
 * Process pool for executing work queue
 * 
 * @author Marat Komarov
 */

class Scalr_System_Ipc_ProcessPool extends Scalr_Util_Observable {
	
	public $name;
	
	public $size;
	
	public $workQueue;
	
	public $worker;
	
	public $daemonize;
	
	public $workTimeout;
	
	public $workerTimeout;
	
	public $workerMemoryLimit;
	
	public $workerMemoryLimitTick = 10000; // 10 seconds
	
	public $startupTimeout = 5000; // 5 seconds
	
	public $termTimeout = 5000; // 5 seconds
	
	public $preventParalleling;
	
	private $nowWorkingSet;
	
	protected $poolPid;
	
	protected $childPid;
	
	protected $childs = array();
	
	protected $isChild = false;
	
	protected $childEventQueue;	

	protected $ready = false;	
	
	private static $termExitCode = 9;
	
	private $logger;
	
	private $timeLogger;
	
	private $stopForking = false;
	
	private $timeoutFly;
	
	private $inWaitLoop;
	
	protected $slippageLimit = 10;
	
	private $slippage = 0;
	
	private $cleanupComplete = false;
	
	/**
	 * @var Scalr_System_Ipc_Shm
	 */
	private $shm;
	
	/**
	 * @var Scalr_System_Ipc_ShmArray
	 */
	private $workersStat;
	
	const SHM_STARTUP_BARRIER = 0;
	
	/**
	 * 
	 * @param array $config
	 * @key int [size]*
	 * @key Scalr_DataQueue [workQueue]* Work queue. Must be multi-process safe (ex impl: Scalr_System_Ipc_ShmQueue)
	 * @key Scalr_System_Ipc_DefaultWorker [worker]*
	 * @key string [name] Pool name. Will be used instead of posix_getpid() as a ipc resources suffix
	 * @key int [startupTimeout] Time to wait when 'start' event will be received from all childs 
	 * @key int [workTimeout] Max execution time for $worker->handleWork() (default infinity)
	 * @key int [workerTimeout] Max execution time for worker process (default infinity)
	 * @key int [termTimeout] Time to wait after sending SIGTERM to worker process (default 5 seconds)
	 * @key bool [daemonize] daemonize process pool (default false)
	 * @key bool [preventParalleling] Prevents same work parallel processing
	 * @key Scalr_Util_Set [nowWorkingSet] Set of currently blocked item values. Use with [preventParalleling] option
	 * @key int [slippageLimit] Maximum number of childs crash without processing messages from workQueue
	 * @key int [workerMemoryLimit] Memory limit for worker process
	 * @key int [workerMemoryLimitTick] Tick time for worker memory limit check   
	 * 
	 * @event ready
	 * @event shutdown
	 * @event signal
	 * 
	 * @return Scalr_System_Ipc_ProcessPool
	 */
	function __construct ($config) {
		// Check system requirements
        if (substr(PHP_OS, 0, 3) === 'WIN') {
            throw new Scalr_System_Ipc_Exception('Cannot run on windows');
        } else if (!in_array(substr(PHP_SAPI, 0, 3), array('cli', 'cgi'))) {
        	throw new Scalr_System_Ipc_Exception('Can only run on CLI or CGI enviroment');
        } else if (!function_exists('pcntl_fork')) {
        	throw new Scalr_System_Ipc_Exception('pcntl_* functions are required');
        } else if (!function_exists('posix_kill')) {
        	throw new Scalr_System_Ipc_Exception('posix_* functions are required');
        }

        // Apply configuration
        foreach ($config as $k => $v) {
        	if (property_exists($this, $k)) {
        		$this->{$k} = $v;
        	}
		}
		if ($this->size < 1) {
			throw new Scalr_System_Ipc_Exception(sprintf(
					"'size' must be more then 1. '%s' is given", $this->size));
		}
		
		if ($this->workQueue && !is_object($this->workQueue)) {
			$this->workQueue = new Scalr_System_Ipc_ShmQueue($this->workQueue);
		}
		
		if ($this->preventParalleling) {
			if (!$this->nowWorkingSet) {
				$this->nowWorkingSet = new Scalr_System_Ipc_ShmSet(array(
					"name" => "scalr.ipc.processPool.nowWorkingSet-" . ($this->name ? $this->name : posix_getpid())
				));
			}
		}
		
		$this->shm = new Scalr_System_Ipc_Shm(array(
			"name" => "scalr.ipc.processPool.shm-" . ($this->name ? $this->name : posix_getpid())
		));
		
		$this->defineEvents(array(
			/**
			 * Fires when pool process received Unix signal
			 * @event signal
			 * @param Scalr_System_Ipc_ProcessPool $ppool
			 * @param int $signal
			 */
			"signal",
		
			/**
			 * Fires when pool ready for processing tasks
			 * @event ready
			 * @param Scalr_System_Ipc_ProcessPool $ppool
			 */
			"ready",
		
			/**
			 * Fires when pool is going terminate 
			 * @event shutdown
			 * @param Scalr_System_Ipc_ProcessPool $ppool
			 */
			"shutdown"
		));
		
		$this->logger = Logger::getLogger(__CLASS__);
		$this->timeLogger = Logger::getLogger("time");
		register_shutdown_function(array($this, "_cleanup"));
	}
	
	function start () {
		$t1 = microtime(true);
		$msg = "Starting process pool (size: {$this->size}";
		if ($this->daemonize) $msg .= ", daemonize: true";
		if ($this->preventParalleling) $msg .= ", preventParalleling: true";
		$msg .= ")";
        $this->logger->info($msg);
		
		// @see http://www.php.net/manual/en/function.pcntl-fork.php#41150
        @ob_end_flush();		
		
		if ($this->daemonize) {
			$this->logger->info("Going to daemonize process pool. Fork child process");
			$pid = pcntl_fork();
			if ($pid == -1) {
				throw new Scalr_System_Ipc_Exception("Cannot daemonize process pool: cannot fork process");
			} else if ($pid == 0) {
				// Child
				$this->logger->info("Detaching process from terminal");
				if (posix_setsid() == -1) {
					throw new Scalr_System_Ipc_Exception("Cannot detach process from terminal");
				}
				$this->sleepMillis(200);
			} else {
				// Parent process
				die();
			}
		}
		
		
		$this->poolPid = posix_getpid();
		$this->initSignalHandler();
		
		/*
		$this->workersStat = new Scalr_System_Ipc_ShmArray(array(
			"name" => "scalr.ipc.processPool.workersStat.{$this->poolPid}" 
		));
		*/
		
		$this->shm->put(self::SHM_STARTUP_BARRIER, 0);
		
		$this->childEventQueue = new Scalr_System_Ipc_ShmQueue(array(
			// Important! suffix must be pool pid
			"name" => "scalr.ipc.processPool.ev-" . $this->poolPid,
			"autoInit" => true
		));
		
		$this->timeoutFly = new Scalr_Util_Timeout(0);
		
		// Start forking
		try {
			$userWorkQueue = $this->worker->startForking($this->workQueue);
			if ($userWorkQueue) {
				$this->workQueue = $userWorkQueue;
			}
		} catch (Exception $e) {
			$this->logger->error("Exception in worker->startForking(). "
					. "Caught: <".get_class($e)."> {$e->getMessage()}");
			$this->shutdown();
			throw $e;
		} 

		// Fork childs		
		for ($i=0; $i<$this->size; $i++) {
			try {
				$this->forkChild();
			} catch (Exception $e) {
				$this->logger->error("Exception during fork childs. "
						. "Caught: <".get_class($e)."> {$e->getMessage()}");
				$this->shutdown();
				throw $e;
			}
		}

		
		// Wait when all childs enter startup barrier

		if (!pcntl_setpriority(-10)) {
			$this->logger->warn("Cannot set higher priority for main process");
		}
	
		try {
			$timeout = new Scalr_Util_Timeout($this->startupTimeout);
			while (!$timeout->reached()) {
				//if (count($this->workersStat) == $this->size) {
				//	break;
				//}
				
				$this->handleChildEvents();	
				$this->logger->info("Barrier capacity: " . $this->shm->get(self::SHM_STARTUP_BARRIER));
				if ($this->shm->get(self::SHM_STARTUP_BARRIER) == $this->size) {
					break;
				}
				$timeout->sleep(10);
			}
		} catch (Scalr_Util_TimeoutException $e) {
			$this->logger->error("Caught timeout exception");
			
			$this->shutdown();			
			
			throw new Scalr_System_Ipc_Exception(sprintf("Timeout exceed (%d millis) "
					. "while waiting when all childs enter startup barrier", 
					$this->startupTimeout));
		}
		$this->logger->debug("All children (".count($this->childs).") have entered startup barrier");
		//$this->timeLogger->info("startup;" . (microtime(true) - $t1) . ";;;");
		
		// Send to all childs SIGUSR2
		$this->logger->debug("Send SIGUSR2 to all workers");
		foreach ($this->childs as $i => $childInfo) {
			$this->kill($childInfo["pid"], SIGUSR2); // Wakeup
			$this->childs[$i]["startTime"] = microtime(true);
		}
		
		$this->logger->debug("Process pool is ready");
		$this->ready = true;
		$this->fireEvent("ready", $this);
	

	
		// Setup SIGALRM 
		pcntl_alarm(1);
		
		$this->wait();		
	}
	
	function shutdown () {
		$this->logger->info("Shutdown...");
		$this->fireEvent("shutdown", $this);
		
		$this->stopForking = true;
		foreach ($this->childs as $childInfo) {
			$this->kill($childInfo["pid"], SIGTERM);
		}
		$this->wait();
	}
	
	function _cleanup () {
		if (!$this->cleanupComplete && posix_getpid() == $this->poolPid) {
			try {
				$this->shm->delete();
			} catch (Exception $ignore) {
			}
			
			try {
				if ($this->childEventQueue) {
					$this->childEventQueue->delete();
				}
			} catch (Exception $ignore) {
			}
			
			try {
				if ($this->workersStat) {
					$this->workersStat->delete();
				}
			} catch (Exception $ignore) {
			}
			
			
			try {
				if ($this->preventParalleling) {
					$this->nowWorkingSet->delete();
				}
			} catch (Exception $ignore) {
			}
			
			$this->cleanupComplete = true;

		}
	}
	
	protected function postShutdown () {
		$this->_cleanup();
		$this->worker->endForking();		
	}
	
	protected function wait () {
		if ($this->inWaitLoop) {
			return;
		}
		$this->inWaitLoop = true;
		
		while ($this->childs) {
			//$this->logger->info("YAAAAAAAAARRRRRRRRRRRRR!!!!!!!!1111111 wait loop");
			
			// Handle child events
			$this->handleChildEvents(50);			
			
			$t1 = microtime(true);
			// When children die, this gets rid of the zombies
			$pid = pcntl_wait($status, WNOHANG);
			if ($pid > 0) {
				$this->logger->info(sprintf("wait() from child %s. Status: %d", $pid, $status));
				$this->onSIGCHLD($pid, $status);
			}
			//$this->logger->info("time wait: " . round(microtime(true) - $t1) . " sec");
			
			$t1 = microtime(true);
			foreach ($this->childs as $childInfo) {
				if ($childInfo["termStartTime"]) {
					// Kill maybe
					
					if ($this->timeoutReached($this->termTimeout, $childInfo["termStartTime"])) {
						$this->logger->info(sprintf("Child %d reached termination timeout and will be KILLED", 
								$childInfo["pid"]));
						$this->kill($childInfo["pid"], SIGKILL);
					}
					
				} else {
					// Terminate maybe	
		
					$term = $this->workTimeout && $childInfo["workStartTime"] &&
							$this->timeoutReached($this->workTimeout, $childInfo["workStartTime"]);
					if ($term) {
						$this->logger->info(sprintf("Child %d reached WORK max execution time", 
								$childInfo["pid"]));
					} else {
						$term = $this->workerTimeout && $childInfo["startTime"] &&
							$this->timeoutReached($this->workerTimeout, $childInfo["startTime"]);
						if ($term) {
							$this->logger->info(sprintf("Child %d reached WORKER max execution time", 
									$childInfo["pid"]));
						}
					}
					
					if ($term) {
						$this->terminateChild($childInfo["pid"]);
					}
				}
			}
			//$this->logger->info("time iterate over childs: " . round(microtime(true) - $t1, 4) . " sec");
			
			$this->sleepMillis(10);
		}
		
		$this->inWaitLoop = false;
		
		$this->postShutdown();
	}

	protected function forkChild ($useBarrier=true) {
		$this->logger->info("Fork child process");
		$pid = pcntl_fork();
		
		if ($pid == -1) {
			// Cannot fork child				
			throw new Scalr_System_Ipc_Exception("Cannot fork child process");
			
		} else if ($pid) {
			// Current process
			$this->logger->debug(sprintf("Child %s was forked", $pid));
			$this->childs[$pid] = array("pid" => $pid);
			$this->worker->childForked($pid);
			
		} else {
			// Child process
			try {
				$this->isChild = true;
				$this->childPid = posix_getpid();
				$this->logger->info("Starting...");
				$this->fireChildEvent("start");
				
				if (!$useBarrier) {
					$this->ready = true;
				} else {
					/*
					$stat = new Scalr_System_Ipc_WorkerStat();
					$stat->pid = $this->childPid;
					$stat->startTime = microtime(true);
					$stat->ready = true;
					$this->workersStat[$this->childPid] = $stat;
					*/
					
					// Wait for SIGUSR2				
					while (!$this->ready) {
						$this->sleepMillis(10);
					}
				}
				
				$this->worker->startChild();
				
				if ($this->workQueue) {
					$memoryTick = new Scalr_Util_Timeout($this->workerMemoryLimitTick);
					$os = Scalr_System_OS::getInstance();
					while ($message = $this->workQueue->peek()) {
						$t1 = microtime(true);
						$this->logger->info("Peek message from work queue");

						// Notify parent before message handler	
						/*					
						$stat = $this->workersStat[$this->childPid];
						$stat->message = $message;
						$stat->workStartTime = microtime(true);
						$stat->workEndTime = null;
						$this->workersStat[$this->childPid] = $stat;
						*/
						
						$this->fireChildEvent("beforeHandleWork", array(
							"microtime" => microtime(true),
							"message" => $message
						));
						
						if ($this->preventParalleling) {
							if ($this->nowWorkingSet->contains($message)) {
								$this->logger->warn(sprintf("Skip message processing because same message "
										. "is currently processing by another process (message: '%s')", 
										serialize($message)));
							}
							
							$this->nowWorkingSet->add($message);
						}
						
						//$this->timeLogger->info("before handle work ($message);; " . (microtime(true) - $t1) . ";;");
						
						//$t1 = microtime(true);
						$this->worker->handleWork($message);
						
						//$this->timeLogger->info("handle work ($message);;; " . (microtime(true) - $t1) . ";");
						
						//$t1 = microtime(true);
						if ($this->preventParalleling) {
							$this->nowWorkingSet->remove($message);
						}
						
						// Notify parent after message handler
						$this->fireChildEvent("afterHandleWork", array(
							"message" => $message
						));
						if ($this->workerMemoryLimit && $memoryTick->reached(false)) {
							$this->fireChildEvent("memoryUsage", array(
								"memory" => $os->getMemoryUsage(posix_getpid(), Scalr_System_OS::MEM_RES)
							));
							$memoryTick->reset();
						}
						
						
						/*
						$stat = $this->workersStat[$this->childPid];
						$stat->workEndTime = microtime(true);
						
						if ($this->workerMemoryLimit && $memoryTick->reached(false)) {
							$stat->memoryUsage = $os->getMemoryUsage($this->childPid, Scalr_System_OS::MEM_RES);
							$stat->memoryUsageTime = microtime(true);
							$memoryTick->reset();
						}
						
						$this->workersStat[$this->childPid] = $stat;
						*/
						
						//$this->timeLogger->info("after handle work ($message);;;; " . (microtime(true) - $t1));
						//$this->logger->info("TIME after handleWork : " . round(microtime(true) - $t1, 4) . " sec");
					}
				}
				
				$this->worker->endChild();
				$this->logger->info("Done");
				
			} catch (Exception $e) {
				// Raise fatal error
				$this->logger->error(sprintf("Unhandled exception in worker process: <%s> '%s'", 
						get_class($e), $e->getMessage()));
				$this->logger->info(sprintf("Worker process %d terminated (exit code: %d)", 
						$this->childPid, self::$termExitCode));
						
				// Sometimes (in our tests when daemonize=true) parent process doesn't receive SIGCHLD
				// Sending kill signal will force SIGCHLD
				// TODO: Consider it deeper				   
				posix_kill($this->childPid, SIGKILL);
				
				exit(self::$termExitCode);
			}
			
			exit();
		}
	}
	
	private function timeoutReached ($timeout, $startTime) {
		$this->timeoutFly->start = $startTime;
		$this->timeoutFly->setTimeout($timeout);
		return $this->timeoutFly->reached(false);
	}
	
	private function sleepMillis ($millis) {
		Scalr_Util_Timeout::sleep($millis);
	}
	
	protected function kill ($pid, $signal, $logPrefix="") {
		$this->logger->info(sprintf("%sSend %s -> %s", $logPrefix, self::$signames[$signal], $pid));
		return posix_kill($pid, $signal);
	}
	
	protected function terminateChild ($pid) {
		if (key_exists($pid, $this->childs)) {
			$this->kill($pid, SIGTERM);
			$this->childs[$pid]["termStartTime"] = microtime(true);
		}
	}
	
	protected function fireChildEvent ($evName, $evData=array()) {
		//$t1 = microtime(true);
		$evData["type"] = $evName;
		$evData["pid"] = posix_getpid();
		$this->childEventQueue->put($evData);
		//$this->logger->info("TIME put '$evName' event: " . (round(microtime(true) - $t1, 4)) . " sec");
		/*
		if (!$this->kill($this->poolPid, SIGUSR1, "[".posix_getpid()."] ")) {
			$this->logger->fatal("Cannot send signal to parent process");
			posix_kill(posix_getpid(), SIGKILL);
		}
		*/
	}
	

	protected static $signames = array(
		SIGHUP => "SIGHUP",
		SIGINT => "SIGINT",
		SIGQUIT => "SIGQUIT",
		SIGILL => "SIGILL",
		SIGTRAP => "SIGTRAP",
		SIGABRT => "SIGABRT",
		SIGBUS => "SIGBUS",
		SIGFPE => "SIGFPE",
		SIGKILL => "SIGKILL",
		SIGUSR1 => "SIGUSR1",
		SIGSEGV => "SIGSEGV",
		SIGUSR2 => "SIGUSR2",
		SIGPIPE => "SIGPIPE",
		SIGALRM => "SIGALRM",
		SIGTERM => "SIGTERM",
		SIGSTKFLT => "SIGSTKFLT",
		SIGCHLD => "SIGCHLD",
		SIGCONT => "SIGCONT",
		SIGSTOP => "SIGSTOP",
		SIGTSTP => "SIGTSTP",
		SIGTTIN => "SIGTTIN",
		SIGTTOU => "SIGTTOU",
		SIGURG => "SIGURG",
		SIGXCPU => "SIGXCPU",
		SIGXFSZ => "SIGXFSZ",
		SIGVTALRM => "SIGVTALRM",
		SIGPROF => "SIGPROF",
		SIGWINCH => "SIGWINCH",
		SIGPOLL => "SIGPOLL",
		SIGIO => "SIGIO",
		SIGPWR => "SIGPWR",
		SIGSYS => "SIGSYS",
		SIGBABY => "SIGBABY"
	);
	
	protected function initSignalHandler () {
		$fn = array($this, "signalHandler");
		$signals = array(SIGCHLD, SIGTERM, SIGABRT, SIGALRM, SIGUSR2);
		if ($this->daemonize) {
			$signals[] = SIGHUP;
		}
		foreach ($signals as $sig) {
			$this->logger->debug("Install ".self::$signames[$sig]." handler");
			if (!pcntl_signal($sig, $fn)) {
				$this->logger->warn(sprintf("Cannot install signal handler on signal %s in process %d", 
						self::$signames[$sig], posix_getpid()));
			}
		}
	}
	
	function signalHandler ($sig) {
		$mypid = posix_getpid();
		
		switch ($sig) {
			// Child terminated
			case SIGCHLD: 
				// In parent
				$pid = pcntl_waitpid(0, $status, WNOHANG);	
				if ($pid != -1) {			
					$this->logger->info(sprintf("Received %s from %d. Status: %d", 
							self::$signames[$sig], $pid, $status));	
					$this->onSIGCHLD($pid, $status);
				}
				break;
				
			// Startup barrier ready
			case SIGUSR2: 
				// In child
				$this->logger->debug(sprintf("Received %s", self::$signames[$sig]));
				$this->ready = true;
				break;
				
			// Timer alarm 
			case SIGALRM:
				//$this->logger->debug(sprintf("Received %s", self::$signames[$sig]));
				// Check zomby child processes
				foreach (array_keys($this->childs) as $pid) {
					if (!posix_kill($pid, 0)) {
						unset($this->childs[$pid]);
					}
				}
				
				pcntl_alarm(1);
				break;
				
			case SIGHUP:
				// Works when $this->daemonize = true
				$this->logger->info(sprintf("Received %s", self::$signames[$sig]));
				// Restart workers 
				foreach ($this->childs as $childInfo) {
					$this->terminateChild($childInfo["pid"]);
				}
				break;
				
			// Terminate process
			case SIGTERM:
			case SIGABRT:
				
				if ($mypid == $this->poolPid) {
					// In parent
					$this->logger->info(sprintf("Received %s in parent", self::$signames[$sig]));
					if (!$this->terminating) {
						$this->terminating = true;
						$this->fireEvent("signal", $this, $sig);
						$this->shutdown();
					} else {
						$this->logger->warn(sprintf("Termination is already initiated"));
					}
					return;					 		
				} else {
					// In child
					$this->logger->info(sprintf("Received %s in child", self::$signames[$sig]));					
					if ($this->isChild) {
						$this->logger->debug("Worker terminating...");
						try {
							$this->worker->terminate();
						} catch (Exception $e) {
							$this->logger->error("Exception in worker->terminate(). Caught: {$e->getMessage()}");
						}
						$this->logger->info(sprintf("Worker process %d terminated (exit code: %d)", 
								$mypid, self::$termExitCode));

						// Sometimes (in our tests when daemonize=true) parent process does'nt receive SIGCHLD
						// Sending kill signal will force SIGCHLD
						// TODO: Consider it deeper  
						posix_kill($mypid, SIGKILL);
						exit(self::$termExitCode);					
					}
				}
				break;
			
			default:
				$this->logger->info(sprintf("Received %s", self::$signames[$sig]));
				break;
		}
		
		$this->fireEvent("signal", $this, $sig);
	}
	
	protected function onSIGCHLD ($pid, $status) {
		if ($pid <= 0) { 
			return;
		}

		if (key_exists($pid, $this->childs)) {
			// Remove work from nowWorking set 
			if ($this->childs[$pid]["message"] && $this->preventParalleling) {
				$this->nowWorkingSet->remove($this->childs[$pid]["message"]);
			}
			unset($this->childs[$pid]);
			
			$this->logger->debug(sprintf("Child termination options. "
					. "wifexited: %d, wifsignaled: %d, wifstopped: %d, stopForking: %d", 
					pcntl_wifexited($status), pcntl_wifsignaled($status), 
					pcntl_wifstopped($status), $this->stopForking));
			
			// In case of unnormal exit fork new child process
			// 1. status=65280 when child process died with fatal error (set by PHP)
			// 2. exit=9 when child was terminated by parent or by unhandled exception (set by ProcessPool)
			// 3. stopeed by signal
			if ((pcntl_wifexited($status) && $status == 65280) ||
				(pcntl_wifexited($status) && pcntl_wexitstatus($status) == self::$termExitCode) ||
				(pcntl_wifsignaled($status))) {
				try {
					if (!$this->stopForking) {
						// Increase slippage
						$this->slippage++;
						
						if ($this->slippage < $this->slippageLimit) {
							$this->forkChild(false);
						} else {
							$this->logger->info(sprintf("Slippage limit: %d exceed. No new childs will be forked", 
									$this->slippage));
							$this->stopForking = true;
						}
					} else {
						$this->logger->debug("'stopForking' flag prevents new process forking");
					}
				} catch (Scalr_System_Ipc_Exception $e) {
					$this->logger->error(sprintf("Cannot fork child. Caught: <%s> %s", 
							get_class($e), $e->getMessage()));
				}
			}
		}
	}

	protected function handleChildEvents ($nmess=null) {
		$i = 0;
		while ($message = $this->childEventQueue->peek()) {
			$t1 = microtime(true);
			$this->logger->debug(sprintf("Peeked '%s' from event queue", $message["type"]));
			
			switch ($message["type"]) {
				case "beforeHandleWork":
					$this->childs[$message["pid"]]["workStartTime"] = $message["microtime"];
					$this->childs[$message["pid"]]["message"] = $message["message"];
					break;
					
				case "afterHandleWork":
					unset($this->childs[$message["pid"]]["workStartTime"]);
					unset($this->childs[$message["pid"]]["message"]);

					// Reset slippage counter
					$this->slippage = 0;
					break;
					
				case "start":
					if (!$this->ready) {
						$this->shm->put(self::SHM_STARTUP_BARRIER, $this->shm->get(self::SHM_STARTUP_BARRIER) + 1);
					} else {
						$this->childs[$message["pid"]]["startTime"] = microtime(true);
					}
					break;
				
				case "memoryUsage":
					if ($this->workerMemoryLimit && $message["memory"] > $this->workerMemoryLimit) {
						$this->logger->warn(sprintf(
								"Worker %d allocates %d Kb. Maximum %d Kb is allowed by configuration", 
								$message["pid"], $message["memory"], $this->workerMemoryLimit));
						$this->terminateChild($message["pid"]);
					}
					break;
					
				default:
					$this->logger->warn("Peeked unknown message from child event queue. "
							. "Serialized message: {$message0}");
			}
			
			$this->logger->info("Child message handle: " . round(microtime(true) - $t1, 4) . " sec");
			
			$i++;
			if ($nmess && $i >= $nmess) {
				break;
			}
		}
	}
	
	function getPid () {
		return $this->poolPid;
	}
}

/*
class Scalr_System_Ipc_WorkerStat {
	public 
		// Worker process pid
		$pid,
		// Worker start time
		$startTime, 
		// Ready flag
		$ready,
		// Message working on
		$message,
		// Current work start time
		$workStartTime,
		// Current work end time
		$workEndTime,
		// Memory usage
		$memoryUsage,
		// Memory usage probe time
		$memoryUsageTime;
}
*/