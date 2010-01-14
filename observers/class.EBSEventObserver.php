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
		
		public function OnBeforeInstanceLaunch(BeforeInstanceLaunchEvent $event)
		{			
			$instanceinfo = $this->DB->GetRow("SELECT * FROM farm_instances WHERE id=?",
				array($event->DBInstance->ID)
			);

			$event->DBInstance->ReLoad();
			$DBFarm = $event->DBInstance->GetDBFarmObject(); 
			
			$ami_info = $this->DB->GetRow("SELECT * FROM roles WHERE ami_id=?", array($event->DBInstance->AMIID));
			
			$AmazonEC2Client = $this->GetAmazonEC2ClientObject($DBFarm->Region);
			
			$this->Logger->info(sprintf("EBSEventObserver::OnBeforeInstanceLaunch(instance_id = %s, ami_id = %s, alias = %s, engine = %s, volumeid = %s, size = %s, isdbmaster = %s)",
				$event->DBInstance->InstanceID,
				$event->DBInstance->AMIID,
				$ami_info['alias'],
				$DBFarm->GetSetting(DBFarm::SETTING_MYSQL_DATA_STORAGE_ENGINE),
				$DBFarm->GetSetting(DBFarm::SETTING_MYSQL_MASTER_EBS_VOLUME_ID),
				$DBFarm->GetSetting(DBFarm::SETTING_MYSQL_EBS_VOLUME_SIZE),
				$event->DBInstance->IsDBMaster
			));
						
			if ($ami_info['alias'] == ROLE_ALIAS::MYSQL && $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_DATA_STORAGE_ENGINE) == MYSQL_STORAGE_ENGINE::EBS)
			{
				if (!$DBFarm->GetSetting(DBFarm::SETTING_MYSQL_MASTER_EBS_VOLUME_ID))
				{
					$this->Logger->info(sprintf("There is no master EBS volume. Creating new one... Size = %s GB", $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_EBS_VOLUME_SIZE)));
					
					$CreateVolumeType = new CreateVolumeType();
    				$CreateVolumeType->availabilityZone = $event->DBInstance->AvailZone;
    				$CreateVolumeType->size = $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_EBS_VOLUME_SIZE);
					
					$res = $AmazonEC2Client->CreateVolume($CreateVolumeType);
				    if ($res->volumeId)
				    {
				    	$this->Logger->info(sprintf("Master EBS volume created, VolumeID = %s", $res->volumeId));
				    	$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_MASTER_EBS_VOLUME_ID, $res->volumeId);
				    }
				}
			}
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
			
			$event->DBInstance->ReLoad();
			$DBFarmRole = $event->DBInstance->GetDBFarmRoleObject();
						
			
			$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['region']);
			$volumes_to_attach = array();

			// Manualy attached volumes
			$volumes = $this->DB->GetAll("SELECT volumeid FROM farm_ebs WHERE farmid=? AND farm_roleid=? AND instance_index=?",
				array($this->FarmID, $event->DBInstance->FarmRoleID, $event->DBInstance->Index)
			);
			
			//
			// EBS Arrays
			//
			$arrays = $this->DB->GetAll("SELECT id FROM ebs_arrays WHERE farm_roleid=? AND instance_index=? AND attach_on_boot=?",
				array($event->DBInstance->FarmRoleID, $event->DBInstance->Index, 1)
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
				$DBEBSArray->InstanceID = $event->DBInstance->InstanceID;
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
									$event->DBInstance->InstanceID,
									$info->volumeSet->item->attachmentSet->item->instanceId
								)
							));
							
							try
							{
								$DetachVolumeType = new DetachVolumeType($DBEBSVolume->VolumeID);
								$EC2Client->DetachVolume($DetachVolumeType);
								
								$DBEBSVolume->State = FARM_EBS_STATE::CREATING;
								$DBEBSVolume->InstanceID = $event->DBInstance->InstanceID;
								$DBEBSVolume->Save();
								
								// Add task to queue for EBS volume status check
								TaskQueue::Attach(QUEUE_NAME::EBS_STATE_CHECK)->AppendTask(new CheckEBSVolumeStateTask($DBEBSVolume->VolumeID));
								
								if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_USE_EBS) && $DBEBSVolume->IsManual == 0)
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
									$DBEBSVolume->VolumeID, $event->DBInstance->InstanceID
								)
							));
							
							try
							{
								Scalr::AttachEBS2Instance($EC2Client, $event->DBInstance, $farminfo, $DBEBSVolume);
								
								if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_USE_EBS))
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
							$DBEBSVolume->VolumeID, $event->DBInstance->InstanceID, $e->getMessage())
						);
					}
				}
			}
			
			
			if (!$auto_attached_volumes && $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_USE_EBS))
			{
				$this->Logger->info(new FarmLogMessage(
					$this->FarmID, 
					sprintf(_("There are no free EBS volumes for role %s in availability zone %s. Creating new one(s)."),
						$DBFarmRole->GetRoleName(), $event->DBInstance->AvailZone	
					)
				));
				
				// Create new EBS volume and then attach it
				$CreateVolumeType = new CreateVolumeType();
		    	if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_SNAPID))
		    		$CreateVolumeType->snapshotId = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_SNAPID);
		    	
		    	if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_SIZE))
		    		$CreateVolumeType->size = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_SIZE);
		    		 
		    	$CreateVolumeType->availabilityZone = $event->DBInstance->AvailZone;
		    	
		    	try
		    	{
		    		$result = $EC2Client->CreateVolume($CreateVolumeType);
		    		if ($result->volumeId)
		    		{
		    			$DBEBSVolume = new DBEBSVolume($result->volumeId);
		    			
		    			$DBEBSVolume->FarmID = $this->FarmID;
		    			$DBEBSVolume->State = FARM_EBS_STATE::CREATING;
		    			$DBEBSVolume->FarmRoleID = $event->DBInstance->FarmRoleID;
		    			$DBEBSVolume->InstanceID = $event->DBInstance->InstanceID;
		    			$DBEBSVolume->InstanceIndex = $event->DBInstance->Index;
		    			$DBEBSVolume->AvailZone = $event->DBInstance->AvailZone;
		    			$DBEBSVolume->Region = $event->DBInstance->Region;
		    			$DBEBSVolume->IsManual = '0';
		    			
		    			$DBEBSVolume->IsFSExists = ($DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_SNAPID)) ? 1 : 0;
		    			
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
				(int)$event->DBInstance->IsRebootLaunched,
				(int)$event->DBInstance->SkipEBSObserver,
				$event->DBInstance->InstanceID
			));
			
			if ($event->DBInstance->IsRebootLaunched == 1 || $event->DBInstance->SkipEBSObserver)
				return;
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			
			
			$this->DB->Execute("UPDATE farm_ebs SET state=?, instance_id='' WHERE instance_id=? AND ismanual='1'",
				array(FARM_EBS_STATE::AVAILABLE, $event->DBInstance->InstanceID)
			);
			
			$this->DB->Execute("UPDATE ebs_arrays SET status=?, instance_id='' WHERE instance_id=?",
				array(EBS_ARRAY_STATUS::AVAILABLE, $event->DBInstance->InstanceID)
			);
			
			$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['region']);
			
			try
			{
				$DBFarmRole = $event->DBInstance->GetDBFarmRoleObject();
			}
			catch(Exception $e)
			{
				
			}
			
			// If role exists in farm roles list
			if ($DBFarmRole)
			{
								
				$this->Logger->info(sprintf(_("Role: %s, EBS: %s"), 
					$DBFarmRole->GetRoleName(),
					$DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_USE_EBS)
				));
				
				if (!$DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_USE_EBS))				
					return;
									
				// Get EBS attached to terminated instance
				$ebs_volumes = $this->DB->GetAll("SELECT * FROM farm_ebs WHERE instance_id=? AND ismanual='0'", 
					array($event->DBInstance->InstanceID)
				);
				
				if (count($ebs_volumes) > 0)
				{
					$farm_ebs = $this->DB->GetOne("SELECT COUNT(*) FROM farm_ebs WHERE ismanual='0' AND state != ? AND farm_roleid=?",
						array(FARM_EBS_STATE::DETACHING, $event->DBInstance->FarmRoleID)
					);
					
					if ($farm_ebs > $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES))
						$need_delete = true;
				}
				
				foreach ($ebs_volumes as $ebs)
				{
					// Delete unused EBS
					if ($need_delete)
					{
						$this->Logger->info(new FarmLogMessage(
							$event->DBInstance->FarmID,
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
				$volumes = $this->DB->GetAll("SELECT * FROM farm_ebs WHERE farm_roleid=? AND ismanual='0' AND instance_index=?",
					array($event->DBInstance->FarmRoleID, $event->DBInstance->Index)
				);
				
				if (count($volumes) == 0)
					return;
					
				foreach ($volumes as $ebs)
				{
					$this->Logger->info(new FarmLogMessage(
						$event->DBInstance->FarmID,
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