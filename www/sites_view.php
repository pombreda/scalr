<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Applications&nbsp;&raquo;&nbsp;View";
	
	if ($_POST && $post_actionsubmit)
	{
		if ($post_action == "delete")
		{
			$ZoneControler = new DNSZoneControler();
		    
		    foreach ((array)$_POST["delete"] as $dd)
			{	
				$db->BeginTrans();
				 
				try
				{
					if ($_SESSION["uid"] != 0)
						$zone = $db->GetRow("SELECT * FROM zones WHERE id=? AND clientid=?", array($dd, $_SESSION["uid"]));
					else 
						$zone = $db->GetRow("SELECT * FROM zones WHERE id=?", array($dd));
					
				    if ($zone)
					{
	    				$ZoneControler->Delete($zone["id"]);	    				
	    				$i++;
					}
				}
				catch(Exception $e)
				{
					$db->RollbackTrans();
		    		$Logger->fatal("Exception thrown during application delete: {$e->getMessage()}");
		    		$err[] = "Cannot delete application '{$zone['name']}'. Please try again later.";
				}
				
				
			}
			
			if (count($err) == 0)
			{
				$db->CommitTrans();
				
				$okmsg = "Applications you are trying to delete have been marked for deletion. They will be deleted in few minutes.";
				UI::Redirect("sites_view.php?farmid={$req_farmid}");
			}
		}
	};
	
	$paging = new SQLPaging();
	
	if ($_SESSION["uid"] == 0)
	   $sql = "select * from zones WHERE 1=1";
	else
	   $sql = "select * from zones WHERE clientid='{$_SESSION['uid']}'";

	if ($req_farmid)
	{
	    $id = (int)$req_farmid;
	    $sql .= " AND farmid='{$id}'";
	    $paging->AddURLFilter("farmid", $id);
	}
	
	if ($req_ami_id)
	{
	    $ami_id = $db->qstr($req_ami_id);
	    
	    $sql .= " AND ami_id={$ami_id}";
	    $paging->AddURLFilter("ami_id", $id);
	}
	//Paging
	
	$paging->SetSQLQuery($sql);
	$paging->ApplyFilter($_POST["filter_q"], array("zone"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");

	// Rows
	$display["rows"] = $db->GetAll($paging->SQL);	
	foreach ($display["rows"] as &$row)
	{
	    if ($row["ami_id"])
			$row["role"] = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", $row["ami_id"]);
			
	    $row["farm"] = $db->GetRow("SELECT * FROM farms WHERE id=?", $row["farmid"]);
	    
	    $row['role_alias'] = $row["role"]['alias'];
	    
	    switch($row["status"])
	    {
	    	case ZONE_STATUS::ACTIVE:
	    		$row["string_status"] = "Active";
	    		break;
	    	case ZONE_STATUS::DELETED:
	    		$row["string_status"] = "Pending delete";
	    		break;
	    	case ZONE_STATUS::PENDING:
	    		$row["string_status"] = "Pending create";
	    		break;
	    	case ZONE_STATUS::INACTIVE:
	    		$row["string_status"] = "Inactive";
	    		break;
	    }
	}
	
	$display["page_data_options"] = array(array("name" => "Delete", "action" => "delete"));
	
	$display["help"] = "This page lists your applications<br /><br />
	<b>Role:</b> Instances of this role are creating domain A records in application DNS zone.<br />
	<b>DNS Zone status</b> can be:<br />
	<span style='margin-left:12px;'>&bull; Active &mdash; Scalr nameservers are serving DNS zone and it is being updated dynamically</span><br />
	<span style='margin-left:12px;'>&bull; Inactive &mdash; Scalr nameservers  are not serving this domain</span><br />
	<span style='margin-left:12px;'>&bull; Pending delete &mdash; DNS zone marked for deletion</span><br />
	<span style='margin-left:12px;'>&bull; Pending create &mdash; DNS zone will be created soon</span><br />
	";
	
	require("src/append.inc.php");
?>