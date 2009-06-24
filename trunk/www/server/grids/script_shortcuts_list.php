<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
		
		$sql = "select farm_role_scripts.*, scripts.name as scriptname from farm_role_scripts 
	    INNER JOIN scripts ON scripts.id = farm_role_scripts.scriptid
	    WHERE ismenuitem='1' AND farmid IN (SELECT id FROM farms WHERE clientid='{$_SESSION['uid']}')";
	    
	    if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("name", "description") as $field)
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
		$sql .= " LIMIT $start, $limit";
		
		$response["data"] = array();
		
		//
		// Rows
		//
		foreach ($db->GetAll($sql) as $row)
		{
			$row['farmname'] = $db->GetOne("SELECT name FROM farms WHERE id=?", array($row['farmid']));
			if ($row['ami_id'])
				$row['rolename'] = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($row['ami_id']));
				
			$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>