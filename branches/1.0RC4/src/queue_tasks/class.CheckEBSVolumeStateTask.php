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
		protected function GetAmazonEC2ClientObject($clientid)
		{
	    	// Get Client Object
			$Client = Client::Load($clientid);
	
	    	// Return new instance of AmazonEC2 object
			return new AmazonEC2($Client->AWSPrivateKey, $Client->AWSCertificate);
		}
		
		public function Run()
		{
			$DB = Core::GetDBInstance();
			$ebsinfo = $DB->GetRow("SELECT * FROM farm_ebs WHERE volumeid=?", array($this->VolumeID));
			if ($ebsinfo)
			{
				if ($ebsinfo['state'] == FARM_EBS_STATE::CREATING)
				{
					try
					{
						// Get farminfo from database
						$farminfo = $DB->GetRow("SELECT * FROM farms WHERE id=?", array($ebsinfo['farmid']));
						
						// Get instance info fro database
						$instanceinfo = $DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($ebsinfo['instance_id']));
						
						
						// Get EC2 Client
						$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['clientid']);
						
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
									Scalr::AttachEBS2Instance($EC2Client, $instanceinfo, $farminfo, $this->VolumeID);
								}
								catch(Exception $e)
								{
									LoggerManager::getLogger(__CLASS__)->fatal(new FarmLogMessage($ebsinfo['farmid'],
										sprintf(_("Cannot attach volume to instance: %s"), $e->getMessage())
									));
									return false;
								}
								
								$DB->Execute("UPDATE farm_ebs SET state=? WHERE volumeid=?", array(FARM_EBS_STATE::ATTACHED, $this->VolumeID));
							}
							else
							{
								$DB->Execute("UPDATE farm_ebs SET state=?, instance_id='' WHERE volumeid=?", array(FARM_EBS_STATE::AVAILABLE, $this->VolumeID));
								return true;
							}
						}
						elseif ($volume->status == AMAZON_EBS_STATE::IN_USE && $volume->attachmentSet->item->instanceId == $ebsinfo['instance_id'])
						{
							$DB->Execute("UPDATE farm_ebs SET state=? WHERE volumeid=?", array(FARM_EBS_STATE::ATTACHED, $this->VolumeID));
							return true;
						}
						else
						{
							if ($volume->status == AMAZON_EBS_STATE::IN_USE)
							{
								LoggerManager::getLogger(__CLASS__)->warn(new FarmLogMessage($ebsinfo['farmid'],
									sprintf(_("Cannot attach volume %s to instance %s. Volume already attached to another instance. Sending detach request..."), $this->VolumeID, $ebsinfo['instance_id'])
								));
								
								$DetachVolumeType = new DetachVolumeType($this->VolumeID);
								$EC2Client->DetachVolume($DetachVolumeType);
						
								return false;
							}
							else
							{
								LoggerManager::getLogger(__CLASS__)->error(new FarmLogMessage($ebsinfo['farmid'],
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