<?php

	class Scalr_Cronjob_EBSMaintenance extends Scalr_System_Cronjob_MultiProcess_DefaultWorker {
		
		static function getConfig () {
			return array(
				"description" => "Process EBS queues"
			);
		}
		
		private $db;
		
        private $logger;
                
    	function __construct() {
        	$this->logger = LoggerManager::getLogger(__CLASS__);
        }
        
		function startChild () {
			// Reopen DB connection in child
			$this->db = Core::GetDBInstance(null, true);
        	// Reconfigure observers;
        	Scalr::ReconfigureObservers();					
		}        
        
        function enqueueWork ($workQueue) {	
        	$workQueue->put(QUEUE_NAME::EBS_DELETE);
        	$workQueue->put(QUEUE_NAME::EBS_MOUNT);
        	$workQueue->put(QUEUE_NAME::EBS_STATE_CHECK);
        }
        
        function handleWork ($queueName) {
        	$GLOBALS["SUB_TRANSACTIONID"] = abs(crc32(posix_getpid().$queueName));        	
        	
        	$this->logger->info(sprintf("[%s] Processing queue: %s", $GLOBALS["SUB_TRANSACTIONID"], $queueName));
        	
       
            $FarmObservers = array();
            $queue = TaskQueue::Attach($queueName);
            
            // Process tasks from EBS status check queue
            while ($Task = $queue->Peek())
            {
            	$remove_task = false;
            	
            	try
            	{
	            	//TODO: move Max_Fail_attempts to CONFIG
	            	if ($Task->FailedAttempts == 5)
	            	{
	            		$remove_task = true;
	            		$this->logger->error("Task #{$Task->ID} (".serialize($Task).") removed from queue. MaxFailureAttemts limit exceed.");
	            	}
	            	elseif ($Task->Run())
	            	{
	            		$remove_task = true;
	            	}
	            	else
	            	{
	            		$queue->IncrementFailureAttemptsCounter();
	            	}
            	}
            	catch(Exception $e)
            	{
            		$remove_task = true;
            	}
            		
            	if ($remove_task)
            		$queue->Remove($Task);
            }
            
            // Reset queue
            $queue->Reset();
        }
        
        function endForking () {
        	// Reopen DB connection
			$this->db = Core::GetDBInstance(null, true);
			
			// Rotate MySQL master snapshots.
			$list = $this->db->GetAll("SELECT * FROM farm_role_settings WHERE name=?", array(DBFarmRole::SETTING_MYSQL_EBS_SNAPS_ROTATION_ENABLED));
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
				
				$farminfo = $this->db->GetRow("SELECT * FROM farms WHERE id=?", array($DBFarmRole->FarmID));				
				if ($farminfo['status'] == FARM_STATUS::RUNNING)
				{					
					$old_snapshots = $this->db->GetAll("SELECT * FROM ebs_snaps_info WHERE is_autoebs_master_snap='1' AND farm_roleid=?  ORDER BY id ASC", array($DBFarmRole->ID));
					if (count($old_snapshots) > $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_EBS_SNAPS_ROTATE))
					{
						try
						{					
							$Client = Client::Load($farminfo['clientid']);
							$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region'])); 
							$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
							
							while (count($old_snapshots) > $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_EBS_SNAPS_ROTATE))
							{
								$snapinfo = array_shift($old_snapshots);
								try
								{
									$AmazonEC2Client->DeleteSnapshot($snapinfo['snapid']);
									$this->db->Execute("DELETE FROM ebs_snaps_info WHERE id=?", array($snapinfo['id']));
								}
								catch(Exception $e)
								{
									if (stristr($e->getMessage(), "does not exist"))
										$this->db->Execute("DELETE FROM ebs_snaps_info WHERE id=?", array($snapinfo['id']));
															
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
			$snapshots_settings = $this->db->Execute("SELECT * FROM autosnap_settings 
				WHERE (UNIX_TIMESTAMP(DATE_ADD(dtlastsnapshot, INTERVAL period HOUR)) < UNIX_TIMESTAMP(NOW()) OR dtlastsnapshot IS NULL)
				AND volumeid != '0'");
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
						$AmazonEC2Client->DescribeVolumes($snapshot_settings['volumeid']);
					}
					catch(Exception $e)
					{
						if (stristr($e->getMessage(), "does not exist"))
							$this->db->Execute("DELETE FROM autosnap_settings WHERE id=?", array($snapshot_settings['id']));
												
						throw $e;
					}
					
					// Create new snapshot
					$result = $AmazonEC2Client->CreateSnapshot($snapshot_settings['volumeid']);
					$snapshot_id = $result->snapshotId;
					
					$this->db->Execute("UPDATE autosnap_settings SET last_snapshotid=?, dtlastsnapshot=NOW() WHERE id=?",
						array($snapshot_id, $snapshot_settings['id'])
					);
					
					$this->db->Execute("INSERT INTO ebs_snaps_info SET snapid=?, comment=?, dtcreated=NOW(), region=?, autosnapshotid=?", 
						array($snapshot_id, _("Auto-snapshot"), $snapshot_settings['region'], $snapshot_settings['id'])
					);
					
					// Remove old snapshots
					if ($snapshot_settings['rotate'] != 0)
					{
						$old_snapshots = $this->db->GetAll("SELECT * FROM ebs_snaps_info WHERE autosnapshotid=? ORDER BY id ASC", array($snapshot_settings['id']));
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
										$this->db->Execute("DELETE FROM ebs_snaps_info WHERE id=?", array($snapinfo['id']));
									}
									catch(Exception $e)
									{
										if (stristr($e->getMessage(), "does not exist"))
											$this->db->Execute("DELETE FROM ebs_snaps_info WHERE id=?", array($snapinfo['id']));
																
										throw $e;
									}
									
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
					$this->logger->warn(sprintf(
						_("Cannot create snapshot for volume %s. %s"),
						$snapshot_settings['volumeid'],
						$e->getMessage()
					));
				}
			}
        }
    }
?>