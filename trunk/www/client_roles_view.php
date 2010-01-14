<?
	require_once('src/prepend.inc.php');
	$display['load_extjs'] = true;
	
	set_time_limit(3600);
	
	if ($req_clientid)
	{
	    $id = (int)$req_clientid;
	    $display['grid_query_string'] .= "&clientid={$clientid}";
	}
	   
	if ($req_type)
	{
		$type = preg_replace("/[^A-Za-z]+/", "", $req_type);
	    $display['grid_query_string'] .= "&type={$type}";
	}
	
	if ($req_task == 'abort')
	{
		//TODO: !!!!
		
		$roleinfo = $db->GetRow("SELECT * FROM roles WHERE id=?", array($req_id));
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
				$db->Execute("UPDATE farm_roles SET replace_to_ami = '' WHERE replace_to_ami=?", 
					array($roleinfo['ami_id'])
				);
				
				if ($roleinfo['ami_id'])
				{
					$db->Execute("UPDATE roles SET `replace` = '', iscompleted='2', fail_details=? 
						WHERE id=?",
						array(_("Rebundle complete, but the rebundled AMI is not operable by Scalr."), $roleinfo["id"])
					);
				}
				else
				{
					$db->Execute("UPDATE roles SET `replace` = '', iscompleted='2', fail_details=?, prototype_iid='' 
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
	if ($_POST && ($post_with_selected || $post_confirm))
	{
		if ($post_action == "delete" && count($post_id) != 0)
		{			
			if (!$post_confirm)
			{
				foreach($post_id as $roleid)
				{
					$info = $db->GetRow("SELECT * FROM roles WHERE id=? AND clientid=?", array($roleid, $_SESSION['uid']));
					$display['roles'][$info['id']] = $info;
				}
				$display['title'] = 'Roles removal confirmation';
				
				$Smarty->assign($display);
				$Smarty->display("client_role_delete_confirmation.tpl");
				exit();
			}
			else
			{
				// Delete users
				$i = 0;			
				foreach ((array)$post_id as $k=>$v)
				{
					if ($_SESSION["uid"] != 0)
						$roleinfo = $db->GetRow("SELECT * FROM roles WHERE id=? AND clientid='{$_SESSION['uid']}'", array($v));
				    else 
						$roleinfo = $db->GetRow("SELECT * FROM roles WHERE id=?", array($v));
				     
				    if ($roleinfo && $roleinfo["iscompleted"] != 0)
				    {
	    			    $Client = Client::Load($roleinfo['clientid']);
					    $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($roleinfo['region'])); 
						$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
				    	
				    	$info = $db->GetRow("SELECT * FROM farm_roles WHERE ami_id=?", array($roleinfo["ami_id"]));
	    			    if (!$info)
	    			    {
	    			        if ($post_remove_image[$roleinfo['ami_id']] || $post_dereg_ami[$roleinfo['ami_id']])
	    			        {
		    			    	try
		    			    	{
		    			        	$DescribeImagesType = new DescribeImagesType();
			    			        $DescribeImagesType->imagesSet->item[] = array("imageId" => $roleinfo['ami_id']);
			    			    	$amazon_role_info = $AmazonEC2Client->DescribeImages($DescribeImagesType);
		    			    	}
		    			    	catch(Exception $e){}
		    			    	
		    			    	$image_path = $amazon_role_info->imagesSet->item->imageLocation;
		    			    	
		    			    	$chunks = explode("/", $image_path);
		    			    	
		    			    	$bucket_name = $chunks[0];
		    			    	if (count($chunks) == 3)
		    			    		$prefix = $chunks[1];
		    			    	else
		    			    		$prefix = str_replace(".manifest.xml", "", $chunks[1]);
		    			    	
		    			    	try
		    			    	{
		    			    		$bucket_not_exists = false;
		    			    		$S3Client = new AmazonS3($Client->AWSAccessKeyID, $Client->AWSAccessKey);
		    			    		$objects = $S3Client->ListBucket($bucket_name, $prefix);
		    			    	}
		    			    	catch(Exception $e)
		    			    	{
		    			    		if (stristr($e->getMessage(), "The specified bucket does not exist"))
		    			    			$bucket_not_exists = true;
		    			    	}	
		    			    			    			    	
		    			    	if ($amazon_role_info)
		    			    	{
		    			    		if (!$bucket_not_exists && $post_remove_image[$roleinfo['ami_id']])
			    			    	{
			    			    		foreach ($objects as $object)
			    			    			$S3Client->DeleteObject($object->Key, $bucket_name);
			    			    	}
		    			    		
		    			    		if ($post_dereg_ami[$roleinfo['ami_id']] || $bucket_not_exists)
			    			    	{
			    			    		$AmazonEC2Client->DeregisterImage($roleinfo['ami_id']);
			    			    	}
		    			    	}
	    			        }
	    			        
	        				$db->Execute("DELETE FROM roles WHERE id=?", array($v));
	        				$i++;	
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
	}
	
	$display["title"] = _("Roles > View");
	
	require_once ("src/append.inc.php");
?>