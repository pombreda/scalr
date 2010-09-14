<?php

class Scalr_Messaging_Msg_Mysql_PromoteToMaster extends Scalr_Messaging_Msg {
	public $rootPassword;
	public $replPassword;
	public $statPassword;
	public $volumeId;
	
	function __construct ($rootPassword=null, $replPassword=null, $statPassword=null, $volumeId=null) {
		parent::__construct();
		$this->rootPassword = $rootPassword;
		$this->replPassword = $replPassword;
		$this->statPassword = $statPassword;
		$this->volumeId = $volumeId;
	}
}