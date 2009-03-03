<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = _("Console output");
		
    if ($req_iid)
    {
        $instanceinfo = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($req_iid));
        if ($instanceinfo)
        {
            $farminfo = $db->GetRow("SELECT * FROM farms WHERE id='{$instanceinfo['farmid']}'");
            
            if ($farminfo["clientid"] != $_SESSION["uid"] && $_SESSION["uid"] != 0)
            {
            	$errmsg = _("Instance not found");
            	CoreUtils::Redirect("farms.view.php");
            }
            
            $Client = Client::Load($farminfo['clientid']);
            
            if ($farminfo["clientid"] != $_SESSION['uid'] && $_SESSION['uid'] != 0)
                UI::Redirect("index.php");
			
		    $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region'])); 
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		    
		    try
		    {
		    	$output = $AmazonEC2Client->GetConsoleOutput($req_iid);
				$display["console_output"] = trim(base64_decode($output->output));
				$display["console_output"] = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $display["console_output"]);
				$display["console_output"] = nl2br($display["console_output"]);
				
				$display["console_output"] = str_replace("\033[74G", "</span>", $display["console_output"]);
				$display["console_output"] = str_replace("\033[39;49m", "</span>", $display["console_output"]);
				$display["console_output"] = str_replace("\033[80G <br />", "<span style='padding-left:20px;'></span>", $display["console_output"]);
				$display["console_output"] = str_replace("\033[80G", "<span style='padding-left:20px;'>&nbsp;</span>", $display["console_output"]);
				$display["console_output"] = str_replace("\033[31m", "<span style='color:red;'>", $display["console_output"]);
				$display["console_output"] = str_replace("\033[33m", "<span style='color:brown;'>", $display["console_output"]);				
				
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