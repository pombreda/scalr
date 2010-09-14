<?php
	
	class ScalrAPI20100225 extends ScalrAPI20091006
	{
		private $validObjectTypes = array('role','instance','farm');
		private $validWatcherNames = array('CPU','MEM','LA','NET');
		private $validGraphTypes = array('daily','weekly','monthly','yearly');
		
		public function GetStatisticsGraphURL($ObjectType, $ObjectID, $WatcherName, $GraphType)
		{
			//$this->Client->ID
			if (!in_array($ObjectType, $this->validObjectTypes))
				throw new Exception('Incorrect value of object type. Valid values are: role, instance and farm');

			if (!in_array($WatcherName, $this->validWatcherNames))
				throw new Exception('Incorrect value of watcher name. Valid values are: CPU, MEM, LA and NET');

			if (!in_array($GraphType, $this->validGraphTypes))
				throw new Exception('Incorrect value of graph type. Valid values are: daily, weekly, monthly and yearly');
				
			try
			{
				switch($ObjectType)
				{
					case 'role':
						
						$DBFarmRole = DBFarmRole::LoadByID($ObjectID);
						$DBFarm = $DBFarmRole->GetFarmObject();
						$role = "FR_{$DBFarmRole->ID}";
						
						break;
						
					case 'instance':
						
						$DBServer = DBServer::LoadByID($ObjectID);
						$DBFarm = $DBServer->GetFarmObject();
						$role = "INSTANCE_{$DBServer->farmRoleId}_{$DBServer->index}";
						
						break;
						
					case 'farm':
						
						$DBFarm = DBFarm::LoadByID($ObjectID);
						$role = 'FARM';
						
						break;
				}
			}
			catch(Exception $e)
			{
				throw new Exception("Object #{$ObjectID} not found in database");
			}
			
			if ($DBFarm->ClientID != $this->Client->ID)
				throw new Exception("Object #{$ObjectID} not found in database");
				
			$response = $this->CreateInitialResponse();
				
			if (CONFIG::$MONITORING_TYPE == MONITORING_TYPE::REMOTE)
			{
				$_REQUEST['role_name'] = $_REQUEST['role'];

				$data = array(
					'task'			=> 'get_stats_image_url',
					'farmid'		=> $DBFarm->ID,
					'watchername'	=> "{$WatcherName}SNMP",
					'graph_type'	=> $GraphType,
					'role_name'		=> $role
				);
			
				$content = @file_get_contents(CONFIG::$MONITORING_SERVER_URL."/server/statistics.php?".http_build_query($data));
				$r = @json_decode($content);
				if ($r->type == 'ok')
					$response->GraphURL = $r->msg;
				else
				{
					if ($r->msg)
						throw new Exception($r->msg);
					else
						throw new Exception("Internal API error");
				}
			}
			else
			{
				//TODO:
				throw new Exception("This API method not implemented for Local monitoring type");
			}
			
			return $response;
		}
	}
?>