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
        		//$_COOKIE['scalr_uid'];
        		$_SESSION["cpwd"] = $cpwd;
        		
        		Scalr_Session::create($_COOKIE["scalr_uid"], $_COOKIE["scalr_uid"], Scalr_AuthToken::ACCOUNT_ADMIN);
    		}
    	}
    	
    	// Auth
    	if (Scalr_Session::getInstance()->getClientId() == 0)
        	$newhash = $Crypto->Hash(CONFIG::$ADMIN_LOGIN.":".CONFIG::$ADMIN_PASSWORD.":".$_SESSION["sault"]);
    	else 
    	{
    	    $user = $db->GetRow("SELECT * FROM clients WHERE id=?", Scalr_Session::getInstance()->getClientId());
    	    $newhash = $Crypto->Hash("{$user['email']}:{$user['password']}:".$_SESSION["sault"]);
    	}
    	
    	$valid = ($newhash == $_SESSION["hash"] && !empty($_SESSION["hash"]));
    	
    	if (!$valid && !stristr($_SERVER['PHP_SELF'], "login.php") && !stristr($_SERVER['PHP_SELF'], "index.php"))
    	{
    		if (CONTEXTS::$APPCONTEXT != APPCONTEXT::AJAX_REQUEST)
    		{
	    		$_SESSION["REQUEST_URI"] = $_SERVER['REQUEST_URI'];
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

    	if (CONTEXTS::$APPCONTEXT != APPCONTEXT::AJAX_REQUEST && ($user || $valid))
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
    	
    	if (Scalr_Session::getInstance()->getClientId() != 0)
    	{
    		define("SCALR_SERVER_TZ", date_default_timezone_get());
    		
    		$tz = Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(ENVIRONMENT_SETTINGS::TIMEZONE);
    		if ($tz)
	    		date_default_timezone_set($tz);
	    		
	    	$display['logged_as'] = Client::Load(Scalr_Session::getInstance()->getClientId())->Email; 
    	}
    	
    	if (Scalr_Session::getInstance()->getEnvironment())
    	{
	    	$locations = Scalr_Session::getInstance()->getEnvironment()->getLocations();
	    	$display['locations'] = $locations;
    	}
    }
    
    //TODO: MOVE TO SESSION
    
    if ($req_region)
    	$_SESSION['aws_region'] = $req_region; 
    
    if (!$_SESSION['aws_region'])
    	$_SESSION['aws_region'] = 'us-east-1';
?>