<?
	class BWScalingSensor implements IScalingSensor
	{
		public function __construct()
		{
			$this->DB = Core::GetDBInstance(null, true);
			$this->SNMP = new SNMP();
			$this->Logger = Logger::getLogger("BWScalingSensor");
		}
		
		public function GetValue(DBFarmRole $DBFarmRole)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($DBFarmRole->FarmID));
			$farm_ami_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE ami_id=?", array($DBFarmRole->AMIID));
			
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
            	preg_match_all("/[0-9]+/si", $this->SNMP->Get(".1.3.6.1.2.1.2.2.1.16.2"), $matches);
				$bw_out = (int)$matches[0][0];
            	
            	$bw = round($bw_out/1024/1024, 2);
				$this->Logger->info(sprintf("Bandwidth usage (out) for instance '%s' = %s MB", $DBInstance->InstanceID, $bw));
                                    
                $roleBW += $bw;
			}
			
			$roleBW = round($roleBW/count($instances), 2);
			
			if ($prev_sensor_data)
			{
				$time = (time()-$prev_sensor_data['dtlastupdate']);
				$bandwidth_usage = ($roleBW - $prev_sensor_data['raw_sensor_data'])*8;
				
				$bandwidth_channel_usage = $bandwidth_usage/$time; // in Mbits/sec
				$retval = round($bandwidth_channel_usage, 2);
			}
			else
				$retval = 0;
			
			$this->DB->Execute("REPLACE INTO sensor_data SET farm_roleid=?, sensor_name=?, sensor_value=?, dtlastupdate=?, raw_sensor_data=?",
				array($DBFarmRole->ID, get_class($this), $retval, time(), $roleBW)
			);
			
			return $retval;
		}
	}
?>