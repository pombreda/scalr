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
		public function OnHostInit($instanceinfo, $local_ip, $remote_ip, $public_key)
		{			
			// Get farm info and client info from database;
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			$clientinfo = $this->DB->GetRow("SELECT * FROM clients WHERE id=?", array($farminfo["clientid"]));
		
			// Decrypt admin master password
			$cpwd = $this->Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
			
			// Generate s3cmd config file
			$s3cfg = CONFIG::$S3CFG_TEMPLATE;
			$s3cfg = str_replace("[access_key]", $this->Crypto->Decrypt($clientinfo["aws_accesskeyid"], $cpwd), $s3cfg);
			$s3cfg = str_replace("[secret_key]", $this->Crypto->Decrypt($clientinfo["aws_accesskey"], $cpwd), $s3cfg);
			$s3cfg = str_replace("\r\n", "\n", $s3cfg);

			// Prepare public key for SSH connection
			$pub_key_file = tempnam("/tmp", "AWSK");
			$res = file_put_contents($pub_key_file, $public_key);
			$this->Logger->debug("Creating temporary file for public key: {$res}");
			
			// Prepare private key for SSH connection
			$priv_key_file = tempnam("/tmp", "AWSK");
			$res = file_put_contents($priv_key_file, $farminfo["private_key"]);
			$this->Logger->debug("Creating temporary file for private key: {$res}");
			
			// Connect to SSH
			$SSH2 = new SSH2();
			$SSH2->AddPubkey("root", $pub_key_file, $priv_key_file);
			if ($SSH2->Connect($remote_ip, 22))
			{
				// Decrypt client's EC2 private key and certificate
				$private_key = $this->Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
				$certificate = $this->Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
				
				// Upload keys and s3 config to instance
				$res = $SSH2->SendFile("/etc/aws/keys/pk.pem", $private_key, "w+", false);
				$res2 = $SSH2->SendFile("/etc/aws/keys/cert.pem", $certificate, "w+", false);
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
							$res = $SSH2->Exec("chmod 0700 /usr/local/bin/{$name} && /usr/local/bin/{$name}", $hook, "w+");
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
				
				$this->Logger->warn(new FarmLogMessage($farminfo['id'], "Cannot upload ec2 keys to '{$instanceinfo['instance_id']}' instance. Failed to connect to SSH '{$remote_ip}:22'"));
				
				throw new Exception("Cannot upload keys on '{$instanceinfo['instance_id']}'. Failed to connect to '{$remote_ip}:22'.");
			}
		}
	}
?>