<?
	require_once('src/prepend.inc.php');

	$instance_info = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($req_iid));
	
	if ($_SESSION["uid"] != 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($instance_info['farmid'], $_SESSION["uid"]));
    else
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($instance_info['farmid']));
        
	if (!$farminfo)
	{
	    $errmsg = _("Farm not found");
	    UI::Redirect("farms_view.php");
	}
	
	$Client = Client::Load($farminfo['clientid']);
	
    $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region'])); 
	$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
	
    if ($post_cancel)
    	UI::Redirect("instances_view.php?farmid={$farminfo['id']}");
    
    if ($req_task == "unassign")
    {
    	if ($_POST)
    	{
    		try
    		{
    			if (!$post_releaseaddress)
    				$AmazonEC2Client->DisassociateAddress($instance_info['external_ip']);
    			else
    				$AmazonEC2Client->ReleaseAddress($instance_info['external_ip']);
    			
    		}
    		catch(Exception $e)
    		{
    			$errmsg = sprintf(_("Cannot unassign elastic IP: %s"), $e->getMessage());
    		}
    		
    		if (!$errmsg)
    		{
    			$db->Execute("UPDATE farm_instances SET isipchanged='1', isactive='0', custom_elastic_ip=? WHERE id=?",
					array("", $instance_info['id'])
				);
				
				$okmsg = sprintf(_("Elastic IP is now unassigned from instance %s. New IP will be assigned to it shortly."), $instance_info['instance_id']);
				UI::Redirect("instances_view.php?farmid={$farminfo['id']}");
    		}
    	}
    }
	elseif ($req_task == "assign")
	{		
		if ($post_assigntype)
		{
			// Assign already allocated IP address
			if ($post_assigntype == 1)
			{
				$ip_address = $post_eip;				
			}
			// Allocate and assign new address
			elseif ($post_assigntype == 2)
			{
				try
				{
					// Alocate new IP address
					$address = $AmazonEC2Client->AllocateAddress();
				}
				catch (Exception $e)
				{
					$errmsg = sprintf(_("Cannot allocate new elastic IP address: %s"), $e->getMessage());
				}
				
				if ($address)
				{
					// wait few seconds before we can associate ip with instance.
					sleep(5);
					$ip_address = $address->publicIp;
				}
			}
			
			if ($ip_address)
			{
				try
				{
					$assign_retries = 1;
					while (true)
					{
						try
						{
							// Associate elastic ip address with instance
							$AmazonEC2Client->AssociateAddress($req_iid, $ip_address);
							$assigned_ip = $ip_address;
						}
						catch(Exception $e)
						{
							if (!stristr($e->getMessage(), "does not belong to you") || $assign_retries == 3)
								throw new Exception($e->getMessage());
							else
							{
								// Waiting...
								$Logger->debug("Waiting 2 seconds...");
								sleep(2);
								$assign_retries++;
								continue;
							}
						}
						
						break;
					}
				}
				catch(Exception $e)
				{
					$errmsg = sprintf(_("Cannot associate with instance specified Elastic IP: %s"), $e->getMessage());
				}
			}
			
			if ($assigned_ip)
			{
				$db->Execute("UPDATE farm_instances SET external_ip=?, isipchanged='1', isactive='0', custom_elastic_ip=? WHERE id=?",
					array($assigned_ip, $assigned_ip, $instance_info['id'])
				);
				
				$okmsg = _("Elastic IP successfully associated with instance");
				UI::Redirect("instances_view.php?farmid={$farminfo['id']}");
			}
		}
		
		// Get list of all elastic ips
		$result = $AmazonEC2Client->DescribeAddresses();
		$ips = $result->addressesSet->item;
		if ($ips instanceof stdClass)
			$ips = array($ips);
			 
		foreach ($ips as $address)
		{
			// Exclude ips used by scalr
			if (!$address->instanceId)
			{
				$check = $db->GetOne("SELECT id FROM elastic_ips WHERE ipaddress=?", array($address->publicIp));
				if (!$check)
					$display["ips"][] = $address->publicIp; 
			}
		}
	}
	
	$display["iid"] = $req_iid;
	$display["task"] = $req_task;
	$display["ipaddr"] = $instance_info['external_ip'];
	
	require_once ("src/append.inc.php");
?>