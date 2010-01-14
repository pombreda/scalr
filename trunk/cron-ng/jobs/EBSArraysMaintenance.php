<?php

	class Scalr_Cronjob_EBSArraysMaintenance extends Scalr_System_Cronjob_MultiProcess_DefaultWorker {
		
		static function getConfig () {
			return array(
				"description" => "EBS arrays maintenance"
			);
		}
		
        public $logger;

        private $db;
        
    	public function __construct() {
        	$this->logger = LoggerManager::getLogger(__CLASS__);
        	$this->db = Core::GetDBInstance(); 
        }
        
        function startChild () {
        	// Reopen DB connection in child
        	$this->db = Core::GetDBInstance(null, true);
            // Reconfigure observers;
        	Scalr::ReconfigureObservers();        	
        }        
        
        function enqueueWork ($workQueue) {
        	
        	$this->logger->info("Fetching EBS arrays...");
        	$rows = $this->db->GetAll("SELECT id FROM ebs_arrays WHERE status IN (?,?,?,?)",
            	array(
            		EBS_ARRAY_STATUS::CREATING_VOLUMES, 
            		EBS_ARRAY_STATUS::ATTACHING_VOLUMES, 
            		EBS_ARRAY_STATUS::DETACHING_VOLUMES, 
            		EBS_ARRAY_STATUS::PENDING_DELETE
            	)
            );
            $this->logger->info("Found ".count($rows)." arrays");
                      
            foreach ($rows as $row) {
            	$workQueue->put($row["id"]);
            } 
        }
        
        function handleWork ($ebsArrayId) {

            
        	$DBEBSArray = DBEBSArray::Load($ebsArrayId);
        	
            $GLOBALS["SUB_TRANSACTIONID"] = abs(crc32(posix_getpid().$ebsArrayId));
      	
        	$this->logger->info(sprintf("[%s] Checking EBS array (name: %s. status: %s)", 
        			$GLOBALS["SUB_TRANSACTIONID"], $DBEBSArray->Name, $DBEBSArray->Status));  	
        	                        
        	$array_volumes = $this->db->GetAll("SELECT * FROM farm_ebs WHERE ebs_arrayid=?", array($DBEBSArray->ID));
        	
            $Client = Client::Load($DBEBSArray->ClientID);
            
            $EC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBEBSArray->Region)); 
			$EC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
            
            $completed_volumes = 0;
            $size = 0;
            
            if ($DBEBSArray->Status == EBS_ARRAY_STATUS::PENDING_DELETE && count($array_volumes) == 0)
            {
            	$DBEBSArray->Delete();
            	return;
            }
            
            foreach ($array_volumes as $array_volume)
            {
            	try
            	{
            		$res = $EC2Client->DescribeVolumes($array_volume['volumeid']);
            		
            		$this->logger->info(sprintf(_("Volume: %s, State: %s"), $array_volume['volumeid'], $res->volumeSet->item->status));
            		
            		$size = $size + (int)$res->volumeSet->item->size;
            		
            		switch($DBEBSArray->Status)
		            {
		            	case EBS_ARRAY_STATUS::CREATING_VOLUMES:
            		
	            			if ($res->volumeSet->item->status == AMAZON_EBS_STATE::AVAILABLE)
	            			{
	            				//
	            				$completed_volumes++;
	            			}
	            			elseif ($res->volumeSet->item->status == AMAZON_EBS_STATE::CREATING)
	            			{
	            				// Creating
	            			}
	            			else
	            			{
	            				//TODO:
	            				$this->logger->info(sprintf(_("Array: %s, Volume: %s. Volume status %s expect 'creating' or 'available'"),
	            					$DBEBSArray->ID, $array_volume['volumeid'], $res->volumeSet->item->status 
	            				));	            				
	            			}
	            			
	            		break;
	            		
	            		case EBS_ARRAY_STATUS::ATTACHING_VOLUMES:

	            			if ($res->volumeSet->item->status == AMAZON_EBS_STATE::IN_USE && $res->volumeSet->item->attachmentSet->item->status == 'attached')
	            			{
	            				$completed_volumes++;
	            			}
	            			else
	            			{
	            				$this->logger->info(sprintf(_("Array: %s, Volume: %s. Volume status %s (%s) expect 'in_use' ('attached')"),
	            					$DBEBSArray->ID, $array_volume['volumeid'], $res->volumeSet->item->status,
	            					$res->volumeSet->item->attachmentSet->item->status
	            				));
	            			}
	            		
	            		break;
	            		
	            		case EBS_ARRAY_STATUS::DETACHING_VOLUMES:
            				
	            			if ($res->volumeSet->item->status == AMAZON_EBS_STATE::AVAILABLE)
	            			{
								$DBEBSVolume = DBEBSVolume::Load($array_volume['volumeid']);
								$DBEBSVolume->State = FARM_EBS_STATE::AVAILABLE;
								$DBEBSVolume->Save();
								
	            				$completed_volumes++;
	            			}
	            			elseif ($res->volumeSet->item->status == AMAZON_EBS_STATE::DETACHING)
	            			{
	            				//
	            			}
	            			else
	            			{
	            				$this->logger->info(sprintf(_("Array: %s, Volume: %s. Volume status %s (%s) expect 'detaching'"),
	            					$DBEBSArray->ID, $array_volume['volumeid'], $res->volumeSet->item->status,
	            					$res->volumeSet->item->attachmentSet->item->status
	            				));
	            			}
	            			
            			break;
		            }
            	}
            	catch(Exception $e)
            	{
            		if (stristr($e->getMessage(), "does not exist"))
					{
						if ($DBEBSArray->Status != EBS_ARRAY_STATUS::PENDING_DELETE)
						{
							$DBEBSArray->Status = EBS_ARRAY_STATUS::CORRUPT;
							$DBEBSArray->CorruptReason = sprintf(_("Volume %s that was a part of array was deleted and no longer available."), $array_volume['volumeid']);
							
							//TODO: Remove zomby EBS volumes (from corrupted array)
							
							$DBEBSArray->Save();
						}
					}
					else
					{
						//TODO:
            			$this->logger->info(sprintf(_("Array: %s (%s), Volume: %s. Exception: %s"),
            				$DBEBSArray->ID, $DBEBSArray->Status, $array_volume['volumeid'], $e->getMessage() 
            			));
					}
            	}
            }
            
            if ($completed_volumes != count($array_volumes))
            	return;
            
            if ($DBEBSArray->Status == EBS_ARRAY_STATUS::DETACHING_VOLUMES)
            {
            	$DBEBSArray->Status = EBS_ARRAY_STATUS::AVAILABLE;
            	$DBEBSArray->InstanceID = '';
            	$DBEBSArray->Mountpoint = '';
            	$DBEBSArray->Save();
            }
            	
            if ($DBEBSArray->Status == EBS_ARRAY_STATUS::ATTACHING_VOLUMES)
            {
            	//MOUNT OR CREATE Filesystem
            	
            	try
            	{
            		$DBInstance = DBInstance::LoadByIID($DBEBSArray->InstanceID);
            	}
            	catch(Exception $e) {}
            	
            	if ($DBInstance)
            	{	                
	                if ($DBEBSArray->IsFSCreated)
		                $DBEBSArray->Status = EBS_ARRAY_STATUS::MOUNTING;
		            else
		            	$DBEBSArray->Status = EBS_ARRAY_STATUS::CREATING_FS;
		            	
		            $DBEBSArray->Save();

		            $DBInstance->SendMessage(new MountPointsReconfigureScalrMessage());
            	}
            	else
            	{
            		$this->logger->info(sprintf(_("Cannot attach array: %s (%s). Instance %s is no longer available."),
            			$DBEBSArray->ID, $DBEBSArray->Status, $DBEBSArray->InstanceID 
            		));
            		
            		$DBEBSArray->InstanceID = '';
            		$DBEBSArray->Status = EBS_ARRAY_STATUS::AVAILABLE;
            		
            		$DBEBSArray->Save();
            	}
            }
            
            //
            // Array created
            //
            if ($DBEBSArray->Status == EBS_ARRAY_STATUS::CREATING_VOLUMES)
            {
            	if (!$DBEBSArray->Size)
            		$this->db->Execute("UPDATE ebs_arrays SET size=? WHERE id=?", array($size, $DBEBSArray->ID));
            	
            	if (!$DBEBSArray->InstanceID)
            	{
            		$this->db->Execute("UPDATE ebs_arrays SET status=? WHERE id=?", array(EBS_ARRAY_STATUS::AVAILABLE, $DBEBSArray->ID));
            	}
            	else
            	{
            		//TODO:
            	}
            }
        }
        
        function endForking () {
        	// Reopen DB connection
        	$this->db = Core::GetDBInstance(null, true);        	
        	
            // Auto-snapshoting
            $snapshots_settings = $this->db->Execute("SELECT * FROM autosnap_settings 
				WHERE (UNIX_TIMESTAMP(DATE_ADD(dtlastsnapshot, INTERVAL period HOUR)) < UNIX_TIMESTAMP(NOW()) OR dtlastsnapshot IS NULL)
				AND arrayid != '0'");
			while ($snapshot_settings = $snapshots_settings->FetchRow())
			{
				try
				{
					$Client = Client::Load($snapshot_settings['clientid']);
					
					$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($snapshot_settings['region'])); 
					$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
					
					$DBEBSArray = DBEBSArray::Load($snapshot_settings['arrayid']);
					
			    	$snapid = $DBEBSArray->CreateSnapshot(sprintf(_("Automatic snapshot for '%s' array"), $DBEBSArray->Name), $snapshot_settings['id']);
			    	
			    	$this->db->Execute("UPDATE autosnap_settings SET dtlastsnapshot=NOW(), last_snapshotid=? WHERE id=?",
			    		array("array-snap-{$snapid}", $snapshot_settings['id'])
			    	);
			    	
					// Remove old snapshots
					if ($snapshot_settings['rotate'] != 0)
					{
						$old_snapshots = $this->db->GetAll("SELECT * FROM ebs_array_snaps WHERE autosnapshotid=? ORDER BY id ASC", array($snapshot_settings['id']));
						if (count($old_snapshots) > $snapshot_settings['rotate'])
						{
							try
							{
								while (count($old_snapshots) > $snapshot_settings['rotate'])
								{
									$snapinfo = array_shift($old_snapshots);
									
									$ebs_snaps = $this->db->GetAll("SELECT * FROM ebs_snaps_info WHERE ebs_array_snapid=?", array($snapinfo['id']));
									
									foreach ($ebs_snaps as $ebs_snap)
									{
										try
										{
											$AmazonEC2Client->DeleteSnapshot($ebs_snap['snapid']);
											$this->db->Execute("DELETE FROM ebs_snaps_info WHERE id=?", array($ebs_snap['id']));
										}
										catch(Exception $e)
										{
											if (stristr($e->getMessage(), "does not exist"))
												$this->db->Execute("DELETE FROM ebs_snaps_info WHERE id=?", array($ebs_snap['id']));
																	
											throw $e;
										}
									}
									
									$this->db->Execute("DELETE FROM ebs_array_snaps WHERE id=?", array($snapinfo['id']));
								}
							}
							catch(Exception $e)
							{
								$this->logger->error(sprintf(
									_("Cannot delete old snapshots for volume %s. %s"),
									$snapshot_settings['volumeid'],
									$e->getMessage()
								));
							}
						}
					}
				}
				catch(Exception $e)
				{
					$this->logger->error(sprintf(
						_("Cannot create snapshot for array %s. %s"),
						$snapshot_settings['arrayid'],
						$e->getMessage()
					));	
				}
			}
            
        	//Check snapshots
            $snapshots = $this->db->Execute("SELECT * FROM ebs_array_snaps WHERE status=?", array(EBS_ARRAY_SNAP_STATUS::PENDING));
            while ($snapshot = $snapshots->FetchRow())
            {
            	$Client = Client::Load($snapshot['clientid']);
            	$EC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($snapshot['region'])); 
			    $EC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
            	
            	$corrupted = false;
            	$completed_snapshots = 0;
            	$available_snapshots = 0;
            	
            	$ebs_snapshots = $this->db->GetAll("SELECT * FROM ebs_snaps_info WHERE ebs_array_snapid=?", array($snapshot['id']));
            	if (count($ebs_snapshots) == $snapshot['ebs_snaps_count'])
            	{
            		foreach ($ebs_snapshots as $ebs_snapshot)
            		{
            			try
            			{
	            			$res = $EC2Client->DescribeSnapshots($ebs_snapshot['snapid']);
	            			if ($res->snapshotSet->item->snapshotId)
	            			{
	            				$available_snapshots++;
	            				if ($res->snapshotSet->item->status == 'completed')
	            					$completed_snapshots++;
	            			}
            			}
            			catch(Exception $e)
            			{
            				//TODO:
            			}
            		}
            	}
            	else
            		$corrupted = true;
            		
            	if ($available_snapshots != $snapshot['ebs_snaps_count'])
            		$corrupted = true;
            		
            	if ($corrupted)
            	{
            		$this->db->Execute("UPDATE ebs_array_snaps SET status=? WHERE id=?", array(
            			EBS_ARRAY_SNAP_STATUS::CORRUPT, $snapshot['id']
            		));
            	}
            	elseif ($completed_snapshots == $snapshot['ebs_snaps_count'])
            	{
            		$this->db->Execute("UPDATE ebs_array_snaps SET status=? WHERE id=?", array(
            			EBS_ARRAY_SNAP_STATUS::COMPLETED, $snapshot['id']
            		));
            	}
            }
        }
    }
