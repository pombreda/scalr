<?
	class SQSScalingAlgo extends ScalingAlgo implements IScalingAlgo
	{
		const PROPERTY_MIN_SIZE = 'scaling.sqs.min';
		const PROPERTY_MAX_SIZE = 'scaling.sqs.max';
		const PROPERTY_QUEUE_NAME = 'scaling.sqs.queue_name';
		
		public function __construct()
		{
			parent::__construct();
			$this->Logger = Logger::getLogger(get_class($this));
		}
		
		public static function GetAlgoDescription()
		{
			return "SQS queue size";
		}
		
		public static function ValidateConfiguration(array &$config, DBFarmRole $DBFarmRole)
		{
			$algo_name = strtolower(str_replace("ScalingAlgo", "", __CLASS__));
			
			if ($config["scaling.{$algo_name}.enabled"] == 1)
			{
				$config[self::PROPERTY_MIN_SIZE] = (int)$config[self::PROPERTY_MIN_SIZE];
				if($config[self::PROPERTY_MIN_SIZE] < 1)
					throw new Exception(sprintf(_("Minimum SQS queue size for role '%s' must be a number greather than 0"), $DBFarmRole->GetRoleName()));
					
				$config[self::PROPERTY_MAX_SIZE] = (int)$config[self::PROPERTY_MAX_SIZE];
				if($config[self::PROPERTY_MAX_SIZE] < 2)
					throw new Exception(sprintf(_("Maximum SQS queue size for role '%s' must be a number greather than 2"), $DBFarmRole->GetRoleName()));
					
				if($config[self::PROPERTY_MAX_SIZE] < $config[self::PROPERTY_MIN_SIZE])
					$err[] = sprintf(_("Maximum SQS queue size for role '%s' must be greather than minimum queue size"), $DBFarmRole->GetRoleName());
					
				if (!$config[self::PROPERTY_QUEUE_NAME] || $config[self::PROPERTY_QUEUE_NAME] == '0')
					throw new Exception(sprintf(_("SQS Queue name for role '%s' required"), $DBFarmRole->GetRoleName()));
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
			$ConfigurationForm->AppendField( new DataFormField(self::PROPERTY_MIN_SIZE, FORM_FIELD_TYPE::TEXT, "Minimum queue size", null, null, 10));
			$ConfigurationForm->AppendField( new DataFormField(self::PROPERTY_MAX_SIZE, FORM_FIELD_TYPE::TEXT, "Maximum queue size", null, null, 10));
			
			if ($clientid)
			{
				try
				{
					$db = Core::GetDBInstance();
					
					$Client = Client::Load($clientid);
					$AmazonSQS = AmazonSQS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
					$queues = $AmazonSQS->ListQueues();
					$options[] = "";
					foreach ($queues as $queue)
						$options[$queue] = $queue;
						
					$ConfigurationForm->AppendField( new DataFormField(self::PROPERTY_QUEUE_NAME, FORM_FIELD_TYPE::SELECT, "Queue name", null, $options, ""));
				}
				catch(Exception $e)
				{
					$err = true;
				}
			}
			
			if (!$clientid || $err)
				$ConfigurationForm->AppendField( new DataFormField(self::PROPERTY_QUEUE_NAME, FORM_FIELD_TYPE::TEXT, "Queue name", null, null, ""));
			
			return $ConfigurationForm;
		}
		
		public function MakeDecision(DBFarmRole $DBFarmRole)
		{			
			//
			// Get data from SQS sensor
			//
			$BWSensor = SensorFactory::NewSensor(SensorFactory::SQS_SENSOR);
			$sensor_value = $BWSensor->GetValue($DBFarmRole);
			$this->Logger->info("SQSScalingAlgo({$DBFarmRole->FarmID}, {$DBFarmRole->AMIID}) Sensor returned value: {$sensor_value}.");
			
			if ($sensor_value > $this->GetProperty(self::PROPERTY_MAX_SIZE))
				return ScalingAlgo::UPSCALE;
			elseif ($sensor_value < $this->GetProperty(self::PROPERTY_MIN_SIZE))
				return ScalingAlgo::DOWNSCALE;
			else
				return ScalingAlgo::NOOP;
		}
	}
?>