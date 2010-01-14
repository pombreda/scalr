<?php

// TODO: test it

class Scalr_System_Ipc_ShmSet implements Scalr_Util_Set {
	
	/**
	 * @var Scalr_System_Ipc_Shm
	 */
	private $shm; 

	private $semaphore;	
	
	private $logger;
	
	private $initialConfig;
	
	/**
	 * @param $config
	 * @key string [name]
	 * @key string [key]
	 * @key bool [autoInit]
	 * @key array [items]
	 */
	function __construct ($config) {
		$this->logger = LoggerManager::getLogger(__CLASS__);
		
		$this->initialConfig = $config;
		$this->shm = new Scalr_System_Ipc_Shm($config);
		
		sem_acquire($this->sem());
		try {
			$meta = $this->getMeta();
			if ($meta === null) {
				$this->clear0();
			}
			sem_release($this->sem());
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
		
		if ($config["items"]) {
			foreach ($config["items"] as $item) {
				$this->add($item);
			}
		}
	}
	
	function add ($item) {
		sem_acquire($this->sem());
		
		try {
			$ret = false;
			if (!$this->contains0($item)) {
				
				$meta = $this->getMeta();
				$this->shm->put($meta["nextIndex"], $item);
				$meta["nextIndex"]++;
				$meta["size"]++;
				$this->putMeta($meta);
				
				$ret = true;
			}
			
			sem_release($this->sem());
			return $ret;
			
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
	}
	
	function remove ($item) {
		sem_acquire($this->sem());
		
		try {
			$ret = false;
			if (-1 != ($i = $this->indexOf($item))) {
				$this->shm->remove($i);
				$meta = $this->getMeta();
				$meta["size"]--;
				$this->putMeta($meta);
				
				$ret = true;
			}
			
			sem_release($this->sem());
			return $ret;
			
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
	}
	
	function contains ($item) {
		sem_acquire($this->sem());
		try {
			$ret = $this->contains0($item);
			sem_release($this->sem());
			return $ret;
			
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
	}
	
	private function contains0 ($item) {
		return $this->indexOf($item) != -1;	
	}
	
	function size () {
		sem_acquire($this->sem());
		try {
			$meta = $this->getMeta();
			sem_release($this->sem());
			return $meta["size"];
			
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
	}	
	
	function clear () {
		sem_acquire($this->sem());
		try {
			$this->clear0();
			sem_release($this->sem());
			
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
	}
	
	private function clear0 () {
		$this->putMeta(array("size" => 0, "nextIndex" => 1));
	}
	
	function delete () {
		sem_acquire($this->sem());
		
		try {
			$this->shm->delete();
			unset($this->shm);
		} catch (Exception $ignore) {
		}
		
		sem_release($this->sem());
		sem_remove($this->semaphore);
	}
	
	private function indexOf ($item) {
		$meta = $this->getMeta();
		for ($i=1; $i<$meta["nextIndex"]; $i++) {
			if ($item == $this->shm->get($i)) {
				return $i;
			}
		}
		
		return -1;
	}
	
	private function getMeta () {
		return $this->shm->get(0);
	}
	
	private function putMeta ($meta) {
		$this->shm->put(0, $meta);
	}
	
	private function sem () {
		if (!$this->semaphore) {
			$key = $this->shm->key + 8;
			$this->logger->debug(sprintf("Get semaphore (key: 0x%08x)", $key));
			$this->semaphore = sem_get($key, 1, 0666, true);
		}
		
		return $this->semaphore;
	}
}