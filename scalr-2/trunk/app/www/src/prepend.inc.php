<?
	@session_start();
	require_once (dirname(__FILE__)."/../../src/prepend.inc.php");

	Scalr_Session::restore();

	if (/*isset($_SERVER['HTTP_X_AJAX_SCALR']) && 0 || 0 ||*/ !Scalr_Session::getInstance()->isAuthenticated()) {
		// @TODO: must be "!Scalr_Session::getInstance()->isAuthenticated()", this is for testing purpose
		Scalr_Session::destroy();

		if (isset($_SERVER['HTTP_X_AJAX_SCALR']))
			header("HTTP/1.0 403 Forbidden");
		else
			header('Location: /login.html');

		exit();
	}

	if ( !defined("NO_TEMPLATES")) { // @TODO: remove after clearing old ajax handlers
		if (Scalr_Session::getInstance()->getEnvironment()) {
			$env = array('list' => array(), 'current' => Scalr_Session::getInstance()->getEnvironment()->name);
			$current = Scalr_Session::getInstance()->getEnvironment()->id;
			foreach (Scalr_Session::getInstance()->getEnvironment()->loadByFilter(array('clientId' => Scalr_Session::getInstance()->getClientId())) as $value) {
				$env['list'][] = array('text' => $value['name'], 'envId' => $value['id'], 'checked' => ($value['id'] == $current) ? true : false, 'group' => 'env', 'style' => 'width: 124px');
			}

			$Smarty->assign('session_environments', json_encode($env));
		}
	}

	// All uncaught exceptions will raise ApplicationException
	function exception_handler($exception)
	{
		UI::DisplayException($exception);
	}
	set_exception_handler("exception_handler");


	Core::load("XMLNavigation", dirname(__FILE__)); // @TODO: delete xml menu, replace with new one
	define("NOW", str_replace("..","", substr(basename($_SERVER['PHP_SELF']),0, -4))); // @TODO: remove with old templates

	// Auth
	if (Scalr_Session::getInstance()->isAuthenticated())
	{
		if (Scalr_Session::getInstance()->getClientId() != 0) {
			$user = $db->GetRow("SELECT * FROM clients WHERE id=?", Scalr_Session::getInstance()->getClientId());
		}

		if (Scalr_Session::getInstance()->getClientId() != 0 && $user["isactive"] == 0 && !stristr($_SERVER['PHP_SELF'], "billing.php") && !stristr($_SERVER['REQUEST_URI'], 'logout'))
			UI::Redirect("/billing.php");

		//if (CONTEXTS::$APPCONTEXT != APPCONTEXT::AJAX_REQUEST)
		//{
			//
			// Load menu
			//
			require_once (dirname(__FILE__)."/navigation.inc.php");
		//}
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



    if ($req_redirect_to == 'support')
	{	
		$farms_rs = $db->GetAll("SELECT id FROM farms WHERE clientid=?", array(Scalr_Session::getInstance()->getClientId()));
		$farms = array();
		foreach ($farms_rs as $frm)
			$farms[] = $frm['id'];
			
		$farms = implode(', ', array_values($farms));
		
		$Client = Client::Load(Scalr_Session::getInstance()->getClientId());
		
		$args = array(
        	"name"		=> $Client->Fullname,
			"Farms"		=> $farms,
			"ClientID"	=> $Client->ID,
			"email"		=> $Client->Email,
        	"expires" => date("D M d H:i:s O Y", time()+120)
        );
        		        			
		$token = GenerateTenderMultipassToken(json_encode($args));
        //////////////////////////////
        	        			
        UI::Redirect("http://support.scalr.net/?sso={$token}");
	}
    
    define("CHARGIFY_SITE_SHARED_KEY", "jHw77cfhB3ZJiVpTdJex");
    
    //TODO: MOVE TO SESSION

	//
	// Select AWS regions (Temp, for old ugly code)
	//
	$regions = array();
	foreach (AWSRegions::GetList() as $region)
	{
		$regions[$region] = AWSRegions::GetName($region);
	}
	$display['regions'] = $regions;


    if ($req_region)
    	$_SESSION['aws_region'] = $req_region;

    if (!$_SESSION['aws_region'])
    	$_SESSION['aws_region'] = 'us-east-1';
?>
