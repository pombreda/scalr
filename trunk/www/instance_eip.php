<?
	require_once('src/prepend.inc.php');

	$instance_info = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($req_iid));
	
	if ($_SESSION["uid"] != 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($instance_info['farmid'], $_SESSION["uid"]));
    else
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($instance_info['farmid']));
        
	if (!$farminfo)
	{
	    $errmsg = "Farm not found";
	    UI::Redirect("farms_view.php");
	}
	
	if ($_SESSION['uid'] == 0)
    {
    	$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($farminfo['clientid']));
	
		// Decrypt client prvate key and certificate
    	$private_key = $Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
    	$certificate = $Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
    }
    else
    {
    	$private_key = $_SESSION["aws_private_key"];
    	$certificate = $_SESSION["aws_certificate"];
    }
	
    $AmazonEC2Client = new AmazonEC2($private_key, $certificate);
	
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
    			$errmsg = "Cannot unassign elastic IP: {$e->getMessage()}";
    		}
    		
    		if (!$errmsg)
    		{
    			$db->Execute("UPDATE farm_instances SET isipchanged='1', isactive='0', custom_elastic_ip=? WHERE id=?",
					array("", $instance_info['id'])
				);
				
				$okmsg = "Elastic IP is now unassigned from instance {$instance_info['instance_id']}. New IP will be assigned to it shortly.";
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
					$errmsg = "Cannot allocate new elastic IP address: {$e->getMessage()}";
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
					$errmsg = "Cannot associate with instance specified Elastic IP: {$e->getMessage()}";
				}
			}
			
			if ($assigned_ip)
			{
				$db->Execute("UPDATE farm_instances SET external_ip=?, isipchanged='1', isactive='0', custom_elastic_ip=? WHERE id=?",
					array($assigned_ip, $assigned_ip, $instance_info['id'])
				);
				
				$okmsg = "Elastic IP successfully associated with instance";
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