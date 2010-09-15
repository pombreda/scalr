<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		$sql = "SELECT * FROM servers WHERE 1=1";
		
		if ($_SESSION["uid"] != 0)
		   $sql .= " AND client_id='{$_SESSION['uid']}'";
	
		if ($req_farmid)
		{
		    $id = (int)$req_farmid;
		    $sql .= " AND farm_id='{$id}'";
		}
	
		if ($req_farm_roleid)
		{
		    $id = (int)$req_farm_roleid;
		    $sql .= " AND farm_roleid='{$id}'";
		}
		
		if ($req_role_id)
		{
		    $id = (int)$req_roleid;
		    $sql .= " AND role_id='{$id}'";
		}
		
		if ($req_clientid)
		{
		    $id = (int)$req_clientid;
		    $sql .= " AND client_id='{$id}'";
		}
		
		if ($req_server_id)
		{
		    $sql .= " AND server_id={$db->qstr($req_server_id)}";
		}
		
		if ($req_hide_terminated == 'true')
			$sql .= " AND status != '".SERVER_STATUS::TERMINATED."'";
		
		if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("server_id", "farm_id", "remote_ip", "local_ip", "status") as $field)
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

		if ($response['total'] && $start > $response['total'])
			$start = floor($response['total'] / $limit) * $limit;

		$sql .= " LIMIT $start, $limit";
		
		$response["data"] = array();
		
		foreach ($db->GetAll($sql) as $row)
		{			
			try
			{
				$DBServer = DBServer::LoadByID($row['server_id']);
				
				$row['cloud_server_id'] = $DBServer->GetCloudServerID();
				$row['ismaster'] = $DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER);
			}
			catch(Exception $e){  }
			
			$row['farm_name'] = $db->GetOne("SELECT name FROM farms WHERE id=?", array($row['farm_id']));
			$row['role_name'] = $db->GetOne("SELECT name FROM roles WHERE id=?", array($row['role_id']));
			$row['isrebooting'] = $db->GetOne("SELECT value FROM server_properties WHERE server_id=? AND `name`=?", array(
				$row['server_id'], SERVER_PROPERTIES::REBOOTING
			));
			
			// $tz was set in ../../src/prepend.inc.php and contain TZ of current client
			if ($tz)
			{
				date_default_timezone_set(SCALR_SERVER_TZ);
				$tm = strtotime($row['dtadded']);
				date_default_timezone_set($tz);
			}
			else
				$tm = strtotime($row['dtadded']);
			
			$i_dns = $db->GetOne("SELECT value FROM server_properties WHERE server_id=? AND `name`=?", array(
				$row['server_id'], SERVER_PROPERTIES::EXCLUDE_FROM_DNS
			));
			
			$r_dns = $db->GetOne("SELECT value FROM farm_role_settings WHERE farm_roleid=? AND `name`=?", array(
				$row['farm_roleid'], DBFarmRole::SETTING_EXCLUDE_FROM_DNS
			));
			
			$row['dns'] = (!$i_dns && !$r_dns) ? true : false;
				
			$row['uptime'] = Formater::Time2HumanReadable(time() - (int)$tm, false);
			
			$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>