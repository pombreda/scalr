<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		$DBFarm = DBFarm::LoadByID($req_farmid);
		
		if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($DBFarm->EnvID))
	    	throw new Exception(_("Farm not found in database"));
		
		$sql = "SELECT * from farm_roles WHERE farmid='{$DBFarm->ID}'";
			
		if ($req_role_id)
		{
			$role_id = $db->qstr($req_role_id);
			$sql .= " AND role_id={$role_id}";
		}
		
		if ($req_farm_roleid)
		{
			$farm_roleid = $db->qstr($req_farm_roleid);
			$sql .= " AND id={$farm_roleid}";
		}
		
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("ami_id", "platform") as $field)
			{
				$likes[] = "$field LIKE '%{$filter}%'";
			}
			$sql .= !stristr($sql, "WHERE") ? " WHERE " : " AND (";
			$sql .= join(" OR ", $likes);
			$sql .= ")";
		}
		
		$sort = $req_sort ? mysql_escape_string($req_sort) : "id";
		$dir = $req_dir ? mysql_escape_string($req_dir) : "ASC";
		$sql .= " ORDER BY $sort $dir";
			
		$response["total"] = $db->GetOne(preg_replace('/\*/', 'COUNT(*)', $sql, 1));
		
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		$sql .= " LIMIT $start, $limit";
		
		$response["data"] = array();
		
		foreach ($db->GetAll($sql) as $row)
		{	
			$row["servers"] = $db->GetOne("SELECT COUNT(*) FROM servers WHERE farm_roleid=?", array($row['id']));
			
			$row['farm_status'] = $db->GetOne("SELECT status FROM farms WHERE id=?", array($row['farmid']));
			
			$row["domains"] = $db->GetOne("SELECT COUNT(*) FROM dns_zones WHERE farm_roleid=? AND status != ? AND farm_id=?", 
				array($row["id"], DNS_ZONE_STATUS::PENDING_DELETE, $row['farmid'])
			);
			
			$DBFarmRole = DBFarmRole::LoadByID($row['id']);
			
			$row['min_count'] = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES);
			$row['max_count'] = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES);
			
			$row['location'] = $DBFarmRole->GetSetting(DBFarmRole::SETTING_CLOUD_LOCATION);
			
			$DBRole = DBRole::loadById($row['role_id']);
			$row["name"] = $DBRole->name;
			$row['image_id'] = $DBRole->getImageId(
				$DBFarmRole->Platform, 
				$DBFarmRole->GetSetting(DBFarmRole::SETTING_CLOUD_LOCATION)
			);
			
			$row['shortcuts'] = $db->GetAll("SELECT * FROM farm_role_scripts WHERE farm_roleid=? AND ismenuitem='1'",
				array($row['id'])
			);
			foreach ($row['shortcuts'] as &$shortcut)
				$shortcut['name'] = $db->GetOne("SELECT name FROM scripts WHERE id=?", array($shortcut['scriptid']));
			
				
			$scalingManager = new Scalr_Scaling_Manager($DBFarmRole);
			$scaling_algos = array();
        	foreach ($scalingManager->getFarmRoleMetrics() as $farmRoleMetric)
        		$scaling_algos[] = $farmRoleMetric->getMetric()->name;
				
        	if (count($scaling_algos) == 0)
        		$row['scaling_algos'] = _("Scaling disabled");
        	else
				$row['scaling_algos'] = implode(', ', $scaling_algos);
				
			$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>