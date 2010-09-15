<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		$sql = "SELECT clientid,id,name,status,region,dtadded FROM farms WHERE 1=1";
		
		if ($_SESSION["uid"] != 0)
		   $sql .= " AND clientid='{$_SESSION['uid']}'";
	
		if ($req_farmid || $req_id)
		{
		    $id = ($req_farmid) ? (int)$req_farmid : (int)$req_id;
		    $sql .= " AND id='{$id}'";
		}
	
		if ($req_clientid)
		{
		    $id = (int)$req_clientid;
		    $sql .= " AND clientid='{$id}'";
		}
		
		if (isset($req_status))
		{
		    $status = (int)$req_status;
		    $sql .= " AND status='{$status}'";
		}
		
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("name", "id") as $field)
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
		
		foreach ($db->GetAll($sql) as $row)
		{
			$row["servers"] = $db->GetOne("SELECT COUNT(*) FROM servers WHERE farm_id='{$row['id']}' AND status != '".SERVER_STATUS::TERMINATED."'");
			$row["roles"] = $db->GetOne("SELECT COUNT(*) FROM farm_roles WHERE farmid='{$row['id']}'");
			$row["zones"] = $db->GetOne("SELECT COUNT(*) FROM dns_zones WHERE farm_id='{$row['id']}'");
			
			$row['dtadded'] = date("M j, Y H:i:s", strtotime($row["dtadded"]));
			
			if ($_SESSION['uid'] == 0)
				$row['client_email'] = $db->GetOne("SELECT email FROM clients WHERE id='{$row['clientid']}'"); 
				
			$row["havemysqlrole"] = (bool)$db->GetOne("SELECT id FROM farm_roles WHERE role_id IN (SELECT id FROM roles WHERE alias='mysql') AND farmid='{$row['id']}'");
			
			$row['status_txt'] = FARM_STATUS::GetStatusName($row['status']);
			
			if ($row['status'] == FARM_STATUS::RUNNING)
			{
				$row['shortcuts'] = $db->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? AND (farm_roleid IS NULL OR farm_roleid='0') AND ismenuitem='1'",
					array($row['id'])
				);
				foreach ($row['shortcuts'] as &$shortcut)
					$shortcut['name'] = $db->GetOne("SELECT name FROM scripts WHERE id=?", array($shortcut['scriptid']));
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