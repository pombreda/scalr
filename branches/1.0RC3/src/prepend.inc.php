<?
	define("TRANSACTION_ID", uniqid("tran"));
	define("DEFAULT_LOCALE", "en_US");
	
	// Start session
	session_start();
	
	// 	Attempt to normalize settings
	@error_reporting(E_ALL ^E_NOTICE ^E_USER_NOTICE);
	@ini_set('magic_quotes_runtime', '0');
	@ini_set('magic_quotes_gpc', '0');
	@ini_set('variables_order', 'GPCS');
	@ini_set('gpc_order', 'GPC');
	
	@ini_set('session.bug_compat_42', '0');
	@ini_set('session.bug_compat_warn', '0');
	
	// Increase execution time limit	
	set_time_limit(180);
	
	// A kind of sanitization :-/
	if (get_magic_quotes_gpc())
	{
		function mstripslashes(&$item, $key)
		{
			$item = stripslashes($item);
		}
		
		array_walk_recursive($_POST, "mstripslashes");
		array_walk_recursive($_GET, "mstripslashes");
		array_walk_recursive($_REQUEST, "mstripslashes");
	}
	
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
	@extract($_GET, EXTR_PREFIX_ALL, "get");
	@extract($_POST, EXTR_PREFIX_ALL, "post");
	@extract($_SESSION, EXTR_PREFIX_ALL, "sess");
	@extract($_REQUEST, EXTR_PREFIX_ALL, "req");
	
	// Environment stuff
	$base = dirname(__FILE__);
	define("SRCPATH", $base);
	define("APPPATH", "{$base}/..");
	define("LIBPATH", "{$base}/Lib");
	define("CACHEPATH", "$base/../cache");

	$ADODB_CACHE_DIR = "$cachepath/adodb";
	
	define("CF_TEMPLATES_PATH", APPPATH."/templates/".LOCALE);
	define("CF_SMARTYBIN_PATH", CACHEPATH."/smarty_bin/".LOCALE);
	define("CF_SMARTYCACHE_PATH", CACHEPATH."/smarty/".LOCALE);

	// Load enums
	require_once(SRCPATH."/types/enum.APPCONTEXT.php");
	require_once(SRCPATH."/types/enum.FORM_FIELD_TYPE.php");
	require_once(SRCPATH."/types/enum.SUBSCRIPTION_STATUS.php");
	require_once(SRCPATH."/types/enum.INSTANCE_TYPE.php");
	require_once(SRCPATH."/types/enum.INSTANCE_ARCHITECTURE.php");
	require_once(SRCPATH."/types/enum.ZONE_STATUS.php");
	require_once(SRCPATH."/types/enum.EVENT_TYPE.php");
	require_once(SRCPATH."/types/enum.RRD_STORAGE_TYPE.php");
	require_once(SRCPATH."/types/enum.GRAPH_TYPE.php");
	require_once(SRCPATH."/types/enum.SNMP_TRAP.php");
	require_once(SRCPATH."/types/enum.MYSQL_BACKUP_TYPE.php");
	require_once(SRCPATH."/types/enum.FARM_STATUS.php");
	require_once(SRCPATH."/types/enum.INSTANCE_COST.php");
	require_once(SRCPATH."/types/enum.INSTANCE_STATE.php");	
	require_once(SRCPATH."/types/enum.QUEUE_NAME.php");
	require_once(SRCPATH."/types/enum.ROLE_ALIAS.php");
	require_once(SRCPATH."/types/enum.ROLE_TYPE.php");
	
	//Load structs
	require_once(SRCPATH."/structs/struct.CONTEXTS.php");
	require_once(SRCPATH."/structs/struct.CONFIG.php");
	
	require_once(SRCPATH."/exceptions/class.ApplicationException.php");
	require_once(SRCPATH."/class.UI.php");
	require_once(SRCPATH."/class.Debug.php");
	require_once(SRCPATH."/class.TaskQueue.php");
	
	require_once(SRCPATH."/class.DataForm.php");
	require_once(SRCPATH."/class.DataFormField.php");
	
	// All uncaught exceptions will raise ApplicationException
	function exception_handler($exception) 
	{
		UI::DisplayException($exception);
	}
	set_exception_handler("exception_handler");
		
	////////////////////////////////////////
	// LibWebta		                      //
	////////////////////////////////////////
	require(SRCPATH."/LibWebta/prepend.inc.php");
	Core::Load("Security/Crypto");
	Core::Load("Data/DB/ADODB/adodb-exceptions.inc.php", LIBPATH);
	Core::Load("Data/DB/ADODB/adodb.inc.php", LIBPATH);
	Core::Load("UI/Smarty/Smarty.class.php", LIBPATH);
	Core::Load("UI/Smarty/Smarty_Compiler.class.php", LIBPATH);
	Core::Load("NET/Mail/PHPMailer");
	Core::Load("NET/Mail/PHPSmartyMailer");
	Core::Load("Data/Formater/Formater");
	Core::Load("Data/Validation/Validator");
	Core::Load("UI/Paging/Paging");
	Core::Load("IO/Basic");
	Core::Load("UI/Paging/SQLPaging");
	Core::Load("System/Independent/Shell/class.ShellFactory.php");
	Core::Load("Data/Formater");
	Core::Load("NET/SSH");
	Core::Load("NET/API/AWS/AmazonEC2");
	Core::Load("NET/API/AWS/AmazonS3");
	Core::Load("NET/SNMP");
	
	Core::Load("DNSZoneController", SRCPATH);
			
	$cfg = parse_ini_file(APPPATH."/etc/config.ini", true);
	if (!count($cfg)) { 
		die("Cannot parse config.ini file"); 
	};

	define("CF_DEBUG_DB", $cfg["debug"]["db"]);

	// ADODB init 
	$db = Core::GetDBInstance($cfg["db"]);		
	
	// Select config from db
	foreach ($db->GetAll("select * from config") as $rsk)
		$cfg[$rsk["key"]] = $rsk["value"];
		
	$ConfigReflection = new ReflectionClass("CONFIG");
		
	// Define Constants and paste config into CONFIG struct
	foreach ($cfg as $k=>$v) 
	{ 	
		if (is_array($v)) 
			foreach ($v as $kk=>$vv)
			{
				$key = strtoupper("{$k}_{$kk}");
				
				if ($ConfigReflection->hasProperty($key))
					CONFIG::$$key = $vv;
					
				define("CF_{$key}", $vv);
			}
		else
		{
			if (is_array($k))
				$nk = strtoupper("{$k[0]}_{$k[1]}");
			else
				$nk = strtoupper("{$k}");

			if ($ConfigReflection->hasProperty($nk))
				CONFIG::$$nk = $v;
				
			define("CF_{$nk}", $v);
		}
	}
	
	unset($cfg);
	
	// Define log4php contants
	define("LOG4PHP_DIR", LIBPATH.'/IO/Logging/log4php/src/main/php');
	define("LOG4PHP_CONFIGURATION", APPPATH.'/etc/log4php.xml');
	define("LOG4PHP_CONFIGURATOR_CLASS", 'LoggerDOMConfiguratorScalr');
		
	// Require log4php stuff
	require_once (SRCPATH.'/class.FarmLogMessage.php');
	require_once (SRCPATH.'/class.LoggerPatternLayoutScalr.php');
	require_once (SRCPATH.'/class.LoggerPatternParserScalr.php');
	require_once (SRCPATH.'/class.LoggerBasicPatternConverterScalr.php');
			
	require_once (SRCPATH.'/class.LoggerAppenderScalr.php');
	require_once (SRCPATH.'/class.LoggerAppenderEmergMail.php');
	require_once (SRCPATH.'/class.LoggerDOMConfiguratorScalr.php');

	require_once(LOG4PHP_DIR . '/LoggerManager.php');
		
	// Get Global Logger intance
	$Logger = LoggerManager::getLogger('Application');

	// Define json_encode function if extension not installed
	if (!function_exists("json_encode"))
	{
		Core::Load("Data/JSON/JSON.php");
		$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		function json_encode($text)
		{
			global $json;
			return $json->encode($text);
		}
		
		function json_decode($text, $assoc = true)
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
		if (CONFIG::$DEBUG_APP)
		{
			$Smarty->clear_all_cache();
			$Smarty->caching = false;
		}
		else
			$Smarty->caching = true;
	}
	
	// PHPSmartyMailer init
	$Mailer = Core::GetPHPSmartyMailerInstance();
	$Mailer->From 		= CONFIG::$EMAIL_ADDRESS;
	$Mailer->FromName 	= CONFIG::$EMAIL_NAME;
	
	// Crtypto init
	$Crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
	    
    // Set zone lock timeouts
    CONFIG::$ZONE_LOCK_WAIT_TIMEOUT = 5000000; // in miliseconds (1000000 = 1 second)
    CONFIG::$ZONE_LOCK_WAIT_RETRIES = 3;
    
    CONFIG::$HTTP_PROTO = (CONFIG::$HTTP_PROTO) ? CONFIG::$HTTP_PROTO : "http";
    
    // cache lifetime
    CONFIG::$EVENTS_RSS_CACHE_LIFETIME = 300; // in seconds
    CONFIG::$EVENTS_TIMELINE_CACHE_LIFETIME = 300; // in seconds
    CONFIG::$AJAX_PROCESSLIST_CACHE_LIFETIME = 60; // in seconds

    // Get control password
    $cpwd = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
       
    // Set path to SNMP Trap binary
    SNMP::SetSNMPTrapPath(CONFIG::$SNMPTRAP_PATH);
    
    // Require observer interfaces
    require_once (APPPATH.'/observers/interface.IDeferredEventObserver.php');
    require_once (APPPATH.'/observers/interface.IEventObserver.php');
    
    // Require deferred event observers
    require_once (APPPATH.'/observers/class.MailEventObserver.php');
    require_once (APPPATH.'/observers/class.RESTEventObserver.php');
    
    // Require event observers
    require_once (APPPATH.'/observers/abstract.EventObserver.php');
    require_once (APPPATH.'/observers/class.DNSEventObserver.php');
    require_once (APPPATH.'/observers/class.DBEventObserver.php');
    require_once (APPPATH.'/observers/class.SNMPInformer.php');
    require_once (APPPATH.'/observers/class.EC2EventObserver.php');
    require_once (APPPATH.'/observers/class.SSHWorker.php');
    require_once (APPPATH.'/observers/class.ElasticIPsEventObserver.php');
        
    require_once (SRCPATH.'/class.Scalr.php');
        
    //
    // Attach event observers
    //
    Scalr::AttachObserver(new SSHWorker());
	Scalr::AttachObserver(new DBEventObserver());
	Scalr::AttachObserver(new EC2EventObserver());
	Scalr::AttachObserver(new SNMPInformer());
	Scalr::AttachObserver(new ElasticIPsEventObserver());
	Scalr::AttachObserver(new DNSEventObserver());
	    
    //
    // Attach deferred event observers
    //
	Scalr::AttachObserver(new MailEventObserver(), true);
	Scalr::AttachObserver(new RESTEventObserver(), true);
?>