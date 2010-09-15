<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		if ($_SESSION["uid"] == 0)
		   throw new Exception(_("Requested page cannot be viewed from the admin account"));
		
		$sql = "SELECT * FROM bundle_tasks WHERE client_id = '{$_SESSION["uid"]}'";
		
		//
		// If specified user id
		//
		
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("server_id", "rolename", "failure_reason", "snapshot_id", "id") as $field)
			{
				$likes[] = "$field LIKE '%{$filter}%'";
			}
			$sql .= !stristr($sql, "WHERE") ? " WHERE " : " AND (";
			$sql .= join(" OR ", $likes);
			$sql .= ")";
		}
		
		$sort = $req_sort ? mysql_escape_string($req_sort) : "id";
		$dir = $req_dir ? mysql_escape_string($req_dir) : "DESC";
		$sql .= " ORDER BY $sort $dir";
			
		$response["total"] = $db->Execute($sql)->RecordCount();
		
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		$sql .= " LIMIT $start, $limit";
		
		$response["data"] = array();
	
		//
		// Rows
		//
		foreach ($db->GetAll($sql) as $row)
		{	
			$row['server_exists'] = DBServer::IsExists($row['server_id']);
			$response["data"][] = $row;
		}
	
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>