<? 
	require("src/prepend.inc.php"); 
		
	if ($_SESSION["uid"] == 0)
	{
		$errmsg = "Requested page cannot be viewed from admin account";
		UI::Redirect("index.php");
	}
	
	$display["title"] = "Settings&nbsp;&raquo;&nbsp;System";
	
	$client_settings = array(
		CLIENT_SETTINGS::MAX_INSTANCES_LIMIT => "int", 
		CLIENT_SETTINGS::MAX_EIPS_LIMIT => "int",
		CLIENT_SETTINGS::RSS_LOGIN => "string",
		CLIENT_SETTINGS::RSS_PASSWORD => "string",
		CLIENT_SETTINGS::SYNC_TIMEOUT => "int",
		CLIENT_SETTINGS::API_ENABLED  => "int",
		CLIENT_SETTINGS::API_ALLOWED_IPS => "string",
		CLIENT_SETTINGS::TIMEZONE => "string"
	);
	
	$timezone_abbreviations_list = timezone_abbreviations_list();
	foreach ($timezone_abbreviations_list as $timezone_abbreviations)
	{
		foreach ($timezone_abbreviations as $v)
		{
			if ($v['timezone_id'])
				$timezones[$v['timezone_id']] = $v['offset'];
		}
	}
	
	ksort($timezones);
	
	$display['timezones'] = array_keys($timezones);
	
	$Validator = new Validator();
		
	if ($_POST) 
	{		
        if (strlen($post_rss_login) < 6)
            $err[] = "RSS feed login must be 6 chars or more";

        if (strlen($post_rss_password) < 6)
            $err[] = "RSS feed password must be 6 chars or more";

        if (count($err) == 0)
        {                      
			try
			{
            	$HTMLPurifier_Config = HTMLPurifier_Config::createDefault();
			    $HTMLPurifier_Config->set('HTML', 'Allowed', '');
			    $HTMLPurifier_Config->set('Cache', 'DefinitionImpl', null);	    
				$HTMLPurifier_Config->set('Core', 'CollectErrors', true);
				$purifier = new HTMLPurifier($HTMLPurifier_Config);
				
				foreach ($client_settings as $client_setting=>$type)
            	{
					switch($type)
					{
						case "int":
							$value = (int)$_POST[str_replace(".", "_", $client_setting)];
							break;
						default:
							$value = $purifier->purify($_POST[str_replace(".", "_", $client_setting)]);
							break;
					}
					
            		$db->Execute("REPLACE INTO client_settings SET `key`=?, `value`=?, `clientid`=?", array($client_setting, $value, $_SESSION['uid']));
            	}
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
	
	foreach ($client_settings as $client_setting=>$type)
    	$$client_setting = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array($client_setting, $_SESSION['uid']));
	
    $display["client_max_instances"] = $client_max_instances ? $client_max_instances : CONFIG::$CLIENT_MAX_INSTANCES;
	$display["client_max_eips"] = $client_max_eips ? $client_max_eips : CONFIG::$CLIENT_MAX_EIPS;
	$display["sync_timeout"] = $sync_timeout ? $sync_timeout : CONFIG::$SYNC_TIMEOUT;
	
	$Client = Client::Load($_SESSION['uid']);
	
	$display["rss_login"] = $Client->GetSettingValue(CLIENT_SETTINGS::RSS_LOGIN);
	$display["rss_password"] = $Client->GetSettingValue(CLIENT_SETTINGS::RSS_PASSWORD);
	$display["api_enabled"] = $Client->GetSettingValue(CLIENT_SETTINGS::API_ENABLED);
	$display["api_allowed_ips"] = $Client->GetSettingValue(CLIENT_SETTINGS::API_ALLOWED_IPS);
	$display["timezone"] = $Client->GetSettingValue(CLIENT_SETTINGS::TIMEZONE);
	if (!$display["timezone"])
		$display["timezone"] = 'CST6CDT';
	
	$display["scalr_api_keyid"] = $Client->ScalrKeyID;
	$display["scalr_api_key"] = $Client->GetScalrAPIKey();
			
	$display["help"] = "By default, every AWS account can allocate maximum 5 Elastic IPs. If you're already using Elastic IPs outside Scalr, make sure to substract this amount, otherwise IPs will be reassigned to Scalr instances without any prompt.";
	
	require("src/append.inc.php"); 
?>