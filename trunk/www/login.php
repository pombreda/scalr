<? 
	require("src/prepend.inc.php"); 
	
	CONTEXTS::$APPCONTEXT = APPCONTEXT::ORDER_WIZARD;
	
	if ($get_logout)
	{
		@session_destroy();
		
		$mess = _("Succesfully logged out");
		UI::Redirect("login.php");
	}
	
	if ($req_action == "pwdrecovery")
	{
		if ($_POST)
		{
			$clientinfo = $db->GetRow("SELECT * FROM clients WHERE email=?", array($post_email));
			if ($clientinfo)
			{
				if ($clientinfo["isactive"] == 1)
				{
					$password = $Crypto->Sault(10);				
					$db->Execute("UPDATE clients SET password=? WHERE id=?", 
						array($Crypto->Hash($password), $clientinfo["id"])
					);
					
					$clientinfo["password"] = $password;
					
					// Send welcome E-mail
					$Mailer->ClearAddresses();
					$res = $Mailer->Send("emails/welcome.eml", 
						array("client" => $clientinfo, "site_url" => "http://{$_SERVER['HTTP_HOST']}"), 
						$clientinfo['email'], 
						$clientinfo['fullname']
					);
					
					$display["okmsg"] = "Your password has been reset and emailed<br> to you";
					$_POST = false;
					$template_name = "login.tpl";
				}
				else
					$err[] = "Your account is not active yet";
			}
			else
				$err[] = "Specified e-mail not found in our database";
		}
		
		if (!$template_name)
			$template_name = "pwdrecovery.tpl";
	}
	
	if ($_POST || $req_isadmin == 1)
	{
	    if (($post_login == CONFIG::$ADMIN_LOGIN) && ($Crypto->Hash($post_pass) == CONFIG::$ADMIN_PASSWORD))
		{		    
		    if (CheckIPAcceess())
		    {
				$sault = $Crypto->Sault();
				$_SESSION["sault"] = $sault;
				$_SESSION["hash"] = $Crypto->Hash("{$post_login}:".$Crypto->Hash($post_pass).":{$sault}");
				$_SESSION["uid"] = 0;
				$_SESSION["cpwd"] = $post_pass;
				
				$rpath = ($_SESSION["REQUEST_URI"]) ? $_SESSION["REQUEST_URI"] : "index.php";
				unset($_SESSION["REQUEST_URI"]);
				
				UI::Redirect("{$rpath}");
		    }
		    else
		    	$err[] = "Incorrect login or password";
		}
		else
		{
			if($req_isadmin && CheckIPAcceess())
			{
				$hash = $Crypto->Hash(CONFIG::$ADMIN_LOGIN.":".CONFIG::$ADMIN_PASSWORD.":".$_SESSION["sault"]);
				$valid_hash = ($newhash == $_SESSION["hash"] && !empty($_SESSION["hash"]));
				
				if ($hash == $valid_hash)
				{
					$user = $db->GetRow("SELECT * FROM clients WHERE id=?", array($req_id));
					$valid_admin = true;
				}
				else
					$err[] = "Your session expired. Please log in again";
			}
			else
				$user = $db->GetRow("SELECT * FROM clients WHERE email=?", array($post_login));
			
			if ($user)
			{
			    if ($user["isactive"] == 0)
			    	$err[] = "Your account has been stopped by service administrator. Please <a href='mailto:".CONFIG::$EMAIL_ADDRESS."'>contact us</a> for more information";
			    else
			    {
					if ($user["password"] == $Crypto->Hash($post_pass) || $valid_admin)
				    {
	                    $sault = $Crypto->Sault();
	        			$_SESSION["sault"] = $sault;
	        			$_SESSION["hash"] = $Crypto->Hash("{$user['email']}:{$user["password"]}:{$sault}");
	        			$_SESSION["uid"] = $user["id"];
	        			$_SESSION["cpwd"] = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
	        			$_SESSION["aws_accesskey"] = $Crypto->Decrypt($user["aws_accesskey"], $_SESSION["cpwd"]);
	        			$_SESSION["aws_accesskeyid"] = $Crypto->Decrypt($user["aws_accesskeyid"], $_SESSION["cpwd"]);
	        			$_SESSION["aws_accountid"] = $user["aws_accountid"];
	        			
	        			if ($user["aws_private_key_enc"])
	        				$_SESSION["aws_private_key"] = $Crypto->Decrypt($user["aws_private_key_enc"], $_SESSION["cpwd"]);
	        				
	        			if ($user["aws_certificate_enc"])
	        				$_SESSION["aws_certificate"] = $Crypto->Decrypt($user["aws_certificate_enc"], $_SESSION["cpwd"]);
	        			
	        			$rpath = ($_SESSION["REQUEST_URI"]) ? $_SESSION["REQUEST_URI"] : "index.php";
	        			unset($_SESSION["REQUEST_URI"]);
	        			
	        			$errmsg = false;
	        			$err = false;
	        			
	        			UI::Redirect("{$rpath}");
				    }
				    else 
	                    $err[] = "Incorrect login or password";
			    }
			}
			else 
                $err[] = "Incorrect login or password";
		}
	}
	
	function CheckIPAcceess()
	{
	    global $db;
	    
	    $current_ip = $_SERVER["REMOTE_ADDR"];
    	$current_ip_parts = explode(".", $current_ip);
    	
    	$ipaccesstable = $db->Execute("SELECT * FROM ipaccess");
    	while ($row = $ipaccesstable->fetchRow())
    	{
    	    $allowedhost = $row["ipaddress"];
    	    
    	    if (preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/si", $allowedhost))
    	    {
    	        if (ip2long($allowedhost) == ip2long($current_ip))
    	           return true;
    	    }
    	    elseif (stristr($allowedhost, "*"))
    	    {
    	        $ip_parts = explode(".", trim($allowedhost));
    	        if (
    				($ip_parts[0] == "*" || $ip_parts[0] == $current_ip_parts[0]) &&
    				($ip_parts[1] == "*" || $ip_parts[1] == $current_ip_parts[1]) &&
    				($ip_parts[2] == "*" || $ip_parts[2] == $current_ip_parts[2]) &&
    				($ip_parts[3] == "*" || $ip_parts[3] == $current_ip_parts[3])
    			   )
    			return true;
    	    }
    	    else 
    	    {
    	        $ip = @gethostbyname($allowedhost);
    	        if ($ip != $allowedhost)
    	        {
    	            if (ip2long($ip) == ip2long($current_ip))
    	               return true;
    	        }
    	    }
    	}
    	
        return false;
	}
	
	require("src/append.inc.php"); 
?>