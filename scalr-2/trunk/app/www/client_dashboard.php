<? 
	require("src/prepend.inc.php"); 
		
	if ($_SESSION['uid'] == 0)
		UI::Redirect("/index.php");
	
	if ($req_redirect_to == 'support')
	{	
		$Client = Client::Load($_SESSION['uid']);
		
		$farms_rs = $db->GetAll("SELECT id FROM farms WHERE clientid=?", array($Client->ID));
		$farms = array();
		foreach ($farms_rs as $frm)
			$farms[] = $frm['id'];
			
		$farms = implode(', ', array_values($farms));
		
		$args = array(
        	"name"		=> $Client->Fullname,
			"Package"	=> $db->GetOne("SELECT CONCAT(name,' ($',cost,')') FROM billing_packages WHERE id=?", array($Client->GetSettingValue(CLIENT_SETTINGS::BILLING_PACKAGE))),
        	"Farms"		=> $farms,
			"AWS Account ID" => $Client->AWSAccountID,
			"ClientID"	=> $Client->ID,
			"email"		=> $Client->Email,
        	"expires" => date("D M d H:i:s O Y", time()+120)
        );
        		        			
		$token = GenerateTenderMultipassToken(json_encode($args));
        //////////////////////////////
        	        			
        UI::Redirect("http://support.scalr.net/?sso={$token}");
	}
		
	$display["title"] = _("Dashboard");
	$display['load_extjs'] = true;
	$display["table_title_text"] = sprintf(_("Current time: %s"), date("M j, Y H:i:s"));

	/*
	$info = $db->GetRow("SELECT TO_DAYS(NOW())-TO_DAYS(dtdue) as due_days, dtdue FROM clients WHERE id = '{$Client->ID}'");

	if (!$Client->GetSettingValue(CLIENT_SETTINGS::BILLING_CGF_CID))
	{
		$packageid = $Client->GetSettingValue(CLIENT_SETTINGS::BILLING_PACKAGE);
		if ($packageid)
			$info['package'] = $db->GetRow("SELECT * FROM billing_packages WHERE id=?", array($packageid));
		
		if ($Client->IsBilled == 1)
		{
				
			if ($info['due_days'] <= -1)
				$status = 'Active';
			else
				$status = 'Overdue';
		}
	}
	else
	{
		if ($Client->IsBilled != 1)
		{
			$info['package'] = array('name' => 'Development', 'cost' => '0.00');
			$status = 'Active';
		}
		else
		{
			if ($Client->GetSettingValue(CLIENT_SETTINGS::BILLING_CGF_PKG) == 'production')
				$info['package'] = array('name' => 'Production', 'cost' => '99.00');
			else
				$info['package'] = array('name' => 'Beta-legacy', 'cost' => '50.00');
			$status = 'Active';
		}
		
		if ($Client->GetSettingValue(CLIENT_SETTINGS::BILLING_CGF_SID))
		{
			require_once(dirname(__FILE__)."/site/src/Lib/ChargifyClient/class.ChargifyConnector.php");
			require_once(dirname(__FILE__)."/site/src/Lib/ChargifyClient/class.ChargifyCreditCard.php");
			require_once(dirname(__FILE__)."/site/src/Lib/ChargifyClient/class.ChargifyCustomer.php");
			require_once(dirname(__FILE__)."/site/src/Lib/ChargifyClient/class.ChargifyProduct.php");
			require_once(dirname(__FILE__)."/site/src/Lib/ChargifyClient/class.ChargifySubscription.php");
			
			$c = new ChargifyConnector();
			
			$c->getSubscriptionsByCustomerID($Client->GetSettingValue(CLIENT_SETTINGS::BILLING_CGF_CID));
		}
	}
	*/
	
	//
	//	Aws problems
	//

	try
	{
		$awsProblemsCacheFile = CACHEPATH."/aws_problems.cache";
			       
		if (@file_exists($awsProblemsCacheFile))
		{
			@clearstatcache();
			$time = @filemtime($awsProblemsCacheFile);
			$cache_life_time = 30*60;		// 30 minutes

			if ((time() - $time) <= $cache_life_time) 
				 $display['aws_problems'] = @file_get_contents($awsProblemsCacheFile);
		}   
          
		if ($display['aws_problems'] === null)
	    	$display['aws_problems'] = CreateAwsProblemsCacheFile($db, $Smarty, $awsProblemsCacheFile);
	}
	catch(Exception $e){}

	$display['client'] = array(
		'email'		=> $Client->Email,
		'package' 	=> $info['package'],
		'status'	=> $status,
		'due_date'	=> $due_date
	);
	
	require("src/append.inc.php");  	
	
	/**
	* Function creates a cash file for limited number of aws problems
	* 
	* @param mixed $db
	* @param mixed $Smarty
	* @param mixed $awsProblemsCacheFile
	*/
	
	function CreateAwsProblemsCacheFile($db,$Smarty,$awsProblemsCacheFile)
	{
		try
		{
 			$templatePath 	= "inc/aws_problems.tpl";		// smarty's teamplate dir set to tempaltes/en_US
			$itemsLimit 	= 5;							// request items limit
			$dayLimit 		= 2;

			$awsProblesmRecords = $db->GetAll("SELECT * FROM aws_errors 
				WHERE TO_DAYS(NOW()) - TO_DAYS(`pub_date`) <= ? 
				ORDER BY `pub_date` DESC LIMIT ?",
				array($dayLimit,$itemsLimit)
			);
			
			// if problems are existed, then create cache file from tempalate
			if($awsProblesmRecords)
			{
				$Smarty->assign('aws_problems_items', $awsProblesmRecords);		// data was set to template
				$htmlCacheResult = $Smarty->fetch($templatePath);				// html from tamplate operation result
			}

			// creates/updates  cache file
			@file_put_contents($awsProblemsCacheFile, $htmlCacheResult);

			return $htmlCacheResult;
			
		}
		catch(Exception $e)
		{
			throw new Exception(_("Aws cache file error: ".$e->getMessage()));
		}

	}
?>
