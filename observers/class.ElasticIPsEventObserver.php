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
			
			$DBFarmRole = $event->DBInstance->GetDBFarmRoleObject();
			
			if (!$DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_USE_ELASIC_IPS))
				return;
			
			// Check for already allocated and free elastic IP in database
			$ip = $this->DB->GetRow("SELECT * FROM elastic_ips WHERE farmid=? AND ((farm_roleid=? AND instance_index='{$event->DBInstance->Index}') OR instance_id = ?)",
				array($this->FarmID, $DBFarmRole->ID, $event->DBInstance->InstanceID)
			);
			
			$this->Logger->debug(sprintf(_("IP for replace: %s"), $ip['ipaddress']));
			
			//
			// Check IP address
			//
			if ($ip['ipaddress'])
			{
				if (!$this->CheckElasticIP($ip['ipaddress'], $farminfo))
				{
					Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage(
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
				$this->Logger->debug(sprintf(_("Farm role: %s, %s, %s"), 
					$DBFarmRole->GetRoleName(), $DBFarmRole->AMIID, $DBFarmRole->ID
				));
				
				$alocated_ips = $this->DB->GetOne("SELECT COUNT(*) FROM elastic_ips WHERE farm_roleid=?",
					array($DBFarmRole->ID)
				);
				
				$this->Logger->debug(sprintf(_("Allocated IPs: %s, MaxInstances: %s"), 
					$alocated_ips, $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES)
				));
				
				// Check elastic IPs limit. We cannot allocate more than 'Max instances' option for role
				if ($alocated_ips < $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES))
				{
					$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['region']);
					
					try
					{
						// Alocate new IP address
						$address = $EC2Client->AllocateAddress();
					}
					catch (Exception $e)
					{
						Logger::getLogger(LOG_CATEGORY::FARM)->error(new FarmLogMessage(
							$this->FarmID, 
							sprintf(_("Cannot allocate new elastic ip for instance '%s': %s"), 
								$event->DBInstance->InstanceID, 
								$e->getMessage()
							)
						));
						return;
					}
					
					// Add allocated IP address to database
					$this->DB->Execute("INSERT INTO elastic_ips SET farmid=?, farm_roleid=?, ipaddress=?, state='0', instance_id='', clientid=?, instance_index=?",
						array($this->FarmID, $DBFarmRole->ID, $address->publicIp, $farminfo['clientid'], $event->DBInstance->Index)
					);
					
					$ip['ipaddress'] = $address->publicIp;
					
					Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage(
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
							$EC2Client->AssociateAddress($event->DBInstance->InstanceID, $ip['ipaddress']);
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
					Logger::getLogger(LOG_CATEGORY::FARM)->error(new FarmLogMessage(
						$this->FarmID, 
						sprintf(_("Cannot associate elastic ip with instance: %s"), 
							$e->getMessage()
						)
					));
					return;
				}					
				
				$this->Logger->info("IP: {$ip['ipaddress']} assigned to instance '{$event->DBInstance->InstanceID}'");
				
				// Update leastic IPs table
				$this->DB->Execute("UPDATE elastic_ips SET state='1', instance_id=? WHERE ipaddress=?",
					array($event->DBInstance->InstanceID, $ip['ipaddress'])
				);
				
				// Update instance info in database
				$this->DB->Execute("UPDATE farm_instances SET external_ip=?, isipchanged='1', isactive='0' WHERE id=?",
					array($ip['ipaddress'], $event->DBInstance->ID)
				);
				
				Scalr::FireEvent($this->FarmID, new IPAddressChangedEvent($event->DBInstance, $ip['ipaddress']));
			}
			else
			{
				Logger::getLogger(LOG_CATEGORY::FARM)->fatal(new FarmLogMessage(
					$this->FarmID, 
					sprintf(_("Cannot allocate elastic ip address for instance %s on farm %s"),
						$event->DBInstance->InstanceID,
						$farminfo['name']
					)
				));
			}
		}
		
		/**
		 * Release IP address when instance terminated
		 *
		 * @param HostDownEvent $event
		 */
		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->DBInstance->IsRebootLaunched == 1)
				return;
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			try
			{
				$DBFarmRole = $event->DBInstance->GetDBFarmRoleObject();
			}
			catch(Exception $e)
			{
				//
			}
			
			if ($DBFarmRole)
			{
				// Count already allocate elastic IPS for role
				$alocated_ips = $this->DB->GetOne("SELECT COUNT(*) FROM elastic_ips WHERE farm_roleid=?",
					array($DBFarmRole->ID)
				);
			
				// If number of allocated IPs more than 'Max instances' option for role, we must release elastic IP
				if ($alocated_ips > $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES))
				{
					$ip = $this->DB->GetRow("SELECT * FROM elastic_ips WHERE instance_index=? AND farm_roleid=?", 
						array($event->DBInstance->Index, $DBFarmRole->ID)
					);
					
					if ($ip['state'] == 0)
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
							Logger::getLogger(LOG_CATEGORY::FARM)->error(new FarmLogMessage(
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
			else
			{
				$ips = $this->DB->GetAll("SELECT * FROM elastic_ips WHERE farm_roleid=?", array($event->DBInstance->FarmRoleID));
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
							Logger::getLogger(LOG_CATEGORY::FARM)->error(new FarmLogMessage(
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