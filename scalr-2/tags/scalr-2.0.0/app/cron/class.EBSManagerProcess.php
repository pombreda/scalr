<?
	class EBSManagerProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "EC2 EBS Manager";
        public $Logger;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->logger = Logger::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
                      
            $this->ThreadArgs = $db->GetAll("SELECT id FROM ec2_ebs WHERE attachment_status NOT IN (?,?) OR mount_status NOT IN (?,?)", array(
            	EC2_EBS_ATTACH_STATUS::ATTACHED, EC2_EBS_ATTACH_STATUS::AVAILABLE,
            	EC2_EBS_MOUNT_STATUS::MOUNTED, EC2_EBS_MOUNT_STATUS::NOT_MOUNTED
            ));
        }
        
        public function OnEndForking()
        {
        	$db = Core::GetDBInstance(null, true);
			
			// Rotate MySQL master snapshots.
			$list = $db->GetAll("SELECT farm_roleid FROM farm_role_settings WHERE name=?", array(DBFarmRole::SETTING_MYSQL_EBS_SNAPS_ROTATION_ENABLED));
			foreach ($list as $list_item)
			{
				try
				{
					$DBFarmRole = DBFarmRole::LoadByID($list_item['farm_roleid']);
				}
				catch(Exception $e)
				{
					continue;	
				}
				
				$DBFarm = $DBFarmRole->GetFarmObject();
				
				if ($DBFarm->Status == FARM_STATUS::RUNNING)
				{					
					$old_snapshots = $db->GetAll("SELECT * FROM ebs_snaps_info WHERE is_autoebs_master_snap='1' AND farm_roleid=?  ORDER BY id ASC", array($DBFarmRole->ID));
					if (count($old_snapshots) > $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_EBS_SNAPS_ROTATE))
					{
						try
						{					
							$Client = Client::Load($DBFarm->ClientID);
							$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBFarm->Region)); 
							$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
							
							while (count($old_snapshots) > $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_EBS_SNAPS_ROTATE))
							{
								$snapinfo = array_shift($old_snapshots);
								try
								{
									$AmazonEC2Client->DeleteSnapshot($snapinfo['snapid']);
									$db->Execute("DELETE FROM ebs_snaps_info WHERE id=?", array($snapinfo['id']));
								}
								catch(Exception $e)
								{
									if (stristr($e->getMessage(), "does not exist"))
										$db->Execute("DELETE FROM ebs_snaps_info WHERE id=?", array($snapinfo['id']));
															
									throw $e;
								}
								
							}
						}
						catch(Exception $e)
						{
							$this->logger->warn(sprintf(
								_("Cannot delete old snapshots for volume %s. %s"),
								$snapshot_settings['volumeid'],
								$e->getMessage()
							));
						}
					}
				}
			}
			
        	// Auto - snapshoting
			$snapshots_settings = $db->Execute("SELECT * FROM autosnap_settings 
					WHERE (UNIX_TIMESTAMP(DATE_ADD(dtlastsnapshot, INTERVAL period HOUR)) < UNIX_TIMESTAMP(NOW()) OR dtlastsnapshot IS NULL)
					AND objectid != '0' AND object_type = ?",
				array(AUTOSNAPSHOT_TYPE::EBSSnap)				
			);
			while ($snapshot_settings = $snapshots_settings->FetchRow())
			{
				try
				{
					$Client = Client::Load($snapshot_settings['clientid']);
					
					$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($snapshot_settings['region'])); 
					$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
					
					// Check volume
					try
					{
						$AmazonEC2Client->DescribeVolumes($snapshot_settings['objectid']);
					}
					catch(Exception $e)
					{
						if (stristr($e->getMessage(), "does not exist"))
							$db->Execute("DELETE FROM autosnap_settings WHERE id=?", 
								array($snapshot_settings['id'])
							);
												
						throw $e;
					}
					
					// Create new snapshot
					$result = $AmazonEC2Client->CreateSnapshot($snapshot_settings['objectid']);
					$snapshot_id = $result->snapshotId;
					
					$db->Execute("UPDATE autosnap_settings SET last_snapshotid=?, dtlastsnapshot=NOW() WHERE id=?",
						array($snapshot_id, $snapshot_settings['id'])
					);
					
					$db->Execute("INSERT INTO ebs_snaps_info SET snapid=?, comment=?, dtcreated=NOW(), region=?, autosnapshotid=?", 
						array($snapshot_id, _("Auto-snapshot"), $snapshot_settings['region'], $snapshot_settings['id'])
					);
					
					// Remove old snapshots
					if ($snapshot_settings['rotate'] != 0)
					{
						$old_snapshots = $db->GetAll("SELECT * FROM ebs_snaps_info WHERE autosnapshotid=? ORDER BY id ASC", array($snapshot_settings['id']));
						if (count($old_snapshots) > $snapshot_settings['rotate'])
						{
							try
							{
								while (count($old_snapshots) > $snapshot_settings['rotate'])
								{
									$snapinfo = array_shift($old_snapshots);
									try
									{
										$AmazonEC2Client->DeleteSnapshot($snapinfo['snapid']);
										$db->Execute("DELETE FROM ebs_snaps_info WHERE id=?", array($snapinfo['id']));
									}
									catch(Exception $e)
									{
										if (stristr($e->getMessage(), "does not exist"))
											$db->Execute("DELETE FROM ebs_snaps_info WHERE id=?", array($snapinfo['id']));
																
										throw $e;
									}
									
								}
							}
							catch(Exception $e)
							{
								$this->logger->error(sprintf(
									_("Cannot delete old snapshots for volume %s. %s"),
									$snapshot_settings['objectid'],
									$e->getMessage()
								));
							}
						}
					}
				}
				catch(Exception $e)
				{
					$this->logger->warn(sprintf(
						_("Cannot create snapshot for volume %s. %s"),
						$snapshot_settings['objectid'],
						$e->getMessage()
					));
				}
			}
        }
        
        public function StartThread($volume)
        {        	
        	$db = Core::GetDBInstance(null, true);
        	
        	$DBEBSVolume = DBEBSVolume::loadById($volume['id']);
        	
        	$EC2Client = $this->GetEC2Client($DBEBSVolume);
        	
        	if ($DBEBSVolume->volumeId)
        	{
	        	try
	        	{
	        		$result = $EC2Client->DescribeVolumes($DBEBSVolume->volumeId);
	        		$volumeinfo = $result->volumeSet->item;
	        	}
	        	catch(Exception $e)
	        	{
	        		if (stristr($e->getMessage(), "does not exist"))
	        		{
	        			$DBEBSVolume->delete();
	        			exit();
	        		}
					else
		        		$this->logger->error("Cannot get EBS volume information: {$e->getMessage()}. Database ID: {$DBEBSVolume->id}");
	        	}
        	}
        	
        	switch($DBEBSVolume->attachmentStatus)
        	{
        		case EC2_EBS_ATTACH_STATUS::DELETING:
        			
        			if ($DBEBSVolume->volumeId)
        			{
        				try {
        					$EC2Client->DeleteVolume($DBEBSVolume->volumeId);
        					$removeFromDb = true;
        				}
        				catch(Exception $e)
        				{
        					if (stristr($e->getMessage(), "does not exist"))
				        		$removeFromDb = true;
							else
				        		$this->logger->error("Cannot remove volume: {$e->getMessage()}. Database ID: {$DBEBSVolume->id}");
        				}
        			}
        			else
        				$removeFromDb = true;
        				
        			if ($removeFromDb)
        				$DBEBSVolume->delete();
        			
        			break;
        		
        		case EC2_EBS_ATTACH_STATUS::ATTACHING:
        			
        			switch($volumeinfo->status)
        			{
        				case AMAZON_EBS_STATE::IN_USE:
        					
        					$volumeInstanceId = $volumeinfo->attachmentSet->item->instanceId;
        					$DBServer = DBServer::LoadByID($DBEBSVolume->serverId);
        					
        					if ($volumeInstanceId == $DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID))
        					{
        						$DBEBSVolume->attachmentStatus = EC2_EBS_ATTACH_STATUS::ATTACHED;
        					}
        					else
        					{
        						$this->logger->warn(sprintf(
        							_("Volume #%s should be attached to server %s (%s), but it already attached to instance %s. Re-attaching..."),
        							$DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID),
        							$DBServer->serverId,
        							$volumeInstanceId
        						));
        						
        						//TODO:
        						$DetachVolumeType = new DetachVolumeType(
        							$DBEBSVolume->volumeId,
        							$DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID),
        							$DBEBSVolume->deviceName,
        							true
        						);
        						
        						$EC2Client->DetachVolume($DetachVolumeType);
        					}
        					
        					$DBEBSVolume->save();
        					
        					break;
        					
        				case AMAZON_EBS_STATE::AVAILABLE:
        					
        					$attach_volume = true;
        					
        					break;
        					
        				case AMAZON_EBS_STATE::ATTACHING:
        					
        					// NOTHING TO DO;
        					
        					break;
        					
        				default:
        					
        					$this->logger->error("Cannot attach volume to server {$DBServer->serverId}. Volume status: {$volumeinfo->status}. Volume Database ID: {$DBEBSVolume->id}. Volume ID: {$DBEBSVolume->volumeId} (".serialize($volumeinfo).")");
        					
        					break;
        			}
        			
        			break;
        		
        		case EC2_EBS_ATTACH_STATUS::CREATING:
        			
        			if (!$DBEBSVolume->volumeId)
        			{
        				if ($DBEBSVolume->ec2AvailZone == 'x-scalr-diff')
        				{
        					if ($DBEBSVolume->serverId)
        						$DBEBSVolume->ec2AvailZone = DBServer::LoadByID($DBEBSVolume->serverId)->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE);
        					else
        						$DBEBSVolume->delete();
        				}
        				
        				$CreateVolumeType = new CreateVolumeType(
        					$DBEBSVolume->size,
        					($DBEBSVolume->snapId) ? $DBEBSVolume->snapId : "",
        					$DBEBSVolume->ec2AvailZone
        				);
        				
        				try
        				{
	        				$result = $EC2Client->CreateVolume($CreateVolumeType);
	        				if ($result->volumeId)
	        				{
	        					$DBEBSVolume->volumeId = $result->volumeId;
	        					$DBEBSVolume->save();
	        					
	        					$this->logger->info("Created new volume: {$DBEBSVolume->volumeId}. Database ID: {$DBEBSVolume->id}");
	        				}
	        				else
	        				{
	        					$this->logger->error("Cannot create volume. Database ID: {$DBEBSVolume->id}");
	        					exit();
	        				}
        				}
        				catch(Exception $e)
        				{
        					$this->logger->error("Cannot create volume: {$e->getMessage()}. Database ID: {$DBEBSVolume->id}");
        					exit();
        				}
        			}
        			else
        			{
        				if ($volumeinfo)
        				{
        					if ($volumeinfo->status == AMAZON_EBS_STATE::AVAILABLE)
        					{
        						if (!$DBEBSVolume->serverId)
        						{
        							$DBEBSVolume->attachmentStatus = EC2_EBS_ATTACH_STATUS::AVAILABLE;
        							$DBEBSVolume->save();
        						}
        						else
        							$attach_volume = true;
        					}
        				}
        			}
        			
        			break;
        	}
        	
        	switch($DBEBSVolume->mountStatus)
        	{
        		case EC2_EBS_MOUNT_STATUS::AWAITING_ATTACHMENT:
        			
        			if ($DBEBSVolume->attachmentStatus == EC2_EBS_ATTACH_STATUS::ATTACHED)
        			{
        				$DBEBSVolume->mountStatus = EC2_EBS_MOUNT_STATUS::MOUNTING;
        				$DBEBSVolume->save();
        				$DBServer = DBServer::LoadByID($DBEBSVolume->serverId);
        				$DBServer->SendMessage(new Scalr_Messaging_Msg_MountPointsReconfigure());
        			}
        			
        			break;
        			
        		case EC2_EBS_MOUNT_STATUS::MOUNTING:
        			
        			//NOTHING TO DO
        			
        			break;
        	}
        	        	
        	if ($attach_volume)
        	{
        		try {
        			$DBServer = DBServer::LoadByID($DBEBSVolume->serverId);
        		}
        		catch(ServerNotFoundException $e)
        		{
        			$DBEBSVolume->attachmentStatus = EC2_EBS_ATTACH_STATUS::AVAILABLE;
        			$DBEBSVolume->mountStatus = EC2_EBS_MOUNT_STATUS::NOT_MOUNTED;
        			$DBEBSVolume->save();
        		}
        		
        		if ($DBServer) {
        			
        			try {
		        		$attachVolumeType = new AttachVolumeType(
		        			$DBEBSVolume->volumeId,
		        			$DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID),
		        			$DBServer->GetFreeDeviceName()
		        		);
					    $result = $EC2Client->AttachVolume($attachVolumeType);
        			}
        			catch(Exception $e)
        			{
        				$this->logger->warn("Cannot attach volume: {$e->getMessage()}");
        			}
				    
				    if ($result->status == AMAZON_EBS_STATE::IN_USE || $result->status == AMAZON_EBS_STATE::ATTACHING)
				    {
				    	$DBEBSVolume->attachmentStatus = EC2_EBS_ATTACH_STATUS::ATTACHING;
				    	$DBEBSVolume->deviceName = $attachVolumeType->device;
				    	$DBEBSVolume->save();
				    }
				   	else
				   		$this->logger->warn("Cannot attach volume: volume status: {$result->status} ({$volumeinfo->status}). Database ID: {$DBEBSVolume->id}. Volume ID: {$DBEBSVolume->volumeId}");
        		}
        	}
        }
        
        private function GetEC2Client(DBEBSVolume $DBEBSVolume)
        {
        	$Client = Client::Load($DBEBSVolume->clientId);
            $ec2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBEBSVolume->ec2Region)); 
		    $ec2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
        	       	
        	return $ec2Client;
        }
    }
?>