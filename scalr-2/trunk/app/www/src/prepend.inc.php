<?
	$ADM = true;
	session_start();
	require_once (dirname(__FILE__)."/../../src/prepend.inc.php");

	// Define current context

	if (!$context)
	{
		CONTEXTS::$APPCONTEXT = !stristr($_SERVER['PHP_SELF'], "event_handler.php") ? APPCONTEXT::CONTROL_PANEL : APPCONTEXT::EVENT_HANDLER;

		if ($_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest")
		{
			CONTEXTS::$APPCONTEXT = APPCONTEXT::AJAX_REQUEST;
		}
	}
	else
		CONTEXTS::$APPCONTEXT = $context;
	
	if (!defined("NO_AUTH"))
	{
    	Core::load("Data/JSON/JSON.php");
    	Core::load("XMLNavigation", dirname(__FILE__));
    	
    	define("NOW", str_replace("..","", substr(basename($_SERVER['PHP_SELF']),0, -4)));
	
    	if ($_COOKIE['scalr_uid'])
    	{
    		$Client = Client::Load($_COOKIE['scalr_uid']);
    		
    		$cpwd = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../../etc/.passwd"));
    		
    		$signature = $Crypto->Hash("{$_COOKIE["scalr_sault"]}:{$_COOKIE["scalr_hash"]}:{$_COOKIE["scalr_uid"]}:{$_SERVER['REMOTE_ADDR']}:{$cpwd}"); 
    		if ($signature == $_COOKIE['scalr_signature'])
    		{
    			$_SESSION["sault"] = $_COOKIE['scalr_sault'];
        		$_SESSION["hash"] = $_COOKIE['scalr_hash'];
        		$_SESSION["uid"] = $_COOKIE['scalr_uid'];
        		$_SESSION["cpwd"] = $cpwd;
        		$_SESSION["aws_accesskey"] = $Client->AWSAccessKey;
        		$_SESSION["aws_accesskeyid"] = $Client->AWSAccessKeyID;
        		$_SESSION["aws_accountid"] = $Client->AWSAccountID;
        		
        		$_SESSION["aws_private_key"] = $Client->AWSPrivateKey;
        		$_SESSION["aws_certificate"] = $Client->AWSCertificate;
    		}
    	}
    	
    	// Auth
    	if ($_SESSION["uid"] == 0)
        	$newhash = $Crypto->Hash(CONFIG::$ADMIN_LOGIN.":".CONFIG::$ADMIN_PASSWORD.":".$_SESSION["sault"]);
    	else 
    	{
    	    $user = $db->GetRow("SELECT * FROM clients WHERE id=?", $_SESSION['uid']);
    	    $newhash = $Crypto->Hash("{$user['email']}:{$user['password']}:".$_SESSION["sault"]);
    	}
    	
    	$valid = ($newhash == $_SESSION["hash"] && !empty($_SESSION["hash"]));
    	
    	if (!$valid && !stristr($_SERVER['PHP_SELF'], "login.php") && !stristr($_SERVER['PHP_SELF'], "index.php"))
    	{
    		if (CONTEXTS::$APPCONTEXT != APPCONTEXT::AJAX_REQUEST)
    		{
	    		$_SESSION["REQUEST_URI"] = $_SERVER['REQUEST_URI'];
	    		$_SESSION["uid"] = null;
	    		$err[] = "Please login";
	    		UI::Redirect("/login.php");
    		}
    		else
    		{
    			throw new ApplicationException(_("Session expired. Please <a href='/login.php'>login</a> again."), 
    					ApplicationException::NOT_AUTHORIZED);

    			exit();
    		}
    	}

    	if (CONTEXTS::$APPCONTEXT != APPCONTEXT::AJAX_REQUEST)
    	{
	    	//
	    	// Load menu
	    	//
	    	require_once (dirname(__FILE__)."/navigation.inc.php");
    	}
    	
    	
    	if ($get_search)
    	{
    		$display["grid_query_string"] = "&query=".addslashes($get_search);
    		$display["search"] = htmlspecialchars($get_search);
    	}
 		
    	
    	// title 
    	$display["title"] = "Scalr CP";
    	
    	if ($_SESSION['uid'] != 0)
    	{
    		if (!$_SESSION["aws_accesskey"] || 
    			!$_SESSION["aws_private_key"] || 
    			!$_SESSION["aws_certificate"]
    		) {
    			if (!stristr($_SERVER['PHP_SELF'], 'aws_settings.php') && 
    				!stristr($_SERVER['PHP_SELF'], 'login.php') &&
    				!stristr($_SERVER['PHP_SELF'], 'profile.php') &&
    				!stristr($_SERVER['PHP_SELF'], 'client_dashboard.php')
    			)
    			{
    				$errmsg = "Welcome to Scalr - in order to get started, we need some additional information.  Please enter the requested information below.";
    				UI::Redirect("/aws_settings.php");
    			}
    		}
    	}
    	
    	if ($_SESSION['uid'] != 0)
    	{
    		define("SCALR_SERVER_TZ", date_default_timezone_get());
    		
    		$Client = Client::Load($_SESSION['uid']);
    		$tz = $Client->GetSettingValue(CLIENT_SETTINGS::TIMEZONE);
    		if ($tz)
	    		date_default_timezone_set($tz);
    	}
    }
    
    if ($req_region)
    	$_SESSION['aws_region'] = $req_region; 
    
    //TODO: Move default region to config
    if (!$_SESSION['aws_region'])
    	$_SESSION['aws_region'] = 'us-east-1';
?>