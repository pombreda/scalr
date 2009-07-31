<?php
	
	class FarmTerminatedEvent extends Event
	{
		public $RemoveZoneFromDNS;
    	public $KeepElasticIPs;
    	public $TermOnSyncFail;
    	public $KeepEBS;
    	
    	public function __construct($RemoveZoneFromDNS, $KeepElasticIPs, $TermOnSyncFail, $KeepEBS)
    	{
    		$this->RemoveZoneFromDNS = $RemoveZoneFromDNS;
    		$this->KeepElasticIPs = $KeepElasticIPs;
    		$this->TermOnSyncFail = $TermOnSyncFail;
    		$this->KeepEBS = $KeepEBS;
    	}
	}
?>