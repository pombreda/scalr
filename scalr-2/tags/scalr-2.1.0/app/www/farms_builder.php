<?
	require("src/prepend.inc.php");
    $display['load_extjs'] = true;

    //TODO: ONLY client

	set_time_limit(360);

	$req_farmid = ($req_farmid) ? $req_farmid : $req_id;

	if ($req_saved) {
		$okmsg = _("Farm successfully saved");
		UI::Redirect("farms_builder.php?id=" . intval($req_farmid));
	}

/*	if ($_SESSION['uid'] == 0)
    {
    	if (!$req_farmid)
            //UI::Redirect("farms_view.php");
        else
        {
            //$uid = $db->GetOne("SELECT clientid FROM farms WHERE id='{$req_farmid}'");
        }
    }
    else
    {
        $uid = $_SESSION['uid'];
    }*/

//    $Client = Client::Load($uid);

/*    $used_slots = $db->GetOne("SELECT SUM(value) FROM farm_role_settings WHERE name=?
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
	}

    $display["roles_descr"] = $db->GetAll("SELECT id, ami_id, name, description FROM roles WHERE roletype=? OR (roletype=? and clientid=?)",
    	array(ROLE_TYPE::SHARED, ROLE_TYPE::CUSTOM, $uid)
    );

    if ($req_configure == 1)
    {
    	$display["role_id"] = $req_role_id;
    	$display["return_to"] = $req_return_to;
    } */

    /**
     * Tabs
     */
    $display["tabs_list"] = array(
    	"general" => _("Settings"),
    	"roles" => _("Roles"),
    	"rso"	=> _("Roles startup order")
   	);

   	$display['id'] = $req_farmid;
   	$display['role_id'] = $req_role_id ? intval($req_role_id) : 0;
   	$display['current_time_zone'] = @date_default_timezone_get();
   	$display['current_time'] = date("D h:i a");
   	$display['current_env_id'] = Scalr_Session::getInstance()->getEnvironmentId();

	require("src/append.inc.php");
?>