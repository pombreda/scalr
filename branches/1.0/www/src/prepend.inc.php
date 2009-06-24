<?
	$ADM = true;
	session_start();
	require_once (dirname(__FILE__)."/../../src/prepend.inc.php");

	// Define current context
	if (!$context)
		CONTEXTS::$APPCONTEXT = !stristr($_SERVER['PHP_SELF'], "event_handler.php") ? APPCONTEXT::CONTROL_PANEL : APPCONTEXT::EVENT_HANDLER;
	else
		CONTEXTS::$APPCONTEXT = $context;
	
	if (!defined("NO_AUTH"))
	{
    	Core::load("Data/JSON/JSON.php");
    	Core::load("XMLNavigation", dirname(__FILE__));
    	
    	define("NOW", str_replace("..","", substr(basename($_SERVER['PHP_SELF']),0, -4)));
	
    	// Auth
    	if ($_SESSION["uid"] == 0)
        	$newhash = $Crypto->Hash(CONFIG::$ADMIN_LOGIN.":".CONFIG::$ADMIN_PASSWORD.":".$_SESSION["sault"]);
    	else 
    	{
    	    $user = $db->GetRow("SELECT * FROM clients WHERE id=?", $_SESSION['uid']);
    	    $newhash = $Crypto->Hash("{$user['email']}:{$user['password']}:".$_SESSION["sault"]);
    	}
    	
    	$valid = ($newhash == $_SESSION["hash"] && !empty($_SESSION["hash"]));
    	
    	if (!$valid && !stristr($_SERVER['PHP_SELF'], "login.php"))
    	{
    		if (CONTEXTS::$APPCONTEXT != APPCONTEXT::AJAX_REQUEST)
    		{
	    		$_SESSION["REQUEST_URI"] = $_SERVER['REQUEST_URI'];
	    		$mess = "Please login";
	    		UI::Redirect("/login.php");
    		}
    		else
    		{
    			throw new Exception(_("Session expired. Please <a href='/login.php'>login</a> again."));
    			exit();
    		}
    	}

    	//
    	// Load menu
    	//
    	require_once (dirname(__FILE__)."/navigation.inc.php");
    	
    	
    	if ($get_search)
    		$display["grid_query_string"] = "&query={$get_search}";    		
    	
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
    				!stristr($_SERVER['PHP_SELF'], 'profile.php')
    			)
    				UI::Redirect("aws_settings.php");
    				
    			$errmsg = "Welcome to Scalr - in order to get started, we need some additional information.  Please enter the reqested information below.";
    		}
    	}
    }
    
    if ($req_region)
    	$_SESSION['aws_region'] = $req_region; 
    
    //TODO: Move default region to config
    if (!$_SESSION['aws_region'])
    	$_SESSION['aws_region'] = 'us-east-1';
?>