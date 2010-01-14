<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		if ($_SESSION["uid"] != 0)
	        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION["uid"]));
	    else 
	        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
	        
		if (!$farminfo)
			throw new Exception(_("Farm not found in database"));
		
		$sql = "SELECT * from farm_roles WHERE farmid='{$farminfo['id']}'";
			
		if ($get_ami_id)
		{
			$ami_id = $db->qstr($get_ami_id);
			$sql .= " AND ami_id={$ami_id}";
		}
		
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("ami_id") as $field)
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
			$row["name"] = $db->GetOne("SELECT name FROM roles WHERE ami_id='{$row['ami_id']}'");
			$row["sites"] = $db->GetOne("SELECT COUNT(*) FROM zones WHERE ami_id='{$row["ami_id"]}' AND status != ? AND farmid=?", array(ZONE_STATUS::DELETED, $farminfo['id']));
			$row["r_instances"] = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state=? AND farmid='{$row['farmid']}' AND ami_id='{$row['ami_id']}'", array(INSTANCE_STATE::RUNNING));
			$row["p_instances"] = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state IN (?,?) AND farmid='{$row['farmid']}' AND ami_id='{$row['ami_id']}'", array(INSTANCE_STATE::PENDING, INSTANCE_STATE::INIT));
			
			$DBFarmRole = DBFarmRole::LoadByID($row['id']);
			$row['min_count'] = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES);
			$row['max_count'] = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES);
			$row['avail_zone'] = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_AVAIL_ZONE);
			if (!$row['avail_zone'])
				$row['avail_zone'] = 'Choose randomly';
			
			$row['shortcuts'] = $db->GetAll("SELECT * FROM farm_role_scripts WHERE farm_roleid=? AND ismenuitem='1'",
				array($row['id'])
			);
			foreach ($row['shortcuts'] as &$shortcut)
				$shortcut['name'] = $db->GetOne("SELECT name FROM scripts WHERE id=?", array($shortcut['scriptid']));
			
			$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>