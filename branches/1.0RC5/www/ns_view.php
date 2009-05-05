<? 
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	
	$display["title"] = "Nameservers&nbsp;&raquo;&nbsp;View";

	if ($_POST && $post_actionsubmit)
	{
		if ($_POST["action"] == "delete")
		{
			foreach ((array)$_POST["delete"] as $dd)
			{	
				$info = $db->GetRow("SELECT * FROM nameservers WHERE id='{$dd}'");
			    if ($info)
			    {
    			    $db->Execute("DELETE FROM nameservers WHERE id='{$dd}'");
    				
    			    $DNSZoneController = new DNSZoneControler();
                    $records = $db->GetAll("SELECT * FROM records WHERE rvalue='{$info['host']}' AND rtype='NS'");
                    foreach ($records as $record)
                    {
                        $zoneinfo = $db->GetRow("SELECT * FROM zones WHERE id='{$record['zoneid']}'");
                        
                        if ($zoneinfo)
                        {
                            $db->Execute("DELETE FROM records WHERE id='{$record['id']}'");
                            if (!$DNSZoneController->Update($record["zoneid"]))
                                $Logger->fatal("Cannot delete NS record '{$info['host']}' from zone '{$zoneinfo['zone']}'", E_ERROR);
                            else 
                                $Logger->info("NS record '{$info['host']}' removed from zone '{$zoneinfo['zone']}'", E_USER_NOTICE);
                        }
                    }
    			    
    				$i++;
			    }
			}
			
			$okmsg = "{$i} Nameservers deleted";
			UI::Redirect("ns_view.php");
		}
	};
	
	$sql = "SELECT * FROM nameservers";


	//Paging
	$paging = new SQLPaging($sql);
	$paging->ApplyFilter($_POST["filter_q"], array("host"));
	$paging->ApplySQLPaging();
	$paging->ParseHTML();
	$display["filter"] = $paging->GetFilterHTML("inc/table_filter.tpl");
	$display["paging"] = $paging->GetPagerHTML("inc/paging.tpl");

	
	// Rows
	$display["rows"] = $db->GetAll($paging->SQL);
	
	$display["page_data_options"] = array(array("name" => "Delete", "action" => "delete"));
	
	require("src/append.inc.php"); 
	
?>