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
          
        if (count($err) == 0)
        {                      
			try
			{
            	$db->Execute("REPLACE INTO client_settings SET `key`=?, `value`=?, `clientid`=?", array('reboot_timeout', (int)$post_reboot_timeout, $_SESSION['uid']));
            	$db->Execute("REPLACE INTO client_settings SET `key`=?, `value`=?, `clientid`=?", array('launch_timeout', (int)$post_launch_timeout, $_SESSION['uid']));
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
	
	$display["reboot_timeout"] = $reboot_timeout ? $reboot_timeout : CONFIG::$REBOOT_TIMEOUT;
	$display["launch_timeout"] = $launch_timeout ? $launch_timeout : CONFIG::$LAUNCH_TIMEOUT;
		
	require("src/append.inc.php"); 
?>