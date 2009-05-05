<?
	require_once('src/prepend.inc.php');
    
	if ($get_task == "download_private_key")
	{
	    if ($_SESSION['uid'] != 0)
	       $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($get_id, $_SESSION['uid']));
	    else 
	       $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($get_id));
	       
	    if (!$farminfo)
	    {
	        $errmsg = _("Farm not found");
	        UI::Redirect("farms_view.php");
	    }
	    
	    header('Pragma: private');
		header('Cache-control: private, must-revalidate');
	    header('Content-type: plain/text');
        header('Content-Disposition: attachment; filename="'.$farminfo["name"].'.pem"');
        header('Content-Length: '.strlen($farminfo['private_key']));

        print $farminfo['private_key'];
        exit();
	}
	
	if (!$_POST && !$get_task && $get_code)
	{
		if ($get_code == 1)
			$okmsg = _("Farm successfully updated");
	}
	
	$paging = new SQLPaging();

	$sql = "SELECT * from farms WHERE 1=1";
	
	if ($_SESSION["uid"] != 0)
	   $sql .= " AND clientid='{$_SESSION['uid']}'";

	if ($req_farmid || $req_id)
	{
	    $id = ($req_farmid) ? (int)$req_farmid : (int)$req_id;
	    $sql .= " AND id='{$id}'";
	    $paging->AddURLFilter("farmid", $id);
	}

	if ($req_clientid)
	{
	    $id = (int)$req_clientid;
	    $sql .= " AND clientid='{$id}'";
	    $paging->AddURLFilter("clientid", $id);
	}
	
	if (isset($req_status))
	{
	    $status = (int)$req_status;
	    $sql .= " AND status='{$status}'";
	    $paging->AddURLFilter("status", $status);
	}
	
	//
	//Paging
	//
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = "ORDER BY id ASC";
	$paging->ApplyFilter($_POST["filter_q"], array("name"));
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
		$row["instances"] = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE farmid='{$row['id']}'");
		$row["roles"] = $db->GetOne("SELECT COUNT(*) FROM farm_amis WHERE farmid='{$row['id']}'");
		$row["sites"] = $db->GetOne("SELECT COUNT(*) FROM zones WHERE farmid='{$row['id']}' AND status != ?", array(ZONE_STATUS::DELETED));
		
		if ($_SESSION['uid'] == 0)
			$row["client"] = $db->GetRow("SELECT * FROM clients WHERE id='{$row['clientid']}'");
			
		$row["havemysqlrole"] = (bool)$db->GetOne("SELECT id FROM farm_amis WHERE ami_id IN (SELECT ami_id FROM ami_roles WHERE alias='mysql') AND farmid='{$row['id']}'");
		
		$row['status_txt'] = FARM_STATUS::GetStatusName($row['status']);
		
		if ($row['status'] == FARM_STATUS::RUNNING)
		{
			$row['shortcuts'] = $db->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? AND ami_id IS NULL AND ismenuitem='1'",
				array($row['id'])
			);
			foreach ($row['shortcuts'] as &$shortcut)
				$shortcut['name'] = $db->GetOne("SELECT name FROM scripts WHERE id=?", array($shortcut['scriptid']));
		}
	}
	
	$display["title"] = _("Farms > View");
	
	if ($_SESSION["uid"] != 0)
	   $display["page_data_options_add"] = true;
	   
	$display["page_data_options"] = array(
		array("name" => _("Delete"), "action" => "delete")
	);
	
	$display["help"] = _("This is a list of all your Server Farms. A Server Farm is a logical group of EC2 machines that serve your application. It can include load balancers, databases, web severs, and other custom servers. Servers in these farms can be redundant, self curing, and auto-scaling.");
	
	require_once ("src/append.inc.php");
?>