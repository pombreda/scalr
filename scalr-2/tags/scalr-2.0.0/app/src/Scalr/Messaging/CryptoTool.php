<?php

class Scalr_Messaging_CryptoTool {
	const CRYPTO_ALGO = MCRYPT_TRIPLEDES;
	const CIPHER_MODE = MCRYPT_MODE_CBC;
	const CRYPTO_KEY_SIZE = 24;
	const CRYPTO_BLOCK_SIZE = 8;
	
	
	const HASH_ALGO = 'SHA256';
	
	static private $instance;
	
	/**
	 * @return Scalr_Messaging_CryptoTool
	 */
	static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new Scalr_Messaging_CryptoTool();
		}
		return self::$instance;
	}	
	
	private function splitKeyIv ($cryptoKey) {
		$key = substr($cryptoKey, 0, self::CRYPTO_KEY_SIZE); 	# Use first n bytes as key
		$iv = substr($cryptoKey, -self::CRYPTO_BLOCK_SIZE);		# Use last m bytes as IV
		return array($key, $iv);
	}
	
	private function pkcs5Padding ($text, $blocksize) {
	    $pad = $blocksize - (strlen($text) % $blocksize);
    	return $text . str_repeat(chr($pad), $pad);
	}
	
	function encrypt ($string, $cryptoKey) {
		list($key, $iv) = $this->splitKeyIv($cryptoKey);
		$string = $this->pkcs5Padding($string, self::CRYPTO_BLOCK_SIZE);
		return base64_encode(mcrypt_encrypt(self::CRYPTO_ALGO, $key, $string, self::CIPHER_MODE, $iv));    		
	}
	
	function decrypt ($string, $cryptoKey) {
		list($key, $iv) = $this->splitKeyIv($cryptoKey);
		$ret = mcrypt_decrypt(self::CRYPTO_ALGO, $key, base64_decode($string), self::CIPHER_MODE, $iv);
		// Remove padding
		return trim($ret, "\x00..\x1F");		
	}
	
	function sign ($data, $key, $timestamp=null) {
		$date = date("c", $timestamp ? $timestamp : time());
		$canonical_string = $data . $date;
		$hash = base64_encode(hash_hmac(self::HASH_ALGO, $canonical_string, $key, 1));
		return array($hash, $date);
	}
}