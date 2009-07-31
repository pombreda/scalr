<?
	class ScalarizrCallbackService extends ScalrRESTService
    {    	
    	protected $Client;
    	
    	/**
    	 * 
    	 * @var DBInstance
    	 */
    	protected $DBInstance;
    	    	    	    	
    	/**
    	 * Determine instance external ip address
    	 * @return string
    	 */
    	public function GetCallerIPAddress()
    	{
    		if (!$this->DBInstance->InternalIP)
				$this->DBInstance->InternalIP = $this->Request['LocalIP'];
			
			if ($this->DBInstance->InternalIP == $_SERVER['REMOTE_ADDR'])
			{
				if (!$this->DBInstance->ExternalIP)
				{
					try
					{
						$famrinfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->DBInstance->FarmID));
						
						$Client = Client::Load($farminfo['clientid']);

						$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region'])); 
						$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
						
				    	$response = $AmazonEC2Client->DescribeInstances($this->DBInstance->InstanceID);
				    	$ip = @gethostbyname($response->reservationSet->item->instancesSet->item->dnsName);
				    	
				    	$ip_address = $ip;
				    	
				    	$this->Logger->info(sprintf("Instance external ip = '%s'", $ip_address));
					}
					catch(Exception $e)
					{
						$this->Logger->fatal(sprintf(_("Cannot determine external IP for instance %s: %s"),
							$this->DBInstance->InstanceID, $e->getMessage()
						));
						exit();
					}
				}
				else
					$ip_address = $this->DBInstance->ExternalIP;
			}
			else
				$ip_address = $_SERVER['REMOTE_ADDR'];
			
			return $ip_address;
    	}
    	    	
    	public function ExecuteRequest()
    	{
    		if (!$this->VerifyCallingInstance())
    			return false;
    		
    		$this->Logger->info(serialize($this->Request));
    			
    		$this->DBInstance = DBInstance::LoadByIID($this->Request['InstanceID']);
    		if ($this->DBInstance->ScalarizrPackageVersion != $this->Request['PkgVer'])
    			$this->DBInstance->UpdateProperty(DBInstance::PROPERTY_SCALARIZR_PACKAGE_VERSION, $this->Request['PkgVer']);
			
    		$this->Logger->info(sprintf(_("Received %s action from %s instance..."), $this->Request['Action'], $this->Request['InstanceID']));	
    		
    		try
			{
				$result = call_user_func(array($this, $this->Request['Action']));
				return $result;
			}
			catch(Exception $e)
			{
				throw new Exception(sprintf(_("Cannot execute operation %s of callback service"), 
					$this->Request['Action']
				));
			}
    	}
    }
?>