<? 
	require("src/prepend.inc.php"); 
		
	if ($_SESSION['uid'] == 0)
		UI::Redirect("index.php");
	
	$display["title"] = "Settings&nbsp;&raquo;&nbsp;System";
	
	$Validator = new Validator();
		
	if ($_POST) 
	{		
		// Validate input data                  
        if (!$Validator->IsNumeric($post_reboot_timeout))
            $err[] = "Reboot timeount must be an integer";

        if (!$Validator->IsNumeric($post_launch_timeout))
            $err[] = "Launch timeount must be an integer";

        if (strlen($post_rss_login) < 6)
            $err[] = "RSS feed login must be 6 chars or more";

        if (strlen($post_rss_password) < 6)
            $err[] = "RSS feed password must be 6 chars or more";
            
        if (count($err) == 0)
        {                      
			try
			{
            	$db->Execute("REPLACE INTO client_settings SET `key`=?, `value`=?, `clientid`=?", array('reboot_timeout', (int)$post_reboot_timeout, $_SESSION['uid']));
            	$db->Execute("REPLACE INTO client_settings SET `key`=?, `value`=?, `clientid`=?", array('launch_timeout', (int)$post_launch_timeout, $_SESSION['uid']));
            	$db->Execute("REPLACE INTO client_settings SET `key`=?, `value`=?, `clientid`=?", array('client_max_instances', (int)$post_client_max_instances, $_SESSION['uid']));
            	            	
            	$db->Execute("REPLACE INTO client_settings SET `key`=?, `value`=?, `clientid`=?", array('rss_login', $post_rss_login, $_SESSION['uid']));
            	$db->Execute("REPLACE INTO client_settings SET `key`=?, `value`=?, `clientid`=?", array('rss_password', $post_rss_password, $_SESSION['uid']));
			}
			catch (Exception $e)
			{
				throw new ApplicationException($e->getMessage(), E_ERROR);
			}
        
            if (count($err) == 0)
            {        		
        		$errmsg = false;
            	$okmsg = "System settings successfully saved";
                UI::Redirect("index.php");
            }
        }
	}
	
	$reboot_timeout = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array('reboot_timeout', $_SESSION['uid']));
	$launch_timeout = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array('launch_timeout', $_SESSION['uid']));
	$client_max_instances = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array('client_max_instances', $_SESSION['uid']));
	
	$display["reboot_timeout"] = $reboot_timeout ? $reboot_timeout : CONFIG::$REBOOT_TIMEOUT;
	$display["launch_timeout"] = $launch_timeout ? $launch_timeout : CONFIG::$LAUNCH_TIMEOUT;
	$display["client_max_instances"] = $client_max_instances ? $client_max_instances : CONFIG::$CLIENT_MAX_INSTANCES;
	
	$display["rss_login"] = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array('rss_login', $_SESSION['uid']));
	$display["rss_password"] = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array('rss_password', $_SESSION['uid']));	
		
	require("src/append.inc.php"); 
?>