<?php

class Scalr_Service_ZohoCrm {

	private $config;
	
	private static $modulesConfig = array(
		Scalr_Service_ZohoCrm_ModuleName::ACCOUNT => array(
			"entityCls" => "Scalr_Service_ZohoCrm_Entity_Account",
			"moduleName" => Scalr_Service_ZohoCrm_ModuleName::ACCOUNT
		),
		Scalr_Service_ZohoCrm_ModuleName::LEAD => array(
			"entityCls" => "Scalr_Service_ZohoCrm_Entity_Lead",
			"moduleName" => Scalr_Service_ZohoCrm_ModuleName::LEAD
		),
		Scalr_Service_ZohoCrm_ModuleName::CONTACT => array(
			"entityCls" => "Scalr_Service_ZohoCrm_Entity_Contact",
			"moduleName" => Scalr_Service_ZohoCrm_ModuleName::CONTACT
		),
		Scalr_Service_ZohoCrm_ModuleName::SALES_ORDER => array(
			"entityCls" => "Scalr_Service_ZohoCrm_Entity_SalesOrder",
			"moduleName" => Scalr_Service_ZohoCrm_ModuleName::SALES_ORDER
		),
	);
	
	private $services = array();
	
	/**
	 * 
	 * @param $config array
	 * @cfg $apiKey
	 * @cfg $username
	 * @cfg $password
	 * @cfg $ticketId
	 */
	function __construct ($config) {
		$this->config = $config;
		
		$session = new Scalr_Service_ZohoCrm_Session();
		if ($this->config['ticketId']) {
			$session->ticketId = $this->config['ticketId'];
			unset($this->config['ticketId']);
		}
		$this->config['session'] = $session;
	}
	
	/**
	 * @param string $moduleName
	 * @return Scalr_Service_ZohoCrm_Service
	 */
	function factory ($moduleName) {
		if (!key_exists($moduleName, $this->services)) {
			if (key_exists($moduleName, self::$modulesConfig)) {
				$this->services[$moduleName] = new Scalr_Service_ZohoCrm_Service(
						array_merge($this->config, self::$modulesConfig[$moduleName]));
			} else {
				throw new Scalr_Service_ZohoCrm_Exception(sprintf("Undefined module '%s'", $moduleName));
			}
		}
		
		return $this->services[$moduleName];
	}
	
	function getNumApiCalls () {
		return $this->config['session']->numApiCalls;
	}
}

final class Scalr_Service_ZohoCrm_ModuleName {
	const LEAD = "Leads";
	const ACCOUNT = "Accounts";
	const CONTACT = "Contacts";
	const SALES_ORDER = "SalesOrders";
}

class Scalr_Service_ZohoCrm_Session {
	/**
	 * ZohoCRM session id
	 * @var string
	 */
	public $ticketId;
	
	/**
	 * Number of API calls   
	 * @var int
	 */
	public $numApiCalls = 0;
}