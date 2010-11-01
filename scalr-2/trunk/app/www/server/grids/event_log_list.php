<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		if (Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_ADMIN))
		{
			$farms = $db->GetAll("SELECT id FROM farms WHERE env_id=?", array(Scalr_Session::getInstance()->getEnvironmentId()));
			$frms = array();
			foreach ($farms as $f)
				array_push($frms, $f['id']);	
				
			$frms = implode(',', array_values($frms));
			
			if ($frms == '')
				$frms = '0';
			
			$auth_sql = " AND farmid IN ({$frms}) AND farmid > 0";
		}
		
		$sql = "SELECT * from logentries WHERE id > 0 {$auth_sql}";
	
		if ($req_iid)
		{
			$iid = preg_replace("/[^A-Za-z0-9-]+/si", "", $req_iid);
			$sql  .= " AND serverid = '{$iid}'";
		}
		
		if ($req_farmid)
		{
			$farmid = (int)$_REQUEST["farmid"];
			$sql  .= " AND farmid = '{$farmid}'";
		}
			
		if ($req_severity)
		{		
			$severities = implode(",", array_values($req_severity));
			$sql  .= " AND severity IN ($severities)";
		}
		else
		{
			$display["checked_severities"] = array(1 => false, 2 => true, 3 => true, 4 => true, 5 => true);
			$sql  .= " AND severity IN (2,3,4,5)";
		}
		
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("message","serverid","source") as $field)
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
		
		$response["total"] = $db->GetOne(str_replace("*", 'COUNT(*)', $sql));
		
		if($req_action != "download")
		{
			$start = $req_start ? (int) $req_start : 0;
			$limit = $req_limit ? (int) $req_limit : 20;
			$sql .= " LIMIT $start, $limit";
		}
		$response['success'] = '';
		$response['error'] = '';
		
		$severities = array(1 => "Debug", 2 => "Info", 3 => "Warning", 4 => "Error", 5 => "Fatal");
		
		$response["data"] = array();
		
		foreach ($db->GetAll($sql) as $row)
		{
			$row["time"] = date("M j, Y H:i:s", $row["time"]);
					
			$row["servername"] = $row["serverid"];
			$row["s_severity"] = $severities[$row["severity"]];
			$row["severity"] = (int)$row["severity"];
			
			if (!$farm_names[$row['farmid']])
				$farm_names[$row['farmid']] = $db->GetOne("SELECT name FROM farms WHERE id=?", array($row['farmid']));
			
			$row['farm_name'] = $farm_names[$row['farmid']];
			
			$row['message'] = nl2br(htmlspecialchars($row['message']));
			
			$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	
	if ($req_showLog)
		print json_encode($response);	
	else
		return $response;
	
?>