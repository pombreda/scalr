<?

	// Attempt to normalize settings
	@error_reporting(E_ALL ^E_NOTICE);
	@ini_set('display_errors', '1');
	@ini_set('display_startup_errors', '1');
	@ini_set('magic_quotes_runtime', '');
	@ini_set('variables_order', 'GPCS');
	@ini_set('gpc_order', 'GPC');
	@ini_set('register_globals', '0');
	@ini_set('session.bug_compat_warn', '0');
	@ini_set('session.bug_compat_42', '0');
	define("DEFAULT_LOCALE", "en_US");
	
	set_time_limit(180);
	
	//
	// Locale init
	//
	if ($sess_lang)
		$locale = $sess_lang;
	else if ($get_lang)
		$locale = $get_lang;
	else
		$locale = DEFAULT_LOCALE;
		
	define("LOCALE", $locale);
    $_SESSION["LOCALE"] = LOCALE;
    setcookie("locale", LOCALE, time() + 86400*30);
	putenv("LANG=".LOCALE);
	setlocale(LC_ALL, LOCALE);
	define("TEXT_DOMAIN", "default");
	bindtextdomain (TEXT_DOMAIN, LANGS_DIR);
	textdomain(TEXT_DOMAIN);
	bind_textdomain_codeset(TEXT_DOMAIN, "UTF-8");
	$display["lang"] = LOCALE;
	
	// Globalize
	if (get_magic_quotes_gpc()) 
	{
        foreach($_POST as &$value)
        {
            if (!is_array($value))
                $value = stripslashes($value);
        }
    }
	
	// Globalize
	@extract($_GET, EXTR_PREFIX_ALL, "get");
	@extract($_POST, EXTR_PREFIX_ALL, "post");
	@extract($_SESSION, EXTR_PREFIX_ALL, "sess");
	@extract($_REQUEST, EXTR_PREFIX_ALL, "req");
	
	// Environment stuff
	$base = dirname(__FILE__);
	define("APPPATH", "{$base}/..");
	$srcpath = "$base";
	$libpath = "{$srcpath}/Lib";
	define("LIBPATH", $libpath);
	$cachepath = "$base/../cache";
	$ADODB_CACHE_DIR = "$cachepath/adodb";
	
	define("CF_TEMPLATES_PATH", "{$base}/../templates/".LOCALE);
	define("CF_SMARTYBIN_PATH", "{$cachepath}/smarty_bin/".LOCALE);
	define("CF_SMARTYCACHE_PATH", "{$cachepath}/smarty/".LOCALE);

	define("CF_DEBUG_DB", false);
	session_start();
	
	////////////////////////////////////////
	// LibWebta		                      //
	////////////////////////////////////////
	require_once("{$srcpath}/class.CustomException.php");
	require("{$srcpath}/LibWebta/prepend.inc.php");
	Core::Load("CoreException");
	Core::Load("CoreUtils");
	Core::Load("Security/Crypto");
	Core::Load("IO/Logging/Log");
	Core::Load("Data/DB/ADODB/adodb-exceptions.inc.php", LIBPATH);
	Core::Load("Data/DB/ADODB/adodb.inc.php", LIBPATH);
	Core::Load("UI/Smarty/Smarty.class.php", LIBPATH);
	Core::Load("NET/Mail/PHPMailer");
	Core::Load("NET/Mail/PHPSmartyMailer");
	Core::Load("Data/Formater/Formater");
	Core::Load("Data/Validation/Validator");
	Core::Load("UI/Paging/Paging");
	Core::Load("UI/Paging/SQLPaging");
	Core::Load("System/Independent/Shell/class.ShellFactory.php");
	Core::Load("Data/Formater");
	Core::Load("NET/SSH");
	Core::Load("NET/API/AWS/AmazonEC2");
	Core::Load("NET/API/AWS/AmazonS3");
	Core::Load("DNSZoneController", $base);
		
	Core::SetExceptionClassName("CustomException");
	
	try 
	{
		$cfg = parse_ini_file("{$base}/../etc/config.ini", true);
	}
	catch (Exception $e)
	{
		throw new CustomException("Cannot parse config.ini file", 0);
	}
	
	define(HASH_METHOD, CF_CRYPTO_ALGO);
	
	// ADODB init 
	$db = Core::GetDBInstance($cfg["db"]);		
	
	// Select config from db
	foreach ($db->GetAll("select * from config") as $rsk)
		$cfg[$rsk["key"]] = $rsk["value"];
	foreach ($cfg as $k=>$v) 
	{ 
		if (is_array($v)) 
		{
			foreach ($v as $kk=>$vv) 
				 define(strtoupper("CF_{$k}_{$kk}"), $vv); 
		}
		else
		{
			if (is_array($k))
				define(strtoupper("CF_".$k[0]."_".$k[1].""), $v); 
			else
				define(strtoupper("CF_".$k), $v); 
		}
	}
	
	// Logger init
	if (!Log::HasLogger("Default"))
	{
        Log::RegisterLogger("DB", "Default", "syslog");						
    	Log::SetAdapterOption("fieldMessage", "message", "Default");
    	Log::SetAdapterOption("fieldLevel", "severity", "Default");
    	Log::SetAdapterOption("fieldDatetime", "dtadded", "Default");
    	Log::SetAdapterOption("fieldTimestamp", "dtadded_time", "Default");
	}
	Log::SetDefaultLogger("Default");
	
	// Crypto init
	Core::GetInstance("Crypto", CF_CRYPTOKEY);
	
	if (!function_exists("json_encode"))
	{
		Core::Load("Data/JSON/JSON.php");
		$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		function json_encode($text)
		{
			global $json;
			return $json->encode($text);
		}
		
		function json_decode($text)
		{
			global $json;
			return $json->decode($text);
		}
	}
	
	// Smarty init
	if (!defined("NO_TEMPLATES"))
	{
		$Smarty = Core::GetSmartyInstance();
		// Cache control
		if (defined("CF_DEBUG_APP") && CF_DEBUG_APP)
		{
			$Smarty->clear_all_cache();
			$Smarty->caching = false;
		}
		else
			$Smarty->caching = true;
	}
	
	// PHPSmartyMailer init
	$Mailer = Core::GetPHPSmartyMailerInstance();
	$Mailer->From 		= CF_EMAIL_ADDRESS;
	$Mailer->FromName 	= CF_EMAIL_NAME;
	
	require_once("{$base}/appfunctions.inc.php");
		
?>