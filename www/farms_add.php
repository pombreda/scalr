<? 
	require("src/prepend.inc.php"); 
	$display["title"] = "Edit farm";
    
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
    $display["warnmsg"] = "You have {$avail_slots} spare instances available on your account.";
    
    if ($_SESSION['uid'] == 0)
    {
	    $clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($uid));
		
		// Decrypt client prvate key and certificate
	    $private_key = $Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
	    $certificate = $Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
    }
    else
    {
    	$private_key = $_SESSION["aws_private_key"];
    	$certificate = $_SESSION["aws_certificate"];
    }
	
	$AmazonEC2Client = new AmazonEC2($private_key, $certificate);
                    
    // Get Avail zones
    $avail_zones_resp = $AmazonEC2Client->DescribeAvailabilityZones();
    $display["avail_zones"] = array();
    
    // Random assign zone
    array_push($display["avail_zones"], "");
    
    foreach ($avail_zones_resp->availabilityZoneInfo->item as $zone)
    {
    	if ($zone->zoneState == 'available')
    		array_push($display["avail_zones"], (string)$zone->zoneName);
    }
	
    if ($req_id)
    {
        $display["farminfo"] = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_id));
        
        if ($_SESSION['uid'] != 0 && $_SESSION['uid'] != $display["farminfo"]["clientid"])
            UI::Redirect("farms_view.php");
        
        if (!$display["farminfo"])
        {
            $errmsg = "Farm not found";
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

        	$role = array(
        		'name' 		=> $ami_info["name"],
        		'arch' 		=> $ami_info["architecture"],
        		'alias' 	=> $ami_info["alias"],
        		'ami_id'	=> $ami_info["ami_id"],
        		'options'	=> array(
        			'min_instances' 	=> $row['min_count'],
        			'max_instances' 	=> $row['max_count'],
        			'min_LA'			=> $row['min_LA'],
        			'max_LA'			=> $row['max_LA'],
        			'reboot_timeout'	=> $row['reboot_timeout'],
        			'launch_timeout'	=> $row['launch_timeout'],
        			'placement'			=> ($row['avail_zone']) ? $row['avail_zone'] : "",
        			'i_type'			=> $row['instance_type'],
        			'use_elastic_ips'	=> ($row['use_elastic_ips'] == 1) ? true : false
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
    
    /**
     * Tabs
     */
    $display["tabs_list"] = array(
    	"general" => "Settings", 
    	"roles" => "Roles"
   	);
   		
   	$display["help"] = "Tick the checkbox to add the role to your farm<br>
   	Click on the role name to customize it's behavior
   	";
   	
	require("src/append.inc.php"); 
?>