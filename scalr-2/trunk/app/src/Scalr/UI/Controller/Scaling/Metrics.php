<?php

class Scalr_UI_Controller_Scaling_Metrics extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'metricId';
	
	public function defaultAction()
	{
		$this->viewAction();
	}
	
	public function xSaveAction()
	{
		$this->request->defineParams(array(
			'metricId' => array('type' => 'int'),
			'name', 'filePath', 'retrieveMethod', 'calcFunction'
		));
		
		try {
			$metric = Scalr_Model::init(Scalr_Model::SCALING_METRIC);
			
			if ($this->getParam('metricId'))
			{
				$metric->loadById($this->getParam('metricId'));
				if ($metric->clientId != Scalr_Session::getInstance()->getClientId())
					throw new Exception("Metric not found");
			}
			else
			{
				$metric->clientId = $this->session->getClientId();
				$metric->envId = $this->session->getEnvironmentId();
				$metric->alias = 'custom';
				$metric->algorithm = Scalr_Scaling_Algorithm::SENSOR_ALGO;
			}
			
			if (!preg_match("/^[A-Za-z0-9]{6,}/", $this->getParam('name')))
				throw new Exception("Metric name should me alphanumeric and greater than 5 chars");
			
			$metric->name = $this->getParam('name');
			$metric->filePath = $this->getParam('filePath');
			$metric->retrieveMethod = $this->getParam('retrieveMethod');
			$metric->calcFunction = $this->getParam('calcFunction');
			
			$metric->save();
			
			$this->response->setJsonResponse(array('success' => true), 'text');
		}
		catch(Exception $e)
		{
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()), 'text');
		}
	}
	
	public function createAction()
	{
		$this->response->setJsonResponse(array(
			'success' => true,
			'moduleParams' => array(
				'name' => '',
				'filePath' => '',
				'retrieveMethod' => '',
				'calcFunction' => ''		
			),
			'module' => $this->response->template->fetchJs('scaling/metrics/create.js')
		));
	}
	
	public function editAction()
	{
		$this->request->defineParams(array(
			'metricId' => array('type' => 'int')
		));
		
		$metric = Scalr_Model::init(Scalr_Model::SCALING_METRIC);
		$metric->loadById($this->getParam('metricId'));
		if ($metric->clientId != Scalr_Session::getInstance()->getClientId())
			throw new Exception ("Metric not found");
		
		$this->response->setJsonResponse(array(
			'success' => true,
			'moduleParams' => array(
				'name' => $metric->name,
				'filePath' => $metric->filePath,
				'retrieveMethod' => $metric->retrieveMethod,
				'calcFunction' => $metric->calcFunction	
			),
			'module' => $this->response->template->fetchJs('scaling/metrics/create.js')
		));
	}
	
	public function viewAction()
	{
		$this->response->setJsonResponse(array(
			'success' => true,
			'module' => $this->response->template->fetchJs('scaling/metrics/view.js')
		));
	}
	
	public function xListViewMetricsAction()
	{
		$this->request->defineParams(array(
			'metricId' => array('type' => 'int'),
			'sort' => array('type' => 'string', 'default' => 'id'),
			'dir' => array('type' => 'string', 'default' => 'ASC')
		));
		
		$sql = "select * FROM scaling_metrics WHERE 1=1";
		$sql .= " AND (env_id='".Scalr_Session::getInstance()->getEnvironmentId()."' OR env_id='0')";
		
		$response = $this->buildResponseFromSql($sql, array("name", "file_path"));
		
		$this->response->setJsonResponse($response);
	}
}
