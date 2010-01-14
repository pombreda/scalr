<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
		
		if ($_SESSION['uid'] == 0)
		   $sql = "SELECT * from roles WHERE 1=1";
		else
		   $sql = "SELECT * from roles WHERE (clientid='{$_SESSION['uid']}' OR (roletype='".ROLE_TYPE::SHARED."' AND clientid = '0') OR (roletype='".ROLE_TYPE::SHARED."' AND clientid != '0' AND approval_state='".APPROVAL_STATE::APPROVED."'))";
			
		//Region filter
		$sql .= " AND region='".$_SESSION['aws_region']."'";
		   
		if ($req_clientid)
		{
		    $id = (int)$req_clientid;
		    $sql .= " AND clientid='{$id}'";
		}
		   
		if ($req_type)
		{
			$type = preg_replace("/[^A-Za-z]+/", "", $req_type);
		    $sql .= " AND roletype='{$type}'";
		}
			
		if ($req_origin)
		{
			if ($req_origin == SCRIPT_ORIGIN_TYPE::CUSTOM)
				$sql .= " AND roletype = '".ROLE_TYPE::CUSTOM."'";
			elseif ($req_origin == SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED)
				$sql .= " AND (roletype = '".ROLE_TYPE::SHARED."' AND clientid != '0')";
			elseif ($req_origin == SCRIPT_ORIGIN_TYPE::SHARED)
				$sql .= " AND (roletype = '".ROLE_TYPE::SHARED."' AND clientid = '0')";
		}
		
		if ($req_approval_state && $req_origin != SCRIPT_ORIGIN_TYPE::SHARED)
		{
			$state = preg_replace("/[^A-Za-z]+/", "", $req_approval_state);
			$sql .= " AND approval_state = '{$state}'";
			$sql .= " AND clientid != '0'";
		}
		elseif ($req_type == ROLE_TYPE::SHARED)
			$sql .= " AND clientid = '0'";
	
			
	    if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("name", "comments", "description", "ami_id") as $field)
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
			if ($row['ami_id'] && $row['roletype'] != ROLE_TYPE::SHARED)
				$row["isreplaced"] = $db->GetOne("SELECT id FROM roles WHERE `replace`='{$row['ami_id']}'");
			
			if ($row['clientid'] == 0)
				$row["client_name"] = "Scalr";
			else
				$row["client_name"] = $db->GetOne("SELECT fullname FROM clients WHERE id='{$row['clientid']}'");
				
			if (!$row["client_name"])
				$row["client_name"] = "";
			
			if ($row["isreplaced"])
				$infrole = $db->GetRow("SELECT * FROM roles WHERE `replace`='{$row['ami_id']}'");
			else
				$infrole = $row;
						
			$time = strtotime($row['dtbuilt']);
			
			if ($time)
				$row['dtbuilt'] = date("M j, Y", $time);
				
			if ($infrole["replace"] != '' && $infrole["iscompleted"] != 2)
				$row["abort_id"] = $infrole['id'];
				
			$row['type'] = ROLE_ALIAS::GetTypeByAlias($row['alias']);
				
			$row['id'] = ($row['isreplaced']) ? $row['isreplaced'] : $row['id'];
			
			if ($row["replace"] == "" || $db->GetOne("SELECT roletype FROM roles WHERE ami_id='{$row['replace']}'") == ROLE_TYPE::SHARED)
	    	   $display["rows"][] = $row;
			
			$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>