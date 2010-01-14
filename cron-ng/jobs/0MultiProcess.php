<?php

class Scalr_Cronjob_0MultiProcess extends Scalr_System_Cronjob_MultiProcess_DefaultWorker {
	
	private $logger;
	
	function __construct () {
		$this->logger = LoggerManager::getLogger(__CLASS__);
	}
	
	static function getConfig () {
		return Scalr_Util_Arrays::mergeReplaceRecursive(parent::getConfig(), array(
			"description" => "MultiProcess cronjob. Do nothing in a 5 workers pool",
			"processPool" => array(
				"size" => 1,
				"daemonize" => true,
				"preventParalleling" => true
			),
			"memoryLimit" => 64000
		));
	}

	/*
	function endForking () {
		throw new Exception("error in endForking");
	}
	*/
	
	/*
	function startChild () {
		$t = 0;
		for ($i=0; $i<10; $i++) {
			$this->logger->info("Do some useful things");
			Scalr_Util_Timeout::sleep(500);
			$t += 500;
			if ($t > 5000) {
				//throw new Exception("Something error in netware heheh");
				die();
			}
			
		}
		
		while (true) {
			$this->logger->info("Do some useful things");
			Scalr_Util_Timeout::sleep(500);
			$t += 500;
			if ($t > 5000) {
				//throw new Exception("Something error in netware heheh");
				die();
			}
		}
	}
	*/
	
	function enqueueWork ($workQueue) {
		foreach (range(1, 10) as $i) {
			$workQueue->put($i);
		}
	}
	
	function handleWork ($message) {
		if ($message == 5) {
			$this->logger->warn("Take Five!!!");
			throw new Exception("I've taked five");
		}
		Scalr_Util_Timeout::sleep(posix_getpid() % 2 ? 1000 : 20);
		$this->logger->info("[".posix_getpid()."] proceed " . $message);
	}
}