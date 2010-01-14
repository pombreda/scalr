<?php

	define("API_SERVER_IP", "174.132.108.66");

	abstract class ScalrAPICore
	{
		const HASH_ALGO = 'SHA256';
		
		protected $Request;
		protected $Client;
		protected $DB;
    	protected $Logger;
		
    	public $Version;
    	
    	protected $LastTransactionID;
    	
		function __construct($version)
		{
			$this->DB = Core::GetDBInstance();
			$this->Logger = Logger::getLogger(__CLASS__);			
			$this->Version = $version;
		}
		
		private function AuthenticateREST($request)
		{
			if (!$request['Signature'])
				throw new Exception("Signature is missing");

			if (!$request['KeyID'])
				throw new Exception("KeyID is missing");
				
			if (!$request['Timestamp'])
				throw new Exception("Timestamp is missing");
			
			//TODO: Validate TimeStamp
				
			ksort($request);
			$string_to_sign = "";
    		foreach ($request as $k=>$v)
    		{
    			if (!in_array($k, array("Signature")))
    				$string_to_sign.= "{$k}{$v}";
    		}
    		
    		$this->Client = Client::LoadByScalrKeyID($request['KeyID']);
    		$auth_key = $this->Client->GetScalrAPIKey();
    		
    		$valid_sign = base64_encode(hash_hmac(self::HASH_ALGO, $string_to_sign, $auth_key, 1));    		
    		if ($valid_sign != $request['Signature'])
    			throw new Exception("Signature doesn't match");
		}
		
		public function BuildRestServer($request)
		{
			try
			{
				$Reflect = new ReflectionObject($this);
				if ($Reflect->hasMethod($request['Action']))
				{
					//Authenticate
					$this->AuthenticateREST($request);
					
					if ($this->Client->GetSettingValue(CLIENT_SETTINGS::API_ENABLED) != 1)
						throw new Exception(_("API disabled for you. You can enable it at 'Settings -> System settings'"));
					
					//Check IP Addresses
					$ips = explode(",", $this->Client->GetSettingValue(CLIENT_SETTINGS::API_ALLOWED_IPS));
					if (!$this->IPAccessCheck($ips) && $_SERVER['REMOTE_ADDR'] != API_SERVER_IP)
						throw new Exception(sprintf(_("Access to the API is not allowed from your IP '%s'"), $_SERVER['REMOTE_ADDR']));
						
						
					//Execute API call
					$ReflectMethod = $Reflect->getMethod($request['Action']);
					$args = array();
					foreach ($ReflectMethod->getParameters() as $param)
					{
						if (!$param->isOptional() && !isset($request[$param->getName()]))
							throw new Exception(sprintf("Missing required parameter '%s'", $param->getName()));
						else
							$args[$param->getName()] = $request[$param->getName()];
					}
					
					$result = $ReflectMethod->invokeArgs($this, $args);
					
					// Create response
					$DOMDocument = new DOMDocument('1.0', 'UTF-8');
					$DOMDocument->loadXML("<{$request['Action']}Response></{$request['Action']}Response>");
					$this->ObjectToXML($result, $DOMDocument->documentElement, $DOMDocument);
					
					$retval = $DOMDocument->saveXML();
				}
				else
					throw new Exception(sprintf("Action '%s' is not defined", $request['Action']));
			}
			catch(Exception $e)
			{
				if (!$this->LastTransactionID)
					$this->LastTransactionID = $this->GenerateTransactionID();
				
				$retval = "<?xml version=\"1.0\"?>\n".
				"<Error>\n".
					"\t<TransactionID>{$this->LastTransactionID}</TransactionID>\n".
					"\t<Message>{$e->getMessage()}</Message>\n".
				"</Error>\n";
			}

			$this->LogRequest(
				$this->LastTransactionID,
				$request['Action'],
				(($_SERVER['REMOTE_ADDR'] == API_SERVER_IP) ? 'Mobile app' : $_SERVER['REMOTE_ADDR']),
				$request,
				$retval
			);
			
			header("Content-type: text/xml");
			header("Content-length: ".strlen($retval));
			print $retval;
		}
		
		protected function LogRequest($trans_id, $action, $ipaddr, $request, $response)
		{
			try
			{
				$this->DB->Execute("INSERT INTO api_log SET
					transaction_id	= ?,
					dtadded			= ?,
					action			= ?,
					ipaddress		= ?,
					request			= ?,
					response		= ?,
					clientid		= ?
				",array(
					$trans_id,
					time(),
					$action,
					$ipaddr,
					http_build_query($request),
					$response,
					$this->Client->ID
				));
			}
			catch(Exception $e) {}
		}
		
		protected function IPAccessCheck($allowed_ips)
		{
			$current_ip = $_SERVER['REMOTE_ADDR'];
			$current_ip_parts = explode(".", $current_ip);
			
			foreach ($allowed_ips as $allowed_ip)
			{
				$allowedhost = trim($allowed_ip);
				if ($allowedhost == '')
					continue;
	    	    
	    	    if (preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/si", $allowedhost))
	    	    {
	    	        if (ip2long($allowedhost) == ip2long($current_ip))
	    	           return true;
	    	    }
	    	    elseif (stristr($allowedhost, "*"))
	    	    {
	    	        $ip_parts = explode(".", trim($allowedhost));
	    	        if (
	    				($ip_parts[0] == "*" || $ip_parts[0] == $current_ip_parts[0]) &&
	    				($ip_parts[1] == "*" || $ip_parts[1] == $current_ip_parts[1]) &&
	    				($ip_parts[2] == "*" || $ip_parts[2] == $current_ip_parts[2]) &&
	    				($ip_parts[3] == "*" || $ip_parts[3] == $current_ip_parts[3])
	    			   )
	    			return true;
	    	    }
	    	    else 
	    	    {
	    	        $ip = @gethostbyname($allowedhost);
	    	        if ($ip != $allowedhost)
	    	        {
	    	            if (ip2long($ip) == ip2long($current_ip))
	    	               return true;
	    	        }
	    	    }
			}
			
			return false;
		}
		
		protected function ObjectToXML($obj, &$DOMElement, &$DOMDocument)
		{
			if (is_object($obj) || is_array($obj))
			{
				foreach ($obj as $k=>$v)
				{
					if (is_object($v))
						$this->ObjectToXML($v, $DOMElement->appendChild($DOMDocument->createElement($k)), $DOMDocument);
					elseif (is_array($v))
						foreach ($v as $vv)
						{
							$e = &$DOMElement->appendChild($DOMDocument->createElement($k));
							$this->ObjectToXML($vv, $e, $DOMDocument);
						}
					else
						$DOMElement->appendChild($DOMDocument->createElement($k, $v));
				}
			}
			else
				$DOMElement->appendChild($DOMDocument->createTextNode($obj));
		}
		
		protected function CreateInitialResponse()
		{
			$response = new stdClass();
			$response->{"TransactionID"} = $this->GenerateTransactionID();
			
			return $response;
		}
		
		private function GenerateTransactionID()
		{
			$pr_bits = false;
	        if (is_a ( $this, 'uuid' )) {
	            if (is_resource ( $this->urand )) {
	                $pr_bits .= @fread ( $this->urand, 16 );
	            }
	        }
	        if (! $pr_bits) {
	            $fp = @fopen ( '/dev/urandom', 'rb' );
	            if ($fp !== false) {
	                $pr_bits .= @fread ( $fp, 16 );
	                @fclose ( $fp );
	            } else {
	                // If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
	                $pr_bits = "";
	                for($cnt = 0; $cnt < 16; $cnt ++) {
	                    $pr_bits .= chr ( mt_rand ( 0, 255 ) );
	                }
	            }
	        }
	        $time_low = bin2hex ( substr ( $pr_bits, 0, 4 ) );
	        $time_mid = bin2hex ( substr ( $pr_bits, 4, 2 ) );
	        $time_hi_and_version = bin2hex ( substr ( $pr_bits, 6, 2 ) );
	        $clock_seq_hi_and_reserved = bin2hex ( substr ( $pr_bits, 8, 2 ) );
	        $node = bin2hex ( substr ( $pr_bits, 10, 6 ) );
	        
	        /**
	         * Set the four most significant bits (bits 12 through 15) of the
	         * time_hi_and_version field to the 4-bit version number from
	         * Section 4.1.3.
	         * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
	         */
	        $time_hi_and_version = hexdec ( $time_hi_and_version );
	        $time_hi_and_version = $time_hi_and_version >> 4;
	        $time_hi_and_version = $time_hi_and_version | 0x4000;
	        
	        /**
	         * Set the two most significant bits (bits 6 and 7) of the
	         * clock_seq_hi_and_reserved to zero and one, respectively.
	         */
	        $clock_seq_hi_and_reserved = hexdec ( $clock_seq_hi_and_reserved );
	        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
	        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;
	        
	        $this->LastTransactionID = sprintf ( '%08s-%04s-%04x-%04x-%012s', $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node );
	        return $this->LastTransactionID; 
		}
	}
?>