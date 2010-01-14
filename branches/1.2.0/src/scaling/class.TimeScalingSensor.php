<?
	class TimeScalingSensor implements IScalingSensor
	{
		public function __construct()
		{
			$this->DB = Core::GetDBInstance(null, true);
			$this->Logger = Logger::getLogger("TimeScalingSensor");
		}
		
		public function GetValue(DBFarmRole $DBFarmRole)
		{
			return array((int)date("Hi"), date("D"));
		}
	}
?>