<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		$DBFarm = DBFarm::LoadByID($req_farmid);
		if ($DBFarm->ClientID != $_SESSION['uid'] && $_SESSION['uid'] != 0)
			throw new Exception("Farm not found");
			
		$DBInstance = DBInstance::LoadByIID($req_iid);
		if ($DBInstance->FarmID != $DBFarm->ID)
			throw new Exception("Farm not found");
		
		$sql = "SELECT * FROM messages WHERE instance_id='{$DBInstance->InstanceID}'";
			
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("instance_id", "message", "messageid") as $field)
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
		    preg_match("/^<\?xml [^>]+>[^<]+<([A-Za-z0-9]+)/si", $row['message'], $matches);
			$row['message_type'] = $matches[1];
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