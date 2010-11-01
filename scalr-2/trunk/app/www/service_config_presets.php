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
			if (!$db->GetOne("SELECT id FROM farm_role_service_config_presets WHERE preset_id=?", array($preset_id)))
			{
				try {
					$serviceConfiguration = Scalr_Model::init(Scalr_Model::SERVICE_CONFIGURATION)->loadById($preset_id);
					if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($serviceConfiguration->envId))
						throw new Exception("No perms");
						
					$serviceConfiguration->delete();
				}
				catch (Exception $e) {}
			}
			else
				$err[] = sprintf(_("Preset id #%s assigned to role and cannot be removed."), $preset_id);
		}
		
		$okmsg = _("Selected presets successfully removed");
		UI::Redirect("/service_config_presets.php");
	}
	
	$display["title"] = _("Service configuration presets");	

	
	require("src/append.inc.php");