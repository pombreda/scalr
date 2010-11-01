<?php
	require("src/prepend.inc.php"); 
		
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER, Scalr_AuthToken::MODULE_CONFIG_PRESETS))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	$display["title"] = _("Service configuration presets");
	
	if ($_POST)
	{
		if (!$req_preset_id && !$req_var)
		{
			if (!in_array($req_role_behavior, array('mysql','app','memcached','cassandra','www')))
				$err[] = _("Please select service name");
		}
			
		if (!$post_name)
			$err[] = _("Preset name required");
		else
		{	
			if (strlen($post_name) < 5)
				$err[] = _("Preset name should be 5 chars or longer");
			elseif (!preg_match("/^[A-Za-z0-9-]+$/", $post_name))
				$err[] = _("Preset name should be alpha-numeric");
			elseif (strtolower($post_name) == "default")
				$err[] = _("default is reserverd name");
			elseif ($req_role_behavior && $db->GetOne("SELECT id FROM service_config_presets WHERE name = ? AND role_behavior = ? AND id != ? AND env_id = ?", array(
				$post_name, $req_role_behavior, (int)$req_preset_id, Scalr_Session::getInstance()->getEnvironmentId()
			)))
				$err[] = _("Preset with selected name already exists");
		}
		
		if (count($err) == 0 && $req_role_behavior && $req_name)
		{
			$_SESSION['preset_role_behavior'] = $req_role_behavior;
			$_SESSION['preset_name'] = $req_name;
		}
	}
	
	if ($req_preset_id)
	{
		try {
			$serviceConfiguration = Scalr_Model::init(Scalr_Model::SERVICE_CONFIGURATION)->loadById($req_preset_id);
			if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($serviceConfiguration->envId))
				throw new Exception("No perms");
		}
		catch (Exception $e)
		{
			$errmsg = _("You have no permissions for viewing requested page");
			UI::Redirect("/service_config_presets.php");
		}
	}
	elseif ($_SESSION['preset_role_behavior'] && !$err)
	{
		$serviceConfiguration = Scalr_Model::init(Scalr_Model::SERVICE_CONFIGURATION);
		$serviceConfiguration->loadBy(array(
			'client_id'		=> Scalr_Session::getInstance()->getClientId(),
			'env_id'		=> Scalr_Session::getInstance()->getEnvironmentId(),
			'name'			=> $_SESSION['preset_name'],
			'role_behavior'	=> $_SESSION['preset_role_behavior']
		));
	}
	
	if (!$serviceConfiguration)
	{
		$display['err'] = $err;
		$display['preset_name'] = $req_name;
		$display['role_behavior'] = $req_role_behavior;
		$Smarty->assign($display);
		$Smarty->display("service_config_preset_add_step1.tpl");
		exit();
	}
	
	$display['extjs_form_items'] = $serviceConfiguration->getParametersExtJson();
	$display['preset_id'] = (int)$req_preset_id;
	$display['preset_name'] = $serviceConfiguration->name;
	$display['preset_role_behavior'] = ROLE_BEHAVIORS::GetName($serviceConfiguration->roleBehavior);
	
	if ($_POST && !$req_role_behavior)
	{
		if ($db->GetOne("SELECT id FROM service_config_presets WHERE name = ? AND role_behavior = ? AND id != ? AND env_id = ?", array(
			$req_name, $serviceConfiguration->roleBehavior, (int)$req_preset_id, Scalr_Session::getInstance()->getEnvironmentId()
		)))
			$err[] = _("Preset with selected name already exists");
		
		if (count($err) == 0)
		{
			try
			{
				foreach ($req_var as $k=>$v)
				{
					if ($v)
						$serviceConfiguration->setParameterValue($k, $v);
				}
				
				$serviceConfiguration->name = $req_name;
				
				$serviceConfiguration->save();
				
				$_SESSION['preset_role_behavior'] = null;
				
				//TODO:
				$resetToDefaults = false;
				
				Scalr::FireEvent(null, new ServiceConfigurationPresetChangedEvent($serviceConfiguration, $resetToDefaults));
				
				$_SESSION['preset_role_behavior'] = null;
				$_SESSION['preset_name'] = null;
			}
			catch(Exception $e)
			{
				$_SESSION['preset_role_behavior'] = null;
				$_SESSION['preset_name'] = null;
				
				print json_encode(array('success' => false, 'error' => $e->getMessage()));
				exit();
			}
			
			print json_encode(array('success' => true));	
			exit();
		}
		else
		{
			$_SESSION['preset_role_behavior'] = null;
			$_SESSION['preset_name'] = null;
			
			print json_encode(array('success' => false, 'error' => $err[0]));	
			exit();
		}
	}	
	
	require("src/append.inc.php");