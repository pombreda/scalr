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
		
		$sql = "SELECT 
			id, 
			email,
			aws_accountid,
			isactive,
			dtadded,
			farms_limit,
			fullname, 
			TO_DAYS(NOW())-TO_DAYS(dtdue) as overdue_days, 
			dtdue, 
			comments
			FROM clients WHERE id > 0";
		
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
		
		if ($req_overdue)
		{
			$sql .= " AND (TO_DAYS(dtdue) < TO_DAYS(NOW()) AND isactive='1')";
		}
		
		if ($req_cancelled)
		{
			$sql .= " AND ((SELECT COUNT(*) FROM subscriptions WHERE status = 'Active' AND subscriptions.clientid = clients.id) = 0 AND isactive='0')";
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
			$row["roles"] = $db->GetOne("SELECT COUNT(*) FROM roles WHERE clientid='{$row['id']}'");
			$row["servers"] = $db->GetOne("SELECT COUNT(*) FROM servers WHERE client_id='{$row['id']}'");
			
			
			$pkg = $db->GetOne("SELECT value FROM client_settings WHERE clientid=? AND `key`=?", array(
				$row['id'], CLIENT_SETTINGS::BILLING_CGF_PKG
			));
			if ($pkg)
			{
				$row['billing_type'] = ucfirst($pkg)." (Chargify)";
			}
			else
			{
				$row['billing_type'] = $db->GetOne("SELECT name FROM billing_packages WHERE id=(SELECT value FROM client_settings WHERE clientid='{$row['id']}' AND `key`='billing.packageid')");
				if ($row['billing_type'])
					$row['billing_type'] = "{$row['billing_type']} (PayPal)";
				else
					$row['billing_type'] = "";
			}
			
			if ($row['dtdue'])
				$row['dtdue'] = date("d-m-Y", strtotime($row['dtdue']));
			else
				$row['dtdue'] = '';
			
			$response["data"][] = $row;
		}
	
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>