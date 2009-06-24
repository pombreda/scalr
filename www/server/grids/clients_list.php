<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		if ($_SESSION["uid"] != 0)
		   throw new Exception(_("Requested page cannot be viewed from the client account"));
		
		$sql = "SELECT id,email,aws_accountid,isactive,farms_limit,fullname,comments  FROM clients WHERE id > 0";
		
		//
		// If specified user id
		//
		if ($get_clientid)
		{
			$clientid = (int)$get_clientid;
			$sql .= " AND id='{$clientid}'";
		}
	
		if (isset($req_isactive))
		{
			$isactive = (int)$req_isactive;
			$sql .= " AND isactive='{$isactive}'";
		}
		
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("email", "aws_accountid", "fullname") as $field)
			{
				$likes[] = "$field LIKE '%{$filter}%'";
			}
			$sql .= !stristr($sql, "WHERE") ? " WHERE " : " AND (";
			$sql .= join(" OR ", $likes);
			$sql .= ")";
		}
		
		$sort = $req_sort ? mysql_escape_string($req_sort) : "email";
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
			$row["farms"] = $db->GetOne("SELECT COUNT(*) FROM farms WHERE clientid='{$row['id']}'");
			$row["apps"] = $db->GetOne("SELECT COUNT(*) FROM zones WHERE clientid='{$row['id']}'");
			$row["roles"] = $db->GetOne("SELECT COUNT(*) FROM ami_roles WHERE clientid='{$row['id']}'");
						
			$response["data"][] = $row;
		}
	
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>