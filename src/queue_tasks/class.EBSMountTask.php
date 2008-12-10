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
			$ebsinfo = $DB->GetRow("SELECT * FROM farm_ebs WHERE volumeid=?", array($this->VolumeID));
			if ($ebsinfo)
			{
				try
				{
					// Get farminfo from database
					$farminfo = $DB->GetRow("SELECT * FROM farms WHERE id=?", array($ebsinfo['farmid']));
					// Get instance info fro database
					$instanceinfo = $DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($ebsinfo['instance_id']));
					// Get farm role info
					$farm_role_info = $db->GetRow("SELECT * FROM farm_amis WHERE ami_id=? OR replace_to_ami=? AND farmid=?",
						array($instanceinfo['ami_id'], $instanceinfo['ami_id'], $farminfo['id'])
					);
					
					// Get EC2 Client
					$EC2Client = $this->GetAmazonEC2ClientObject($farminfo['clientid']);
					
					// Check volume status
					$response = $EC2Client->DescribeVolumes($ebsinfo['volumeid']);
					$volume = $response->volumeSet->item;
					
					if ($volume->status == AMAZON_EBS_STATE::IN_USE)
					{
						if ($volume->attachmentSet->status == 'attached')
						{
							$createfs = $farm_role_info['ebs_snapid'] ? 0 : 1;
	
							// Nicolas request. Device not avaiable on instance after attached state. need some time.
							sleep(5);
							
							$SNMP = new SNMP();
							$trap = vsprintf(SNMP_TRAP::MOUNT_EBS, array($ebsinfo['device'], $farm_role_info['ebs_mountpoint'], $createfs));
				            $res = $SNMP->SendTrap($trap);
				            $this->Logger->info("[FarmID: {$farminfo['id']}] Sending SNMP Trap mountEBS ({$trap}) to '{$instanceinfo['instance_id']}' ('{$instanceinfo['external_ip']}') complete ({$res})");
							
							$DB->Execute("UPDATE farm_ebs SET state=?, isfsexists='1' WHERE volumeid=?", array(FARM_EBS_STATE::ATTACHED, $ebsinfo['volumeid']));
						}
						else
							return false;
					}
					elseif ($volume->status == AMAZON_EBS_STATE::ATTACHING)
					{
						return false;
					}
					else
					{
						return true;
					}
				}
				catch(Exception $e)
				{
					LoggerManager::getLogger(__CLASS__)->fatal(sprintf(_("Cannot check EBS status: %s"), $e->getMessage()));
					return false;
				}
			}
			
			return false;
		}
	}
?>