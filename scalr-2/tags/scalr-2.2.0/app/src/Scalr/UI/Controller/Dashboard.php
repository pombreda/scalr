<?php

class Scalr_UI_Controller_Dashboard extends Scalr_UI_Controller
{
	public function defaultAction()
	{
		$this->response->setJsonResponse(array(
			'success' => true,
			'module' => $this->response->template->fetchJs('dashboard.js')
		));
	}

	public function widgetAccountInfoAction()
	{
		$js_module = array();
		
		$clientId = Scalr_Session::getInstance()->getClientId();
		if ($clientId == 0) {
			array_push($js_module, array(
				'xtype' => 'displayfield', 
				'fieldLabel' => 'Logged in as', 
				'value' => 'SCALR ADMIN'
			));
		}
		else {
			$client = Client::Load($clientId);
			
			array_push($js_module, array(
				'xtype' => 'displayfield', 
				'fieldLabel' => 'Logged in as', 
				'value' => $client->Email
			));
		}
		
		$this->response->setJsonResponse(array(
			'success' => true,
			'module' => $js_module
		));
	}
}
