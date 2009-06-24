<?php
	class ScalarizrEventObserver extends EventObserver
	{
		public $ObserverName = 'Scalarizer Event observer';
		private $Crypto;
		
		function __construct()
		{
			parent::__construct();
			
			$this->Crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
		}
				
		/**
		 *
		 * @param HostInitEvent $event
		 */
		public function OnHostInit(HostInitEvent $event)
		{			
			$DBInstance = DBInstance::LoadByIID($event->InstanceInfo['instance_id']);
			
			$this->Logger->info("ScalarizrEventObserver::OnHostInit()");
			$this->Logger->info("Scalarizr version: {$DBInstance->ScalarizrPackageVersion}");
			
			if (!$DBInstance->IsSupported("0.5-1"))
				return false;
				
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
				
			$Client = Client::Load($farminfo['clientid']);
			
			$config = "
			<config>
			  <aws>
			   <account-id>{$Client->AWSAccountID}</account-id>
			   <access>
			     <key>{$Client->AWSAccessKey}</key>
			     <key-id>{$Client->AWSAccessKeyID}</key-id>
			   </access>
			   <keypair>
			     <cert>{$Client->AWSCertificate}</cert>
			     <pkey>{$Client->AWSPrivateKey}</pkey>
			   </keypair>
			 </aws>
			 <scalr>
			   <access>
			     <key>{$Client->GetScalrAPIKey()}</key>
			     <key-id>{$Client->ScalrKeyID}</key-id>
			   </access>
			   <callback-service-url>".CONFIG::$HTTP_PROTO."://".CONFIG::$EVENTHANDLER_URL."/cb_service.php</callback-service-url>
			   <queryenv-service-url>".CONFIG::$HTTP_PROTO."://".CONFIG::$EVENTHANDLER_URL."/query-env</queryenv-service-url>
			   <clientid>{$Client->ID}</clientid>
			 </scalr>
			</config>
			";
						
			$tmpfile = tempnam("/tmp", "SCK");
			$fp = fopen($tmpfile, "w");
			fwrite($fp, trim($farminfo['scalarizr_cert'])."\n");
			fwrite($fp, trim($farminfo['scalarizr_pkey'])."\n");
			fclose($fp);

			$client = new SoapClient(null, array(
			  "location" => "https://{$DBInstance->ExternalIP}:7151/Scalarizr",
			  "uri" => "urn:net.scalr.scalarizr.ws",
			  "local_cert" => realpath($tmpfile),
			  "trace" => true
			 ));
			
			$response = $client->configure($config);
			
			$this->Logger->info($response);
			
			@unlink($tmpfile);
		}
	}
?>