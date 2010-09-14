<?
	define("TRANSACTION_ID", uniqid("tran"));
	define("DEFAULT_LOCALE", "en_US");
	
	// Start session
	if (!defined("NO_SESSIONS"))
	session_start();
	@date_default_timezone_set(@date_default_timezone_get());
	
	// 	Attempt to normalize settings
	@error_reporting(E_ALL ^E_NOTICE ^E_USER_NOTICE ^E_DEPRECATED);
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
	define("CACHEPATH", "$base/../cache");

	$ADODB_CACHE_DIR = "$cachepath/adodb";
	
	define("CF_TEMPLATES_PATH", APPPATH."/templates/".LOCALE);
	define("CF_SMARTYBIN_PATH", CACHEPATH."/smarty_bin/".LOCALE);
	define("CF_SMARTYCACHE_PATH", CACHEPATH."/smarty/".LOCALE);
	
	// Require autoload definition
	$classpath[] = dirname(__FILE__);
	$classpath[] = dirname(__FILE__) . "/Lib/ZF";
	set_include_path(get_include_path() . PATH_SEPARATOR . join(PATH_SEPARATOR, $classpath));

	require_once (SRCPATH."/autoload.inc.php");

	// require sanitizer
	require_once(SRCPATH."/exceptions/class.ApplicationException.php");
	require_once(SRCPATH."/class.UI.php");
	require_once(SRCPATH."/class.Debug.php");
	require_once(SRCPATH."/class.TaskQueue.php");
	require_once(SRCPATH."/class.FarmTerminationOptions.php");
	
	require_once(SRCPATH."/class.DataForm.php");
	require_once(SRCPATH."/class.DataFormField.php");
	
	require_once(SRCPATH."/queue_tasks/abstract.Task.php");
	
	require_once(SRCPATH."/queue_tasks/class.FireDeferredEventTask.php");

	
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
	Core::Load("NET/Mail/PHPMailer");
	Core::Load("NET/Mail/PHPSmartyMailer");	
	Core::Load("Data/Formater/class.Formater.php");
	Core::Load("Data/Validation/class.Validator.php");
	Core::Load("System/Independent/Shell/class.ShellFactory.php");
	Core::Load("NET/API/AWS/AmazonEC2");
	Core::Load("NET/API/AWS/AmazonS3");
	Core::Load("NET/API/AWS/AmazonSQS");
	Core::Load("NET/API/AWS/AmazonCloudFront");
	Core::Load("NET/API/AWS/AmazonELB");
	Core::Load("NET/API/AWS/AmazonRDS");
	Core::Load("NET/API/AWS/AmazonCloudWatch");
	Core::Load("NET/API/AWS/AmazonVPC");
	Core::Load("NET/SNMP");
	
	require_once(SRCPATH . '/externals/adodb5/adodb-exceptions.inc.php');
	require_once(SRCPATH . '/externals/adodb5/adodb.inc.php');
	
	require_once(SRCPATH . '/externals/Smarty-2.6.26/libs/Smarty.class.php');
	require_once(SRCPATH . '/externals/Smarty-2.6.26/libs/Smarty_Compiler.class.php');
	
	require_once(SRCPATH . '/externals/htmlpurifier-4.1.1/library/HTMLPurifier.auto.php');
	
	$cfg = @parse_ini_file(APPPATH."/etc/config.ini", true);
	if (!count($cfg)) { 
		die(_("Cannot parse config.ini file")); 
	};

	define("CF_DEBUG_DB", $cfg["debug"]["db"]);

	// ADODB init
	try
	{
		$db = Core::GetDBInstance($cfg["db"]);
	}
	catch(Exception $e)
	{		
		throw new Exception("Service is temporary not available. Please try again in a minute.");
		//TODO: Notify about this.		
	}
	
	$ADODB_CACHE_DIR = CACHEPATH."/adodb";
			
	// Select config from db
	foreach ($db->CacheGetAll(3600, "SELECT * FROM config") as $rsk)
	//foreach ($db->GetAll("SELECT * FROM config") as $rsk)
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
	define("LOG4PHP_DIR", SRCPATH.'/externals/apache-log4php-2.0.0-incubating/src/main/php');

	require_once LOG4PHP_DIR . '/Logger.php';
	
	require_once (SRCPATH.'/class.LoggerAppenderScalr.php');
	require_once (SRCPATH.'/class.LoggerPatternLayoutScalr.php');
	require_once (SRCPATH.'/class.FarmLogMessage.php');
	require_once (SRCPATH.'/class.ScriptingLogMessage.php');
	require_once (SRCPATH.'/class.LoggerPatternParserScalr.php');
	require_once (SRCPATH.'/class.LoggerBasicPatternConverterScalr.php');
	require_once (SRCPATH.'/class.LoggerFilterCategoryMatch.php');
	
	Logger::configure(APPPATH.'/etc/log4php.xml', 'LoggerConfiguratorXml');
	$Logger = Logger::getLogger('Application');
		

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
	
	//
	// Configure urls
	//
	
	CONFIG::$IPNURL = "https://".$_SERVER['HTTP_HOST']."/order/ipn.php";
	CONFIG::$PDTURL = "https://".$_SERVER['HTTP_HOST']."/order/pdt.php?utm_nooverride=1";
	
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
			
		$Smarty->register_function('get_static_url', 'get_static_url');
		function get_static_url($params, &$smarty)
		{
			$domains = array('static1.scalr.net', 'static2.scalr.net', 'static3.scalr.net', 'static4.scalr.net', 'static5.scalr.net');
			$h = crc32($params['path']);
			$n = (abs($h) % 5);
			$proto = "https://";
			
			return "{$proto}{$domains[$n]}{$params['path']}";
			//return "https://scalr.net{$params['path']}";
			//return "http://ec2farm-dev.bsd2.webta.local{$params['path']}";
		}
	}
	
	// PHPSmartyMailer init
	$Mailer = Core::GetPHPSmartyMailerInstance();
	$Mailer->From 		= CONFIG::$EMAIL_ADDRESS;
	$Mailer->FromName 	= CONFIG::$EMAIL_NAME;
	$Mailer->CharSet 	= "UTF-8";
	
	// Crtypto init
	$Crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
	    
	
	//TODO: Move all timeouts to config UI.
	
    // Set zone lock timeouts
    CONFIG::$ZONE_LOCK_WAIT_TIMEOUT = 5000000; // in miliseconds (1000000 = 1 second)
    CONFIG::$ZONE_LOCK_WAIT_RETRIES = 3;
    
    CONFIG::$HTTP_PROTO = (CONFIG::$HTTP_PROTO) ? CONFIG::$HTTP_PROTO : "http";
    
    // cache lifetime
    CONFIG::$EVENTS_RSS_CACHE_LIFETIME = 300; // in seconds
    CONFIG::$EVENTS_TIMELINE_CACHE_LIFETIME = 300; // in seconds
    CONFIG::$AJAX_PROCESSLIST_CACHE_LIFETIME = 120; // in seconds
    
    // Get control password
    $cpwd = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
       
    // Set path to SNMP Trap binary
    SNMP::SetSNMPTrapPath(CONFIG::$SNMPTRAP_PATH);
    
    // Require observer interfaces
    require_once (APPPATH.'/observers/interface.IDeferredEventObserver.php');
    require_once (APPPATH.'/observers/interface.IEventObserver.php');
                    
    require_once (SRCPATH.'/class.Scalr.php');
        
    //
    // Attach event observers
    //
    Scalr::AttachObserver(new SSHWorker());
	Scalr::AttachObserver(new DBEventObserver());
	Scalr::AttachObserver(new ScriptingEventObserver());
	Scalr::AttachObserver(new DNSEventObserver());
	Scalr::AttachObserver(new MessagingEventObserver());
	Scalr::AttachObserver(new ScalarizrEventObserver());
	
	Scalr::AttachObserver(new Modules_Platforms_Ec2_Observers_Ec2());
	Scalr::AttachObserver(new Modules_Platforms_Ec2_Observers_Ebs());
	Scalr::AttachObserver(new Modules_Platforms_Ec2_Observers_Eip());
	Scalr::AttachObserver(new Modules_Platforms_Ec2_Observers_Elb());
	
	Scalr::AttachObserver(new Modules_Platforms_Rds_Observers_Rds());
	
    //
    // Attach deferred event observers
    //
	Scalr::AttachObserver(new MailEventObserver(), true);
	Scalr::AttachObserver(new RESTEventObserver(), true);
	
	//
    // Register scaling algos
    //
    RoleScalingManager::RegisterScalingAlgo(new TimeScalingAlgo());
    RoleScalingManager::RegisterScalingAlgo(new BaseScalingAlgo());
    RoleScalingManager::RegisterScalingAlgo(new BWScalingAlgo());
    RoleScalingManager::RegisterScalingAlgo(new LAScalingAlgo());
    RoleScalingManager::RegisterScalingAlgo(new SQSScalingAlgo());
    RoleScalingManager::RegisterScalingAlgo(new RAMScalingAlgo());
    RoleScalingManager::RegisterScalingAlgo(new HTTPResponseTimeScalingAlgo());
    
    $ReflectEVENT_TYPE = new ReflectionClass("EVENT_TYPE");
    $event_types = $ReflectEVENT_TYPE->getConstants();
    foreach ($event_types as $event_type)
    {
    	if (class_exists("{$event_type}Event"))
    	{
    		$ReflectClass = new ReflectionClass("{$event_type}Event");
    		$retval = $ReflectClass->getMethod("GetScriptingVars")->invoke(null);
    		if (!empty($retval))
    		{
    			foreach ($retval as $k=>$v)
    			{
    				if (!CONFIG::$SCRIPT_BUILTIN_VARIABLES[$k])
    				{
	    				CONFIG::$SCRIPT_BUILTIN_VARIABLES[$k] = array(
	    					"PropName"	=> $v,
	    					"EventName" => "{$event_type}"
	    				);
    				}
    				else
    				{
    					if (!is_array(CONFIG::$SCRIPT_BUILTIN_VARIABLES[$k]['EventName']))
    						$events = array(CONFIG::$SCRIPT_BUILTIN_VARIABLES[$k]['EventName']);
    					else
    						$events = CONFIG::$SCRIPT_BUILTIN_VARIABLES[$k]['EventName'];
    						
    					$events[] = $event_type;
    					
    					CONFIG::$SCRIPT_BUILTIN_VARIABLES[$k] = array(
	    					"PropName"	=> $v,
	    					"EventName" => $events
	    				);
    				}
    			}
    		}
    	}
    }
    
	//
	// Select AWS regions
	//
	$regions = array();
	foreach (AWSRegions::GetList() as $region)
	{
		$regions[$region] = AWSRegions::GetName($region);	
	}
	$display['regions'] = $regions;
	
	//
	// Tender integration
	//
	
	define("TENDER_APIKEY", "ebc97df2196a3ac625c1d7a45f6644e9b0b397a548eabb3e479baf05b30f79bd46ed254c76cc4dde1bd8bab0f742ee11b0a68aec0165c69008d4a78e9614b0dd");
	define("TENDER_SITEKEY", "scalr");
	
	function GenerateTenderMultipassToken($data)
	{
		$salted = TENDER_APIKEY . TENDER_SITEKEY;
		$hash = hash('sha1',$salted,true);
		$saltedHash = substr($hash,0,16);
		$iv = "OpenSSL for Ruby";
			
		// double XOR first block
		for ($i = 0; $i < 16; $i++)
		{
			$data[$i] = $data[$i] ^ $iv[$i];
		}
	
		$pad = 16 - (strlen($data) % 16);
		$data = $data . str_repeat(chr($pad), $pad);
	    
		$cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_128,'','cbc','');
		mcrypt_generic_init($cipher, $saltedHash, $iv);
		$encryptedData = mcrypt_generic($cipher,$data);
		mcrypt_generic_deinit($cipher);
	
		return urlencode(base64_encode($encryptedData));
	}

	//
	// ZohoCRM integration
	//
	
	// Configure default mediator
	$mediator = new Scalr_Integration_ZohoCrm_DeferredMediator();
	Scalr_Integration_ZohoCrm_Mediator::setDefaultMediator($mediator);
	Scalr::AttachInternalObserver($mediator);
?>
