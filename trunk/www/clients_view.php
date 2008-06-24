<?
	require_once('src/prepend.inc.php');

	if ($_SESSION["uid"] != 0)
	   CoreUtils::Redirect("index.php");
	
	// Post actions
	if ($_POST && $post_actionsubmit)
	{
		if ($post_action == "delete")
		{
			// Delete users
			$i = 0;			
			foreach ((array)$post_delete as $k=>$v)
			{
				$i++;
				$db->Execute("DELETE FROM clients WHERE id='{$v}'");
				
				$farms = $db->GetAll("SELECT * FROM farms WHERE clientid='{$v}'");
			    foreach ($farms as $farm)
			    {
				    $db->Execute("DELETE FROM farms WHERE id='{$farm["id"]}'");
				    $db->Execute("DELETE FROM farm_amis WHERE farmid='{$farm["id"]}'");
				    $db->Execute("DELETE FROM farm_instances WHERE farmid='{$farm["id"]}'");
			    }
				
				@unlink(APPPATH . "/etc/clients_keys/{$_SESSION['uid']}/pk.pem");
				@unlink(APPPATH . "/etc/clients_keys/{$_SESSION['uid']}/cert.pem");
			}
			
			$mess = "{$i} clients deleted";
			CoreUtils::Redirect("clients_view.php");
		}
	}


	$sql = "SELECT * from clients WHERE id > 0";
	
	//
	// If specified user id
	//
	if ($get_clientid)
		$sql .= " AND id='{$get_clientid}'";

	
	//
	//Paging
	//
	$paging = new SQLPaging($sql);
	$paging->AdditionalSQL = "ORDER BY email ASC";
	$paging->ApplyFilter($_POST["filter_q"], array("aws_accountid", "email"));
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
		$row["farms"] = $db->GetOne("SELECT COUNT(*) FROM farms WHERE clientid='{$row['id']}'");
		$row["instances"] = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE farmid IN (SELECT id FROM farms WHERE clientid='{$row['id']}')");
		$row["amis"] = $db->GetOne("SELECT COUNT(*) FROM ami_roles WHERE clientid='{$row['id']}'");
	}

	$display["title"] = "Clients > Manage";
	
	$display["page_data_options_add"] = true;
	$display["page_data_options"] = array(
		array("name" => "Delete", "action" => "delete")
	);
	require_once ("src/append.inc.php");
?>