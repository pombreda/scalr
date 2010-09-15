<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
		
		$sql = "SELECT * FROM ebs_array_snaps WHERE clientid='{$_SESSION['uid']}'";
		   	
		if ($req_array_id)
		{
			$array_id = (int)$req_array_id;
			$sql .= " AND ebs_arrayid='{$array_id}'";
		}
		
	    if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("description", "status") as $field)
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
		
		//
		// Rows
		//
		foreach ($db->GetAll($sql) as $row)
		{		
			$time = strtotime($row['dtcreated']);
			
			if ($time)
				$row['dtcreated'] = date("M j, Y", $time);
			
			$response["data"][] = $row;
		}
	
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>