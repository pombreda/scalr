<?php
	$response = array();
	
	// AJAX_REQUEST;
	$context = 6;
	
	try
	{
		$enable_json = true;
		include("../../src/prepend.inc.php");
	
		$sql = "SELECT 
			id, 
			name,
			dt_added AS dtAdded,
			is_system AS isSystem
			FROM client_environments
			WHERE client_id = ?";

		$rows = $db->GetAll($sql, array(Scalr_Session::getInstance()->getClientId()));

		$response["data"] = $rows;
		$response["total"] = count($rows);
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}

	print json_encode($response);
?>