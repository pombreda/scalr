<?php

	$response = array();	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");	
				
		$vhost = null;
		
		$sql = "SELECT * FROM `apache_vhosts` WHERE `client_id` = {$_SESSION['uid']}";
		
		if ($req_farm_id)
		{
			$farm_id = (int)$req_farm_id;
			$sql .= " AND `farm_id` = '{$farm_id}'";
		}
		
		$vhostInfo = $db->GetAll($sql);
		$response['data'] = array();
		
		foreach ($vhostInfo as $row)
		{
				$DBFarmRole = DBFarmRole::LoadByID($row['farm_roleid']);				
			
				$vhost['id'] 				= $row['id'];
				$vhost['domain_name'] 		= $row['name'];
				
				$vhost['farmid']			= $DBFarmRole->FarmID;
				$vhost['farm_name'] 		= $DBFarmRole->GetFarmObject()->Name;

				$vhost['farm_roleid']		= $row['farm_roleid'];
				$vhost['role_name'] 		= $DBFarmRole->GetRoleName();
				
				$vhost['isSslEnabled'] 		= $row['is_ssl_enabled'];
				$vhost['last_modified'] 	= $row['last_modified'];
				
				$response['data'][] = $vhost;
		}		
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);