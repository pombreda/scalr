<?php
	class EBSEventObserver extends EventObserver
	{
		public $ObserverName = 'Elastic Block Storage';
		
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
	    	$clientid = $this->DB->GetOne("SELECT clientid FROM farms WHERE id=?", array($this->FarmID));
			
			// Get Client Object
			$Client = Client::Load($clientid);
	
	    	// Return new instance of AmazonEC2 object
			return new AmazonEC2($Client->AWSPrivateKey, $Client->AWSCertificate);
		}
		
		/**
		 * 
		 *
		 * @param FarmTerminatedEvent $event
		 */
		public function OnFarmTerminated(FarmTerminatedEvent $event)
		{
			$this->Logger->info("Keep EBS volumes: {$event->KeepEBS}");
			
			if ($event->KeepEBS == 1)
				return;
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$ebs = $this->DB->GetAll("SELECT * FROM farm_ebs WHERE farmid=?", array($this->FarmID));
			if (count($ebs) > 0)
			{
				$EC2Client = $this->GetAmazonEC2ClientObject();
				
				foreach ($ebs as $volume)
				{
					if ($volume['instance_id'])
					{
						try
						{						
							$DetachVolumeType = new DetachVolumeType($volume['volumeid']);
							$EC2Client->DetachVolume($DetachVolumeType);
						}
						catch(Exception $e)
						{
							$this->Logger->error(sprintf(_("Cannot detach EBS volume '%s' from instance '%s' during farm termination."), 
								$volume['volumeid'], $volume['instance_id'])
							);
						}
						
						$this->DB->Execute("UPDATE farm_ebs SET state=? WHERE id=?", array(FARM_EBS_STATE::DETACHING, $volume['id']));
					}
					
					TaskQueue::Attach(QUEUE_NAME::EBS_DELETE)->AppendTask(new EBSDeleteTask($volume['volumeid']));
				}
			}
		}
		
		/**
		 * 
		 *
		 * @param HostInitEvent $event
		 */
		public function OnHostInit(HostInitEvent $event)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			$farm_role_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND (ami_id=? OR replace_to_ami=?)", 
				array($this->FarmID, $event->InstanceInfo["ami_id"], $event->InstanceInfo["ami_id"])
			);
			$farm_role_info['name'] = $this->DB->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($farm_role_info['ami_id']));
			
			$event->InstanceInfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?", array($event->InstanceInfo['id']));
			
			if (!$farm_role_info['use_ebs'])
				return;
				
			// Check for free role EBS volumes
			$free_ebs = $this->DB->GetRow("SELECT * FROM farm_ebs WHERE farmid=? AND role_name=? AND state=? AND avail_zone=?",
				array($this->FarmID, $farm_role_info['name'], FARM_EBS_STATE::AVAILABLE, $event->InstanceInfo['avail_zone'])
			);
			
			$EC2Client = $this->GetAmazonEC2ClientObject();
			
			if (!$free_ebs && $event->InstanceInfo['replace_iid'])
			{				
				$ebs = $this->DB->GetRow("SELECT * FROM farm_ebs WHERE instance_id=?", 
					array($event->InstanceInfo['replace_iid'])
				);
				
				if ($ebs['avail_zone'] == $event->InstanceInfo['avail_zone'])
				{
					$this->Logger->info(sprintf(_("Found attached EBS '%s' on replacement instance %s"), $ebs['volumeid'], $ebs['instance_id']));
					
					try
					{
						$info = $EC2Client->DescribeVolumes($ebs['volumeid']);
						
						$this->Logger->info(sprintf(_("Volume '%s' state: %s"), 
							$ebs['volumeid'], $info->volumeSet->item->status)
						);
						
						if ($info->volumeSet->item->status == AMAZON_EBS_STATE::IN_USE)
						{
							$this->Logger->info(sprintf(_("Detaching EBS '%s' from the instance %s"), 
								$ebs['volumeid'], $ebs['instance_id'])
							);
							
							$DetachVolumeType = new DetachVolumeType($ebs['volumeid']);
							$EC2Client->DetachVolume($DetachVolumeType);
							
							$this->Logger->info(new FarmLogMessage(
								$this->FarmID, 
								sprintf(_("Detaching volume: %s from the instance %s"),
									$ebs['volumeid'],
									$ebs['instance_id']
								)
							));
							
							$this->DB->Execute("UPDATE farm_ebs SET state=?, instance_id=? WHERE volumeid=?",
								array(FARM_EBS_STATE::CREATING, $event->InstanceInfo['instance_id'], $ebs['volumeid'])
							);
							
							// Add task to queue for EBS volume status check
							TaskQueue::Attach(QUEUE_NAME::EBS_STATE_CHECK)->AppendTask(new CheckEBSVolumeStateTask($ebs['volumeid']));
							
							return true;
						}
						elseif ($info->volumeSet->item->status == AMAZON_EBS_STATE::AVAILABLE)
						{
							$this->DB->Execute("UPDATE farm_ebs SET state=?, instance_id='' WHERE volumeid=?",
								array(FARM_EBS_STATE::AVAILABLE, $ebs['volumeid'])
							);
							
							$free_ebs = $this->DB->GetRow("SELECT * FROM farm_ebs WHERE volumeid=?",
								array($ebs['volumeid'])
							);
						}
					}
					catch(Exception $e)
					{
						$this->Logger->fatal(sprintf(_("Cannot detach volume %s from instance %s: %s"), 
							$ebs['volumeid'], $ebs['instance_id'], $e->getMessage())
						);
					}
				}
			}
			
						
			if ($free_ebs)
			{
				// Attach free EBS volume
				$this->Logger->info(new FarmLogMessage(
					$this->FarmID, 
					sprintf(_("Found free EBS volume created for role %s. VolumeID: %s. Attaching it to the instance %s"),
						$farm_role_info['name'], $free_ebs['volumeid'], $event->InstanceInfo['instance_id']
					)
				));
				
				try
				{
					Scalr::AttachEBS2Instance($EC2Client, $event->InstanceInfo, $farminfo, $free_ebs['volumeid']);
				}
				catch(Exception $e)
				{
					$this->Logger->error(new FarmLogMessage(
						$this->FarmID, 
						sprintf(_("Cannot attach volume: %s"), $e->getMessage())
					));
					return;
				}
			}
			else
			{
				$this->Logger->info(new FarmLogMessage(
					$this->FarmID, 
					sprintf(_("There is no free EBS volumes for role %s in availability zone %s. Creating new one."),
						$farm_role_info['name'], $event->InstanceInfo['avail_zone']	
					)
				));
				
				// Create new EBS volume and then attach it
				$CreateVolumeType = new CreateVolumeType();
		    	if ($farm_role_info['ebs_snapid'])
		    		$CreateVolumeType->snapshotId = $farm_role_info['ebs_snapid'];
		    	else
		    		$CreateVolumeType->size = $farm_role_info['ebs_size'];
		    		 
		    	$CreateVolumeType->availabilityZone = $event->InstanceInfo['avail_zone'];
		    	
		    	try
		    	{
		    		$result = $EC2Client->CreateVolume($CreateVolumeType);
		    		if ($result->volumeId)
		    		{
		    			$this->DB->Execute("INSERT INTO farm_ebs SET
		    				farmid		= ?,
		    				role_name	= ?,
		    				volumeid	= ?,
		    				state		= ?,
		    				instance_id	= ?,
		    				avail_zone	= ?
		    			", array(
		    				$this->FarmID,
		    				$farm_role_info['name'],
		    				$result->volumeId,
		    				FARM_EBS_STATE::CREATING,
		    				$event->InstanceInfo['instance_id'],
		    				$event->InstanceInfo['avail_zone']
		    			));
		    			
		    			$this->Logger->info(new FarmLogMessage(
							$this->FarmID, 
							sprintf(_("Volume creation initialized. VolumeID: %s. Volume will be attached to the instance when creation process complete."),
								$result->volumeId	
							)
						));
						
						// Add task to queue for EBS volume status check
						TaskQueue::Attach(QUEUE_NAME::EBS_STATE_CHECK)->AppendTask(new CheckEBSVolumeStateTask($result->volumeId));
		    		}
		    		else
		    		{
		    			$this->Logger->error(new FarmLogMessage(
							$this->FarmID, 
							_("Volume creation failed. Unexpected error.")
						));
		    		}
		    	}
		    	catch(Exception $e)
		    	{
		    		$this->Logger->fatal(new FarmLogMessage(
						$this->FarmID, 
						sprintf(_("Volume creation failed: %s"), $e->getMessage())
					));
		    	}
			}
		}
		
		/**
		 * 
		 *
		 * @param HostDownEvent $event
		 */
		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->InstanceInfo['isrebootlaunched'] == 1 || $event->InstanceInfo['skip_ebs_observer'])
				return;
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			$farm_role_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND (ami_id=? OR replace_to_ami=?)", 
				array($this->FarmID, $event->InstanceInfo["ami_id"], $event->InstanceInfo["ami_id"])
			);
			
			$EC2Client = $this->GetAmazonEC2ClientObject();
			
			// If role exists in farm roles list
			if ($farm_role_info)
			{
				// Get role name
				$farm_role_info['name'] = $this->DB->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($farm_role_info['ami_id']));
				
				if (!$farm_role_info['use_ebs'])
					return;
					
				// Get EBS attached to terminated instance
				$ebs = $this->DB->GetRow("SELECT * FROM farm_ebs WHERE instance_id=?", 
					array($event->InstanceInfo['instance_id'])
				);
				
				$farm_ebs = $this->DB->GetOne("SELECT COUNT(*) FROM farm_ebs WHERE farmid=? AND state != ?",
					array($this->FarmID, FARM_EBS_STATE::DETACHING)
				);
				
				if ($farm_ebs > $farm_role_info['max_count'])
					$need_delete = true;
				
				if ($ebs)
				{
					// Delete unused EBS
					if ($need_delete)
					{
						$this->Logger->info(sprintf(_("Added new EBS delete task to queue (VolumeID: %s)"), 
							$ebs['volumeid'])
						);
						
						TaskQueue::Attach(QUEUE_NAME::EBS_DELETE)->AppendTask(new EBSDeleteTask($ebs['volumeid']));
					}
					else
					{
						// Mark EBS as available 
						if ($ebs['state'] != FARM_EBS_STATE::DETACHING)
						{
							$this->DB->Execute("UPDATE farm_ebs SET state=?, instance_id='' WHERE id=?", 
								array(FARM_EBS_STATE::AVAILABLE, $ebs['id'])
							);
							
							$this->Logger->info(new FarmLogMessage(
								$this->FarmID, 
								sprintf(_("Volume %s successfully detached from instance %s"), $ebs['volumeid'], $ebs['instance_id'])
							));
						}
					}
				}
			}
			else
			{
				$volumes = $this->DB->GetAll("SELECT * FROM farm_ebs WHERE farmid=? AND role_name=?",
					array($this->FarmID, $event->InstanceInfo['role_name'])
				);
				
				if (count($volumes) == 0)
					return;
					
				foreach ($volumes as $ebs)
				{
					$this->Logger->info(sprintf(_("Added new EBS delete task to queue (VolumeID: %s)"), 
						$ebs['volumeid'])
					);
						
					TaskQueue::Attach(QUEUE_NAME::EBS_DELETE)->AppendTask(new EBSDeleteTask($ebs['volumeid']));
				}
			}
		}
	}
?>