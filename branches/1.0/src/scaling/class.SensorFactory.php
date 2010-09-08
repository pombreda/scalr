<?
	class SensorFactory
	{
		const LA_SENSOR = 'LAScalingSensor';
		const BW_SENSOR = 'BWScalingSensor';
		const SQS_SENSOR = 'SQSScalingSensor';
		
		private static $SensorsCache = array();
		
		public static function NewSensor($sensor_name)
		{
			if (class_exists($sensor_name))
			{
				if (!self::$SensorsCache[$sensor_name])
				{
					$reflect = new ReflectionClass($sensor_name);
					self::$SensorsCache[$sensor_name] = $reflect->newInstance();
				}
				
				return self::$SensorsCache[$sensor_name];
			}
			else
				throw new Exception(sprintf(_("Sensor %s not registered"), $sensor_name));
		}
	}
?>