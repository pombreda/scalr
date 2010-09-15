<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
		
		if ($_SESSION["uid"] != 0)
			$auth_sql = " AND (SELECT clientid FROM farms WHERE id = scripting_log.farmid) = '{$_SESSION["uid"]}'";
		
		$sql = "SELECT * from scripting_log WHERE id > 0 {$auth_sql}";
	
		if ($req_farmid)
		{
			$farmid = (int)$_REQUEST["farmid"];
			$sql  .= " AND farmid = '{$farmid}'";
		}
		
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("message", "server_id") as $field)
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
		$response['data'] = array();
		
		$rows = $db->Execute($sql);
		
		while ($row = $rows->FetchRow())
		{        
	   	 	$row["farm_name"] = $db->GetOne("SELECT name FROM farms WHERE id=?", array($row['farmid']));
	   	 	$row["dtadded"] = date("M j, Y H:i:s", strtotime($row["dtadded"]." ".SCALR_SERVER_TZ));
			$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>