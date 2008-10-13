<?
	require_once('src/prepend.inc.php');
    
	if ($_SESSION["uid"] != 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION["uid"]));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
        
	if (!$farminfo)
	{
	    $errmsg = "Farm not found";
	    UI::Redirect("farms_view.php");
	}
	
	// Post actions
	if ($_POST && $post_actionsubmit)
	{
		if ($post_action == "launch")
		{
			if (count($post_delete) > 0)
			{
    		    if ($_SESSION['uid'] == 0)
    		    {
    		        $uid = $farminfo["clientid"];
    		        
    		        $clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($uid));
	
					// Decrypt client prvate key and certificate
				    $private_key = $Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
				    $certificate = $Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
    		    }
    		    else
    		    { 
                    $uid = $_SESSION["uid"];
                    
                    $private_key = $_SESSION["aws_private_key"];
    				$certificate = $_SESSION["aws_certificate"];
    		    }
			    
				$AmazonEC2Client = new AmazonEC2($private_key, $certificate);
			   
			    // Delete users
    			$i = 0;			
    			foreach ((array)$post_delete as $k=>$v)
    			{
                    $roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", $v);
    			    
                    if ($roleinfo)
                    {
        			    $role = $roleinfo["name"];    
        				$ami = $v;
        				
        				// increase min_count for farm ami
        				$db->Execute("UPDATE farm_amis SET min_count=min_count+1 WHERE farmid='{$farminfo['id']}' AND ami_id='{$v}'");
        				
                        $res = RunInstance($AmazonEC2Client, CONFIG::$SECGROUP_PREFIX.$role, $farminfo['id'], $role, $farminfo['hash'], $v, false, true);                        
                        if (!$res)
                            $err[] = "Cannot run instance. See system log for details!";
                        else
                            $i++;
                    }
    			}
    			
    			$okmsg = "{$i} instanses launched";
    			UI::Redirect("roles_view.php?farmid={$req_farmid}");
			}
		}
	}
    
	$paging = new SQLPaging();

	$sql = "SELECT * from farm_amis WHERE farmid='{$farminfo['id']}'";
		
	if ($get_ami_id)
	{
		$ami_id = $db->qstr($get_ami_id);
		$sql .= " AND ami_id={$ami_id}";
	}
	
	//
	//Paging
	//
	$paging->SetSQLQuery($sql);
	$paging->AdditionalSQL = "ORDER BY id ASC";
	$paging->ApplyFilter($_POST["filter_q"], array("id"));
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
		$row["name"] = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$row['ami_id']}'");
		$row["sites"] = $db->GetOne("SELECT COUNT(*) FROM zones WHERE ami_id='{$row["ami_id"]}' AND status != ? AND farmid=?", array(ZONE_STATUS::DELETED, $farminfo['id']));
		$row["r_instances"] = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state='Running' AND farmid='{$row['farmid']}' AND ami_id='{$row['ami_id']}'");
		$row["p_instances"] = $db->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state='Pending' AND farmid='{$row['farmid']}' AND ami_id='{$row['ami_id']}'");
	}

	$display["title"] = "Farms > View roles";
	
	$display["farmid"] = $req_farmid;
	
	$display["page_data_options"] = array(
		array("name" => "Launch new instance", "action" => "launch")
	);
	
	require_once ("src/append.inc.php");
?>