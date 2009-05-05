<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = _("Requested page cannot be viewed from admin account");
		UI::Redirect("index.php");
	}
	
	$display["title"] = _("Settings&nbsp;&raquo;&nbsp;AWS settings");
	
	$Validator = new Validator();
		
	$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($_SESSION['uid']));
	
	if ($_POST) 
	{		
		$post_aws_accountid = preg_replace("/[^0-9]+/", "", $post_aws_accountid);
		
		// Validate input data            
        if (!$Validator->IsNotEmpty($post_aws_accesskeyid))
            $err[] = _("AWS Key ID required");
        
        if (!$Validator->IsNotEmpty($post_aws_accesskey))
            $err[] = _("AWS Secret Access Key required");
                
        if (!$Validator->IsNumeric($post_aws_accountid) || strlen($post_aws_accountid) != 12)
            $err[] = _("AWS numeric account ID required (See <a href='/faq.html'>FAQ</a> for info on where to get it). ");

        if (!$clientinfo['aws_certificate_enc'] && !$_FILES['cert_file']["tmp_name"])
        	$err[] = _("X.509 Certificate file must be specified");
        	
        if (!$clientinfo['aws_private_key_enc'] && !$_FILES['pk_file']["tmp_name"])
            $err[] = _("X.509 Private key file must be specified");
	              
        // Try to validate certificates and keys //
        if ($_FILES['cert_file']['tmp_name'] || $_FILES['pk_file']['tmp_name'])
        {        	
        	if ($_FILES['pk_file']['tmp_name'])
        		$private_key = @file_get_contents($_FILES['pk_file']['tmp_name']);
        	else
        		$private_key = $_SESSION["aws_private_key"];
        		
        	if ($_FILES['cert_file']['tmp_name'])
        		$cert = @file_get_contents($_FILES['cert_file']['tmp_name']);
        	else
        		$cert = $_SESSION["aws_certificate"];
        		
        	$validate_cert = true;
        }
        
        if ($validate_cert)
        {
        	try
        	{
	        	$AmazonEC2Client = AmazonEC2::GetInstance(); 
				$AmazonEC2Client->SetAuthKeys($private_key, $cert);
	
	            $RunInstancesType = new RunInstancesType();
		        $RunInstancesType->imageId = $db->GetOne("SELECT ami_id FROM ami_roles WHERE roletype=? AND architecture=?",
		        	array(ROLE_TYPE::SHARED, INSTANCE_ARCHITECTURE::I386));
		        $RunInstancesType->minCount = 1;
		        $RunInstancesType->maxCount = 1;
		        $RunInstancesType->AddSecurityGroup("default");
		        $RunInstancesType->instanceType = "m1.small";
	                        
	            $result = $AmazonEC2Client->RunInstances($RunInstancesType);
				if ($result->ownerId != $post_aws_accountid)
					$err[] = sprintf(_("The certificate and private key you specified do not match Account ID %s"), $post_aws_accountid);          
	    
	            $AmazonEC2Client->TerminateInstances(array($result->instancesSet->item->instanceId));
        	}
        	catch(Exception $e)
        	{
        		$err[] = sprintf(_("Failed to verify your certificate and private key. %s"), $e->getMessage());
        	}
        }
        
        if ($post_aws_accesskey != '******' || $post_aws_accesskeyid != $_SESSION["aws_accesskeyid"])
        {
        	try
        	{
        		$AmazonS3 = new AmazonS3(trim($post_aws_accesskeyid), trim($post_aws_accesskey));
    	    	$buckets = $AmazonS3->ListBuckets();
        	}
        	catch(Exception $e)
        	{
        		$err[] = sprintf(_("Failed to verify your access key and access key id. %s"), $e->getMessage());
        	}
        }
        
        ///////////////////////////////////////////
            
        if (count($err) == 0)
        {                      
			$aws_accesskey = $post_aws_accesskey;
        	$akey = ($post_aws_accesskey != '******') ? "aws_accesskey = '{$Crypto->Encrypt(trim($post_aws_accesskey), $_SESSION["cpwd"])}'," : "";
        	
        	try
			{
            // Add user to database
                $db->Execute("UPDATE clients SET
					aws_accesskeyid = ?,
					{$akey}
					aws_accountid   = ?
					WHERE id = ?
				", array($Crypto->Encrypt(trim($post_aws_accesskeyid), $_SESSION["cpwd"]), $post_aws_accountid, $_SESSION['uid']
				));
			}
			catch (Exception $e)
			{
				throw new ApplicationException($e->getMessage(), E_ERROR);
			}
                    
            if ($_FILES['cert_file']['tmp_name'])
            {
				$contents = @file_get_contents($_FILES['cert_file']['tmp_name']);
				if ($contents)
				{
					$enc_contents = $Crypto->Encrypt($contents, $_SESSION['cpwd']);
					
					$db->Execute("UPDATE clients SET
						aws_certificate_enc = ?
						WHERE id = ?
					", array($enc_contents, $_SESSION['uid']));
					
					$aws_certificate = $contents;
				}
				else
				{
					$Logger->fatal(_("Internal error: cannot read uploaded file"));
					$err[] = _("Internal error: cannot read uploaded file");
				}
            }
            else
            	$aws_certificate = $_SESSION["aws_certificate"];
                    
            if ($_FILES['pk_file']['tmp_name'])
            {
            	$contents = @file_get_contents($_FILES['pk_file']['tmp_name']);
				if ($contents)
				{
					$enc_contents = $Crypto->Encrypt($contents, $_SESSION['cpwd']);
					
					$db->Execute("UPDATE clients SET
						aws_private_key_enc = ?
						WHERE id = ?
					", array($enc_contents, $_SESSION['uid']));
					
					$aws_private_key = $contents;
				}
				else
				{
					$Logger->fatal(_("Internal error: cannot read uploaded file"));
					$err[] = _("Internal error: cannot read uploaded file");
				}
            }
            else
            	$aws_private_key = $_SESSION["aws_private_key"];
            
            if (count($err) == 0)
            {
                $_SESSION["aws_accesskey"] = $post_aws_accesskey;
        		$_SESSION["aws_accesskeyid"] = $post_aws_accesskeyid;
        		$_SESSION["aws_accountid"] = $post_aws_accountid;
        		
        		$_SESSION["aws_private_key"] = $aws_private_key;
        		$_SESSION["aws_certificate"] = $aws_certificate;
        		
        		$errmsg = false;
            	$okmsg = _("AWS settings successfully saved");
                UI::Redirect("index.php");
            }
        }
	}
	
	$info = $db->GetRow("SELECT * FROM `clients` WHERE id='{$_SESSION['uid']}'");
	$info["aws_accesskeyid"] = $Crypto->Decrypt($info["aws_accesskeyid"], $_SESSION["cpwd"]);
	$info["aws_accesskey"] = $Crypto->Decrypt($info["aws_accesskey"], $_SESSION["cpwd"]);
	
	$display = array_merge($info, $display);
		
	require("src/append.inc.php"); 
?>