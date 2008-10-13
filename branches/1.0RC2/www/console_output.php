<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Console output";
		
    if ($req_iid)
    {
        $instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($req_iid));
        if ($instanceinfo)
        {
            $farminfo = $db->GetRow("SELECT * FROM farms WHERE id='{$instanceinfo['farmid']}'");
            
            if ($farminfo["clientid"] != $_SESSION["uid"] && $_SESSION["uid"] != 0)
            {
            	$errmsg = "Instance not found";
            	CoreUtils::Redirect("farms.view.php");
            }
            
            $clientinfo = $db->GetRow("SELECT * FROM clients WHERE id='{$farminfo['clientid']}'");
            
            if ($farminfo["clientid"] != $_SESSION['uid'] && $_SESSION['uid'] != 0)
                UI::Redirect("index.php");
                
            
            if ($_SESSION['uid'] == 0)
		    {
		    	// Decrypt client prvate key and certificate
		    	$private_key = $Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
		    	$certificate = $Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
		    }
		    else
		    {
		    	$private_key = $_SESSION["aws_private_key"];
		    	$certificate = $_SESSION["aws_certificate"];
		    }
			
		    $AmazonEC2Client = new AmazonEC2($private_key, $certificate);
		    
		    try
		    {
		    	$output = $AmazonEC2Client->GetConsoleOutput($req_iid);
				$display["console_output"] = trim(base64_decode($output->output));
		    }
		    catch (Exception $e)
		    {
		    	$errmsg = $e->getMessage();	    
		    }
        }
        else 
            UI::Redirect("index.php");
    }
    else 
        UI::Redirect("index.php");
                               
                                                   
    $display["instance_id"] = $instance_id;
	
	require("src/append.inc.php"); 
?>