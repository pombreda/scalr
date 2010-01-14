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
		
		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($_SESSION['aws_region'])); 
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		
		// Rows
		$aws_response = $AmazonEC2Client->DescribeAddresses();
				
		$rowz = $aws_response->addressesSet->item;
			
		if ($rowz instanceof stdClass)
			$rowz = array($rowz);
		
		foreach ($rowz as $pk=>$pv)
		{
			$doadd = true;
			$item = $pv;
			$info = $db->GetRow("SELECT * FROM elastic_ips WHERE ipaddress=?", array($pv->publicIp));
			if ($info)
			{
				$item->dbInfo = $info;
				if ($item->dbInfo['farm_roleid'])
				{
					try
					{
						$DBFarmRole = DBFarmRole::LoadByID($item->dbInfo['farm_roleid']);
						$item->dbInfo['role_name'] = $DBFarmRole->GetRoleName();
					}
					catch(Exception $e){}
				}
								
				$item->farmId = $info['farmid'];
			}
			else
			{
				$dbinstance = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($item->instanceId));
				if ($dbinstance)
				{
					$item->dbInstance = $dbinstance;
					
					try
					{
						$DBFarmRole = DBFarmRole::LoadByID($item->dbInstance['farm_roleid']);
						$item->dbInstance['role_name'] = $DBFarmRole->GetRoleName();
					}
					catch(Exception $e){}
					
					$item->farmId = $dbinstance['farmid'];
				}
			}
			
			if ($item->farmId)
				$item->farmName = $db->GetOne("SELECT name FROM farms WHERE id=?", array($item->farmId));
			
			// Filter by farm id
			if ($req_farmid)
			{
				if ($item->farmId != $req_farmid)
					$doadd = false;
			}
			
			// Filter by role
			if ($req_role)
			{
				if ($item->dbInstance['role_name'] != $req_role && $item->dbInfo['role_name'])
					$doadd = false;
			}
			
			if ($doadd)
				$rowz1[] = $item;
		}
		
		$response["total"] = count($rowz1);
		
		$start = $req_start ? (int) $req_start : 0;
		$limit = $req_limit ? (int) $req_limit : 20;
		
		$rowz = (count($rowz1) > $limit) ? array_slice($rowz1, $start, $limit) : $rowz1;
		
		$response["data"] = array();
		
		
		// Rows
		foreach ($rowz as $r)
		{
		    $response["data"][] = array(
		    	'ipaddress' => $r->publicIp,
		    	'instance_id' => $r->instanceId, 
		    	'farmid' => $r->farmId, 
		    	'farm_name' => $r->farmName, 
		    	'role_name' => ($r->dbInstance && $r->dbInstance['role_name']) ? $r->dbInstance['role_name'] : $r->dbInfo['role_name'],
		    	'indb' => ($r->dbInfo) ? true : false
		    );
		}
	}
	catch(Exception $e)
	{
		$response = array("error" => $e->getMessage(), "data" => array());
	}
	
	print json_encode($response);
?>