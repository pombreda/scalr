<?php

class Scalr_Messaging_Msg_Mysql_CreateDataBundleResult extends Scalr_Messaging_Msg {
	public $snapshotId;
	public $logFile;
	public $logPos;
	
	function __construct ($snapshotId, $logFile, $logPos) {
		parent::__construct();	
		$this->snapshotId = $snapshotId;
		$this->logFile = $logFile;
		$this->logPos = $logPos;	
	}	
}