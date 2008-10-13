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
		private function GetAmazonEC2ClientObject()
		{
	    	// Get clientinfo from database;
			$clientid = $this->DB->GetOne("SELECT clientid FROM farms WHERE id=?", array($this->FarmID));
			$clientinfo = $this->DB->GetRow("SELECT * FROM clients WHERE id=?", array($clientid));
			
			// Decrypt admin master password
	    	$cpwd = $this->Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
	    	
			// Decrypt client prvate key and certificate
	    	$private_key = $this->Crypto->Decrypt($clientinfo["aws_private_key_enc"], $cpwd);
	    	$certificate = $this->Crypto->Decrypt($clientinfo["aws_certificate_enc"], $cpwd);
	
	    	// Return new instance of AmazonEC2 object
			return new AmazonEC2($private_key, $certificate);
		}
		
		/**
		 * Release used elastic IPs if farm terminated
		 *
		 * @param bool $remove_zone_from_DNS
		 * @param bool $keep_elastic_ips
		 */
		public function OnFarmTerminated($remove_zone_from_DNS, $keep_elastic_ips, $term_on_sync_fail)
		{
			$this->Logger->info("Keep elastic IPs: {$keep_elastic_ips}");
			
			if ($keep_elastic_ips == 1)
				return;
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$ips = $this->DB->GetAll("SELECT * FROM elastic_ips WHERE farmid=?", array($this->FarmID));
			if (count($ips) > 0)
			{
				$EC2Client = $this->GetAmazonEC2ClientObject();
				foreach ($ips as $ip)
				{
					try
					{
						$EC2Client->ReleaseAddress($ip["ipaddress"]);
					}
					catch(Exception $e)
					{
						$this->Logger->error("Cannot release elastic IP {$ip['ipaddress']} from farm {$farminfo['name']}: {$e->getMessage()}");
						continue;
					}
					
					$this->DB->Execute("DELETE FROM elastic_ips WHERE ipaddress=?", array($ip['ipaddress']));
				}
			}
		}
		
		/**
		 * Allocate and Assign Elastic IP to instance if role use it.
		 *
		 * @param array $instanceinfo
		 */
		public function OnHostUp($instanceinfo)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$farm_role_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND (ami_id=? OR replace_to_ami=?)",
				array($this->FarmID, $instanceinfo['ami_id'], $instanceinfo['ami_id'])
			);
			
			if (!$farm_role_info['use_elastic_ips'])
				return;
			
			// Check for already allocated and free elastic IP in database
			$ip = $this->DB->GetRow("SELECT * FROM elastic_ips WHERE farmid=? AND (state = '0' OR instance_id = ?)",
				array($this->FarmID, $instanceinfo["instance_id"])
			);
			
			$this->Logger->debug("IP for replace: {$ip['ipaddress']}");
			
			// If free IP not found we mus allocate new IP
			if (!$ip)
			{
				$farm_role_info['name'] = $this->DB->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($farm_role_info['ami_id']));
				
				$this->Logger->debug("Farm role: {$farm_role_info['name']}, {$farm_role_info['ami_id']}, {$farm_role_info['id']}");
				
				if ($farm_role_info['use_elastic_ips'])
				{
					$alocated_ips = $this->DB->GetOne("SELECT COUNT(*) FROM elastic_ips WHERE farmid=? AND role_name=?",
						array($this->FarmID, $farm_role_info['name'])
					);
					
					$this->Logger->debug("Allocated IPs: {$alocated_ips}, MaxInstances: {$farm_role_info['max_count']}");
					
					// Check elastic IPs limit. We cannot allocate more than 'Max instances' option for role
					if ($alocated_ips < $farm_role_info['max_count'])
					{
						$EC2Client = $this->GetAmazonEC2ClientObject();
						
						try
						{
							// Alocate new IP address
							$address = $EC2Client->AllocateAddress();
						}
						catch (Exception $e)
						{
							$this->Logger->error(new FarmLogMessage($this->FarmID, "Cannot allocate new elastic ip for instance '{$instanceinfo['instance_id']}': {$e->getMessage()}"));
							return;
						}
						
						// Add allocated IP address to database
						$this->DB->Execute("INSERT INTO elastic_ips SET farmid=?, role_name=?, ipaddress=?, state='0', instance_id='', clientid=?",
							array($this->FarmID, $farm_role_info['name'], $address->publicIp, $farminfo['clientid'])
						);
						
						$ip['ipaddress'] = $address->publicIp;
						
						$this->Logger->debug("Allocated new IP: {$ip['ipaddress']}");
						
						// Waiting...
						$this->Logger->debug("Waiting 5 seconds...");
						sleep(5);
					}
					else
						$this->Logger->fatal("Limit for elastic IPs reached. Check zomby records in database.");
				}
			}
			
			// If we have ip address
			if ($ip['ipaddress'])
			{
				if (!$EC2Client)
					$EC2Client = $this->GetAmazonEC2ClientObject();
					
				$assign_retries = 1;
				try
				{
					while (true)
					{
						try
						{
							// Associate elastic ip address with instance
							$EC2Client->AssociateAddress($instanceinfo['instance_id'], $ip['ipaddress']);
						}
						catch(Exception $e)
						{
							if (!stristr($e->getMessage(), "does not belong to you") || $assign_retries == 3)
								throw new Exception($e->getMessage());
							else
							{
								// Waiting...
								$this->Logger->debug("Waiting 2 seconds...");
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
					$this->Logger->error(new FarmLogMessage($this->FarmID, "Cannot associate elastic ip with instance: {$e->getMessage()}"));
					return;
				}					
				
				$this->Logger->debug("IP: {$ip['ipaddress']} assigned to instance '{$instanceinfo['instance_id']}'");
				
				// Update leastic IPs table
				$this->DB->Execute("UPDATE elastic_ips SET state='1', instance_id=? WHERE ipaddress=?",
					array($instanceinfo['instance_id'], $ip['ipaddress'])
				);
				
				// Update instance info in database
				$this->DB->Execute("UPDATE farm_instances SET external_ip=?, isipchanged='1', isactive='0' WHERE instance_id=?",
					array($ip['ipaddress'], $instanceinfo['instance_id'])
				);
			}
			elseif ($farm_role_info['use_elastic_ips'])
				$this->Logger->fatal(new FarmLogMessage($this->FarmID, "Cannot allocate elastic ip address fro instance {$instanceinfo['instance_id']} on farm {$farminfo['name']}"));
		}
		
		/**
		 * Release IP address when instance terminated
		 *
		 * @param array $instanceinfo
		 */
		public function OnHostDown($instanceinfo)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$farm_role_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND (ami_id=? OR replace_to_ami=?)",
				array($this->FarmID, $instanceinfo['ami_id'], $instanceinfo['ami_id'])
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
						$EC2Client = $this->GetAmazonEC2ClientObject();
						$EC2Client->ReleaseAddress($ip['ipaddress']);
						
						$this->DB->Execute("DELETE FROM elastic_ips WHERE ipaddress=?", array($ip['ipaddress']));
						
						$this->Logger->warn("Unused elastic IP address: {$ip['ipaddress']} released.");
					}
					catch(Exception $e)
					{
						$this->Logger->error(new FarmLogMessage($this->FarmID, "Cannot release unused elastic ip: {$e->getMessage()}"));
						return;
					}
				}
			}
			else
			{
				$ips = $this->DB->GetAll("SELECT * FROM elastic_ips WHERE farmid=? AND role_name=?", array($this->FarmID, $instanceinfo['role_name']));
				foreach ($ips as $ip)
				{
					if ($ip['ipaddress'])
					{
						try
						{
							$EC2Client = $this->GetAmazonEC2ClientObject();
							$EC2Client->ReleaseAddress($ip['ipaddress']);
							
							$this->DB->Execute("DELETE FROM elastic_ips WHERE ipaddress=?", array($ip['ipaddress']));
							
							$this->Logger->warn("Unused elastic IP address: {$ip['ipaddress']} released.");
						}
						catch(Exception $e)
						{
							$this->Logger->error(new FarmLogMessage($this->FarmID, "Cannot release unused elastic ip: {$e->getMessage()}"));
							return;
						}
					}
				}
			}
		}
	}
?>