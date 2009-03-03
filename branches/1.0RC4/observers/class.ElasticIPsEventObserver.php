<?php
	class ElasticIPsEventObserver extends EventObserver
	{
		public $ObserverName = 'Elastic IPs';
		
		function __construct()
		{
			parent::__construct();
			
			$this->Crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
		}

		/**
		 * Return new instance of AmazonEC2 object
		 *
		 * @return AmazonEC2
		 */
		private function GetAmazonEC2ClientObject($region)
		{
	    	// Get ClientID from database;
			$clientid = $this->DB->GetOne("SELECT clientid FROM farms WHERE id=?", array($this->FarmID));
			
			// Get Client Object
			$Client = Client::Load($clientid);
	
	    	// Return new instance of AmazonEC2 object
			$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region)); 
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
			
			return $AmazonEC2Client;
		}
		
		/**
		 * Release used elastic IPs if farm terminated
		 *
		 * @param FarmTerminatedEvent $event
		 */
		public function OnFarmTerminated(FarmTerminatedEvent $event)
		{
			$this->Logger->info(sprintf(_("Keep elastic IPs: %s"), $event->KeepElasticIPs));
			
			if ($event->KeepElasticIPs == 1)
				return;
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$ips = $this->DB->GetAll("SELECT * FROM elastic_ips WHERE farmid=?", array($this->FarmID));
			if (count($ips) > 0)
			{
				$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['region']);
				foreach ($ips as $ip)
				{
					try
					{
						$EC2Client->ReleaseAddress($ip["ipaddress"]);
					}
					catch(Exception $e)
					{						
						if (!stristr($e->getMessage(), "does not belong to you"))
						{
							$this->Logger->error(sprintf(_("Cannot release elastic IP %s from farm %s: %s"),
								$ip['ipaddress'], $farminfo['name'], $e->getMessage()
							));
							continue;
						}
					}
					
					$this->DB->Execute("DELETE FROM elastic_ips WHERE ipaddress=?", array($ip['ipaddress']));
				}
			}
		}
		
		/**
		 * Check Elastic IP availability
		 * 
		 */
		private function CheckElasticIP($ipaddress, $farminfo)
		{
			$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['region']);
			
			$this->Logger->debug(sprintf(_("Checking IP: %s"), $ipaddress));
			
			$DescribeAddressesType = new DescribeAddressesType();
			$DescribeAddressesType->AddAddress($ipaddress);
			
			try
			{
				$info = $EC2Client->DescribeAddresses($DescribeAddressesType);
				if ($info && $info->addressesSet->item)
					return true;
				else
					return false;
			}
			catch(Exception $e)
			{
				return false;
			}
		}
		
		/**
		 * Allocate and Assign Elastic IP to instance if role use it.
		 *
		 * @param HostUpEvent $event
		 */
		public function OnHostUp(HostUpEvent $event)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$farm_role_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND (ami_id=? OR replace_to_ami=?)",
				array($this->FarmID, $event->InstanceInfo['ami_id'], $event->InstanceInfo['ami_id'])
			);
			
			$farm_role_info['name'] = $this->DB->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($farm_role_info['ami_id']));
			
			if (!$farm_role_info['use_elastic_ips'])
				return;
			
			// Check for already allocated and free elastic IP in database
			$ip = $this->DB->GetRow("SELECT * FROM elastic_ips WHERE farmid=? AND ((state = '0' AND role_name=?) OR instance_id = ?)",
				array($this->FarmID, $farm_role_info['name'], $event->InstanceInfo["instance_id"])
			);
			
			$this->Logger->debug(sprintf(_("IP for replace: %s"), $ip['ipaddress']));
			
			//
			// Check IP address
			//
			if ($ip['ipaddress'])
			{
				if (!$this->CheckElasticIP($ip['ipaddress'], $farminfo))
				{
					$this->Logger->warn(new FarmLogMessage(
						$this->FarmID, 
						sprintf(_("Elastic IP '%s' does not belong to you. Allocating new one."), 
							$ip['ipaddress']
						)
					));
					
					$this->DB->Execute("DELETE FROM elastic_ips WHERE ipaddress=?", array($ip['ipaddress']));
					
					$ip = false;
				}
			}
			
			// If free IP not found we mus allocate new IP
			if (!$ip)
			{				
				$this->Logger->debug(sprintf(_("Farm role: %s, %s, %s"), $farm_role_info['name'], $farm_role_info['ami_id'], $farm_role_info['id']));
				
				if ($farm_role_info['use_elastic_ips'])
				{
					$alocated_ips = $this->DB->GetOne("SELECT COUNT(*) FROM elastic_ips WHERE farmid=? AND role_name=?",
						array($this->FarmID, $farm_role_info['name'])
					);
					
					$this->Logger->debug(sprintf(_("Allocated IPs: %s, MaxInstances: %s"), $alocated_ips, $farm_role_info['max_count']));
					
					// Check elastic IPs limit. We cannot allocate more than 'Max instances' option for role
					if ($alocated_ips < $farm_role_info['max_count'])
					{
						$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['region']);
						
						try
						{
							// Alocate new IP address
							$address = $EC2Client->AllocateAddress();
						}
						catch (Exception $e)
						{
							$this->Logger->error(new FarmLogMessage(
								$this->FarmID, 
								sprintf(_("Cannot allocate new elastic ip for instance '%s': %s"), 
									$event->InstanceInfo['instance_id'], 
									$e->getMessage()
								)
							));
							return;
						}
						
						// Add allocated IP address to database
						$this->DB->Execute("INSERT INTO elastic_ips SET farmid=?, role_name=?, ipaddress=?, state='0', instance_id='', clientid=?",
							array($this->FarmID, $farm_role_info['name'], $address->publicIp, $farminfo['clientid'])
						);
						
						$ip['ipaddress'] = $address->publicIp;
						
						$this->Logger->info(new FarmLogMessage(
							$this->FarmID, 
							sprintf(_("Allocated new IP: %s"), 
								$ip['ipaddress']
							)
						));
						
						// Waiting...
						$this->Logger->debug(_("Waiting 5 seconds..."));
						sleep(5);
					}
					else
						$this->Logger->fatal(_("Limit for elastic IPs reached. Check zomby records in database."));
				}
			}
			
			// If we have ip address
			if ($ip['ipaddress'])
			{
				if (!$EC2Client)
					$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['region']);
					
				$assign_retries = 1;
				try
				{
					while (true)
					{
						try
						{
							// Associate elastic ip address with instance
							$EC2Client->AssociateAddress($event->InstanceInfo['instance_id'], $ip['ipaddress']);
						}
						catch(Exception $e)
						{
							if (!stristr($e->getMessage(), "does not belong to you") || $assign_retries == 3)
								throw new Exception($e->getMessage());
							else
							{
								// Waiting...
								$this->Logger->debug(_("Waiting 2 seconds..."));
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
					$this->Logger->error(new FarmLogMessage(
						$this->FarmID, 
						sprintf(_("Cannot associate elastic ip with instance: %s"), 
							$e->getMessage()
						)
					));
					return;
				}					
				
				$this->Logger->info("IP: {$ip['ipaddress']} assigned to instance '{$event->InstanceInfo['instance_id']}'");
				
				// Update leastic IPs table
				$this->DB->Execute("UPDATE elastic_ips SET state='1', instance_id=? WHERE ipaddress=?",
					array($event->InstanceInfo['instance_id'], $ip['ipaddress'])
				);
				
				// Update instance info in database
				$this->DB->Execute("UPDATE farm_instances SET external_ip=?, isipchanged='1', isactive='0' WHERE instance_id=?",
					array($ip['ipaddress'], $event->InstanceInfo['instance_id'])
				);
			}
			elseif ($farm_role_info['use_elastic_ips'])
				$this->Logger->fatal(new FarmLogMessage(
					$this->FarmID, 
					sprintf(_("Cannot allocate elastic ip address fro instance %s on farm %s"),
						$event->InstanceInfo['instance_id'],
						$farminfo['name']
					)
				));
		}
		
		/**
		 * Release IP address when instance terminated
		 *
		 * @param HostDownEvent $event
		 */
		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->InstanceInfo['isrebootlaunched'] == 1)
				return;
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$farm_role_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND (ami_id=? OR replace_to_ami=?)",
				array($this->FarmID, $event->InstanceInfo['ami_id'], $event->InstanceInfo['ami_id'])
			);
			
			if ($farm_role_info)
			{
				$farm_role_info['name'] = $this->DB->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($farm_role_info['ami_id']));
				
				// Count already allocate elastic IPS for role
				$alocated_ips = $this->DB->GetOne("SELECT COUNT(*) FROM elastic_ips WHERE farmid=? AND role_name=?",
					array($this->FarmID, $farm_role_info['name'])
				);
			
				// If number of allocated IPs more than 'Max instances' option for role, we must release elastic IP
				if ($alocated_ips > $farm_role_info['max_count'])
				{
					$ip = $this->DB->GetRow("SELECT * FROM elastic_ips WHERE state='0' AND farmid=? AND role_name=?", array($this->FarmID, $farm_role_info['name']));
					
					try
					{
						$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['region']);
						$EC2Client->ReleaseAddress($ip['ipaddress']);
						
						$this->DB->Execute("DELETE FROM elastic_ips WHERE ipaddress=?", array($ip['ipaddress']));
						
						$this->Logger->warn(sprintf(_("Unused elastic IP address: %s released."), $ip['ipaddress']));
					}
					catch(Exception $e)
					{
						$this->Logger->error(new FarmLogMessage(
							$this->FarmID, 
							sprintf(_("Cannot release unused elastic ip: %s"),
								$e->getMessage()
							)
						));
						return;
					}
				}
			}
			else
			{
				$ips = $this->DB->GetAll("SELECT * FROM elastic_ips WHERE farmid=? AND role_name=?", array($this->FarmID, $event->InstanceInfo['role_name']));
				foreach ($ips as $ip)
				{
					if ($ip['ipaddress'])
					{
						try
						{
							$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['region']);
							$EC2Client->ReleaseAddress($ip['ipaddress']);
							
							$this->DB->Execute("DELETE FROM elastic_ips WHERE ipaddress=?", array($ip['ipaddress']));
							
							$this->Logger->warn(sprintf(_("Unused elastic IP address: %s released."), $ip['ipaddress']));
						}
						catch(Exception $e)
						{
							$this->Logger->error(new FarmLogMessage(
								$this->FarmID, 
								sprintf(_("Cannot release unused elastic ip: %s"),
									$e->getMessage()
								)
							));
							return;
						}
					}
				}
			}
		}
	}
?>