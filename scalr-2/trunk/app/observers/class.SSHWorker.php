<?php
	class SSHWorker extends EventObserver
	{
		public $ObserverName = 'SSH Worker';
		private $Crypto;
		
		function __construct()
		{
			parent::__construct();
			
			$this->Crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
		}
				
		/**
		 * Upload S3cmd config file, AWS private key and certificate to instance aftre instance boot.
		 * Also execute hostInit hooks from hooks/hostInit folder
		 *
		 * @param array $instanceinfo
		 * @param string $local_ip
		 * @param string $remote_ip
		 * @param string $public_key
		 */
		public function OnHostInit(HostInitEvent $event)
		{			
			if ($event->DBServer->IsSupported("0.5"))
			{
				$this->Logger->info("Scalarizr instance. Skipping SSH observer...");
				return true;
			}
			
			if ($event->DBServer->platform != SERVER_PLATFORMS::EC2)
			{
				return true;
			}
			
			// Get farm info and client info from database;
			$DBFarm = DBFarm::LoadByID($this->FarmID);
						
			// Get AMI info
			$ssh_port = $this->DB->GetOne("SELECT default_ssh_port FROM roles WHERE id=?", array($event->DBServer->roleId));
			if (!$ssh_port)
				$ssh_port = 22;
			
			$Client = $event->DBServer->GetClient();
		
			// Generate s3cmd config file
			$s3cfg = CONFIG::$S3CFG_TEMPLATE;
			$s3cfg = str_replace("[access_key]", $Client->AWSAccessKeyID, $s3cfg);
			$s3cfg = str_replace("[secret_key]", $Client->AWSAccessKey, $s3cfg);
			$s3cfg = str_replace("\r\n", "\n", $s3cfg);

			// Prepare public key for SSH connection
			$pub_key_file = tempnam("/tmp", "AWSK");
			$res = file_put_contents($pub_key_file, $event->PublicKey);
			$this->Logger->debug("Creating temporary file for public key: {$res}");
			
			// Prepare private key for SSH connection
			$priv_key_file = tempnam("/tmp", "AWSK");
			$res = file_put_contents($priv_key_file, $DBFarm->GetSetting(DBFarm::SETTING_AWS_PRIVATE_KEY));
			$this->Logger->debug("Creating temporary file for private key: {$res}");
			
			// Connect to SSH
			$SSH2 = new Scalr_Net_Ssh2_Client();
			$SSH2->addPubkey("root", $pub_key_file, $priv_key_file);
			if ($SSH2->connect($event->ExternalIP, $ssh_port))
			{
				// Upload keys and s3 config to instance
				$res = $SSH2->sendFile("/etc/aws/keys/pk.pem", $Client->AWSPrivateKey, "w+", false);
				$res2 = $SSH2->sendFile("/etc/aws/keys/cert.pem", $Client->AWSCertificate, "w+", false);
				$res3 = $SSH2->sendFile("/etc/aws/keys/s3cmd.cfg", $s3cfg, "w+", false);
				
				// remove temporary files
				@unlink($pub_key_file);
				@unlink($priv_key_file);
			}
			else
			{
				// remove temporary files
				@unlink($pub_key_file);
				@unlink($priv_key_file);
				
				Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($this->FarmID, "Cannot upload ec2 keys to '{$event->DBServer->serverId}' instance. Failed to connect to SSH '{$event->ExternalIP}:{$ssh_port}'"));
				
				throw new Exception("Cannot upload keys on '{$event->DBServer->serverId}'. Failed to connect to '{$event->ExternalIP}:{$ssh_port}'.");
			}
		}
	}
?>