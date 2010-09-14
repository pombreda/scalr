<?php

	require_once dirname(__FILE__) . '/Aws/Ec2/20090404/Client.php';
	
	class Scalr_Service_Cloud_Eucalyptus extends Scalr_Service_Cloud_Aws_Ec2_20090404_Client
	{
		/**
		 * 
		 * Constructor
		 * Sample service URL: 192.168.1.100:8773
		 * 
		 * @param string $accessKey
		 * @param string $accessKeyId
		 * @param string $serviceUrl
		 * @param string $serviceUriPrefix
		 * @param string $serviceProtocol
		 */
		public function __construct($accessKey, $accessKeyId, $serviceUrl, $serviceUriPrefix = '/services/Eucalyptus', $serviceProtocol = 'http://') 		
		{
	      	parent::__construct();
			
			$this->accessKey = $accessKey;
			$this->accessKeyId = $accessKeyId;
			
			$this->serviceUrl = $serviceUrl;
			$this->serviceUriPrefix = $serviceUriPrefix;
			$this->serviceProtocol = $serviceProtocol;
		}
	} 
?>
