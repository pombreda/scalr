<?php
	require_once('../src/prepend.inc.php');
	
	set_time_limit(0);
	
	/*
	$clients = $db->GetAll("SELECT id FROM clients WHERE isactive='1'");
	foreach ($clients as $client)
	{
		$Client = Client::Load($client['id']);
		$client_instances = array();
		
		$farms = $db->GetAll("SELECT * FROM farms WHERE clientid=?", array($Client->ID));
		
		foreach ($farms as $farminfo)
		{
			if (!$client_instances[$farminfo['region']])
			{
				try
				{
					$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region']));
					$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
				
					$client_instances[$farminfo['region']] = $AmazonEC2Client->DescribeInstances();
				}
				catch(Exception $e)
				{
					continue;
				}
			}
			
			$instances = $client_instances[$farminfo['region']]->reservationSet->item;
			if (!is_array($instances))
				$instances = array($instances);
			
			foreach ($instances as $instance)
			{
				$key_name = $instance->instancesSet->item->keyName;
				$state = $instance->instancesSet->item->instanceState->name;
				if (stristr($key_name, "FARM-"))
				{
					$instance_id = $instance->instancesSet->item->instanceId;
					$farm_id = str_replace("FARM-", "", $key_name);
					if ($farm_id == $farminfo['id'])
					{
						$chk = $db->GetRow("SELECT * FROM farm_instances WHERE farmid=? AND instance_id=?", array($farm_id, $instance_id));
						if (!$chk && $state == 'running')
						{
							if (!$AmazonEC2Client)
							{
								$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region']));
								$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
							}
							
							$AmazonEC2Client->TerminateInstances(array($instance_id));
							
							print "FarmID: {$farm_id}, InstanceID: {$instance_id}\n";
						}
					}
				}
			}
		}
	}
	*/
	
	$eips = $db->Execute("SELECT * FROM elastic_ips");
	while ($eip = $eips->FetchRow())
	{
		$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($eip['farmid']));
		
		$client = Client::Load($farminfo['clientid']);
			
		if ($client->AWSCertificate && $client->AWSPrivateKey)
		{
			try
			{
				$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region']));
				$AmazonEC2Client->SetAuthKeys($client->AWSPrivateKey, $client->AWSCertificate);
				
				
				$DescribeAddressType = new DescribeAddressesType();
				$DescribeAddressType->AddAddress($eip['ipaddress']);
				$info = $AmazonEC2Client->DescribeAddresses($DescribeAddressType);
				print "{$eip['ipaddress']}: OK\n";
			}
			catch(Exception $e)
			{
				print "{$eip['ipaddress']}: Error ({$e->getMessage()})\n";
				if (stristr($e->getMessage(), "not found"))
				{
					$db->Execute("DELETE FROM elastic_ips WHERE id=?", array($eip['id']));
				}
				continue;
			}
		}
	}
	
	/*s
	$ebss = $db->Execute("SELECT * FROM farm_ebs");
	while ($ebs = $ebss->FetchRow())
	{
		if ($ebs['farmid'])
		{
			$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($ebs['farmid']));
			
			if ($farminfo)
			{
				try
				{
					$client = Client::Load($farminfo['clientid']);
						
					if ($client->AWSCertificate && $client->AWSPrivateKey)
					{
					
						$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region']));
						$AmazonEC2Client->SetAuthKeys($client->AWSPrivateKey, $client->AWSCertificate);
						
						$info = $AmazonEC2Client->DescribeVolumes($ebs['volumeid']);
						print "{$ebs['volumeid']}: OK\n";
					}
				}
				catch(Exception $e)
				{
					print "{$ebs['volumeid']}: Error ({$e->getMessage()})\n";
					if (stristr($e->getMessage(), "does not exist") || stristr($e->getMessage(), " not found in database"))
					{
						$db->Execute("DELETE FROM farm_ebs WHERE id=?", array($ebs['id']));
					}
					continue;
				}
			}
		}
	}
	*/
?>