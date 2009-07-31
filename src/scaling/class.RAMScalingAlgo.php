<?
	class RAMScalingAlgo extends ScalingAlgo implements IScalingAlgo
	{		
		const PROPERTY_MAX_RAM = 'scaling.ram.max';
		const PROPERTY_MIN_RAM = 'scaling.ram.min';
		
		public function __construct()
		{
			parent::__construct();
			$this->Logger = Logger::getLogger(get_class($this));
		}
		
		public static function GetAlgoDescription()
		{
			return "RAM usage";
		}
		
		public static function ValidateConfiguration(array &$config, DBFarmRole $DBFarmRole)
		{
			$algo_name = strtolower(str_replace("ScalingAlgo", "", __CLASS__));
			
			if ($config["scaling.{$algo_name}.enabled"] == 1)
			{
				$config[self::PROPERTY_MAX_RAM] = (float)$config[self::PROPERTY_MAX_RAM];
				if($config[self::PROPERTY_MAX_RAM] <= 0)
					throw new Exception(sprintf(_("Minimum Free RAM for role '%s' must be a positive number"), $DBFarmRole->GetRoleName()));
					
				$config[self::PROPERTY_MIN_RAM] = (float)$config[self::PROPERTY_MIN_RAM];
				if($config[self::PROPERTY_MIN_RAM] <= 0)
					throw new Exception(sprintf(_("Maximum Free RAM for role '%s' must be a positive number"), $DBFarmRole->GetRoleName()));
					
				if($config[self::PROPERTY_MAX_RAM] < $config[self::PROPERTY_MIN_RAM])
					throw new Exception(sprintf(_("Maximum Free RAM for role '%s' must be greather than minimum Free RAM"), $DBFarmRole->GetRoleName()));
			}
				
			return true;	
		}
		
		/**
		 * Must return a DataForm object that will be used to draw a configuration form for this scalign algo.
		 * @return DataForm object
		 */
		public static function GetConfigurationForm($clientid = null)
		{
			$ConfigurationForm = new DataForm();
			$ConfigurationForm->AppendField( new DataFormField(self::PROPERTY_MIN_RAM, FORM_FIELD_TYPE::TEXT, "Minimum free RAM (MB)", null, null, 512));
			$ConfigurationForm->AppendField( new DataFormField(self::PROPERTY_MAX_RAM, FORM_FIELD_TYPE::TEXT, "Maximum free RAM (MB)", null, null, 1024));
			
			return $ConfigurationForm;
		}
		
		public function MakeDecision(DBFarmRole $DBFarmRole)
		{			
			//
			// Get data from BW sensor
			//
			$RAMSensor = SensorFactory::NewSensor(SensorFactory::RAM_SENSOR);
			$sensor_value = $RAMSensor->GetValue($DBFarmRole);
			$this->Logger->info("RAMScalingAlgo({$DBFarmRole->FarmID}, {$DBFarmRole->AMIID}) Sensor returned value: {$sensor_value}.");
			
			if ($sensor_value > $this->GetProperty(self::PROPERTY_MAX_RAM))
			{
				if($DBFarmRole->GetRunningInstancesCount() < $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES) 
					&& $DBFarmRole->GetPendingInstancesCount() == 0)
					return ScalingAlgo::UPSCALE;
				else
					return ScalingAlgo::NOOP;
			}
			elseif ($sensor_value < $this->GetProperty(self::PROPERTY_MIN_RAM))
			{
				if ($DBFarmRole->GetRunningInstancesCount() > $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES))
					return ScalingAlgo::DOWNSCALE;
				else
					return ScalingAlgo::NOOP;
			}
			else
				return ScalingAlgo::NOOP;
		}
	}
?>