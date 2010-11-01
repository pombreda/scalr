<?php
	require("src/prepend.inc.php"); 
		
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER, Scalr_AuthToken::MODULE_CONFIG_PRESETS))
	{
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("index.php");
	}
	
	$display["title"] = _("Scaling metrics");
	
	if ($_POST)
	{
		try {
			$metric = Scalr_Model::init(Scalr_Model::SCALING_METRIC);
			
			if ($req_metric_id)
			{
				$metric->loadById($req_metric_id);
				if ($metric->clientId != Scalr_Session::getInstance()->getClientId())
					throw new Exception("Metric not found");
			}
			else
			{
				$metric->clientId = Scalr_Session::getInstance()->getClientId();
				$metric->envId = Scalr_Session::getInstance()->getEnvironmentId();
				$metric->alias = 'custom';
				$metric->algorithm = Scalr_Scaling_Algorithm::SENSOR_ALGO;
			}
			
			if (!preg_match("/^[A-Za-z0-9]{6,}/", $req_name))
				throw new Exception("Metric name should me alphanumeric and greater than 5 chars");
			
			$metric->name = $req_name;
			$metric->filePath = $req_file_path;
			$metric->retrieveMethod = $req_retrieve_method;
			$metric->calcFunction = $req_calc_function;
			
			$metric->save();
			
			print json_encode(array('success' => true));	
			exit();
		}
		catch(Exception $e)
		{
			print json_encode(array('success' => false, 'error' => $e->getMessage()));
			exit();
		}
	}
	
	if ($req_metric_id)
	{
		$metric = Scalr_Model::init(Scalr_Model::SCALING_METRIC);
		$metric->loadById($req_metric_id);
		if ($metric->clientId != Scalr_Session::getInstance()->getClientId())
		{
			UI::Redirect("/scaling_metrics.php");
		}
		
		$display['metric'] = $metric;
	}
		
	
	require("src/append.inc.php");