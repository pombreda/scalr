<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = _("Console output");
		
    if ($req_server_id)
    {
    	try
    	{
    		$DBServer = DBServer::LoadByID($req_server_id);
    		
    		if ($_SESSION['uid'] != 0 && $DBServer->clientId != $_SESSION['uid'])
            	CoreUtils::Redirect("servers_view.php");
    		
            $output = PlatformFactory::NewPlatform($DBServer->platform)->GetServerConsoleOutput($DBServer);
            
            if ($output)
            {
	            $display["console_output"] = trim(base64_decode($output));
				$display["console_output"] = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $display["console_output"]);
				$display["console_output"] = nl2br($display["console_output"]);
				
				$display["console_output"] = str_replace("\033[74G", "</span>", $display["console_output"]);
				$display["console_output"] = str_replace("\033[39;49m", "</span>", $display["console_output"]);
				$display["console_output"] = str_replace("\033[80G <br />", "<span style='padding-left:20px;'></span>", $display["console_output"]);
				$display["console_output"] = str_replace("\033[80G", "<span style='padding-left:20px;'>&nbsp;</span>", $display["console_output"]);
				$display["console_output"] = str_replace("\033[31m", "<span style='color:red;'>", $display["console_output"]);
				$display["console_output"] = str_replace("\033[33m", "<span style='color:brown;'>", $display["console_output"]);
            }
    	}
    	catch(Exception $e)
    	{
    		$errmsg = $e->getMessage();
    		CoreUtils::Redirect("servers_view.php");
    	}
    }
    else 
        CoreUtils::Redirect("servers_view.php");
                               
                                                   
    $display["server_id"] = $req_server_id;
	
	require("src/append.inc.php"); 
?>