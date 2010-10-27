<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		$sql = "select * FROM dns_zones WHERE 1=1";
		if ($_SESSION["uid"] != 0)
			$sql .= " AND client_id='{$_SESSION['uid']}'";
		
		if ($req_client_id)
		{
		    $id = (int)$req_client_id;
		    $sql .= " AND client_id='{$id}'";
		}
		
		if ($req_farm_roleid)
		{
		    $farm_roleid = $db->qstr($req_farm_roleid);	    
		    $sql .= " AND farm_roleid={$farm_roleid}";
		}
		
		if ($req_farmid)
		{
		    $farmid = $db->qstr($req_farmid);	    
		    $sql .= " AND farm_id={$farmid}";
		}
		
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("zone_name", "id", "farm_id", "farm_roleid") as $field)
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
			
		$response["total"] = $db->Execute($sql)->RecordCount();
		
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;

		if ($response['total'] && $start > $response['total'])
			$start = floor($response['total'] / $limit) * $limit;

		$sql .= " LIMIT $start, $limit";
		
		$response["data"] = array();
		
		
		// Rows
		foreach ($db->GetAll($sql) as $row)
		{
		    if ($row['farm_roleid'])
		    {
		    	$DBFarmRole = DBFarmRole::LoadByID($row['farm_roleid']);
		    	
		    	$row['role_name'] = $DBFarmRole->GetRoleName();
		    	$row['farm_name'] = $DBFarmRole->GetFarmObject()->Name;
		    	$row['farm_id'] = $DBFarmRole->FarmID;
		    }
			elseif ($row['farm_id'])
			{
				$DBFarm = DBFarm::LoadByID($row['farm_id']);
				
				$row['farm_name'] = $DBFarm->Name;
		    	$row['farm_id'] = $DBFarm->ID;
			}
			
			$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>