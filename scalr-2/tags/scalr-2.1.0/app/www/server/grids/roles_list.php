<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
		
		if (Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
		   $sql = "SELECT id from roles WHERE 1=1";
		else
		   $sql = "SELECT id from roles WHERE (env_id='".Scalr_Session::getInstance()->getEnvironmentId()."' OR (origin='".ROLE_TYPE::SHARED."' AND env_id = '0') OR (origin='".ROLE_TYPE::SHARED."' AND env_id != '0' AND approval_state='".APPROVAL_STATE::APPROVED."'))";
		   
		if ($req_clientid)
		{
		    $id = (int)$req_clientid;
		    $sql .= " AND client_id='{$id}'";
		}

		if ($req_cloud_location)
		{
			$sql .= " AND id IN (SELECT role_id FROM role_images WHERE cloud_location={$db->qstr($req_cloud_location)})";
		}
		
		if ($req_id)
		{
		    $id = (int)$req_id;
		    $sql .= " AND id='{$id}'";
		}
		
		if ($req_type)
		{
			$type = preg_replace("/[^A-Za-z]+/", "", $req_type);
		    $sql .= " AND origin='{$type}'";
		}
			
		if ($req_origin)
		{
			if ($req_origin == SCRIPT_ORIGIN_TYPE::CUSTOM)
				$sql .= " AND origin = '".ROLE_TYPE::CUSTOM."'";
			elseif ($req_origin == SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED)
				$sql .= " AND (origin = '".ROLE_TYPE::SHARED."' AND env_id != '0')";
			elseif ($req_origin == SCRIPT_ORIGIN_TYPE::SHARED)
				$sql .= " AND (origin = '".ROLE_TYPE::SHARED."' AND env_id = '0')";
		}
		
		if ($req_approval_state && $req_origin != SCRIPT_ORIGIN_TYPE::SHARED)
		{
			$state = preg_replace("/[^A-Za-z]+/", "", $req_approval_state);
			$sql .= " AND approval_state = '{$state}'";
			$sql .= " AND env_id != '0'";
		}
		elseif ($req_type == ROLE_TYPE::SHARED)
			$sql .= " AND env_id = '0'";
	
			
	    if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("name", "description") as $field)
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
		
		//
		// Rows
		//
		foreach ($db->GetAll($sql) as $row)
		{	
			$dbRole = DBRole::loadById($row['id']);
			
			$platforms = array();
			foreach ($dbRole->getPlatforms() as $platform)
				$platforms[] = SERVER_PLATFORMS::GetName($platform);
			
			$role = array(
				'name'			=> $dbRole->name,
				'behaviors'		=> implode(", ", $dbRole->getBehaviors()),
				'id'			=> $dbRole->id,
				'architecture'	=> $dbRole->architecture,
				'client_id'		=> $dbRole->clientId,
				'env_id'		=> $dbRole->envId,
				'origin'		=> $dbRole->origin,
				'approval_state'=> $dbRole->approvalState,
				'os'			=> $dbRole->os,
				'platforms'		=> implode(", ", $platforms),
				'generation'	=> ($dbRole->generation == 2) ? 'scalarizer' : 'ami-scripts' 
			);
			
			if ($dbRole->clientId == 0)
				$role["client_name"] = "Scalr";
			else
				$role["client_name"] = $db->GetOne("SELECT fullname FROM clients WHERE id='{$dbRole->clientId}'");
				
			if (!$role["client_name"])
				$role["client_name"] = "";
			
			$response["data"][] = $role;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>