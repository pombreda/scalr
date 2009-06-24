<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		if ($_SESSION['uid'] == 0)
	        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
	    else 
	        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));
        
		if (!$farminfo)
	        throw new Exception(_("Farm not found in database"));
	        
	    
		$display["farminfo"] = $farminfo;
		$display["title"] = sprintf(_("Events for farm %s"), $farminfo['name']);
		
		$sql = "SELECT * from events WHERE farmid='{$farminfo['id']}'";
	
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("message", "type", "dtadded") as $field)
			{
				$likes[] = "$field LIKE '%{$filter}%'";
			}
			$sql .= !stristr($sql, "WHERE") ? " WHERE " : " AND (";
			$sql .= join(" OR ", $likes);
			$sql .= ")";
		}
		
		$response["total"] = $db->GetOne(preg_replace('/\*/', 'COUNT(*)', $sql, 1));

		$sort = $req_sort ? mysql_escape_string($req_sort) : "id";
		$dir = $req_dir ? mysql_escape_string($req_dir) : "DESC";
		$sql .= " ORDER BY $sort $dir";
		
		
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		$sql .= " LIMIT $start, $limit";
		
		$response['success'] = '';
		$response['error'] = '';
		
		foreach ($db->GetAll($sql) as $row)
		{			
			$row['message'] = nl2br($row['message']);	
			$response["data"][] = $row;
		}	
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>