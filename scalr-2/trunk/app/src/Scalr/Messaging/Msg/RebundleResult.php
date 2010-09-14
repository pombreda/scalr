<?php

class Scalr_Messaging_Msg_RebundleResult extends Scalr_Messaging_Msg {
	const STATUS_OK = "ok";
	const STATUS_FAILED = "error";
	
	/**
	 * @var string ok|error
	 */
	public $status;
	
	public $lastError;
	
	public $snapshotId;
	
	public $bundleTaskId;	
	
	function __construct ($bundleTaskId=null, $status=null, $snapshotId=null, $lastError=null) {
		parent::__construct();
		$this->bundleTaskId = $bundleTaskId;
		$this->status = $status;
		$this->snapshotId = $snapshotId;
		$this->lastError = $lastError;
	}
}