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
				if ($_SESSION['uid'] != 0)
                    $info = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($v, $_SESSION['uid']));
                else 
                    $info = $db->GetRow("SELECT * FROM farms WHERE id=?", array($v));
                    
			    if ($info)
			    {
			        $AmazonEC2Client = new AmazonEC2(
                        APPPATH . "/etc/clients_keys/{$info['clientid']}/pk.pem", 
                        APPPATH . "/etc/clients_keys/{$info['clientid']}/cert.pem");
			        
			        $i++;
    				$db->Execute("DELETE FROM farms WHERE id=?", array($v));
    				
    				$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid='{$v}'");
    				foreach ($instances as $instance)
    				{
    				    try 
            			{            
            				$response = $AmazonEC2Client->TerminateInstances(array($instance["instance_id"]));
            					
            				if ($response instanceof SoapFault)
            				{
            					$err[] = $response->faultstring;
            				}
            			}
            			catch (Exception $e)
            			{
            				$err[] = $e->getMessage(); 
            			}
    				}
    				
    				$db->Execute("DELETE FROM farm_amis WHERE farmid='{$v}'");
    				$db->Execute("DELETE FROM farm_instances WHERE farmid='{$v}'");
    				$db->Execute("DELETE FROM records WHERE zoneid IN (SELECT id FROM zones WHERE farmid='{$v}')");
    				$db->Execute("DELETE FROM zones WHERE farmid='{$v}'");
			    }
			}
			
			$okmess = "{$i} farms deleted";
			CoreUtils::Redirect("farms_view.php");
		}
	}
    
	if ($get_task == "download_private_key")
	{
	    if ($_SESSION['uid'] != 0)
	       $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($get_id, $_SESSION['uid']));
	    else 
	       $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($get_id));
	       
	    if (!$farminfo)
	    {
	        $errmsg = "Farm not found";
	        CoreUtils::Redirect("farms_view.php");
	    }
	    
	    $keyname = preg_replace("/[^A-Za-z0-9]+/", "", $farminfo["name"]);
	    
	    header('Content-type: plain/text');
        header('Content-Disposition: attachment; filename="'.$keyname.'.pk"');
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
		$row["sites"] = $db->GetOne("SELECT COUNT(*) FROM zones WHERE farmid='{$row['id']}'");
	}
	
	$display["title"] = "Farms > View";
	
	if ($_SESSION["uid"] != 0)
	   $display["page_data_options_add"] = true;
	   
	$display["page_data_options"] = array(
		array("name" => "Delete", "action" => "delete")
	);
	require_once ("src/append.inc.php");
?>