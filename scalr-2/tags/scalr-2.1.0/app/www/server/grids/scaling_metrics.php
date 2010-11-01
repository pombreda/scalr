<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		$sql = "select * FROM scaling_metrics WHERE 1=1";
		
		if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
			$sql .= " AND (env_id='".Scalr_Session::getInstance()->getEnvironmentId()."' OR env_id='0')";
		
		if ($req_client_id)
		{
		    $id = (int)$req_client_id;
		    $sql .= " AND client_id='{$id}'";
		}
		
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("name", "file_path") as $field)
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
			$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>