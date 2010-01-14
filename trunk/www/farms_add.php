<? 
	require("src/prepend.inc.php"); 
    $display['load_extjs'] = true;
    
	set_time_limit(360);
		
	$req_farmid = ($req_farmid) ? $req_farmid : $req_id;
	
	if ($_SESSION['uid'] == 0)
    {
        if (!$req_farmid)   
            UI::Redirect("farms_view.php");
        else 
        {
            $uid = $db->GetOne("SELECT clientid FROM farms WHERE id='{$req_farmid}'");
        }
    }
    else 
    {
        $uid = $_SESSION['uid'];
    }
    
    $Client = Client::Load($uid);
    
    $used_slots = $db->GetOne("SELECT SUM(value) FROM farm_role_settings WHERE name=? 
        AND farm_roleid IN (SELECT id FROM farm_roles WHERE farmid IN (SELECT id FROM farms WHERE clientid=?))",
        array(DBFarmRole::SETTING_SCALING_MAX_INSTANCES, $Client->ID)
    );
    
    $client_max_instances = $Client->GetSettingValue(CLIENT_SETTINGS::MAX_INSTANCES_LIMIT);
    $i_limit = $client_max_instances ? $client_max_instances : CONFIG::$CLIENT_MAX_INSTANCES;
    
    $client_max_eips = $Client->GetSettingValue(CLIENT_SETTINGS::MAX_EIPS_LIMIT);
    $eips_limit = $client_max_eips ? $client_max_eips : CONFIG::$CLIENT_MAX_EIPS;
    
    $avail_slots = $i_limit - $used_slots;
    if ($avail_slots <= 5)
    	$display["warnmsg"] = sprintf(_("You have %s <a href='http://wiki.scalr.net/What_is.../Spare_instances'>spare instances</a> available on your account."), $avail_slots);
    
    if ($req_farmid)
	{
		$region = $db->GetOne("SELECT region FROM farms WHERE id=?", array($req_farmid));
		
		$display["title"] = _("Edit farm");
	}
    else
    {	
		$display["title"] = _("Farm builder");
    	
    	if (!$req_region)
	    {			
			$Smarty->assign($display);
			$Smarty->display("region_information_step.tpl");
			exit();
	    }
	    else
	    	$region = $req_region;
    }

    $display['region'] = $region;
    $_SESSION['farm_builder_region'] = $region;
        
    $Client = Client::Load($uid);    
	$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region)); 
	$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
                    
    // Get Avail zones
    $avail_zones_resp = $AmazonEC2Client->DescribeAvailabilityZones();
    $display["avail_zones"] = array();
    
    // Random assign zone
    array_push($display["avail_zones"], "");
    
    foreach ($avail_zones_resp->availabilityZoneInfo->item as $zone)
    {
    	if (stristr($zone->zoneState,'available'))
    		array_push($display["avail_zones"], (string)$zone->zoneName);
    }
	
    // Get EBS Snapshots list
    $response = $AmazonEC2Client->DescribeSnapshots();

	$rowz = $response->snapshotSet->item;
		
	if ($rowz instanceof stdClass)
		$rowz = array($rowz);
			
	foreach ($rowz as $pk=>$pv)
	{		
		if ($pv->status == 'completed')
			$display['snapshots'][(string)$pv->snapshotId] = array(
				"snapid" 	=> (string)$pv->snapshotId,
				"createdat"	=> date("M j, Y H:i:s", strtotime((string)$pv->startTime)),
				"size"		=> (string)$pv->volumeSize
			);
	}
	
	ksort($display['snapshots']);
	
	//Get List of registered Scaling Algorithms
	$display['scaling_algos'] = array();
	foreach (RoleScalingManager::$ScalingAlgos as $Algo)
	{
        $algo_name = strtolower(str_replace("ScalingAlgo", "", get_class($Algo)));

        if ($algo_name != 'base')
        {
	        $display['scaling_algos'][$algo_name] = array(
	        	'based_on'	=> $Algo->GetAlgoDescription(),
	        	'settings'	=> $Algo->GetConfigurationForm($display["farminfo"]["clientid"])
	        );
        }
	}
	
    if ($req_id)
    {
        try
        {
	    	$display["farminfo"] = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_id));
	        $DBFarm = DBFarm::LoadByID($req_id);
	        
	        if ($_SESSION['uid'] != 0 && $_SESSION['uid'] != $DBFarm->ClientID)
	            UI::Redirect("farms_view.php");
	        
	        if (!$display["farminfo"])
	        {
	            $errmsg = _("Farm not found");
	            UI::Redirect("farms_view.php");
	        }
        }
        catch(Exception $e)
        {
        	$errmsg = _("Farm not found");
	        UI::Redirect("farms_view.php");
        }
        
        $servers = $db->GetAll("SELECT * FROM farm_roles WHERE farmid=? ORDER BY launch_index ASC", array($DBFarm->ID));
        $display['roles'] = array();
        foreach ($servers as &$row)
        {
            $ami_info = $db->GetRow("SELECT * FROM roles WHERE ami_id=?", array($row['ami_id']));
            if (!$ami_info)
            	continue;
            
        	$row["role"] = $ami_info["name"];
        	
        	$scripts = $db->GetAll("SELECT * FROM farm_role_scripts WHERE farm_roleid=?", array($row['id']));
			$scripts_object = new stdClass();
			foreach ($scripts as $script)
			{
				if (substr($script['event_name'], 0, 11) != 'CustomEvent')
				{
					$scripts_object->{"{$script['event_name']}_{$script['scriptid']}"} = new stdClass();
					$scripts_object->{"{$script['event_name']}_{$script['scriptid']}"}->config = unserialize($script['params']);
					$scripts_object->{"{$script['event_name']}_{$script['scriptid']}"}->target = $script['target'];
					$scripts_object->{"{$script['event_name']}_{$script['scriptid']}"}->version = $script['version'];
					$scripts_object->{"{$script['event_name']}_{$script['scriptid']}"}->issync = $script['issync'];
					$scripts_object->{"{$script['event_name']}_{$script['scriptid']}"}->timeout = $script['timeout'];
					$scripts_object->{"{$script['event_name']}_{$script['scriptid']}"}->order_index = $script['order_index'];
				}
			}
        	
	        if ($ami_info['roletype'] == ROLE_TYPE::SHARED && $ami_info['clientid'] != 0)
	        {
	        	$author_info = $db->GetRow("SELECT fullname FROM clients WHERE id=?", array($ami_info['clientid']));
	        	$author = ($author_info['fullname']) ? $author_info['fullname'] : _('Scalr user');
	        }
	        else
	        	$author = false;
			
	        $DBFarmRole = DBFarmRole::LoadByID($row['id']);

	        $role_settings = $DBFarmRole->GetAllSettings();
	        
	        $role = array(
        		'name' 		=> $ami_info["name"],
        		'arch' 		=> $ami_info["architecture"],
        		'alias' 	=> $ami_info["alias"],
        		'ami_id'	=> $ami_info["ami_id"],
        		'type'		=> ROLE_ALIAS::GetTypeByAlias($ami_info["alias"]),
        		'description' => $ami_info["description"],
        		'scripts'	=> $scripts_object,
        		'author'	=> $author,
        		'settings'		=> $role_settings,
        		'launch_index'	=> (int)$row['launch_index'],
        		'options'	=> array(
        			'reboot_timeout'	=> $row['reboot_timeout'],
        			'launch_timeout'	=> $row['launch_timeout'],
        			'status_timeout'	=> $row['status_timeout'],
        	));
        	
        	$scaling_algo_props = array();
        	$RoleScalingManager = new RoleScalingManager($DBFarmRole);
        	foreach ($RoleScalingManager->GetRegisteredAlgos() as $Algo)
        	{
        		$scaling_algo_props = array_merge($scaling_algo_props, $Algo->GetProperties());
        		
        		$algo_name = strtolower(str_replace("ScalingAlgo", "", get_class($Algo)));
        		
        		if ($algo_name == 'base')
        			continue;
        		
        		$scaling_algo_props["scaling.{$algo_name}.enabled"] = $RoleScalingManager->IsAlgoEnabled($algo_name);
        		if ($algo_name == 'time' && $scaling_algo_props["scaling.time.enabled"] == 1)
        		{
        			$periods = $db->GetAll("SELECT * FROM farm_role_scaling_times WHERE farm_roleid=?", array($DBFarmRole->ID));
        			foreach ($periods as $period)
        			{
	        			$scaling_algo_props[TimeScalingAlgo::PROPERTY_TIME_PERIODS][] = implode(":", array(
							$period['start_time'],
							$period['end_time'],
							$period['days_of_week'],
							$period['instances_count']
	        			));
        			}
        		}
        	}
        	
        	$role['options']['scaling_algos'] = $scaling_algo_props;
        	
        	if ($ami_info["alias"] == ROLE_ALIAS::MYSQL)
        	{
        		$display['farm_mysql_role'] = $ami_info['ami_id'];
        		
        		$role['options']['mysql_bundle_every'] = $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BUNDLE_EVERY) ? $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BUNDLE_EVERY) : 48;
				$role['options']['mysql_make_backup_every'] = $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BCP_EVERY) ? $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BCP_EVERY) : 180;
				$role['options']['mysql_make_backup'] = ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BCP_ENABLED)) ? true : false;
				$role['options']['mysql_bundle'] = ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BUNDLE_ENABLED)) ? true : false;
				
				$role['options']['mysql_data_storage_engine'] = $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_DATA_STORAGE_ENGINE) ? $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_DATA_STORAGE_ENGINE) : 'lvm';
				$role['options']['mysql_ebs_size'] = $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_EBS_VOLUME_SIZE) ? $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_EBS_VOLUME_SIZE) : 100;
        	}
        	array_push($display['roles'], $role);
        }
            
        $display["id"] = $req_id;
    }
    
    //Defaults
    $display["default_scaling_algos"] = array();
    foreach (RoleScalingManager::$ScalingAlgos as $Algo)
    {
        $DataForm = $Algo->GetConfigurationForm();
        foreach ($DataForm->ListFields() as $field)
        {
        	if ($field->FieldType != FORM_FIELD_TYPE::MIN_MAX_SLIDER)
        		$display['default_scaling_algos'][$field->Name] = $field->DefaultValue;
        	else
        	{
        		$s = explode(",", $field->DefaultValue);
        		$display['default_scaling_algos'][$field->Name.".min"] = $s[0];
        		$display['default_scaling_algos'][$field->Name.".max"] = $s[1];
        	}
        }
        
        $algo_name = strtolower(str_replace("ScalingAlgo", "", get_class($Algo)));
        
        if ($algo_name == 'base')
        	continue;
        
        $display['default_scaling_algos']["scaling.{$algo_name}.enabled"] = 0;
	}
        
    $display['default_scaling_algos'] = json_encode($display['default_scaling_algos']);
    
    $display['roles'] = json_encode($display['roles']);
    
    $r = new ReflectionClass("X86_64_TYPE");
    $display["64bit_types"] = array_values($r->getConstants());
    
    $r = new ReflectionClass("I386_TYPE");
    $display["32bit_types"] = array_values($r->getConstants());
    unset($r);
    
    $display["roles_descr"] = $db->GetAll("SELECT ami_id, name, description FROM roles WHERE roletype=? OR (roletype=? and clientid=?)", 
    	array(ROLE_TYPE::SHARED, ROLE_TYPE::CUSTOM, $uid)
    );
    
    if ($req_configure == 1)
    {
    	$display["ami_id"] = $req_ami_id;
    	$display["return_to"] = $req_return_to;
    }
    
    
    	
    /**
     * Tabs
     */
    $display["tabs_list"] = array(
    	"general" => _("Settings"), 
    	"roles" => _("Roles"),
    	"rso"	=> _("Roles startup order")
   	);
   	
   	$display["help"] = _("Tick the checkbox to add the role to your farm.<br> Click on the role name to customize it's behavior");
   	
	require("src/append.inc.php"); 
?>