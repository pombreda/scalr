<?php

	class Scalr_ServiceConfiguration extends Scalr_Model
	{
		protected $dbTableName = 'service_config_presets';
		protected $dbPrimaryKey = "id";
		protected $dbMessageKeyNotFound = "Preset #%s not found in database";

		protected $dbPropertyMap = array(
			'id'			=> 'id',
			'name'			=> array('property' => 'name', 'is_filter' => true),
			'client_id'		=> array('property' => 'clientId', 'is_filter' => true),
			'env_id'		=> array('property' => 'envId', 'is_filter' => true),
			'dtadded'		=> array('property' => 'dtAdded', 'createSql' => 'NOW()', 'type' => 'datetime', 'update' => false),
			'dtlastmodified'=> array('property' => 'dtLastModified', 'createSql' => 'NOW()', 'updateSql' => 'NOW()', 'type' => 'datetime'),
			'role_behavior'	=> array('property' => 'roleBehavior', 'is_filter' => true)
		);
		
		public
			$id,
			$name,
			$clientId,
			$envId,
			$dtAdded,
			$dtLastModified,
			$roleBehavior;
			
		private $parameters;
		
		public function loadBy($info)
		{
			parent::loadBy($info);
			
			error_reporting(E_ALL);
			
		    $ini_params = @parse_ini_file(dirname(__FILE__)."/../../www/storage/service-configuration-manifests/{$this->roleBehavior}.ini", true);
			
			foreach ($ini_params as $param => $props)
			{
				if ($param == '__defaults__')
					continue;
				
				$this->parameters[] = new Scalr_ServiceConfigurationParameter(
					$param,
					$props['default-value'],
					$props['type'],
					$props['description'],
					$props['allowed-values'],
					$props['group']
				);
			}
			
			if ($this->id)
			{
				//load actual values from database;
				$params = $this->db->Execute("SELECT * FROM service_config_preset_data WHERE preset_id = ?", array($this->id));
				while ($param = $params->FetchRow())
					$this->setParameterValue($param['key'], $param['value']);
			}
			
			return $this;
		}
		
		public function delete()
		{
			parent::delete();
			$this->db->Execute("DELETE FROM service_config_preset_data WHERE preset_id = ?", array($this->id));
			$this->db->Execute("DELETE FROM farm_role_service_config_presets WHERE preset_id = ?", array($this->id));
		}
		
		public function save() 
		{
			parent::save();

			$this->db->Execute("DELETE FROM service_config_preset_data WHERE preset_id = ?", array($this->id));
			foreach ($this->parameters as $param)
			{
				if ($param->getValue() != null)
				{
					//Save params
					$this->db->Execute("INSERT INTO service_config_preset_data SET
						`preset_id`	= ?,
						`key`		= ?,
						`value`		= ?
					", array($this->id, $param->getName(), $param->getValue()));
				}
			}
			
			return true;
		}
		
		/**
		 * @return array
		 */
		public function getParameters()
		{
			$retval = array();
			foreach ($this->parameters as $param)
			{
				if ($param->getValue() != "")
					$retval[] = $param;
			}
			
			return $retval;
		}
		
		/**
		 * 
		 * @param string $name
		 * @return Scalr_ServiceConfigurationParameter
		 */
		public function getParameter($name)
		{
			foreach ($this->parameters as &$param)
				if ($param->getName() == $name)
					return $param;
			
			return null;
		}
		
		public function setParameterValue($name, $value)
		{
			foreach ($this->parameters as &$param) {
				if ($param->getName() == $name) {
					if ($param->validate($value)) {
						$param->setValue($value);
					}
				}
			}
		}
		
		public function getParametersExtJson()
		{
			$items = array();
			foreach ($this->parameters as $param)
			{				
				if ($param->getName() == '__defaults__')
					continue;
				
				switch($param->getType())
				{
					case 'text':
						$itemField = new stdClass();
						$itemField->xtype = 'textfield';
						$itemField->name = $param->getName();
						$itemField->allowBlank = true;
						$itemField->width = 200;
						$itemField->value = $param->getValue();
					break;
					
					case 'boolean':
						$itemField = new stdClass();
						$itemField->xtype = 'checkbox';
						$itemField->name = $param->getName();
						$itemField->inputValue = 1;
						$itemField->checked = ($param->getValue() == 1);
						break;
						
					case 'select':
						$itemField = new stdClass();
						$itemField->xtype = 'combo';
						$itemField->name = $param->getName();
						$itemField->allowBlank = true;
						$itemField->editable = true;
						$itemField->typeAhead = false;
						$itemField->selectOnFocus = false;
						$itemField->forceSelection = true;
						$itemField->triggerAction = 'all';
						$itemField->value = $param->getValue();
						$itemField->store = $param->getAllowedValues();
						break;
				}
				
				
				$itemDescription = new stdClass();
				$itemDescription->html = $param->getDescription();
				$itemDescription->style = 'font-style:italic;padding-top:3px;';
				
				$item = new stdClass();
				$item->xtype = 'compositefield';
				$item->fieldLabel = $param->getName();
				$item->items = array(
					$itemField,
					$itemDescription
				);
				
				$items[] = $item;
			}
			
			return json_encode($items);
		}
	}
