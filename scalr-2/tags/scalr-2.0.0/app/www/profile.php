<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
	
	$display["title"] = "Settings&nbsp;&raquo;&nbsp;Profile";
	
	$Validator = new Validator();
		
	if ($_POST) 
	{		
		// Validate input data
        if (!$Validator->IsNotEmpty($post_password))
            $err[] = "Password is required";
            
        if (!$Validator->AreEqual($post_password, $post_password2))
            $err[] = "Two passwords are not equal";            
                  
        if (count($err) == 0)
        {  

        	if ($post_password != '******')
				$password = "password = '".$Crypto->Hash($post_password)."',";
                    
			try
            {
            	// Add user to database
                $db->Execute("UPDATE clients SET
				{$password}
				fullname	= ?,
				org			= ?,
				country		= ?,
				state		= ?,
				city		= ?,
				zipcode		= ?,
				address1	= ?,
				address2	= ?,
				phone		= ?,
				fax			= ?
                	WHERE id = ?
                ", 
				array(
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
					$_SESSION['uid']
				));
			}
			catch (Exception $e)
			{
				throw new ApplicationException($e->getMessage(), E_ERROR);
			}
			
			if (count($err) == 0)
			{
				$okmsg = "Profile successfully updated";
				UI::Redirect("/client_dashboard.php");
			}
        }
	}
	
	$display["countries"] = $db->GetAll("SELECT * FROM countries");
	
	$info = $db->GetRow("SELECT * FROM `clients` WHERE id='{$_SESSION['uid']}'");
	$display = array_merge($info, $display);
	
	$Client = Client::Load($_SESSION['uid']);
	
	require("src/append.inc.php"); 
?>