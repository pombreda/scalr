<? 
	require("src/prepend.inc.php"); 

	if ($get_logout)
	{
		@session_destroy();
		
		$mess = _("Succesfully logged out");
		UI::Redirect("login.php");
	}
	
	
	if ($_POST)
	{
	    if (($post_login == CONFIG::$ADMIN_LOGIN) && ($Crypto->Hash($post_pass) == CONFIG::$ADMIN_PASSWORD))
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
		{
			$user = $db->GetRow("SELECT * FROM clients WHERE email=?", array($post_login));			
			if ($user)
			{
			    if ($user["isactive"] == 0)
			    	$err[] = "Your account has been stopped by service administrator. Please <a href='mailto:".CONFIG::$EMAIL_ADDRESS."'>contact us</a> for more information";
			    else
			    {
					if ($user["password"] == $Crypto->Hash($post_pass))
				    {
	                    $sault = $Crypto->Sault();
	        			$_SESSION["sault"] = $sault;
	        			$_SESSION["hash"] = $Crypto->Hash("{$post_login}:".$Crypto->Hash($post_pass).":{$sault}");
	        			$_SESSION["uid"] = $user["id"];
	        			$_SESSION["cpwd"] = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
	        			$_SESSION["aws_accesskey"] = $user["aws_accesskey"];
	        			$_SESSION["aws_accesskeyid"] = $user["aws_accesskeyid"];
	        			$_SESSION["aws_accountid"] = $user["aws_accountid"];
	        			
	        			$rpath = ($_SESSION["REQUEST_URI"]) ? $_SESSION["REQUEST_URI"] : "index.php";
	        			unset($_SESSION["REQUEST_URI"]);
	        			
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
	
	require("src/append.inc.php"); 
?>