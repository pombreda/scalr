<?
	// Check POSIX
	if (!function_exists('posix_getpid')) 
		$err[] = "<span style='color:red;'>Cannot find posix_getpid function. Make sure that POSIX Functions enabled.</span>";

	// Check PCNTL
	if (!function_exists('pcntl_fork')) 
		$err[] = "<span style='color:red;'>Cannot find pcntl_fork function. Make sure that PCNTL Functions enabled.</span>";
	
	// Check DOM
	if (!class_exists('DOMDocument')) 
		$err[] = "<span style='color:red;'>Cannot find DOM functions. Make sure that DOM Functions enabled.</span>";
		
	// Check SimpleXML
	if (!function_exists('simplexml_load_string')) 
		$err[] = "<span style='color:red;'>Cannot find simplexml_load_string function. Make sure that SimpleXML Functions enabled.</span>";
		
	// Check MySQLi
	if (!function_exists('mysqli_connect')) 
		$err[] = "<span style='color:red;'>Cannot find mysqli_connect function. Make sure that MYSQLi Functions enabled.</span>";
	
	// Check GetText
	if (!function_exists('gettext')) 
		$err[] = "<span style='color:red;'>Cannot find gettext function. Make sure that GetText Functions enabled.</span>";
		
	// Check MCrypt
	if (!function_exists('mcrypt_encrypt')) 
		$err[] = "<span style='color:red;'>Cannot find mcrypt_encrypt function. Make sure that mCrypt Functions enabled.</span>";

	// Check MHash
	if (!function_exists('mhash')) 
		$err[] = "<span style='color:red;'>Cannot find mhash function. Make sure that mHASH Functions enabled.</span>";
		
	// Check OpenSSL
	if (!function_exists('openssl_verify')) 
		$err[] = "<span style='color:red;'>Cannot find OpenSSL functions. Make sure that OpenSSL Functions enabled.</span>";	
		
	// Check SOAP
	if (!class_exists('SoapClient')) 
		$err[] = "<span style='color:red;'>Cannot find SoapClient class. Make sure that SoapClient Extension enabled.</span>";	
		
	// Check SSH
	if (!function_exists('ssh2_connect')) 
		$err[] = "<span style='color:red;'>Cannot find SSH2 functions. Make sure that SSH2 Functions enabled.</span>";

	// Check SNMP
	if (!function_exists('snmpget')) 
		$err[] = "<span style='color:red;'>Cannot find SNMP functions. Make sure that SNMP Functions enabled.</span>";
		
	//
	// Check php sessings
	//
	if (ini_get('safe_mode') == 1)
		$err[] = "<span style='color:red;'>PHP safe mode enabled. Please disable it.</span>";
		
	if (ini_get('register_gloabls') == 1)
		$err[] = "<span style='color:red;'>PHP register globals enabled. Please disable it.</span>";
		
	if (count($err) == 0)
		print "<span style='color:green'>Congratulations, your environment settings match EPP-DRS requirements!</span>";
	else 
	{
		foreach ($err as $e)
			print $e."<br>";
	}
?>