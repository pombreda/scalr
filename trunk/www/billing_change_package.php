<? 
	require("src/prepend.inc.php"); 
	require_once(dirname(__FILE__)."/../src/class.PayPalPaymentProcessor.php");
	
	
	$display["title"] = _("Change account type");
		
	$active_sub = $db->GetRow("SELECT * FROM subscriptions WHERE clientid=? AND status='Active'", array(
		$_SESSION['uid']
	));
	
	if ($active_subs)
		$display['active_subs'] = $active_sub;
		
	$display['package'] = $db->GetRow("SELECT * FROM billing_packages WHERE id=?",
		array($Client->GetSettingValue(CLIENT_SETTINGS::BILLING_PACKAGE))
	);

	if ($_POST)
	{
		if ($display['active_subs'])
			$errmsg = _("You cannot change your account type while you have an active subscription. Please cancel you current subscription first.");
			
		if ($post_new_pkgid)
		{
			
			
			$paypal_config = PayPalPaymentProcessor::GetConfigurationForm();
			$paypal_config->GetFieldByName('business')->Value = CONFIG::$PAYPAL_BUSINESS;
			$paypal_config->GetFieldByName('receiver')->Value = CONFIG::$PAYPAL_RECEIVER;
			$paypal_config->GetFieldByName('isdemo')->Value = CONFIG::$PAYPAL_ISDEMO;
			$paypal_config->GetFieldByName('currency')->Value = 'USD';
			
			// Create PayPal instance
			$PayPal = new PayPalPaymentProcessor($paypal_config);
			
			$package = $db->GetRow("SELECT * FROM billing_packages WHERE `id`=? AND `group`=?", array(
				$post_new_pkgid, $display['package']['group']
			));
			
			if ($package)
			{
				$Client = Client::Load($_SESSION['uid']);
				
				// Redirect client to payment gateway
				$PayPal->RedirectToGateway(
					"{$Client->ID}:{$package['id']}", 
					$package['cost'], 
					CONFIG::$PAYMENT_DESCRIPTION.". Account: {$Client->Email}", 
					CONFIG::$PAYMENT_TERM, 
					"M"
				);
				exit();
			}
			else
				$errmsg = _("Such package not found in database");
		}
	}
	
	$display['packages'] = $db->GetAll("SELECT * FROM billing_packages WHERE `group` = ? AND `id` != ?",
		array($display['package']['group'], $display['package']['id'])
	);
	
	if (count($display['packages']) == 0)
	{
		$errmsg = _("You cannot change your account type. Please <a href='mailto:".CONFIG::$EMAIL_ADDRESS."'>contact us</a> for more information.");
		UI::Redirect("/index.php");
	}
	
	require("src/append.inc.php");
?>