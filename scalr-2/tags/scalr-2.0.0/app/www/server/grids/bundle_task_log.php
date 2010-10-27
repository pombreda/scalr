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
		
		$info = $db->GetOne("SELECT id FROM bundle_tasks WHERE id=? AND client_id=?", array($req_task_id, $_SESSION['uid']));
		if (!$info)
			throw new Exception(_("Task not found"));
		
		   
		$sql = "SELECT * FROM bundle_task_log WHERE bundle_task_id = '{$req_task_id}'";
		
		//
		// If specified user id
		//
		
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("message") as $field)
			{
				$likes[] = "$field LIKE '%{$filter}%'";
			}
			$sql .= !stristr($sql, "WHERE") ? " WHERE " : " AND (";
			$sql .= join(" OR ", $likes);
			$sql .= ")";
		}
		
		$sort = $req_sort ? mysql_escape_string($req_sort) : "dtadded";
		$dir = $req_dir ? mysql_escape_string($req_dir) : "DESC";
		$sql .= " ORDER BY $sort $dir, id DESC";
			
		$response["total"] = $db->Execute($sql)->RecordCount();
		
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 1000;
		$sql .= " LIMIT $start, $limit";
		
		$response["data"] = array();
	
		//
		// Rows
		//
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