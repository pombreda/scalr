<?
	require("src/prepend.inc.php");

	CONTEXTS::$APPCONTEXT = APPCONTEXT::ORDER_WIZARD;

	$display['title'] = _("Self-Scaling Hosting Environment utilizing Amazon's EC2.");
	$display['meta_descr'] = _("Scalr is fully redundant, self-curing and self-scaling hosting environment utilizing Amazon's EC2.  It is open source, allowing you to create server farms through a web-based interface using pre-built AMI's.");
	$display['meta_keywords'] = _("Amazon EC2, scalability, AWS, hosting, scaling, self-scaling, hosting environment, cloud computing, open source, web-based interface");

	$isxmlhttp = ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');

	if (isset($req_logout))
	{
		@session_destroy();

        setcookie("scalr_sault", "0", time()-86400);
		setcookie("scalr_hash", "0", time()-86400);
		setcookie("scalr_uid", "0", time()-86400);
		setcookie("scalr_signature", "0", time()-86400);

		$mess = _("Succesfully logged out");

		UI::Redirect("/login.php");
	}


	if (($req_login && $req_pass) || $req_isadmin == 1)
	{
	    if (($req_login == CONFIG::$ADMIN_LOGIN) && ($Crypto->Hash($req_pass) == CONFIG::$ADMIN_PASSWORD))
		{
		    if (CheckIPAcceess())
		    {
				$sault = $Crypto->Sault();
				$_SESSION["sault"] = $sault;
				$_SESSION["hash"] = $Crypto->Hash("{$req_login}:".$Crypto->Hash($req_pass).":{$sault}");;
				$_SESSION["cpwd"] = $req_pass;

				$rpath = ($_SESSION["REQUEST_URI"]) ? $_SESSION["REQUEST_URI"] : "/admin_dashboard.php";
				unset($_SESSION["REQUEST_URI"]);

				Scalr_Session::create(0, 0, Scalr_AuthToken::SCALR_ADMIN);

				$redirect = $rpath;
		    }
		    else
		    	$err[] = "Incorrect login or password";
		}
		else
		{
			$req_login = trim($req_login);
			
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
				$user = $db->GetRow("SELECT * FROM clients WHERE email=?", array($req_login));

			if ($user)
			{
			    if ($user["isactive"] == 0)
			    	$err[] = "Your account has been stopped by service administrator. Please <a href='mailto:".CONFIG::$EMAIL_ADDRESS."'>contact us</a> for more information.";
			    else
			    {
			    	$bruteforce = false;
			    	if ($user['login_attempts'] >= 3 && strtotime($user['dtlastloginattempt'])+600 > time())
					{
						$err[] = _("Bruteforce Protection!<br>You must wait 10 minutes before trying again.");
						$bruteforce = true;
					}
			    	elseif ($user['login_attempts'] >= 3)
			    	{
				    	$db->Execute("UPDATE clients SET login_attempts='0' WHERE id=?", array($user["id"]));
			    	}

			    	if (!$bruteforce)
			    	{
			    		if ($user["password"] == $Crypto->Hash($req_pass) || $valid_admin)
					    {
		                    $sault = $Crypto->Sault();
		        			$_SESSION["sault"] = $sault;
		        			$_SESSION["hash"] = $Crypto->Hash("{$user['email']}:{$user["password"]}:{$sault}");
		        			$_SESSION["u_email"] = $user["email"];
		        			$_SESSION["cpwd"] = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));

		        			$rpath = ($_SESSION["REQUEST_URI"]) ? $_SESSION["REQUEST_URI"] : "/client_dashboard.php";
		        			unset($_SESSION["REQUEST_URI"]);

		        			Scalr_Session::create($user['id'], $user['id'], Scalr_AuthToken::ACCOUNT_ADMIN);

		        			$errmsg = false;
		        			$err = false;

		        			$db->Execute("UPDATE clients SET `login_attempts`=0, dtlastloginattempt=NOW() WHERE id=?", array($user["id"]));

		        			if ($post_keep_session)
		        			{
		        				setcookie("scalr_uid", $user["id"], time()+86400*2);
		        				setcookie("scalr_sault", $_SESSION["sault"], time()+86400*2);
		        				setcookie("scalr_hash", $_SESSION["hash"], time()+86400*2);
		        				setcookie("scalr_signature", $Crypto->Hash("{$_SESSION["sault"]}:{$_SESSION["hash"]}:{$user['id']}:{$_SERVER['REMOTE_ADDR']}:{$_SESSION["cpwd"]}"), time()+43200);
		        			}

		        			$_SESSION['errmsg'] = null;
		        			$_SESSION['err'] = null;

		        			$redirect = $rpath;

					    }
					    else
					    {
		                    $db->Execute("UPDATE clients SET `login_attempts`=`login_attempts` + 1, dtlastloginattempt=NOW() WHERE id=?", array($user["id"]));
					    	$err[] = _("Incorrect login or password");
					    }
			    	}
			    }
			}
			else
                $err[] = _("Incorrect login or password");
		}
	}

	if (!$err && $redirect)
	{
		if ($isxmlhttp)
		{
			print json_encode(array("result" => "ok", "redirect" => $redirect));
			exit();
		}
		else
		{
			UI::Redirect($redirect);
		}
	}
	else
	{
		if ($isxmlhttp)
		{
			print json_encode(array("result" => "error", "message" => $err[0]));
			exit();
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

	require("src/append.inc.php")
?>