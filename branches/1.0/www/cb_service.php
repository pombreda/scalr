<?
	define("NO_AUTH", true);
    include("src/prepend.inc.php");  
	
    session_destroy();
    
    header("Content-type: text/xml");
    
    /*
     * Date: 2009-02-05
     * Initial version of Scalarizr Callback Service
     */
    require(dirname(__FILE__)."/../src/class.ScalarizrCallbackService20090205.php");
    
    /**
     * ***************************************************************************************
     */    
    try
    {
		$ScalarizrCallbackService = new ScalarizrCallbackService20090205($_REQUEST['Version']);
   	 	$ScalarizrCallbackService->SetRequest(array_merge($_POST, $_GET));
    	
    	$ScalarizrCallbackService->ExecuteRequest();    	
    }
    catch(Exception $e)
    {
    	header("HTTP/1.0 500 Internal Server Error");
    	$Logger->error(sprintf(_("Exception thrown in scalarizer callback interface: %s"), $e->getMessage()));
    	$response = "<response><status>error</status><message>{$e->getMessage()}</message></response>";
    }
    
    if (!$response)
    	$response = "<response><status>ok</status><message>ok</message></response>";

    header("Content-length: ".strlen($response));
    print $response;
    exit();
?>
