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

			try
			{
				$DBEBSVolume = DBEBSVolume::Load($this->VolumeID);
				if ($DBEBSVolume->State == FARM_EBS_STATE::ATTACHED)
					return true;
			}
			catch(Exception $e)
			{
				
			}
			
			if ($DBEBSVolume)
			{

				if ($DBEBSVolume->EBSArrayID)
				{
					$DBEBSArray = DBEBSArray::Load($DBEBSVolume->EBSArrayID);
					$Client = Client::Load($DBEBSArray->ClientID);
					$region = $DBEBSArray->Region;
				}
				else
				{
					// Get farminfo from database
					$farminfo = $DB->GetRow("SELECT * FROM farms WHERE id=?", array($DBEBSVolume->FarmID));
					$Client = Client::Load($farminfo['clientid']);
					$region = $farminfo['region'];
				}

				// Get EC2 Client
				$EC2Client = $this->GetAmazonEC2ClientObject($Client->ID, $region);
				
				try
				{
					// Check volume status
					$response = $EC2Client->DescribeVolumes($DBEBSVolume->VolumeID);
					
					$volume = $response->volumeSet->item;
				}
				catch(Exception $e)
				{
					if (stristr($e->getMessage(), "does not exist"))
					{
						$DBEBSVolume->Delete();
						return true;
					}
					else
					{
						Logger::getLogger(__CLASS__)->error(sprintf(_("Cannot get information about volume: %s"), $e->getMessage()));
						return false;
					}
				}
				
				Logger::getLogger(__CLASS__)->info(sprintf(_("Current volume '%s' status: %s"), 
					$DBEBSVolume->VolumeID, $volume->status)
				);
				
				switch ($volume->status)
				{
					case AMAZON_EBS_STATE::IN_USE:
						
						// If volume in-use we should detach it first
						try
						{
							$DetachVolumeType = new DetachVolumeType($DBEBSVolume->VolumeID);
							$EC2Client->DetachVolume($DetachVolumeType);
							
							$DBEBSVolume->State = FARM_EBS_STATE::DETACHING;
							$DBEBSVolume->Save();
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
							Logger::getLogger(__CLASS__)->info(sprintf(_("Sending volume delete request to EC2. VolumeID: %s"), $DBEBSVolume->VolumeID));
							$EC2Client->DeleteVolume($DBEBSVolume->VolumeID);
							
							$DBEBSVolume->State = FARM_EBS_STATE::DELETING;
							$DBEBSVolume->Save();
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