<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
		
		if (Scalr_Session::getInstance()->getClientId() != 0)
		{
			$filter_sql .= " AND ("; 
				// Show shared roles
				$filter_sql .= " origin='".SCRIPT_ORIGIN_TYPE::SHARED."'";
			
				// Show custom roles
				$filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::CUSTOM."' AND clientid='".Scalr_Session::getInstance()->getClientId()."')";
				
				//Show approved contributed roles
				$filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED."' AND (scripts.approval_state='".APPROVAL_STATE::APPROVED."' OR clientid='".Scalr_Session::getInstance()->getClientId()."'))";
			$filter_sql .= ")";
		}
		
	    $sql = "SELECT 
	    			scripts.id, 
	    			scripts.name, 
	    			scripts.description, 
	    			scripts.origin,
	    			scripts.clientid,
	    			scripts.approval_state,
	    			MAX(script_revisions.dtcreated) as dtupdated, MAX(script_revisions.revision) AS version FROM scripts 
	    		INNER JOIN script_revisions ON script_revisions.scriptid = scripts.id 
	    		WHERE 1=1 {$filter_sql}";
	    
	    if ($req_origin)
	    {
	    	$origin = preg_replace("/[^A-Za-z0-9-]+/", "", $req_origin);
	    	$sql .= " AND origin='{$origin}'";
	    }
	    
	    if ($req_approval_state)
	    {
	    	$approval_state = preg_replace("/[^A-Za-z0-9-]+/", "", $req_approval_state);
	    	$sql .= " AND scripts.approval_state='{$approval_state}'";
	    }
	        
	    if ($req_query)
		{
			$filter = mysql_escape_string($req_query);
			foreach(array("scripts.name", "scripts.description") as $field)
			{
				$likes[] = "$field LIKE '%{$filter}%'";
			}
			$sql .= !stristr($sql, "WHERE") ? " WHERE " : " AND (";
			$sql .= join(" OR ", $likes);
			$sql .= ")";
		}
		
		$sql .= " GROUP BY script_revisions.scriptid";
		
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
			if ($row['clientid'] != 0)
			{
				$client = $db->GetRow("SELECT email, fullname FROM clients WHERE id = ?", array($row['clientid']));
				
				if (Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
					$row["client_email"] = $client['email'];
					
				$row["client_name"] = $client['fullname'];
			}
			
			$row['dtupdated'] = date("M j, Y", strtotime($row["dtupdated"]));
			
			$response["data"][] = $row;
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>