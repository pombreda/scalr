<?php

	class Scalr_SshKey extends Scalr_Model
	{
		protected $dbTableName = 'ssh_keys';
		protected $dbPrimaryKey = "id";
		protected $dbMessageKeyNotFound = "SSH key #%s not found in database";

		const TYPE_GLOBAL = 'global';
		const TYPE_USER	  = 'user';
		
		protected $dbPropertyMap = array(
			'id'			=> 'id',
			'client_id'		=> array('property' => 'clientId', 'is_filter' => true),
			'env_id'		=> array('property' => 'envId', 'is_filter' => true),
			'type'			=> array('property' => 'type', 'is_filter' => false),
			'private_key'	=> array('property' => 'privateKeyEnc', 'is_filter' => false),
			'public_key'	=> array('property' => 'publicKeyEnc', 'is_filter' => false),
			'cloud_location'=> array('property' => 'cloudLocation', 'is_filter' => false),
			'farm_id'		=> array('property' => 'farmId', 'is_filter' => false),
			'cloud_key_name'=> array('property' => 'cloudKeyName', 'is_filter' => false)
		);
		
		public
			$id,
			$clientId,
			$envId,
			$type,
			$cloudPlatform,
			$farmId,
			$cloudKeyName;
			
		protected $privateKeyEnc,
				$publicKeyEnc;
		
		private $crypto = null, $cryptoKey;
				
		/**
		 * @return Scalr_Util_CryptoTool
		 */
		protected function getCrypto()
		{
			if (! $this->crypto) {
				$this->crypto = new Scalr_Util_CryptoTool(MCRYPT_TRIPLEDES, MCRYPT_MODE_CFB, 24, 8);
				$this->cryptoKey = @file_get_contents(dirname(__FILE__)."/../../etc/.cryptokey");
			}

			return $this->crypto;
		}
		
		public function loadGlobalByFarmId($farmId, $cloudLocation)
		{
			$info = $this->db->GetRow("SELECT * FROM ssh_keys WHERE `farm_id`=? AND `cloud_location`=? AND `type`=?", 
				array($farmId, $cloudLocation, self::TYPE_GLOBAL)
			);
			if (!$info)
				return false;
			else 
				return parent::loadBy($info);
		}
		
		public function setPrivate($key)
		{
			$this->privateKeyEnc = $this->getCrypto()->encrypt($key, $this->cryptoKey);
		}
		
		public function setPublic($key)
		{
			$this->publicKeyEnc = $this->getCrypto()->encrypt($key, $this->cryptoKey);
		}
		
		public function getPrivate()
		{
			return $this->getCrypto()->decrypt($this->privateKeyEnc, $this->cryptoKey);
		}
		
		public function getPublic()
		{
			return $this->getCrypto()->decrypt($this->publicKeyEnc, $this->cryptoKey);
		}
	}
