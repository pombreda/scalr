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
		
		$Client = Client::Load($_SESSION['uid']);
		
		$sql = "SELECT * from ebs_arrays WHERE clientid='{$_SESSION['uid']}'";
	
		if ($req_id)
		{
			$id = (int)$req_id;
			$sql .= " AND id='{$id}'";
		}
		
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("name") as $field)
			{
				$likes[] = "$field LIKE '%{$filter}%'";
			}
			$sql .= !stristr($sql, "WHERE") ? " WHERE " : " AND (";
			$sql .= join(" OR ", $likes);
			$sql .= ")";
		}
		
		$sort = $req_sort ? mysql_escape_string($req_sort) : "name";
		$dir = $req_dir ? mysql_escape_string($req_dir) : "ASC";
		$sql .= " ORDER BY $sort $dir";
			
		$response["total"] = $db->GetOne(preg_replace('/\*/', 'COUNT(*)', $sql, 1));
		
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		$sql .= " LIMIT $start, $limit";
		
		$response["data"] = array();
	
		//
		// Rows
		//
		foreach ($db->GetAll($sql) as $row)
		{

			$row['autosnapshoting'] = $db->GetOne("SELECT id FROM autosnap_settings WHERE objectid=? AND objectType=?", array($row['id'],AUTOSNAPSHOT_TYPE::EBSArraySnap));
			try
			{
				if ($row['farm_roleid'])
				{
					$DBFarmRole = DBFarmRole::LoadByID($row['farm_roleid']);
					$row['role_name'] = $DBFarmRole->GetRoleName();
				}
			}
			catch(Exception $e) {}
			 
			$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>