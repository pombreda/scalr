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
		 * Upload S3cmd config file, EC2 private key and certificate to instance aftre instance boot.
		 * Also execute hostInit hooks from hooks/hostInit folder
		 *
		 * @param array $instanceinfo
		 * @param string $local_ip
		 * @param string $remote_ip
		 * @param string $public_key
		 */
		public function OnHostInit(HostInitEvent $event)
		{			
			if ($event->DBInstance->IsSupported("0.5-1"))
			{
				$this->Logger->info("Scalarizr instance. Skipping SSH observer...");
				return true;
			}
			
			// Get farm info and client info from database;
			$DBFarm = DBFarm::LoadByID($this->FarmID);
						
			// Get AMI info
			$ssh_port = $this->DB->GetOne("SELECT default_ssh_port FROM roles WHERE ami_id=?", array($event->DBInstance->AMIID));
			if (!$ssh_port)
				$ssh_port = 22;
			
			$Client = Client::Load($DBFarm->ClientID);
		
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
			$SSH2 = new SSH2();
			$SSH2->AddPubkey("root", $pub_key_file, $priv_key_file);
			if ($SSH2->Connect($event->ExternalIP, $ssh_port))
			{
				// Upload keys and s3 config to instance
				$res = $SSH2->SendFile("/etc/aws/keys/pk.pem", $Client->AWSPrivateKey, "w+", false);
				$res2 = $SSH2->SendFile("/etc/aws/keys/cert.pem", $Client->AWSCertificate, "w+", false);
				$res3 = $SSH2->SendFile("/etc/aws/keys/s3cmd.cfg", $s3cfg, "w+", false);
				
				// Execute onHostInit hooks
				try
				{
					// Get hooks list
					$hooks = glob(APPPATH."/hooks/hostInit/*.sh");
					if (count($hooks) > 0)
					{
						foreach ($hooks as $hook)
						{
							$name = basename($hook);
							$this->Logger->info("Executing onHostInit hook: {$name}");
							
							// Send hook file
							$SSH2->SendFile("/usr/local/bin/{$name}", $hook, "w+");
							
							// Execute hook file
							$res = $SSH2->Exec("chmod 0700 /usr/local/bin/{$name} && /usr/local/bin/{$name}");
							$this->Logger->info("{$name} hook execution output: {$res}");
						}
					}
				}
				catch(Exception $e)
				{
					$this->Logger->fatal("Cannot execute hostInit hooks: {$e->getMessage()}");
				}
				
				// remove temporary files
				@unlink($pub_key_file);
				@unlink($priv_key_file);
			}
			else
			{
				// remove temporary files
				@unlink($pub_key_file);
				@unlink($priv_key_file);
				
				Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($this->FarmID, "Cannot upload ec2 keys to '{$event->DBInstance->InstanceID}' instance. Failed to connect to SSH '{$event->ExternalIP}:22'"));
				
				throw new Exception("Cannot upload keys on '{$event->DBInstance->InstanceID}'. Failed to connect to '{$event->ExternalIP}:22'.");
			}
		}
	}
?>