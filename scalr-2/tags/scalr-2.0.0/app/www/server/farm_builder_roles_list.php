<?
    define("NO_TEMPLATES", true);
    
    try
    {
		require(dirname(__FILE__)."/../src/prepend.inc.php"); 
		
	    header('Pragma: private');
		header('Cache-control: private, must-revalidate');
	    
		header("Content-type: text/javascript");
		
		if ($req_farmid)
		{
		    $DBFarm = DBFarm::LoadByID($req_farmid);
		    if ($_SESSION["uid"] != 0 && $_SESSION['uid'] != $DBFarm->ClientID)
		    	throw new Exception("Farm not found");
		    
		    $region = $DBFarm->Region;
		}
		else
			$region = $req_region;
		
		$roles = array();
		    
	    $roles_sql = "SELECT id, roletype, ami_id, approval_state, name, clientid, platform, alias, architecture, description, isstable, region FROM roles WHERE 1=1";
		if ($_SESSION['uid'] != 0)
		{
			$roles_sql .= " AND (roletype = ? OR (roletype = ? AND clientid=?))";
			$args[] = ROLE_TYPE::SHARED;
			$args[] = ROLE_TYPE::CUSTOM;
			$args[] = $_SESSION['uid'];
		}
				
		$dbroles = $db->Execute($roles_sql, $args);
		while ($role = $dbroles->FetchRow())
		{
			if ($role['roletype'] == ROLE_TYPE::SHARED && $role['clientid'] != 0 && $_SESSION['uid'] != 0)
	        {
	        	if ($DBFarm && ($role['clientid'] != $DBFarm->ClientID  && $role['approval_state'] != APPROVAL_STATE::APPROVED))
	        		continue;
	        }
		    	
	        if (($role['name'] == 'mysql' || $role['name'] == 'mysql64'))
	        	continue;
		        	
	        if (in_array($role['platform'], array(SERVER_PLATFORMS::EC2, SERVER_PLATFORMS::RDS)))
	        {
	        	if ($region != $role['region'])
	        		continue;
	        }
	
	        if ($role['roletype'] == ROLE_TYPE::SHARED)
	        {
	        	if ($role['clientid'] == 0)
	        		$origin = 'Public roles';
	        	else
	        		$origin = 'Contributed roles';
	        }
	        else
	        	$origin = 'Private roles';
	        
	        $roles[] = array(
	        	'role_id'				=> $role['id'],
	        	'arch'					=> $role['architecture'],
	        	'group_description'		=> ROLE_ALIAS::GetTypeByAlias($role["alias"]),
	        	'name'					=> $role['name'],
	        	'alias'					=> $role['alias'],
	        	'description'			=> $role['description'],
	        	'image_id'				=> $role['ami_id'],
	        	'comments_count'		=> '0',
	        	'platform'				=> $role['platform'],
	        	'origin'				=> $origin,
	        	'isstable'				=> (bool)$role['isstable'],
	        	'dtbuilt'				=> $role['dtbuilt']
	        );
		} 
    }
    catch(Exception $e)
    {
    	var_dump($e->getMessage());
    }

    $result = json_encode($roles); 
    header("Content-length: ".strlen($result));
    print $result;
    exit();
?>