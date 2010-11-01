<?
	require("src/prepend.inc.php");

	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_ADMIN))
	{
		$errmsg = "You have no permissions for viewing this page";
		UI::Redirect("index.php");
	}

	$display["title"] = "Settings&nbsp;&raquo;&nbsp;System";

	$client_settings = array(
		CLIENT_SETTINGS::RSS_LOGIN => "string",
		CLIENT_SETTINGS::RSS_PASSWORD => "string",
		CLIENT_SETTINGS::SYNC_TIMEOUT => "int"
	);

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

            		$db->Execute("REPLACE INTO client_settings SET `key`=?, `value`=?, `clientid`=?",
            			array($client_setting, $value, Scalr_Session::getInstance()->getClientId())
            		);
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
    	$$client_setting = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?",
    		array($client_setting, Scalr_Session::getInstance()->getClientId())
    	);


	$Client = Client::Load(Scalr_Session::getInstance()->getClientId());

	$display["rss_login"] = $Client->GetSettingValue(CLIENT_SETTINGS::RSS_LOGIN);
	$display["rss_password"] = $Client->GetSettingValue(CLIENT_SETTINGS::RSS_PASSWORD);


	//$display["help"] = "By default, every AWS account can allocate maximum 5 Elastic IPs. If you're already using Elastic IPs outside Scalr, make sure to substract this amount, otherwise IPs will be reassigned to Scalr instances without any prompt.";

	require("src/append.inc.php");
?>