<?php

	abstract class ScalrRESTService
	{
		const HASH_ALGO = 'SHA1';
		
		protected $Request;
		
		/**
		 * Arguments
		 * @var array
		 */
    	protected $Args;
		
		protected $DB;
    	protected $Logger;
		
		public function __construct()
    	{
    		$this->DB = Core::GetDBInstance();
    		$this->Logger = Logger::getLogger(__CLASS__);
    	}
    	
		/**
    	 * Set request data
    	 * @param $request
    	 * @return void
    	 */
    	public function SetRequest($request)
    	{
    		$this->Request = $request;
			$this->Args = array_change_key_case($request, CASE_LOWER);	
    	}
		
		protected function GetArg($name)
		{
			return $this->Args[strtolower($name)];
		}
    	
		/**
    	 * Verify Calling Instance
    	 */
    	protected function VerifyCallingInstance()
    	{
    		if (!$_SERVER['HTTP_X_SIGNATURE'])
    			return $this->ValidateRequestByFarmHash($this->GetArg('farmid'), $this->GetArg('instanceid'), $this->GetArg('authhash'));
    		else
    			return $this->ValidateRequestBySignature($_SERVER['HTTP_X_SIGNATURE'], $_SERVER['HTTP_DATE']);
    	}
		
		protected function ValidateRequestByFarmHash($farmid, $instanceid, $authhash)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array(
    			$farmid
    		));
    		
    		$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array(
    			$instanceid
    		));
    		
    		if (!$instanceinfo || !$farminfo)
    			throw new Exception(sprintf(_("Cannot verify the instance you are making request from. Make sure that farmid, instance-id and auth-hash parameters are specified.")));
    		
    		if (!$farminfo || $farminfo['hash'] != $authhash || $instanceinfo['farmid'] != $farminfo['id'])
    		{
    			throw new Exception(sprintf(_("Cannot verify the instance you are making request from. Make sure that farmid (%s), instance-id (%s) and auth-hash (%s) parameters are valid."),
    				$farmid, $instanceid, $authhash
    			));
    		}
    		
    		return true;
		}
		
		protected function ValidateRequestBySignature($signature, $timestamp)
		{
			ksort($this->Request);
			$string_to_sign = "";
    		foreach ($this->Request as $k=>$v)
    			$string_to_sign.= "{$k}{$v}";
			
    		$string_to_sign .= $timestamp;
    		
    		if ($this->Request['KeyID'])
    		{
    			 $this->Client = Client::LoadByScalrKeyID($this->Request['KeyID']);
    			 $auth_key = $this->Client->GetScalrAPIKey();
    		}
    		else
    		{
    			$auth_key = $this->DB->GetOne("SELECT token FROM init_tokens WHERE instance_id=?", array($this->Request['InstanceID']));
    			if (!$auth_key)
    				throw new Exception(_("Cannot find init token for specified instance"));
    				
    			//TODO:
    			//$this->DB->Execute("DELETE FROM init_tokens WHERE token=?", array($auth_key));
    		}
    		
    		//TODO: Remove this
    		$this->Logger->info($auth_key);
    		$this->Logger->info($string_to_sign);
    		
    		$valid_sign = base64_encode(hash_hmac(self::HASH_ALGO, $string_to_sign, $auth_key, 1));    		
    		if ($valid_sign != $signature)
    			throw new Exception("Signature doesn't match");
    			
    		return true;
		}
	}
?>