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
		private function GetAmazonEC2ClientObject($region)
		{
	    	$clientid = $this->DB->GetOne("SELECT clientid FROM farms WHERE id=?", array($this->FarmID));
			
			// Get Client Object
			$Client = Client::Load($clientid);
	
	    	// Return new instance of AmazonEC2 object
			$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region)); 
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
			
			return $AmazonEC2Client;
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
			
			$ebs_volumes = $this->DB->GetAll("SELECT volumeid FROM farm_ebs WHERE farmid=? AND ismanual='0'", array($this->FarmID));
			if (count($ebs_volumes) > 0)
			{
				$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['region']);
				
				foreach ($ebs_volumes as $volume)
				{
					$DBEBSVolume = DBEBSVolume::Load($volume['volumeid']);
					
					if ($DBEBSVolume->InstanceID)
					{
						try
						{						
							$DetachVolumeType = new DetachVolumeType($DBEBSVolume->VolumeID);
							$EC2Client->DetachVolume($DetachVolumeType);
						}
						catch(Exception $e)
						{
							$this->Logger->error(sprintf(_("Cannot detach EBS volume '%s' from instance '%s' during farm termination."), 
								$DBEBSVolume->VolumeID, $DBEBSVolume->InstanceID)
							);
						}
						
						$DBEBSVolume->State = FARM_EBS_STATE::DETACHING;
						$DBEBSVolume->Save();
					}
					
					$this->Logger->info(new FarmLogMessage(
						$this->FarmID,
						sprintf(_("Added new EBS volume delete task to queue (VolumeID: %s)"), 
							$volume['volumeid']
						)
					));
					
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
			
			$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['region']);
			$volumes_to_attach = array();

			// Manualy attached volumes
			$volumes = $this->DB->GetAll("SELECT volumeid FROM farm_ebs WHERE farmid=? AND role_name=? AND instance_index=?",
				array($this->FarmID, $farm_role_info['name'], $event->InstanceInfo['index'])
			);
			
			//
			// EBS Arrays
			//
			$arrays = $this->DB->GetAll("SELECT id FROM ebs_arrays WHERE farmid=? AND role_name=? AND instance_index=? AND attach_on_boot=?",
				array($this->FarmID, $farm_role_info['name'], $event->InstanceInfo['index'], 1)
			);
			foreach ($arrays as $array)
			{
				$DBEBSArray = DBEBSArray::Load($array['id']);
				$array_volumes = $this->DB->GetAll("SELECT volumeid FROM farm_ebs WHERE ebs_arrayid=?",
					array($DBEBSArray->ID)
				);
				foreach ($array_volumes as $array_volume)
					$volumes[] = $array_volume;
					
				$DBEBSArray->Status = EBS_ARRAY_STATUS::ATTACHING_VOLUMES;
				$DBEBSArray->InstanceID = $event->InstanceInfo['instance_id'];
				$DBEBSArray->Save();
			}
			
			foreach ($volumes as $volume)
				array_push($volumes_to_attach, DBEBSVolume::Load($volume['volumeid']));			
						
			if (count($volumes_to_attach) > 0)
			{				
				foreach ($volumes_to_attach as $DBEBSVolume)
				{
					$this->Logger->info(sprintf(_("Found EBS volume %s assigned to %s instance"), $DBEBSVolume->VolumeID, $DBEBSVolume->InstaneID));
					
					try
					{
						$info = $EC2Client->DescribeVolumes($DBEBSVolume->VolumeID);
						
						if ($info->volumeSet->item->status == AMAZON_EBS_STATE::DELETING)
						{
							$this->Logger->debug(new FarmLogMessage(
									$this->FarmID,
									sprintf(_("Volume '%s' state on EC2: %s"), 
								$DBEBSVolume->VolumeID, $info->volumeSet->item->status)
							));
							
							$DBEBSVolume->Delete();
						}
						elseif ($info->volumeSet->item->status != AMAZON_EBS_STATE::AVAILABLE)
						{
							$this->Logger->info(new FarmLogMessage(
								$this->FarmID, 
								sprintf(_("The volume %s assigned for instance %s is currently attached to instance %s. Detaching."),
									$DBEBSVolume->VolumeID,
									$event->InstanceInfo['instance_id'],
									$info->volumeSet->item->attachmentSet->item->instanceId
								)
							));
							
							try
							{
								$DetachVolumeType = new DetachVolumeType($DBEBSVolume->VolumeID);
								$EC2Client->DetachVolume($DetachVolumeType);
								
								$DBEBSVolume->State = FARM_EBS_STATE::CREATING;
								$DBEBSVolume->InstanceID = $event->InstanceInfo['instance_id'];
								$DBEBSVolume->Save();
								
								// Add task to queue for EBS volume status check
								TaskQueue::Attach(QUEUE_NAME::EBS_STATE_CHECK)->AppendTask(new CheckEBSVolumeStateTask($DBEBSVolume->VolumeID));
								
								if ($farm_role_info['use_ebs'] && $DBEBSVolume->IsManual == 0)
									$auto_attached_volumes = 1;
							}
							catch(Exception $e)
							{
								//TODO: Track situations...
								$this->Logger->fatal(
								sprintf(_("OnHostInit: Cannot use volume %s. Volume status: %s. Error: %s"),
									$DBEBSVolume->VolumeID,
									$info->volumeSet->item->status,
									$e->getMessage()
								));
							}
						}
						else
						{
							$DBEBSVolume->State = FARM_EBS_STATE::AVAILABLE;
							$DBEBSVolume->InstanceID = '';
							$DBEBSVolume->Device = '';
							$DBEBSVolume->Save();
							
							// Attach free EBS volume
							$this->Logger->info(new FarmLogMessage(
								$this->FarmID, 
								sprintf(_("Volume %s status is available. Attaching it to the instance %s"),
									$DBEBSVolume->VolumeID, $event->InstanceInfo['instance_id']
								)
							));
							
							try
							{
								Scalr::AttachEBS2Instance($EC2Client, $event->InstanceInfo, $farminfo, $DBEBSVolume);
								
								if ($farm_role_info['use_ebs'])
									$auto_attached_volumes = 1;
							}
							catch(Exception $e)
							{
								$this->Logger->error(new FarmLogMessage(
									$this->FarmID, 
									sprintf(_("Cannot attach volume %s: %s"), $DBEBSVolume->VolumeID, $e->getMessage())
								));
							}
						}
					}
					catch(Exception $e)
					{
						$this->Logger->fatal(sprintf(_("Cannot use volume %s for instance %s: %s"), 
							$DBEBSVolume->VolumeID, $event->InstanceInfo['instance_id'], $e->getMessage())
						);
					}
				}
			}
			
			
			if (!$auto_attached_volumes && $farm_role_info['use_ebs'] == 1)
			{
				$this->Logger->info(new FarmLogMessage(
					$this->FarmID, 
					sprintf(_("There is no free EBS volumes for role %s in availability zone %s. Creating new one(s)."),
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
		    			$DBEBSVolume = new DBEBSVolume($result->volumeId);
		    			
		    			$DBEBSVolume->FarmID = $this->FarmID;
		    			$DBEBSVolume->RoleName = $farm_role_info['name'];
		    			$DBEBSVolume->State = FARM_EBS_STATE::CREATING;
		    			$DBEBSVolume->InstanceID = $event->InstanceInfo['instance_id'];
		    			$DBEBSVolume->InstanceIndex = $event->InstanceInfo['index'];
		    			$DBEBSVolume->AvailZone = $event->InstanceInfo['avail_zone'];
		    			$DBEBSVolume->Region = $event->InstanceInfo['region'];
		    			$DBEBSVolume->IsManual = '0';
		    			
		    			$DBEBSVolume->IsFSExists = ($farm_role_info['ebs_snapid']) ? 1 : 0;
		    			
		    			$DBEBSVolume->Save();
		    			
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
			$this->Logger->info(sprintf(_("EBSEventObserver::OnHostDown(%s, %s) instance_id: %s"), 
				$event->InstanceInfo['isrebootlaunched'],
				$event->InstanceInfo['skip_ebs_observer'],
				$event->InstanceInfo["instance_id"]
			));
			
			if ($event->InstanceInfo['isrebootlaunched'] == 1 || $event->InstanceInfo['skip_ebs_observer'])
				return;
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			$farm_role_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE farmid=? AND (ami_id=? OR replace_to_ami=?)", 
				array($this->FarmID, $event->InstanceInfo["ami_id"], $event->InstanceInfo["ami_id"])
			);
			
			$this->DB->Execute("UPDATE farm_ebs SET state=?, instance_id='' WHERE instance_id=? AND ismanual='1'",
				array(FARM_EBS_STATE::AVAILABLE, $event->InstanceInfo['instance_id'])
			);
			
			$this->DB->Execute("UPDATE ebs_arrays SET status=?, instance_id='' WHERE instance_id=?",
				array(EBS_ARRAY_STATUS::AVAILABLE, $event->InstanceInfo['instance_id'])
			);
			
			$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['region']);
			
			// If role exists in farm roles list
			if ($farm_role_info)
			{
				// Get role name
				$farm_role_info['name'] = $this->DB->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($farm_role_info['ami_id']));

				$this->Logger->info(sprintf(_("Role: %s, EBS: %s"), 
					$farm_role_info['name'],
					$farm_role_info['use_ebs']
				));
				
				if (!$farm_role_info['use_ebs'])				
					return;
									
				// Get EBS attached to terminated instance
				$ebs_volumes = $this->DB->GetAll("SELECT * FROM farm_ebs WHERE instance_id=? AND ismanual='0'", 
					array($event->InstanceInfo['instance_id'])
				);
				
				if (count($ebs_volumes) > 0)
				{
					$farm_ebs = $this->DB->GetOne("SELECT COUNT(*) FROM farm_ebs WHERE farmid=? AND ismanual='0' AND state != ? AND role_name=?",
						array($this->FarmID, FARM_EBS_STATE::DETACHING, $ebs_volumes[0]['role_name'])
					);
					
					if ($farm_ebs > $farm_role_info['max_count'])
						$need_delete = true;
				}
				
				foreach ($ebs_volumes as $ebs)
				{
					// Delete unused EBS
					if ($need_delete)
					{
						$this->Logger->info(new FarmLogMessage(
							$event->InstanceInfo['farmid'],
							sprintf(_("Added new EBS volume delete task to queue (VolumeID: %s)"), 
								$ebs['volumeid']
							)
						));
						
						TaskQueue::Attach(QUEUE_NAME::EBS_DELETE)->AppendTask(new EBSDeleteTask($ebs['volumeid']));
					}
					else
					{
						// Mark EBS as available 
						if ($ebs['state'] != FARM_EBS_STATE::DETACHING)
						{
							$this->DB->Execute("UPDATE farm_ebs SET state=?, instance_id='', device='' WHERE id=?", 
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
				$volumes = $this->DB->GetAll("SELECT * FROM farm_ebs WHERE farmid=? AND role_name=? AND ismanual='0' AND instance_index=?",
					array($this->FarmID, $event->InstanceInfo['role_name'], $event->InstanceInfo['index'])
				);
				
				if (count($volumes) == 0)
					return;
					
				foreach ($volumes as $ebs)
				{
					$this->Logger->info(new FarmLogMessage(
						$event->InstanceInfo['farmid'],
						sprintf(_("Added new EBS volume delete task to queue (VolumeID: %s)"), 
							$ebs['volumeid']
						)
					));
						
					TaskQueue::Attach(QUEUE_NAME::EBS_DELETE)->AppendTask(new EBSDeleteTask($ebs['volumeid']));
				}
			}
		}
	}
?>