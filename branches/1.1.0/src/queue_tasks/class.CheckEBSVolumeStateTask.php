<?
	/**
	 * Task for EBS Volume status check
	 */
	class CheckEBSVolumeStateTask extends Task 
	{
		public $VolumeID;
		
		function __construct($volumeid)
		{
			$this->VolumeID = $volumeid;
		}
		
		/**
		 * Return EC2 Client
		 *
		 * @param integer $clientid
		 * @return AmazonEC2
		 */
		protected function GetAmazonEC2ClientObject($clientid, $region)
		{
	    	// Get Client Object
			$Client = Client::Load($clientid);
	
	    	// Return new instance of AmazonEC2 object
			$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region)); 
			$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
			
			return $AmazonEC2Client;
		}
		
		public function Run()
		{
			$DB = Core::GetDBInstance();
			
			try
			{
				$DBEBSVolume = DBEBSVolume::Load($this->VolumeID);
			}
			catch (Exception $e)
			{
				//
			}
			
			if ($DBEBSVolume)
			{
				if ($DBEBSVolume->State == FARM_EBS_STATE::CREATING)
				{
					try
					{
						// Get instance info fro database
						$instanceinfo = $DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($DBEBSVolume->InstanceID));
						$farminfo = $DB->GetRow("SELECT * FROM farms WHERE id=?", array($instanceinfo['farmid']));
						
						$Client = Client::Load($farminfo['clientid']);										
						
						// Get EC2 Client
						$EC2Client = $this->GetAmazonEC2ClientObject($Client->ID, $farminfo['region']);
						
						// Check volume status
						$response = $EC2Client->DescribeVolumes($this->VolumeID);
						$volume = $response->volumeSet->item;
						
						if ($volume->status == AMAZON_EBS_STATE::CREATING)
							return false;
						elseif ($volume->status == AMAZON_EBS_STATE::AVAILABLE)
						{
							if ($instanceinfo)
							{
								try
								{
									Scalr::AttachEBS2Instance($EC2Client, $instanceinfo, $farminfo, $DBEBSVolume);
								}
								catch(Exception $e)
								{
									LoggerManager::getLogger(__CLASS__)->fatal(new FarmLogMessage($DBEBSVolume->FarmID,
										sprintf(_("Cannot attach volume to instance: %s"), $e->getMessage())
									));
									return false;
								}
							}
							else
							{
								// We cannot use DBEBSVolume->Save() in this case. Deadlock.
								
								$DB->Execute("UPDATE farm_ebs SET state=?, instance_id='' WHERE id=?", array(FARM_EBS_STATE::AVAILABLE, $DBEBSVolume->ID));
								
								//$DBEBSVolume->State = FARM_EBS_STATE::AVAILABLE;
								//$DBEBSVolume->InstanceID = '';
								//$DBEBSVolume->Save();
							}
						}
						elseif ($volume->status == AMAZON_EBS_STATE::IN_USE && $volume->attachmentSet->item->instanceId == $DBEBSVolume->InstanceID)
						{
							// We cannot use DBEBSVolume->Save() in this case. Deadlock.
							
							$DB->Execute("UPDATE farm_ebs SET state=? WHERE id=?", array(FARM_EBS_STATE::ATTACHED, $DBEBSVolume->ID));
							
							//$DBEBSVolume->State = FARM_EBS_STATE::ATTACHED;
							//$DBEBSVolume->Save();
						}
						else
						{
							if ($volume->status == AMAZON_EBS_STATE::IN_USE)
							{
								LoggerManager::getLogger(__CLASS__)->warn(new FarmLogMessage($DBEBSVolume->FarmID,
									sprintf(_("Cannot attach volume %s to instance %s. Volume already attached to another instance. Sending detach request..."), $this->VolumeID, $DBEBSVolume->InstanceID)
								));
								
								$DetachVolumeType = new DetachVolumeType($this->VolumeID);
								$EC2Client->DetachVolume($DetachVolumeType);
						
								return false;
							}
							else
							{
								LoggerManager::getLogger(__CLASS__)->error(new FarmLogMessage($DBEBSVolume->FarmID,
									sprintf(_("Cannot attach volume %s to instance. Volume status: %s"), $this->VolumeID, $volume->status)
								));
								return false;
							}
						}
					}
					catch(Exception $e)
					{
						LoggerManager::getLogger(__CLASS__)->fatal(sprintf(_("Cannot check EBS status: %s"), $e->getMessage()));
						return false;
					}
				}
			}
			
			return true;
		}
	}

?>