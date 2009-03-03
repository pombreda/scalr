<?
    define("NO_AUTH", true);
    include("src/prepend.inc.php");  
    
    /*
     * Date: 2008-11-25
     * Initial Query-env interface
     */
    require(dirname(__FILE__)."/../src/class.ScalrEnvironment20081125.php");
    
    /*
     * Date: 2008-12-16
     * Added /list-ebs-mountpoints method
     */
    require(dirname(__FILE__)."/../src/class.ScalrEnvironment20081216.php");

    
    
    /**
     * ***************************************************************************************
     */
    try
    {
   	 	$EnvironmentObject = ScalrEnvironmentFactory::CreateEnvironment($req_version);
    	$response = $EnvironmentObject->Query($req_operation, $_REQUEST);
    }
    catch(Exception $e)
    {
    	header("HTTP/1.0 500 Error");
    	$Logger->error(sprintf(_("Exception thrown in query-env interface: %s"), $e->getMessage()));
    	die($e->getMessage());
    }
    
    header("Content-Type: text/xml");
    print $response;
    exit();
?>