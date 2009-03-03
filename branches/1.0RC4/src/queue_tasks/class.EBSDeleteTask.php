<?
	/**
	 * Task for EBS mount routine aftre attachment complete
	 *
	 */
	class EBSDeleteTask extends CheckEBSVolumeStateTask
	{
		public function Run()
		{
			$DB = Core::GetDBInstance();
			$ebsinfo = $DB->GetRow("SELECT * FROM farm_ebs WHERE volumeid=?", array($this->VolumeID));
			if ($ebsinfo)
			{
				// Get farminfo from database
				$farminfo = $DB->GetRow("SELECT * FROM farms WHERE id=?", array($ebsinfo['farmid']));

				// Get EC2 Client
				$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['clientid']);
				
				try
				{
					// Check volume status
					$response = $EC2Client->DescribeVolumes($ebsinfo['volumeid']);
					
					$volume = $response->volumeSet->item;
				}
				catch(Exception $e)
				{
					if (stristr($e->getMessage(), "does not exist"))
					{
						$DB->Execute("DELETE FROM farm_ebs WHERE volumeid=?", array($this->VolumeID));
						return true;
					}
					else
					{
						Logger::getLogger(__CLASS__)->error(sprintf(_("Cannot get information about volume: %s"), $e->getMessage()));
						return false;
					}
				}
				
				Logger::getLogger(__CLASS__)->info(sprintf(_("Current volume '%s' status: %s"), 
					$ebsinfo['volumeid'], $volume->status)
				);
				
				switch ($volume->status)
				{
					case AMAZON_EBS_STATE::IN_USE:
						
						// If volume in-use we should detach it first
						try
						{
							$DetachVolumeType = new DetachVolumeType($ebsinfo['volumeid']);
							$EC2Client->DetachVolume($DetachVolumeType);
							
							$DB->Execute("UPDATE farm_ebs SET state=? WHERE volumeid=?", 
								array(FARM_EBS_STATE::DETACHING, $this->VolumeID)
							);
						}
						catch(Exception $e)
						{
							Logger::getLogger(__CLASS__)->error(sprintf(_("Cannot detach volume: %s"), $e->getMessage()));
						}
						
						return false;
						
						break;

					case AMAZON_EBS_STATE::DELETING:
					case AMAZON_EBS_STATE::DETACHING:
						
						// Waiting...
						return false;
						
						break;
						
					case AMAZON_EBS_STATE::AVAILABLE:
						
						// Send delete request
						
						try
						{
							Logger::getLogger(__CLASS__)->info(sprintf(_("Sending volume delete request to EC2. VolumeID: %s"), $ebsinfo['volumeid']));
							$EC2Client->DeleteVolume($ebsinfo['volumeid']);
							
							$DB->Execute("UPDATE farm_ebs SET state=? WHERE volumeid=?", 
								array(FARM_EBS_STATE::DELETING, $this->VolumeID)
							);
						}
						catch(Exception $e)
						{
							Logger::getLogger(__CLASS__)->error(sprintf(_("Cannot delete volume: %s"), $e->getMessage()));
							return false;
						}
						
						break;
				}
			}
			else
				return true;
			
			return false;
		}
	}
?>