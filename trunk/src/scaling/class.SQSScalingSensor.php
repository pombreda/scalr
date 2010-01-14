<?
	class SQSScalingSensor implements IScalingSensor
	{
		public function __construct()
		{
			$this->DB = Core::GetDBInstance(null, true);
			$this->SNMP = new SNMP();
			$this->Logger = Logger::getLogger("SQSScalingSensor");
		}
		
		public function GetValue(DBFarmRole $DBFarmRole)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($DBFarmRole->FarmID));
			$farm_ami_info = $this->DB->GetRow("SELECT * FROM farm_roles WHERE ami_id=?", array($DBFarmRole->AMIID));
			
			$Client = Client::Load($farminfo['clientid']);
			$AmazonSQS = AmazonSQS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
			
			try
			{
				$res = $AmazonSQS->GetQueueAttributes($DBFarmRole->GetSetting(SQSScalingAlgo::PROPERTY_QUEUE_NAME));
				$retval = $res['ApproximateNumberOfMessages'];
			}
			catch(Exception $e)
			{
				throw new Exception(sprintf("SQSScalingSensor failed during SQS request: %s", $e->getMessage()));
			}
			
			$this->DB->Execute("REPLACE INTO sensor_data SET farm_roleid=?, sensor_name=?, sensor_value=?, dtlastupdate=?, raw_sensor_data=?",
				array($DBFarmRole->ID, get_class($this), $retval, time(), $retval)
			);
			
			return $retval;
		}
	}
?>