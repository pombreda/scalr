<?
	class BaseScalingAlgo extends ScalingAlgo implements IScalingAlgo
	{		
		public function __construct()
		{
			parent::__construct();
			$this->Logger = Logger::getLogger(get_class($this));
		}
		
		public static function GetAlgoDescription()
		{
			return "Base";
		}
		
		public static function ValidateConfiguration(array &$config, DBFarmRole $DBFarmRole)
		{
			return true;	
		}
		
		/**
		 * Must return a DataForm object that will be used to draw a configuration form for this scalign algo.
		 * @return DataForm object
		 */
		public static function GetConfigurationForm($clientid = null)
		{
			return new DataForm();
		}
		
		public function MakeDecision(DBFarmRole $DBFarmRole)
		{			
			$DB = Core::GetDBInstance(null, true);
			
			$farminfo = $DB->GetRow("SELECT * FROM farms WHERE id=?", array($DBFarmRole->FarmID));
			$farm_pending_instances = $DB->GetOne("SELECT COUNT(*) FROM farm_instances WHERE farmid=? AND state IN (?,?)",
				array($DBFarmRole->FarmID, INSTANCE_STATE::PENDING, INSTANCE_STATE::INIT)
			);
			
			if ($farminfo['farm_roles_launch_order'] == 1 && $farm_pending_instances > 0)
			{
                if ($DBFarmRole->GetRunningInstancesCount() == 0)
                {
					$this->Logger->info("{$farm_pending_instances} instances in pending state. Launch roles one-by-one. Waiting...");
                	return ScalingAlgo::STOP_SCALING;
                }
			}
			
            if ($DBFarmRole->GetRunningInstancesCount() < $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES))
            {
            	if ($DBFarmRole->GetPendingInstancesCount() == 0)
            		return ScalingAlgo::UPSCALE;
            	else
            		return ScalingAlgo::NOOP;
            }
            elseif ($DBFarmRole->GetRunningInstancesCount() > $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES))
			{
				return ScalingAlgo::DOWNSCALE;
			}
			else
				return ScalingAlgo::NOOP;		
		}
	}
?>