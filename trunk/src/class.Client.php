<?php

	class Client
	{
		public $ID;
		public $IsActive;
		public $Email;
		public $Fullname;
		public $AWSAccountID;
		public $AWSAccessKeyID;
		public $AWSAccessKey;
		public $AWSPrivateKey;
		public $AWSCertificate;
		public $FarmsLimit = 0;
		
		public $ScalrKeyID;
		private $ScalrKey;
		
		private $DB;
		
		private static $ClientsCache = array();
		
		private static $FieldPropertyMap = array(
			'id' 			=> 'ID',
			'isactive'		=> 'IsActive',
			'email'			=> 'Email',
			'fullname'		=> 'Fullname',
			'aws_accountid' => 'AWSAccountID',
			'farms_limit'	=> 'FarmsLimit',
			'scalr_api_keyid' => 'ScalrKeyID',
			'scalr_api_key' => 'ScalrKey',
		);
		
		/**
		 * Constructor
		 */
		public function __construct($email, $password)
		{
			$this->Email = $email;
			$this->Password = $password;
			
			$this->DB = Core::GetDBInstance();
		}
		
		public function GetScalrAPIKey()
		{
			return $this->ScalrKey;
		}
		
		public static function GenerateScalrAPIKeys()
		{
			$fp = fopen("/dev/random", "r");
		    $rnd = fread($fp, 128);
		    fclose($fp);
			$key = base64_encode($rnd);
			
			$sault = abs(crc32($key));
			$keyid = dechex($sault).dechex(time());
			
			$this->ScalrKey = $key;
			$this->ScalrKeyID = $keyid;
			
			return array("id" => $this->ScalrKeyID, "key" => $this->ScalrKey);
		}
		
		/**
		 * Load Client Object by ID
		 * @param integer $id
		 * @return Client $Client
		 */
		public static function Load($id)
		{
			if (!self::$ClientsCache[$id])
			{
				$db = Core::GetDBInstance();
				$Crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
				
				$cpwd = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
				
				
				$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($id));
				if (!$clientinfo)
					throw new Exception(sprintf(_("Client ID#%s not found in database"), $id));
					
				$Client = new Client($clientinfo['email'], $clientinfo['password']);

				foreach(self::$FieldPropertyMap as $k=>$v)
				{
					if ($clientinfo[$k])
						$Client->{$v} = $clientinfo[$k];
				}
				
				if ($id == $_SESSION['uid'])
				{
					$Client->AWSPrivateKey = $_SESSION["aws_private_key"];
			    	$Client->AWSCertificate = $_SESSION["aws_certificate"];
			    	
			    	$Client->AWSAccessKeyID = $_SESSION["aws_accesskeyid"];
			    	$Client->AWSAccessKey = $_SESSION["aws_accesskey"];
				}
				else
				{
					$Client->AWSPrivateKey = $Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
			    	$Client->AWSCertificate = $Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
			    	
			    	$Client->AWSAccessKeyID = $Crypto->Decrypt($clientinfo["aws_accesskeyid"], $cpwd);
			    	$Client->AWSAccessKey = $Crypto->Decrypt($clientinfo["aws_accesskey"], $cpwd);
				}
				
				self::$ClientsCache[$id] = $Client;
			}

			return self::$ClientsCache[$id];
		}
		
		/**
		 * Load Client Object by API key ID
		 * @param string $keyid
		 * @return Client
		 */
		public static function LoadByScalrKeyID($keyid)
		{
			$db = Core::GetDBInstance();
			
			$clientid = $db->GetOne("SELECT id FROM clients WHERE scalr_api_keyid=?", array($keyid));
			if (!$clientid)
				throw new Exception(sprintf(_("KeyID=%s not found in database"), $keyid));
				
			return self::Load($clientid);
		}
		
		/**
		 * Load Client Object by E-mail
		 * @param string $email
		 * @return Client $Client
		 */
		public static function LoadByEmail($email)
		{
			$db = Core::GetDBInstance();
			
			$clientid = $db->GetRow("SELECT id FROM clients WHERE email=?", array($email));
			if (!$clientid)
				throw new Exception(sprintf(_("Client with email=%s not found in database"), $email));
				
			return self::Load($clientid);
		}
		
		/**
		 * Returns client setting value by name
		 * 
		 * @param string $name
		 * @return mixed $value
		 */
		public function GetSettingValue($name)
		{
			return $this->DB->GetOne("SELECT value FROM client_settings WHERE clientid=? AND `key`=?",
				array($this->ID, $name)
			);
		}
	}
	
?>