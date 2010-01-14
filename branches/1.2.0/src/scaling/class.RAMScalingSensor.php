<?
	class RAMScalingSensor implements IScalingSensor
	{
		public function __construct()
		{
			$this->DB = Core::GetDBInstance(null, true);
			$this->SNMP = new SNMP();
			$this->Logger = Logger::getLogger("RAMScalingSensor");
		}
		
		public function GetValue(DBFarmRole $DBFarmRole)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($DBFarmRole->FarmID));
			$farm_ami_info = $this->DB->GetRow("SELECT * FROM farm_roles WHERE ami_id=?", array($DBFarmRole->AMIID));
			
			$instances = $this->DB->GetAll("SELECT id FROM farm_instances WHERE farmid=? AND (ami_id=? OR ami_id=?) AND state=?",
				array($DBFarmRole->FarmID, $farm_ami_info['ami_id'], $farm_ami_info['replace_to_ami'], INSTANCE_STATE::RUNNING)
			);
			
			$roleBW = 0;
			
			if (count($instances) == 0)
				return 0;
			
			$prev_sensor_data = $this->DB->GetRow("SELECT raw_sensor_data, dtlastupdate FROM sensor_data WHERE farm_roleid=? AND sensor_name=?",
				array($DBFarmRole->ID, get_class($this))
			);
				
			foreach ($instances as $instance)
			{
				$DBInstance = DBInstance::LoadByID($instance['id']);
				
				$this->SNMP->Connect($DBInstance->ExternalIP, null, $farminfo['hash'], null, null, true);
            	preg_match_all("/[0-9]+/si", $this->SNMP->Get(".1.3.6.1.4.1.2021.4.11.0"), $matches);
				$free_ram = (int)$matches[0][0];
            	
            	$ram = round($free_ram/1024, 2);
				$this->Logger->info(sprintf("Free RAM on instance '%s' = %s MB", $DBInstance->InstanceID, $ram));
                                    
                $roleRAM += $ram;
			}
			
			$retval = round($roleRAM/count($instances), 2);
			
			$this->DB->Execute("REPLACE INTO sensor_data SET farm_roleid=?, sensor_name=?, sensor_value=?, dtlastupdate=?, raw_sensor_data=?",
				array($DBFarmRole->ID, get_class($this), $retval, time(), $roleRAM)
			);
			
			return $retval;
		}
	}
?>