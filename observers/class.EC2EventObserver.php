<?php
	class EC2EventObserver extends EventObserver
	{
		public $ObserverName = 'EC2';
		
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
				
		public function OnHostUp($instanceinfo)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
						
			try
			{
				// If we need replace old instance to new one
				if ($instanceinfo["replace_iid"])
				{
					$this->Logger->debug("Going to termination old instance...");
					
					$EC2Client = $this->GetAmazonEC2ClientObject();
					
					$this->DB->Execute("UPDATE farm_instances SET replace_iid='' WHERE id='{$instanceinfo['id']}'");

					// Get information about replacement instance from database
					$old_instance = $this->DB->GetRow("SELECT * FROM farm_instances WHERE instance_id='{$instanceinfo["replace_iid"]}'");

					// Update elastic IP
					$this->DB->Execute("UPDATE elastic_ips SET state='1', instance_id='{$instanceinfo['instance_id']}' WHERE instance_id=? AND farmid=?",
						array($old_instance['instance_id'], $this->FarmID)
					);
					
					$this->Logger->debug("Old instance: {$old_instance['id']}.");

					if ($old_instance)
					{
						// Terminate old instance
						$res = $EC2Client->TerminateInstances(array($old_instance["instance_id"]));
						if ($res instanceof SoapFault)
							$this->Logger->fatal("Cannot terminate instance '{$old_instance["instance_id"]}' ({$res->faultString}). Please do it manualy.");
						else
							$this->Logger->warn("Instance '{$old_instance["instance_id"]}' has been swapped with the instance {$instanceinfo['instance_id']}");
					}
				}
			}
			catch (Exception $e)
			{
				$this->Logger->fatal($e->getMessage());
			}			
		}
		
		/**
		 * Update database when 'rebundleFail' event recieved from instance
		 *
		 * @param string $ami_id
		 * @param array $instanceinfo
		 * 
		 */
		public function OnRebundleFailed($instanceinfo)
		{
			$EC2Client = $this->GetAmazonEC2ClientObject();
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));

			if ($instanceinfo['state'] == INSTANCE_STATE::PENDING_TERMINATE)
			{
				try
    			{    				
    				$this->Logger->info("Terminating '{$instanceinfo["instance_id"]}' instance.");
    				
    				$response = $EC2Client->TerminateInstances(array($instanceinfo["instance_id"]));
    					
    				if ($response instanceof SoapFault)
    					$this->Logger->warn($response->faultstring);
    			}
    			catch (Exception $e)
    			{
    				$this->Logger->error($e->getMessage()); 
    			}
			}
		}
		
		/**
		 * Update database when 'newAMI' event recieved from instance
		 *
		 * @param string $ami_id
		 * @param array $instanceinfo
		 * 
		 */
		public function OnRebundleComplete($ami_id, $instanceinfo)
		{
			$EC2Client = $this->GetAmazonEC2ClientObject();
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));

			if ($instanceinfo['state'] == INSTANCE_STATE::PENDING_TERMINATE)
			{
				try
    			{    				
    				$this->Logger->info("Terminating '{$instanceinfo["instance_id"]}' instance.");
    				
    				$response = $EC2Client->TerminateInstances(array($instanceinfo["instance_id"]));
    					
    				if ($response instanceof SoapFault)
    					$this->Logger->warn($response->faultstring);
    			}
    			catch (Exception $e)
    			{
    				$this->Logger->error($e->getMessage()); 
    			}
			}
		}
		
		/**
		 * Launch instances on farm
		 *
		 * @param boolean $mark_instances_as_active
		 */
		public function OnFarmLaunched($mark_instances_as_active)
		{
			/* Poller will run instances. */
			/*
			$EC2Client = $this->GetAmazonEC2ClientObject();
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$amis = $this->DB->GetAll("SELECT * FROM farm_amis WHERE farmid='{$farminfo['id']}'");
	        foreach ($amis as $ami)
	        {
	            $roleinfo = $this->DB->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($ami["ami_id"]));
	            if (!$roleinfo)
	            	continue;

    		    $role = $roleinfo["name"];  
    		    $isactive = ($mark_instances_as_active) ? true : false;
    		      
				$res = Scalr::RunInstance($EC2Client, CONFIG::$SECGROUP_PREFIX.$role, $farminfo['id'], $role, $farminfo['hash'], $ami["ami_id"], false, $isactive);                        
				if (!$res)
					$this->Logger->warn("Cannot run new instance");
	        }
			*/
		}
		
		/**
		 * Terminate all running instance on farm
		 *
		 * @param boolean $remove_zone_from_DNS
		 */
		public function OnFarmTerminated($remove_zone_from_DNS, $keep_elastic_ips, $term_on_sync_fail)
		{
			$EC2Client = $this->GetAmazonEC2ClientObject();
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			if ($farminfo['status'] == FARM_STATUS::SYNCHRONIZING)
			{
				// Do not terminate pending terminate instances.
				$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state != ?", 
					array($this->FarmID, INSTANCE_STATE::PENDING_TERMINATE)
				);
			}
			else
			{
				$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=?", 
					array($this->FarmID)
				);
			}
			                    
		    if (count($instances) == 0)
		    	return;
		    	
            foreach ($instances as $instance)
            {                
                try
    			{    				
    				$this->Logger->info("Terminating '{$instance["instance_id"]}' instance. (Farm: {$instance['farmid']})");
    				
    				$response = $EC2Client->TerminateInstances(array($instance["instance_id"]));
    					
    				if ($response instanceof SoapFault)
    					$this->Logger->warn($response->faultstring);
    			}
    			catch (Exception $e)
    			{
    				$this->Logger->error($e->getMessage()); 
    			}
            }
		    
		    $this->DB->Execute("DELETE FROM farm_instances WHERE farmid=? AND state=?", array($this->FarmID, INSTANCE_STATE::PENDING));
		}
	}
?>