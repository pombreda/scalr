<?
	require_once('src/prepend.inc.php');

	// Post actions
	if ($_POST && $post_actionsubmit)
	{
		if ($post_action == "delete")
		{
			// Delete users
			$i = 0;			
			foreach ((array)$post_delete as $k=>$v)
			{
				if ($_SESSION["uid"] != 0)
					$roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE id='{$v}' AND clientid='{$_SESSION['uid']}'");
			    else 
					$roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE id='{$v}'");
			     
			    if ($roleinfo && $roleinfo["iscompleted"] != 0)
			    {
    			    $info = $db->GetRow("SELECT * FROM farm_amis WHERE ami_id=?", array($roleinfo["ami_id"]));
    			    if (!$info)
    			    {
    			        $i++;
        				$db->Execute("DELETE FROM ami_roles WHERE id=?", array($v));	
        				
    			    }
    			    else
    			    {
                        $farm = $db->GetRow("SELECT * FROM farms WHERE id='{$info['farmid']}'");
    			        $err[] = "Cannot delete role {$roleinfo['name']}. It's being used on farm '{$farm['name']}'.";
    			    }
			    }
			}
			
			if (count($err) == 0)
			{
			     $okmsg = "{$i} client roles deleted";
			     UI::Redirect("client_roles_view.php");
			}
		}
	}
	
	$paging = new SQLPaging();

	if ($_SESSION['uid'] == 0)
	   $sql = "SELECT * from ami_roles WHERE clientid != 0";
	else
	   $sql = "SELECT * from ami_roles WHERE clientid='{$_SESSION['uid']}'";
		
	if ($req_clientid)
	{
	    $id = (int)$req_clientid;
	    $paging->AddURLFilter("clientid", $id);
	    $sql .= " AND clientid='{$id}'";
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


	$rows = $db->GetAll($paging->SQL);
	
	//
	// Rows
	//
	foreach ($rows as &$row)
	{
		$row["isreplaced"] = (bool)$db->GetOne("SELECT id FROM ami_roles WHERE `replace`='{$row['ami_id']}'");
		
		$row["client"] = $db->GetRow("SELECT * FROM clients WHERE id='{$row['clientid']}'");
		
		if ($row["replace"] == "" || $db->GetOne("SELECT roletype FROM ami_roles WHERE ami_id='{$row['replace']}'") == 'SHARED')
    	   $display["rows"][] = $row;
	}
	
	$display["title"] = "Custom roles > View";
	
	$display["page_data_options_add"] = true;
	$display["page_data_options"] = array(
		array("name" => "Delete", "action" => "delete")
	);
	require_once ("src/append.inc.php");
?>