<?php
	define("NO_AUTH", true);
    include("../src/prepend.inc.php");  
    session_destroy();

    //var_dump($_REQUEST); die();
/*   
    $_SERVER["HTTP_DATE"] = "Wed 12 May 2010 16:16:33 EET";
    $_SERVER["HTTP_X_SIGNATURE"] = "9VXgY3dU53/fQM94tk1HzpEF3q0=";
    $_SERVER["HTTP_X_SERVER_ID"] = "40c2a5f1-57e3-4b80-9642-138ea8514fb1";
    $payload = "4GeN/EsI5V29xXHfcGN44ygpt+DiGLexrOhYl38akEXkIsAakJgwQfL/fHHS00iyfUnonGObeBHkjWriNqeGB1gzhnH5aaa3tc2UxKB5M3gUpiCCY11m1zhYZF35zRHJ9MMsQLb17oa/bgnw5nbJ4liYIcD88ReFTCjd0blhxJUeSAxbY5OBqTyke/MFkeJnYCgh7VoEBaLenTpecdcMeRdemmM1vxegUqHzvRjmayqBPUKnV6kxOA==
";
*/
    $logger = Logger::getLogger("Messaging");
	$logger->info("Messaging server received request");
    
    try
    {
	    $service = new Scalr_Messaging_Service();
	    $service->addQueueHandler(new Scalr_Messaging_Service_ControlQueueHandler());
	    $service->addQueueHandler(new Scalr_Messaging_Service_LogQueueHandler());
	    //list($http_code, $status_text) = $Service->Handle("control", $payload);
	    list($http_code, $status_text) = $service->handle($_REQUEST["queue"], @file_get_contents("php://input"));
	    $logger->info("Respond with {$http_code} {$status_text}");
	    header("HTTP/1.0 {$http_code} {$status_text}");
    }
    catch(Exception $e)
    {
    	$logger->error("Respond with 500 {$e->getMessage()}");
    	header("HTTP/1.0 500 {$e->getMessage()}");
    }
    	
    exit();