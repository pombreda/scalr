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
	        $errmsg = "Farm not found";
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
		$row["instanses"] = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE farmid='{$row['id']}'");
		$row["roles"] = $db->GetOne("SELECT COUNT(*) FROM farm_amis WHERE farmid='{$row['id']}'");
		$row["sites"] = $db->GetOne("SELECT COUNT(*) FROM zones WHERE farmid='{$row['id']}' AND status != ?", array(ZONE_STATUS::DELETED));
		
		if ($_SESSION['uid'] == 0)
			$row["client"] = $db->GetRow("SELECT * FROM clients WHERE id='{$row['clientid']}'");
	}
	
	$display["title"] = "Farms > View";
	
	if ($_SESSION["uid"] != 0)
	   $display["page_data_options_add"] = true;
	   
	$display["page_data_options"] = array(
		array("name" => "Delete", "action" => "delete")
	);
	
	$display["help"] = "This is a list of all your Server Farms. A Server Farm is a logical group of EC2 machines that serve your application. It can include load balancers, databases, web severs, and other custom servers. Servers in these farms can be redundant, self curing, and auto-scaling.";
	
	require_once ("src/append.inc.php");
?>