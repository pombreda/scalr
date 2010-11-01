<?
    require("../src/prepend.inc.php");
    
    class RDSAjaxUIServer
    {
    	public function __construct()
    	{
    		$this->DB = Core::GetDBInstance();
    		$this->Logger = Logger::getLogger(__CLASS__);
    	}

    	public function RebootDBInstance($instance)
    	{
    		$AmazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
				Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Rds::ACCESS_KEY),
				Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Rds::SECRET_KEY),
				$_SESSION['aws_region']
			);
           
    		$AmazonRDSCLient->RebootDBInstance($instance);
          	
    		return true;
    	}
    	
    	public function TerminateDBInstance($instance)
    	{
    		
    		$AmazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
				Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Rds::ACCESS_KEY),
				Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Rds::SECRET_KEY),
				$_SESSION['aws_region']
			);
			
    		$AmazonRDSCLient->DeleteDBInstance($instance);
          	
    		return true;
    	}
    }

    // Run
    try
    {
    	$AjaxUIServer = new RDSAjaxUIServer();
    	
    	$Reflect = new ReflectionClass($AjaxUIServer);
    	if (!$Reflect->hasMethod($req_action))
    		throw new Exception(sprintf("Unknown action: %s", $req_action));
    		
    	$ReflectMethod = $Reflect->getMethod($req_action);
    		
    	$args = array();
    	foreach ($ReflectMethod->getParameters() as $param)
    	{
    		if (!$param->isArray())
    			$args[$param->name] = $_REQUEST[$param->name];
    		else
    			$args[$param->name] = json_decode($_REQUEST[$param->name]);
    	}	
    	
    	$ReflectMethod->invokeArgs($AjaxUIServer, $args);
    	
    	print json_encode(array(
    		"result"	=> "ok"
    	));
    }
    catch(Exception $e)
    {
    	print json_encode(array(
    		"result"	=> "error",
    		"msg"		=> $e->getMessage()
    	));
    }
        
    exit();
?>