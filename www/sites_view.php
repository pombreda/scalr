<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	$display["title"] = "Applications&nbsp;&raquo;&nbsp;View";
	
	if ($_POST && $post_with_selected)
	{
		if ($post_action == "delete")
		{
			$ZoneControler = new DNSZoneControler();
		    
		    foreach ((array)$_POST["id"] as $dd)
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
				
				$okmsg = "Applications have been marked for deletion. They will be deleted in few minutes.";
				UI::Redirect("sites_view.php?farmid={$req_farmid}");
			}
		}
	};
	
	if ($req_farmid)
	{
	    $id = (int)$req_farmid;
	    $display['grid_query_string'] .= "&farmid={$id}";
	}
	
	if ($req_clientid)
	{
	    $id = (int)$req_clientid;
	    $display['grid_query_string'] .= "&clientid={$id}";
	}
	
	if ($req_ami_id)
	    $display['grid_query_string'] .= "&ami_id={$req_ami_id}";
		
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