<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		if ($_SESSION["uid"] == 0)
		   $sql = "select zone,id,ami_id,role_name,status,farmid FROM zones WHERE 1=1";
		else
		   $sql = "select zone,id,ami_id,role_name,status,farmid FROM zones WHERE clientid='{$_SESSION['uid']}'";
	
		if ($req_farmid)
		{
		    $id = (int)$req_farmid;
		    $sql .= " AND farmid='{$id}'";
		}
		
		if ($req_clientid)
		{
		    $id = (int)$req_clientid;
		    $sql .= " AND clientid='{$id}'";
		}
		
		if ($req_ami_id)
		{
		    $ami_id = $db->qstr($req_ami_id);	    
		    $sql .= " AND ami_id={$ami_id}";
		}
		
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("zone", "id", "ami_id", "role_name") as $field)
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
		
		
		// Rows
		foreach ($db->GetAll($sql) as $row)
		{
		    if ($row["ami_id"])
				$row["role"] = $db->GetRow("SELECT name,ami_id,alias FROM ami_roles WHERE ami_id=?", $row["ami_id"]);
				
		    $farm = $db->GetRow("SELECT name,id,clientid FROM farms WHERE id=?", $row["farmid"]);
		    $row["clientid"] = $farm['clientid'];
		    $row["farm_name"] = $farm['name'];
		    
		    $row['role_alias'] = $row["role"]['alias'];
		    
		    switch($row["status"])
		    {
		    	case ZONE_STATUS::ACTIVE:
		    		$row["string_status"] = "Active";
		    		break;
		    	case ZONE_STATUS::DELETED:
		    		$row["string_status"] = "Pending delete";
		    		break;
		    	case ZONE_STATUS::PENDING:
		    		$row["string_status"] = "Pending create";
		    		break;
		    	case ZONE_STATUS::INACTIVE:
		    		$row["string_status"] = "Inactive";
		    		break;
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