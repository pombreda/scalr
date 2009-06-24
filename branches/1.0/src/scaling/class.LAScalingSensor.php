<?
	class LAScalingSensor implements IScalingSensor
	{
		public function __construct()
		{
			$this->DB = Core::GetDBInstance(null, true);
			$this->SNMP = new SNMP();
			$this->Logger = Logger::getLogger("LAScalingSensor");
		}
		
		public function GetValue(DBFarmRole $DBFarmRole)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($DBFarmRole->FarmID));
			$farm_ami_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE ami_id=?", array($DBFarmRole->AMIID));
			
			$instances = $this->DB->GetAll("SELECT id FROM farm_instances WHERE farmid=? AND (ami_id=? OR ami_id=?) AND state=?",
				array($DBFarmRole->FarmID, $farm_ami_info['ami_id'], $farm_ami_info['replace_to_ami'], INSTANCE_STATE::RUNNING)
			);
			
			$roleLA = 0;
			
			if (count($instances) == 0)
				return 0;
			
			foreach ($instances as $instance)
			{
				$DBInstance = DBInstance::LoadByID($instance['id']);
				
				$this->SNMP->Connect($DBInstance->ExternalIP, null, $farminfo['hash'], null, null, true);
            	$res = $this->SNMP->Get(".1.3.6.1.4.1.2021.10.1.3.3");
            	
            	$la = (float)$res;
				$this->Logger->info(sprintf("LA (15 min average) on '%s' = %s", $DBInstance->InstanceID, $la));
                                    
                $roleLA += $la;
			}
			
			$retval = round($roleLA/count($instances), 2);
			
			$this->DB->Execute("REPLACE INTO sensor_data SET farm_roleid=?, sensor_name=?, sensor_value=?, dtlastupdate=?, raw_sensor_data=?",
				array($DBFarmRole->ID, get_class($this), $retval, time(), $roleLA)
			);
			
			return $retval;
		}
	}
?>