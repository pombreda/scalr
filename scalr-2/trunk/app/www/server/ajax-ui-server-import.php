<?php
    require("../src/prepend.inc.php");
    
    class AjaxUIServerImport
    {
    	private $db;
    	
    	function __construct()
    	{
    		$this->db = Core::GetDBInstance();
    	}
    	
    	function WaitHello ()
    	{
    		if (!$_SESSION["importing_server_id"]) {
    			throw new Exception("There is no server to import");
    		}
    		$dbServer = DBServer::LoadByID($_SESSION["importing_server_id"]);
    		
    		
    		$row = $this->db->GetRow("SELECT * FROM messages WHERE server_id = ? AND type = ?", 
    				array($dbServer->serverId, "in"));
    		$resp = array("helloReceived" => (bool)$row);
    		if ($row) {
    			$bundle_task_id = $this->db->GetOne(
    				"SELECT id FROM bundle_tasks WHERE server_id = ? ORDER BY dtadded DESC LIMIT 1", 
    				array($dbServer->serverId));
    			$_SESSION['okmsg'] = "Communication has been established";
    			$resp["redirectTo"] = "/bundle_tasks.php";
    		}
    		return $resp;
    	}
    }
    
    // Run
    try
    {
    	$AjaxUIServer = new AjaxUIServerImport();
    	
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

    	$result = $ReflectMethod->invokeArgs($AjaxUIServer, $args);

    	if(empty($result))    	
	    	throw new Exception("empty result");

    	print json_encode(array(
    		"result"	=> "ok",
			"data"		=> $result
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