<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	
	$display["title"] = _("Clients&nbsp;&raquo;&nbsp;Add / Edit");
	
	$Validator = new Validator();
		
	if ($_POST) 
	{		
		// Validate input data
	    
	    if (!$Validator->IsEmail($post_email))
            $err[] = _("Invalid E-mail address");
		  
        if (!$Validator->IsNotEmpty($post_password))
            $err[] = _("Password required");
            
        if (!$Validator->AreEqual($post_password, $post_password2))
            $err[] = _("Two passwords are not equal");
	                
        if (!$Validator->IsNumeric($post_farms_limit) || $post_farms_limit < 0)
            $err[] = _("Farms limit must be a number");
          
        if (count($err) == 0)
        {  
    	    if (!$post_id)
    		{
    		    try
                {
        		    // Add user to database
        		    $db->Execute("INSERT INTO clients SET
						email           = ?,
						password        = ?,
						aws_accesskeyid = ?,
						aws_accesskey = ?,
						aws_accountid   = ?,
						farms_limit     = ?,
						fullname	= ?,
						org			= ?,
						country		= ?,
						state		= ?,
						city		= ?,
						zipcode		= ?,
						address1	= ?,
						address2	= ?,
						phone		= ?,
						fax			= ?,
						comments	= ?,
						dtadded		= NOW(),
						isactive	= '1'
        			 ", array(
        		    	$post_email, 
        		    	$Crypto->Hash($post_password), 
        		    	$Crypto->Encrypt($post_aws_accesskeyid, $_SESSION["cpwd"]), 
        		    	$Crypto->Encrypt($post_aws_accesskey, $_SESSION["cpwd"]), 
        		    	$post_aws_accountid, 
        		    	$post_farms_limit,
        		    	$post_name, 
						$post_org, 
						$post_country, 
						$post_state, 
						$post_city, 
						$post_zipcode, 
						$post_address1, 
						$post_address2,
						$post_phone,
						$post_fax,
						$post_comments
        		    ));
        		}
                catch (Exception $e)
                {
                    throw new ApplicationException($e->getMessage(), E_ERROR);
                }
                    	
    			$clientid = $db->Insert_ID();
    				    			
    			// Write cert.pem and pk.pem to clients keys folder
	    		if ($_FILES['cert_file']['tmp_name'])
	            {
					$contents = @file_get_contents($_FILES['cert_file']['tmp_name']);
					if ($contents)
					{
						$enc_contents = $Crypto->Encrypt($contents, $_SESSION['cpwd']);
						$db->Execute("UPDATE clients SET
							aws_certificate_enc = ?
							WHERE id = ?
						", array($enc_contents, $clientid));
					}
					else
					{
						$Logger->fatal(_("Internal error: cannot read uploaded file"));
						$err[] = _("Internal error: cannot read uploaded file");
					}
	            }
	                    
	            if ($_FILES['pk_file']['tmp_name'])
	            {
	            	$contents = @file_get_contents($_FILES['pk_file']['tmp_name']);
					if ($contents)
					{
						$enc_contents = $Crypto->Encrypt($contents, $_SESSION['cpwd']);
						$db->Execute("UPDATE clients SET
							aws_private_key_enc = ?
							WHERE id = ?
						", array($enc_contents, $clientid));
					}
					else
					{
						$Logger->fatal(_("Internal error: cannot read uploaded file"));
						$err[] = _("Internal error: cannot read uploaded file");
					}
	            }
                
	            
	            // Add client to zohocrm
	            try 
	            {
		            Scalr::FireInternalEvent("addClient", Client::Load($clientid));
	            }
	            catch (Exception $e)
	            {
            		throw new ApplicationException($e->getMessage(), E_ERROR);	
	            }
	            
	            
                if (count($err) == 0)
                {
                    $okmsg = _("Client successfully added!");
                    UI::Redirect("clients_view.php");
                }
                else 
                    $db->Execute("DELETE FROM clients WHERE id='{$clientid}'");
    		}
    		else 
    		{
    			$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", $post_id);
    			if ($clientinfo)
    			{
        			if ($post_password != '******')
                        $password = "password = '".$Crypto->Hash($post_password)."',";
                    
                    try
                    {
        			    // Add user to database
            		    $db->Execute("UPDATE clients SET
							email           = ?,
							{$password}
							aws_accesskeyid = ?,
							aws_accesskey   = ?,
							aws_accountid   = ?,
							farms_limit     = ?,
							fullname	= ?,
							org			= ?,
							country		= ?,
							state		= ?,
							city		= ?,
							zipcode		= ?,
							address1	= ?,
							address2	= ?,
							phone		= ?,
							fax			= ?,
							comments	= ?
            			    	WHERE id = ?
            			    ", 
							array(
								$post_email, 
								$Crypto->Encrypt($post_aws_accesskeyid, $_SESSION["cpwd"]), 
								$Crypto->Encrypt($post_aws_accesskey, $_SESSION["cpwd"]), 
								$post_aws_accountid,
								$post_farms_limit,
								$post_name, 
								$post_org, 
								$post_country, 
								$post_state, 
								$post_city, 
								$post_zipcode, 
								$post_address1, 
								$post_address2,
								$post_phone,
								$post_fax,
								$post_comments,
								$post_id
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
							", array($enc_contents, $post_id));
						}
						else
						{
							$Logger->fatal(_("Internal error: cannot read uploaded file"));
							$err[] = _("Internal error: cannot read uploaded file");
						}
		            }
		                    
		            if ($_FILES['pk_file']['tmp_name'])
		            {
		            	$contents = @file_get_contents($_FILES['pk_file']['tmp_name']);
						if ($contents)
						{
							$enc_contents = $Crypto->Encrypt($contents, $_SESSION['cpwd']);
							$db->Execute("UPDATE clients SET
								aws_private_key_enc = ?
								WHERE id = ?
							", array($enc_contents, $post_id));
						}
						else
						{
							$Logger->fatal(_("Internal error: cannot read uploaded file"));
							$err[] = _("Internal error: cannot read uploaded file");
						}
		            }
        		    
		            // Update client in zohocrm
		            try 
		            {
			            Scalr::FireInternalEvent("updateClient", Client::Load($post_id));
		            }
		            catch (Exception $e)
		            {
		            	throw new ApplicationException($e->getMessage(), E_ERROR);	
		            }		            
		            
		            
        		    if (count($err) == 0)
        		    {
        		        $okmsg = _("Client successfully updated");
        		        UI::Redirect("clients_view.php");
        		    }
    			}
    			else
    			{
    			    $errmsg = _("Client not found");
    			    UI::Redirect("clients_view.php");
    			}
    		}
        }
	}
	
	$display["countries"] = $db->GetAll("SELECT * FROM countries");
	
	if ($get_id)
	{
		$info = $db->GetRow("SELECT * FROM `clients` WHERE id=?", array($get_id));
		$info["aws_accesskeyid"] = $Crypto->Decrypt($info["aws_accesskeyid"], $_SESSION["cpwd"]);
		$info["aws_accesskey"] = $Crypto->Decrypt($info["aws_accesskey"], $_SESSION["cpwd"]);
		
		$display = array_merge($info, $display);
	}
	else
		$display = array_merge($_POST, $display);
		
	require("src/append.inc.php"); 
?>