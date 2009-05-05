<?
	require_once('src/prepend.inc.php');
    
	if ($_SESSION["uid"] != 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION["uid"]));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
        
	if (!$farminfo)
	{
	    $errmsg = _("Farm not found");
	    UI::Redirect("farms_view.php");
	}
	
	$display["farm_status"] = $farminfo["status"];
	
	// Post actions
	
	if ($req_task == "launch_new_instance" && $farminfo["status"] == 1)
	{
		$Client = Client::Load($farminfo["clientid"]);
				    
		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region'])); 
		$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		   
		$roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($req_ami_id));
		if ($roleinfo)
        {
        	$role = $roleinfo["name"];    
			$ami = $v;
        		
			$farm_ami_info = $db->GetRow("SELECT * FROM farm_amis WHERE farmid='{$farminfo['id']}' AND ami_id='{$req_ami_id}'");
        	if ($farm_ami_info["max_count"] < $farm_ami_info["min_count"]+1)
        	{
        		$increase_max_count = ", max_count=max_count+1";
        		
        		$warnmsg = sprintf(_("The number of running %s instances is equal to maximum instances setting for this role. Maximum Instances setting for role %s has been increased automatically"), 
        			$roleinfo['name'], $roleinfo['name']
        		);
        	}
        		
        	// increase min_count for farm ami
        	$db->Execute("UPDATE farm_amis SET min_count=min_count+1{$increase_max_count} WHERE farmid='{$farminfo['id']}' AND ami_id='{$req_ami_id}'");
        		
            $res = Scalr::RunInstance(CONFIG::$SECGROUP_PREFIX.$role, $farminfo['id'], $role, $farminfo['hash'], $req_ami_id, false, true);                        
            if (!$res)
            	$errmsg = _("Cannot run instance. See system log for details.");
			else
            	$okmsg = _("Instance successfully launched");
		}
		else
			$errmsg = _("Role not found in database.");
		
    	UI::Redirect("roles_view.php?farmid={$req_farmid}");
	}
    
	$paging = new SQLPaging();

	$sql = "SELECT * from farm_amis WHERE farmid='{$farminfo['id']}'";
		
	if ($get_ami_id)
	{
		$ami_id = $db->qstr($get_ami_id);
		$sql .= " AND ami_id={$ami_id}";
	}
	
	//
	//Paging
	//
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = "ORDER BY id ASC";
	$paging->ApplyFilter($_POST["filter_q"], array("id"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");


	$display["rows"] = $db->GetAll($paging->SQL);

	//
	// Rows
	//
	foreach ($display["rows"] as &$row)
	{
		$row["name"] = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$row['ami_id']}'");
		$row["sites"] = $db->GetOne("SELECT COUNT(*) FROM zones WHERE ami_id='{$row["ami_id"]}' AND status != ? AND farmid=?", array(ZONE_STATUS::DELETED, $farminfo['id']));
		$row["r_instances"] = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state=? AND farmid='{$row['farmid']}' AND ami_id='{$row['ami_id']}'", array(INSTANCE_STATE::RUNNING));
		$row["p_instances"] = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state IN (?,?) AND farmid='{$row['farmid']}' AND ami_id='{$row['ami_id']}'", array(INSTANCE_STATE::PENDING, INSTANCE_STATE::INIT));
	}

	$display["title"] = _("Farms > View roles");
	
	$display["farmid"] = $req_farmid;
	
	$display["page_data_options"] = array(
		array("name" => _("Launch new instance"), "action" => "launch")
	);
	
	require_once ("src/append.inc.php");
?>