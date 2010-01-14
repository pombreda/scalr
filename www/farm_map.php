<?
    require("src/prepend.inc.php"); 
    require_once(SRCPATH."/types/class.EnumFactory.php");
    
    if ($_SESSION['uid'] == 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_id));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_id, $_SESSION['uid']));

    if (!$farminfo || $post_cancel)
        UI::Redirect("farms_view.php");
    
	$display["title"] = _("Farm&nbsp;&raquo;&nbsp;Map");
	$display["farminfo"] = $farminfo;
	
	Core::Load("NET/SNMP");
	$SNMP = new SNMP();
	
	$ReflectState = new ReflectionClass("INSTANCE_STATE"); 
	
	$display["roles"] = $db->GetAll("SELECT farm_roles.*, farm_roles.id as id, roles.name, roles.alias, roles.alias as icon, roles.architecture FROM farm_roles INNER JOIN roles ON roles.ami_id = farm_roles.ami_id WHERE farmid=?", array($farminfo['id']));
	foreach ($display["roles"] as &$role)
	{
		// Default icon for nonexistent aias
		try {
			EnumFactory::GetValue("ROLE_ALIAS", strtoupper($role["alias"]));
		} catch (Exception $e){
			 $role["icon"] = "default";
		}
	
		$DBFarmRole = DBFarmRole::LoadByID($role['id']);
		
		$role["instances"] = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND ami_id=? AND state != ?",
			array($farminfo['id'], $role['ami_id'], INSTANCE_STATE::TERMINATED)
		);
		
		$role["empty_instances"] = array_fill(0, $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES)-count($role["instances"]), "empty");
		
		$role_LA = 0;
		
		foreach ($role["instances"] as &$instance)
		{			
			$instance['canusecustomEIPs'] = ($role['use_elastic_ips']) ? false : true;
		    
		    if ($instance['external_ip'])
		    {
	            $community = $db->GetOne("SELECT hash FROM farms WHERE id=(SELECT farmid FROM farm_instances WHERE instance_id='{$instance['instance_id']}')");
	            
	            $SNMP->Connect($instance['external_ip'], null, $community, null, null, true);
	            $res = $SNMP->Get(".1.3.6.1.4.1.2021.10.1.3.3");
	            if (!$res)
	                $instance['LA'] = false;
	            else
	            { 
	                $instance['LA'] = number_format((float)$res, 2);
	                $role_LA += (float)$res;
	            }
		    }
		    
		    if ($role['alias'] == ROLE_ALIAS::MYSQL)
		    	$instance['mysql_type'] = $instance['isdbmaster'] ? 'Master' : 'Slave';
		    
		    $instance['alias'] = $role['alias'];
		    	
		    foreach ($ReflectState->getConstants() as $name => $value)
		    {
		    	if ($value == $instance['state'])
		    	{
		    		$instance['state_image'] = $name;
		    		break;
		    	}
		    }
		    		    
		    $instance['issync'] = $db->GetOne("SELECT name FROM roles WHERE prototype_iid=? AND iscompleted='0'", array($instance['instance_id']));
		}
		
		if (count($role["instances"]) > 0)
			$role_LA = round($role_LA/count($role["instances"]), 2);
		else
			$role_LA = 0;
			
		$role['min_LA'] = $DBFarmRole->GetSetting(LAScalingAlgo::PROPERTY_MIN_LA);
		$role['max_LA'] = $DBFarmRole->GetSetting(LAScalingAlgo::PROPERTY_MAX_LA);  
			
		if ($role['max_LA'] != 0)
			$LA_percent = $role_LA/(float)$role['max_LA']*100;
		else
		{
			$LA_percent = $role_LA/99999*100;
			$role['min_LA'] = 'X';
			$role['max_LA'] = 'X';
		}
		
		if ($LA_percent < 50)
			$role['la_bar']['color'] = 'b';
		elseif ($LA_percent >= 50 && $LA_percent < 75)
			$role['la_bar']['color'] = 'y';
		else
			$role['la_bar']['color'] = 'r';
			
		$role['la_bar']['width'] = 48/100*$LA_percent;
		$role["LA"] = $role_LA;
	}
	
	require_once("src/append.inc.php");
?>