<? 
	require("src/prepend.inc.php"); 
	$display["title"] = _("Edit farm");
    
	set_time_limit(360);
		
	if ($_SESSION['uid'] == 0)
    {
        $req_farmid = ($req_farmid) ? $req_farmid : $req_id;
        
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
	
    $used_slots = $db->GetOne("SELECT SUM(max_count) FROM farm_amis WHERE farmid IN (SELECT id FROM farms WHERE clientid='{$uid}')");
    
    $client_max_instances = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array('client_max_instances', $uid));
    $i_limit = $client_max_instances ? $client_max_instances : CONFIG::$CLIENT_MAX_INSTANCES;
    
    $client_max_eips = $db->GetOne("SELECT `value` FROM client_settings WHERE `key`=? AND clientid=?", array('client_max_eips', $uid));
    $eips_limit = $client_max_eips ? $client_max_eips : CONFIG::$CLIENT_MAX_EIPS;
    
    $avail_slots = $i_limit - $used_slots;
    if ($avail_slots <= 5)
    	$display["warnmsg"] = sprintf(_("You have %s spare instances available on your account."), $avail_slots);
    
	
    $Client = Client::Load($uid);    
	$AmazonEC2Client = new AmazonEC2($Client->AWSPrivateKey, $Client->AWSCertificate);
                    
    // Get Avail zones
    $avail_zones_resp = $AmazonEC2Client->DescribeAvailabilityZones();
    $display["avail_zones"] = array();
    
    // Random assign zone
    array_push($display["avail_zones"], "");
    
    foreach ($avail_zones_resp->availabilityZoneInfo->item as $zone)
    {
    	if (stristr($zone->zoneState,'available')) //TODO:
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
			$display['snapshots'][] = $pv->snapshotId;
	}
    
    if ($req_id)
    {
        $display["farminfo"] = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_id));
        
        if ($_SESSION['uid'] != 0 && $_SESSION['uid'] != $display["farminfo"]["clientid"])
            UI::Redirect("farms_view.php");
        
        if (!$display["farminfo"])
        {
            $errmsg = _("Farm not found");
            UI::Redirect("farms_view.php");
        }
        
        $servers = $db->GetAll("SELECT * FROM farm_amis WHERE farmid=?", array($req_id));
        $display['roles'] = array();
        foreach ($servers as &$row)
        {
            $ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($row['ami_id']));
            if (!$ami_info)
            	continue;
            
        	$row["role"] = $ami_info["name"];
        	
        	$scripts = $db->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? AND ami_id=?", array($display["farminfo"]["id"], $row['ami_id']));
			$scripts_object = new stdClass();
			foreach ($scripts as $script)
			{
				$scripts_object->{"{$script['event_name']}_{$script['scriptid']}"} = new stdClass();
				$scripts_object->{"{$script['event_name']}_{$script['scriptid']}"}->config = unserialize($script['params']);
				$scripts_object->{"{$script['event_name']}_{$script['scriptid']}"}->target = $script['target'];
				$scripts_object->{"{$script['event_name']}_{$script['scriptid']}"}->version = $script['version'];
				$scripts_object->{"{$script['event_name']}_{$script['scriptid']}"}->issync = $script['issync'];
				$scripts_object->{"{$script['event_name']}_{$script['scriptid']}"}->timeout = $script['timeout'];
			}
        	
	        if ($ami_info['roletype'] == ROLE_TYPE::SHARED && $ami_info['clientid'] != 0)
	        {
	        	$author_info = $db->GetRow("SELECT fullname FROM clients WHERE id=?", array($ami_info['clientid']));
	        	$author = ($author_info['fullname']) ? $author_info['fullname'] : _('Scalr user');
	        }
	        else
	        	$author = false;
			
        	$role = array(
        		'name' 		=> $ami_info["name"],
        		'arch' 		=> $ami_info["architecture"],
        		'alias' 	=> $ami_info["alias"],
        		'ami_id'	=> $ami_info["ami_id"],
        		'type'		=> ROLE_ALIAS::GetTypeByAlias($ami_info["alias"]),
        		'description' => $ami_info["description"],
        		'scripts'	=> $scripts_object,
        		'author'	=> $author,
        		'options'	=> array(
        			'min_instances' 	=> $row['min_count'],
        			'max_instances' 	=> $row['max_count'],
        			'min_LA'			=> $row['min_LA'],
        			'max_LA'			=> $row['max_LA'],
        			'reboot_timeout'	=> $row['reboot_timeout'],
        			'launch_timeout'	=> $row['launch_timeout'],
        			'placement'			=> ($row['avail_zone']) ? $row['avail_zone'] : "",
        			'i_type'			=> $row['instance_type'],
        			'use_elastic_ips'	=> ($row['use_elastic_ips'] == 1) ? true : false,
        			'use_ebs'			=> ($row['use_ebs'] == 1) ? true : false,
        			'ebs_size'			=> ($row['ebs_snapid']) ? 0 : $row['ebs_size'],
        			'ebs_snapid'		=> $row['ebs_snapid'],
        			'ebs_mount'			=> ($row['ebs_mount'] == 1) ? true : false,
        			'ebs_mountpoint'	=> $row['ebs_mountpoint']
        	));
        	if ($ami_info["alias"] == ROLE_ALIAS::MYSQL)
        	{
        		$role['options']['mysql_bundle_every'] = $display["farminfo"]['mysql_rebundle_every'] ? $display["farminfo"]['mysql_rebundle_every'] : 48;
				$role['options']['mysql_make_backup_every'] = $display["farminfo"]['mysql_bcp_every'] ? $display["farminfo"]['mysql_bcp_every'] : 180;
				$role['options']['mysql_make_backup'] = ($display["farminfo"]['mysql_bcp'] == 1) ? true : false;
				$role['options']['mysql_bundle'] = ($display["farminfo"]['mysql_bundle'] == 1) ? true : false;
        	}
        	array_push($display['roles'], $role);
        }
            
        $display["id"] = $req_id;
    }
    
    $display['roles'] = json_encode($display['roles']);
    
    $r = new ReflectionClass("X86_64_TYPE");
    $display["64bit_types"] = array_values($r->getConstants());
    
    $r = new ReflectionClass("I386_TYPE");
    $display["32bit_types"] = array_values($r->getConstants());
    unset($r);
    
    $display["roles_descr"] = $db->GetAll("SELECT ami_id, name, description FROM ami_roles WHERE roletype=? OR (roletype=? and clientid=?)", 
    	array(ROLE_TYPE::SHARED, ROLE_TYPE::CUSTOM, $uid)
    );
    
    if ($req_configure == 1)
    	$display["ami_id"] = $req_ami_id;
    
    /**
     * Tabs
     */
    $display["tabs_list"] = array(
    	"general" => _("Settings"), 
    	"roles" => _("Roles")
   	);

   	$display["intable_tabs"] = array(
   		array("id" => "info", "name" => _("About"), "display" => ""),
   		array("id" => "scaling", "name" => _("Scaling"), "display" => ""),
   		array("id" => "mysql", "name" => _("MySQL settings"), "display" => "none"),
   		array("id" => "placement", "name" => _("Placement and type"), "display" => ""),
   		array("id" => "eips", "name" => _("Elastic IPs"), "display" => ""),
   		array("id" => "ebs", "name" => _("EBS"), "display" => ""),
   		array("id" => "timeouts", "name" => _("Timeouts"), "display" => ""),
   		array("id" => "scripts", "name" => _("Scripting"), "display" => ""),
   		array("id" => "params", "name" => _("Parameters"), "display" => "")
   	);
   	
   	$display['intable_selected_tab'] = "info";
   	
   	$display["help"] = _("Tick the checkbox to add the role to your farm.<br> Click on the role name to customize it's behavior");
   	
	require("src/append.inc.php"); 
?>