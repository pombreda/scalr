<? 
	define("CF_PAGING_ITEMS", 20);
	require("src/prepend.inc.php"); 
	
	if ($_SESSION["uid"] != 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION["uid"]));
    else
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
        
	if (!$farminfo)
	{
	    $errmsg = "Farm not found";
	    CoreUtils::Redirect("farms_view.php");
	}
	
	$AmazonEC2Client = new AmazonEC2(
                        APPPATH . "/etc/clients_keys/{$farminfo['clientid']}/pk.pem", 
                        APPPATH . "/etc/clients_keys/{$farminfo['clientid']}/cert.pem");
	$display["title"] = "Instances&nbsp;&raquo;&nbsp;View";
	
	Core::Load("NET/SNMP");
	$SNMP = new SNMP();
	
	if ($_POST && $post_actionsubmit)
	{
		$i = 0;
		if (is_array($post_actid))
		{
			
			try 
			{
				$instances = array();
				foreach ($post_actid as $v)
					array_push($instances, $v);

				// Do something
				if ($post_action == "terminate")
					$response = $AmazonEC2Client->TerminateInstances($instances);
				elseif ($post_action == "reboot")
					$response = $AmazonEC2Client->RebootInstances($instances);
					
				if ($response instanceof SoapFault)
				{
					$err[] = $response->faultstring;
				}
			}
			catch (Exception $e)
			{
				$err[] = $e->getMessage(); 
			}
			
			$i++;
		}	
		$okmsg = "{$i} instances succesfully " . ($post_action == "reboot" ? "rebooted" : "terminated");
		CoreUtils::Redirect("?state=" . ($post_action == "reboot" ? "running" : "shutting-down")."&farmid={$req_farmid}");
	}
	
	//Paging
	$paging = new Paging();
	$paging->ItemsOnPage = 20;

	// Rows
	$response = $AmazonEC2Client->DescribeInstances();
	
	
	if (!is_array($response->reservationSet->item))
	    $rowz[] = $response->reservationSet->item;
	else 
	   $rowz = $response->reservationSet->item;
	
	// Custom properties
	foreach ($rowz as $pk=>$pv)
	{
		// Groupslist
		$gl = array();
		
		if (is_array($rowz[$pk]->groupSet->item))
		{
			foreach ($rowz[$pk]->groupSet->item as $g)
			{
				$gl[] = $g->groupId;
			}
			$rowz[$pk]->groupsList = implode($gl, ", ");
		}
		else
			$rowz[$pk]->groupsList = $rowz[$pk]->groupSet->item->groupId;
		
	    $rowz[$pk]->Role = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($rowz[$pk]->instancesSet->item->imageId));
		
	    if ($rowz[$pk]->instancesSet->item->launchTime)
	    {
	        $ltime = strtotime($rowz[$pk]->instancesSet->item->launchTime);
	        $rowz[$pk]->Uptime = Formater::Time2HumanReadable(time()-$ltime);
	    }
	    
	    $rowz[$pk]->IP = $db->GetOne("SELECT external_ip FROM farm_instances WHERE instance_id='{$rowz[$pk]->instancesSet->item->instanceId}'");
	    
	    ///
	    ///
	    ///
	    if ($rowz[$pk]->IP)
	    {
            $community = $db->GetOne("SELECT hash FROM farms WHERE id=(SELECT farmid FROM farm_instances WHERE instance_id='{$rowz[$pk]->instancesSet->item->instanceId}')");
            
            $SNMP->Connect($rowz[$pk]->IP, null, $community);
            $res = $SNMP->Get(".1.3.6.1.4.1.2021.10.1.3.3");
            if (!$res)
                $rowz[$pk]->LA = "Unknown";
            else 
                $rowz[$pk]->LA = number_format((float)$res, 2);
	    }
	    else 
	       $rowz[$pk]->LA = "Unknown";
	    ///
	    
		$doadd = true;
		if (isset($get_state))
		{
			if ($rowz[$pk]->instancesSet->item->instanceState->name != $get_state)	
				$doadd = false; 
		}
		
		if (isset($get_iid))
		{
    		if ($get_iid && $rowz[$pk]->instancesSet->item->instanceId != $get_iid)
                $doadd = false;
		}
		
		if (isset($get_farmid))
		{
		    if (!$db->GetOne("SELECT id FROM farm_instances WHERE farmid=? AND instance_id=?", array($get_farmid, $rowz[$pk]->instancesSet->item->instanceId)))
		      $doadd = false;
		}
		
		if (!$get_nofarm)
		{
			if ($r[0] == "unknown")
			$doadd = false; 
		} 
		
		if ($doadd)
			$rowz1[] = $rowz[$pk];
	}
	
	$rowz = $rowz1;
	
	$paging->Total = count($rowz); 
	
	$paging->ParseHTML();
	
	$display["rows"] = (count($rowz) > CF_PAGING_ITEMS) ? array_slice($rowz, $paging->PageNo * CF_PAGING_ITEMS, CF_PAGING_ITEMS) : $rowz;
	
	$display["paging"] = $paging->GetHTML("inc/paging.tpl");
	
	$display["farmid"] = $farminfo["id"];
	
	$display["page_data_options"] = array(
		array("name" => "Terminate", "action" => "terminate"),
		array("name" => "Reboot", "action" => "reboot")
	);
	$display["page_data_options_add"] = false;
	
	require("src/append.inc.php"); 
	
?>