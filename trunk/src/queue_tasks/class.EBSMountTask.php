<?
	/**
	 * Task for EBS mount routine aftre attachment complete
	 *
	 */
	class EBSMountTask extends CheckEBSVolumeStateTask
	{
		public function Run()
		{
			$DB = Core::GetDBInstance();
			
			LoggerManager::getLogger(__CLASS__)->info(
				sprintf(_("EBSMountTask: %s"), $this->VolumeID)
			);
			
			try
			{
				$DBEBSVolume = DBEBSVolume::Load($this->VolumeID);
			}
			catch (Exception $e)
			{
				
			}
			
			if ($DBEBSVolume)
			{
				try
				{
					// Get farminfo from database
					$farminfo = $DB->GetRow("SELECT * FROM farms WHERE id=?", array($DBEBSVolume->FarmID));
					// Get instance info fro database
					$DBInstance = DBInstance::LoadByIID($DBEBSVolume->InstanceID);
					$DBFarmRole = $DBInstance->GetDBFarmRoleObject();
										
					// Get EC2 Client
					$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['clientid'], $farminfo['region']);
					
					// Check volume status
					$response = $EC2Client->DescribeVolumes($DBEBSVolume->VolumeID);
					$volume = $response->volumeSet->item;
					
					LoggerManager::getLogger(__CLASS__)->info(
						sprintf(_("Volume status: %s (%s)"), $volume->status, $volume->attachmentSet->item->status)
					);
					
					if ($volume->status == AMAZON_EBS_STATE::IN_USE)
					{
						if ($volume->attachmentSet->item->status == 'attached')
						{
							$createfs = ($DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_SNAPID) || $DBEBSVolume->IsFSExists == 1) ? 0 : 1;
	
							// Nicolas request. Device not avaiable on instance after attached state. need some time.
							sleep(5);

							$DBInstance->SendMessage(new MountPointsReconfigureScalrMessage(
								$DBEBSVolume->Device, 
								($DBEBSVolume->IsManual == 1) ? $DBEBSVolume->MountPoint : $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_MOUNTPOINT), 
								$createfs
							));
							
							$DBEBSVolume->State = FARM_EBS_STATE::MOUNTING;
				            $DBEBSVolume->Save();
				            
				            return true;
						}
						else
							return false;
					}
					elseif ($volume->status == AMAZON_EBS_STATE::ATTACHING)
					{
						return false;
					}
					elseif ($volume->status == AMAZON_EBS_STATE::AVAILABLE)
					{
						try
						{
							Scalr::AttachEBS2Instance($EC2Client, $DBInstance, $farminfo, $DBEBSVolume);
							return true;
						}
						catch(Exception $e)
						{
							Logger::getLogger(LOG_CATEGORY::FARM)->fatal(new FarmLogMessage($DBEBSVolume->FarmID,
								sprintf(_("Cannot attach volume to instance: %s"), $e->getMessage())
							));
							return false;
						}
					}
					else
					{
						return true;
					}
				}
				catch(Exception $e)
				{
					if (!stristr($e->getMessage(), "DTD are not"))
						LoggerManager::getLogger(__CLASS__)->fatal(sprintf(_("Cannot check EBS status: %s"), $e->getMessage()));
					else
						LoggerManager::getLogger(__CLASS__)->warn(sprintf(_("Cannot check EBS status: %s"), $e->getMessage()));
						
					return false;
				}
			}
			
			return false;
		}
	}
?>