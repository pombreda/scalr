<?
	require_once('src/prepend.inc.php');

	if ($req_task == 'abort')
	{
		$roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE id=?", array($req_id));
		if ($roleinfo['clientid'] != $_SESSION["uid"] && $_SESSION["uid"] != 0)
			UI::Redirect("client_roles_view.php");
			
		if (isset($post_cbtn_3))
			UI::Redirect("client_roles_view.php");
			
		if ($roleinfo["iscompleted"] == 0 && !$post_confirmed)
		{
			$Smarty->assign(array("id" => $req_id, "instance_id" => $roleinfo['prototype_iid']));
			$Smarty->display("sync_cancel.tpl");
			exit();
		}
			
		if ($roleinfo["replace"] != '' && $roleinfo["iscompleted"] != 2)
		{
			$db->BeginTrans();
			
			try
			{
				$db->Execute("UPDATE farm_amis SET replace_to_ami = '' WHERE replace_to_ami=?", 
					array($roleinfo['ami_id'])
				);
				
				if ($roleinfo['ami_id'])
				{
					$db->Execute("UPDATE ami_roles SET `replace` = '', iscompleted='2', fail_details=? 
						WHERE id=?",
						array(_("Rebundle complete, but the rebundled AMI is not operable by Scalr."), $roleinfo["id"])
					);
				}
				else
				{
					$db->Execute("UPDATE ami_roles SET `replace` = '', iscompleted='2', fail_details=?, prototype_iid='' 
						WHERE id=?",
						array(_("Canceled by user"), $roleinfo["id"])
					);
				}
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
	    		throw new ApplicationException($e->getMessage());
			}
			
			$db->CommitTrans();

			if (count($instances) > 0)
			{			
			    $Client = Client::Load($roleinfo['clientid']);
			    
			    $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($roleinfo['region'])); 
				$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
			    
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
			}
			
			if (count($err) > 0)
			{
				$errmsg = _("Role synchronization aborted with the following errors:");
				UI::Redirect("client_roles_view.php");
			}
			else
			{
				$okmsg = _("Role synchronization aborted");
				UI::Redirect("client_roles_view.php");
			}
		}
		else
			UI::Redirect("client_roles_view.php");
	}
	
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
					$roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE id=? AND clientid='{$_SESSION['uid']}'", array($v));
			    else 
					$roleinfo = $db->GetRow("SELECT * FROM ami_roles WHERE id=?", array($v));
			     
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
    			        $err[] = sprintf(_("Cannot delete role %s. It's being used on farm '%s'."), $roleinfo['name'], $farm['name']);
    			    }
			    }
			}
			
			if (count($err) == 0)
			{
			     $okmsg = sprintf(_("%s client roles deleted"), $i);
			     UI::Redirect("client_roles_view.php");
			}
		}
	}
	
	$paging = new SQLPaging();

	if ($_SESSION['uid'] == 0)
	   $sql = "SELECT * from ami_roles WHERE 1=1";
	else
	   $sql = "SELECT * from ami_roles WHERE (clientid='{$_SESSION['uid']}' OR (roletype='".ROLE_TYPE::SHARED."' AND clientid = '0') OR (roletype='".ROLE_TYPE::SHARED."' AND clientid != '0' AND approval_state='".APPROVAL_STATE::APPROVED."'))";
		
	//Region filter
	$sql .= " AND region='".$_SESSION['aws_region']."'";
	   
	if ($req_clientid)
	{
	    $id = (int)$req_clientid;
	    $paging->AddURLFilter("clientid", $id);
	    $sql .= " AND clientid='{$id}'";
	}
	   
	if ($req_type)
	{
		$type = preg_replace("/[^A-Za-z]+/", "", $req_type);
		$paging->AddURLFilter("type", $type);
	    $sql .= " AND roletype='{$type}'";
	}
		
	if (isset($req_approval_state))
	{
		$state = preg_replace("/[^A-Za-z]+/", "", $req_approval_state);
		$paging->AddURLFilter("approval_state", $state);
		$sql .= " AND approval_state = '{$state}'";
		$sql .= " AND clientid != '0'";
	}
	elseif ($req_type == ROLE_TYPE::SHARED)
		$sql .= " AND clientid = '0'";
	
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
		if ($row['ami_id'] && $row['roletype'] != ROLE_TYPE::SHARED)
			$row["isreplaced"] = $db->GetOne("SELECT id FROM ami_roles WHERE `replace`='{$row['ami_id']}'");
		
		if ($row['clientid'] == 0)
			$row["client"] = array("fullname" => "Scalr");
		else
			$row["client"] = $db->GetRow("SELECT * FROM clients WHERE id='{$row['clientid']}'");
		
		if ($row["isreplaced"])
			$infrole = $db->GetRow("SELECT * FROM ami_roles WHERE `replace`='{$row['ami_id']}'");
		else
			$infrole = $row;
			
		$time = strtotime($row['dtbuilt']);
		$row['dtbuilt'] = ($time) ? date("M d, Y H:i", $time) : "";
			
		if ($infrole["replace"] != '' && $infrole["iscompleted"] != 2)
			$row["abort_id"] = $infrole['id'];
			
		$row['type'] = ROLE_ALIAS::GetTypeByAlias($row['alias']);
			
		if ($row["replace"] == "" || $db->GetOne("SELECT roletype FROM ami_roles WHERE ami_id='{$row['replace']}'") == ROLE_TYPE::SHARED)
    	   $display["rows"][] = $row;
	}
	
	$display["title"] = _("Roles > View");
	
	$display["page_data_options_add"] = true;
	$display["page_data_options"] = array(
		array("name" => _("Delete"), "action" => "delete")
	);
	require_once ("src/append.inc.php");
?>