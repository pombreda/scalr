<?
	require_once('src/prepend.inc.php');

	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	
	// Post actions
	if ($_POST && $post_actionsubmit)
	{
		switch($post_action)
		{
			case "activate":
			case "deactivate":
				
				$flag = ($post_action == "activate") ? '1' : '0';
				
				$i = 0;			
				foreach ((array)$post_delete as $clientid)
				{
					$db->Execute("UPDATE clients SET isactive=? WHERE id=?", array($flag, $clientid));
					$i++;
				}
				
				$mess = "{$i} clients updated";
				UI::Redirect("clients_view.php");
				
				break;
			
			case "delete":
				
				// Delete users
				$i = 0;			
				foreach ((array)$post_delete as $clientid)
				{
					$i++;
					$db->Execute("DELETE FROM clients WHERE id='{$clientid}'");
					
					$farms = $db->GetAll("SELECT * FROM farms WHERE clientid='{$clientid}'");
				    foreach ($farms as $farm)
				    {
					    $db->Execute("DELETE FROM farms WHERE id='{$farm["id"]}'");
					    $db->Execute("DELETE FROM farm_amis WHERE farmid='{$farm["id"]}'");
					    $db->Execute("DELETE FROM farm_instances WHERE farmid='{$farm["id"]}'");
				    }
					
					@unlink(APPPATH . "/etc/clients_keys/{$clientid}/pk.pem");
					@unlink(APPPATH . "/etc/clients_keys/{$clientid}/cert.pem");
				}
				
				$mess = "{$i} clients deleted";
				UI::Redirect("clients_view.php");
				
				break;
		}
	}


	$sql = "SELECT * from clients WHERE id > 0";
	
	//
	// If specified user id
	//
	if ($get_clientid)
	{
		$clientid = (int)$get_clientid;
		$sql .= " AND id='{$clientid}'";
	}

	
	//
	//Paging
	//
	$paging = new SQLPaging($sql);
	$paging->AdditionalSQL = "ORDER BY email ASC";
	$paging->ApplyFilter($_POST["filter_q"], array("aws_accountid", "email", "fullname"));
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
		array("name" => "Activate", "action" => "activate"),
		array("name" => "Deactivate", "action" => "deactivate"),
		array("name" => "Delete", "action" => "delete")
	);
	require_once ("src/append.inc.php");
?>