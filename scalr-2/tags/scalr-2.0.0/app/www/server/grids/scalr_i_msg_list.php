<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");

		$DBServer = DBServer::LoadByID($req_server_id);
		if ($DBServer->clientId != $_SESSION['uid'] && $_SESSION['uid'] != 0)
			throw new Exception("Server not found");
		
		$sql = "SELECT * FROM messages WHERE server_id='{$DBServer->serverId}'";
			
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("server_id", "message", "messageid") as $field)
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
		
		
		// Rows
		foreach ($db->GetAll($sql) as $row)
		{		    
			preg_match("/^<\?xml [^>]+>[^<]*<message(.*?)name=\"([A-Za-z0-9_]+)\"/si", $row['message'], $matches);
			
			$row['message_type'] = $matches[2];
		    $row['message'] = '';
			$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>