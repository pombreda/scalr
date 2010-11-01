<?php
	require("src/prepend.inc.php"); 
		
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER, Scalr_AuthToken::MODULE_CONFIG_PRESETS))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}	

	if ($_POST['action'] == 'delete')
	{
		foreach ($req_id as $preset_id)
		{
			try {
				$metric = Scalr_Model::init(Scalr_Model::SCALING_METRIC)->loadById($preset_id);
				if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($metric->envId))
					throw new Exception("No perms");
					
				$metric->delete();
			}
			catch (Exception $e) {}
		}
		
		$okmsg = _("Selected metrics successfully removed");
		UI::Redirect("/scaling_metrics.php");
	}
	
	$display["title"] = _("Scaling metrics");	

	
	require("src/append.inc.php");