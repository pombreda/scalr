<?php
	class DBEventObserver extends EventObserver
	{
		/**
		 * Observer name
		 *
		 * @var unknown_type
		 */
		public $ObserverName = 'DB';
		
		/**
		 * Update database when 'mysqlBckComplete' event recieved from instance
		 *
		 * @param MysqlBackupCompleteEvent $event
		 */
		public function OnMysqlBackupComplete(MysqlBackupCompleteEvent $event)
		{
			$DBFarm = DBFarm::LoadByID($this->FarmID);
			
			if ($event->Operation == MYSQL_BACKUP_TYPE::DUMP)
			{
				$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_LAST_BCP_TS, time());
				$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BCP_RUNNING, 0);
			}
			elseif ($event->Operation == MYSQL_BACKUP_TYPE::BUNDLE)
			{
				$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_LAST_BUNDLE_TS, time());
				$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BUNDLE_RUNNING, 0);
								
				if ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_DATA_STORAGE_ENGINE) == MYSQL_STORAGE_ENGINE::EBS)
				{
					try
					{
						$DBfarmRole = DBFarmRole::LoadByID($event->DBInstance->FarmRoleID);
						$farm_roleid = $DBfarmRole->ID;
					}
					catch(Exception $e) {
						$farm_roleid = 0;
					}
					
					$this->DB->Execute("INSERT INTO ebs_snaps_info SET snapid=?, comment=?, dtcreated=NOW(), region=?, ebs_array_snapid='0', is_autoebs_master_snap='1', farm_roleid=?",
						array($event->SnapshotInfo, _('MySQL Master volume snapshot'), $farminfo['region'], $farm_roleid)
					);
				}
			}
		}
		
		/**
		 * Update database when 'mysqlBckFail' event recieved from instance
		 *
		 * @param MysqlBackupFailEvent $event
		 */
		public function OnMysqlBackupFail(MysqlBackupFailEvent $event)
		{
			$DBFarm = DBFarm::LoadByID($this->FarmID);
			
			$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BCP_RUNNING, 0);
			if ($event->Operation == MYSQL_BACKUP_TYPE::DUMP)
			{
				$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BCP_RUNNING, 0);
			}
			elseif ($event->Operation == MYSQL_BACKUP_TYPE::BUNDLE)
			{
				$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BUNDLE_RUNNING, 0);
			}
		}
		
		/**
		 * Update database when replication was broken on slave
		 *
		 * @param MySQLReplicationFailEvent $event
		 */
		public function OnMySQLReplicationFail(MySQLReplicationFailEvent $event)
		{
			$this->DB->Execute("UPDATE farm_instances SET mysql_replication_status='0' 
				WHERE id=?", array($event->DBInstance->ID)
			);
		}
		
		/**
		 * Update database when replication was recovered on slave
		 *
		 * @param MySQLReplicationRecoveredEvent $event
		 */
		public function OnMySQLReplicationRecovered(MySQLReplicationRecoveredEvent $event)
		{
			$this->DB->Execute("UPDATE farm_instances SET mysql_replication_status='1' 
				WHERE id=?", array($event->DBInstance->ID)
			);
		}
		
		/**
		 * Update database when 'newMysqlMaster' event recieved from instance
		 *
		 * @param NewMysqlMasterUpEvent $event
		 */
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event)
		{			
			$this->DB->Execute("UPDATE farm_instances SET isdbmaster='0' WHERE farmid=?", 
				array($this->FarmID)
			);
						
			$DBFarm = DBFarm::LoadByID($this->FarmID);
			$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_LAST_BUNDLE_TS, time());
			
			$this->DB->Execute("UPDATE farm_instances SET isdbmaster='1' WHERE farmid=? AND instance_id=?", 
				array($this->FarmID, $event->DBInstance->InstanceID)
			);
		}
		
		/**
		 * Update database when 'hostInit' event recieved from instance
		 *
		 * @param HostInitEvent $event
		 * 
		 */
		public function OnHostInit(HostInitEvent $event)
		{			
			$this->DB->Execute("UPDATE farm_instances SET internal_ip=?, external_ip=?, state=? WHERE id=?", 
				array($event->InternalIP, $event->ExternalIP, INSTANCE_STATE::INIT, $event->DBInstance->ID)
			);
		
			$DBFarm = DBFarm::LoadByID($this->FarmID);
			if (!$DBFarm->GetSetting(DBFarm::SETTING_AWS_PUBLIC_KEY))
				$DBFarm->SetSetting(DBFarm::SETTING_AWS_PUBLIC_KEY, $event->PublicKey);
			
			$event->DBInstance->ReLoad();
		}
			
		/**
		 * Update database when 'newAMI' event recieved from instance
		 *
		 * @param RebundleCompleteEvent $event
		 * 
		 */
		public function OnRebundleComplete(RebundleCompleteEvent $event)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$this->DB->Execute("UPDATE roles SET ami_id=?, iscompleted='1', dtbuilt=NOW(), prototype_role=?, prototype_iid = '', rebundle_trap_received = '1'
				WHERE prototype_iid=? AND iscompleted='0'", 
				array($event->AMIID, $event->DBInstance->GetDBFarmRoleObject()->GetRoleName(), $event->DBInstance->InstanceID)
			);
	
			$old_ami_info = $this->DB->GetRow("SELECT * FROM roles WHERE ami_id=?", 
				array($event->DBInstance->AMIID)
			);
			
			$ami_info = $this->DB->GetRow("SELECT * FROM roles WHERE ami_id=?", array($event->AMIID));
			
			//  Update params list //
			$this->DB->Execute("INSERT INTO role_options (name, type, isrequired, defval, allow_multiple_choice, options, ami_id, hash)
				SELECT name, type, isrequired, defval, allow_multiple_choice, options, ?, hash FROM role_options WHERE ami_id=?",
				array($event->AMIID, $event->DBInstance->AMIID)
			);
			
			/////////////////////////
			
			if ($event->DBInstance->State == INSTANCE_STATE::PENDING_TERMINATE)
			{
				$role_name = $this->DB->GetOne("SELECT name FROM roles WHERE ami_id=?", 
					array($event->AMIID)
				);

                $this->DB->Execute("UPDATE farm_ebs SET state=?, instance_id='' WHERE instance_id=? AND state=?", 
					array(FARM_EBS_STATE::AVAILABLE, $event->DBInstance->InstanceID, FARM_EBS_STATE::ATTACHED)
				);
                				
                $this->DB->Execute("UPDATE ebs_arrays SET status=?, instance_id='' WHERE instance_id=? AND status=?", 
					array(EBS_ARRAY_STATUS::AVAILABLE, $event->DBInstance->InstanceID, EBS_ARRAY_STATUS::IN_USE)
				);
				                
				if ($old_ami_info["roletype"] != ROLE_TYPE::SHARED)
				{
					$this->DB->Execute("UPDATE farm_roles SET ami_id=?, replace_to_ami='', dtlastsync=NOW() 
						WHERE ami_id=? AND farmid IN (SELECT id FROM farms WHERE clientid=?)",
						array($event->AMIID, $event->DBInstance->AMIID, $farminfo['clientid'])
					);
					
					$this->DB->Execute("UPDATE zones SET ami_id=?, role_name=? WHERE ami_id=? AND clientid=?", 
						array($event->AMIID, $role_name, $event->DBInstance->AMIID, $farminfo['clientid'])
					);
					
					if ($role_name == $this->DB->GetOne("SELECT name FROM roles WHERE ami_id=?", array($event->DBInstance->AMIID)))
                    {
						$this->Logger->info("Deleting old role AMI ('{$event->DBInstance->AMIID}') from database.");
						$this->DB->Execute("DELETE FROM roles WHERE ami_id=? AND roletype=?", array($event->DBInstance->AMIID, ROLE_TYPE::CUSTOM));
					}
				}
				else
				{
					$this->DB->Execute("UPDATE farm_roles SET ami_id='{$event->AMIID}', replace_to_ami='', dtlastsync=NOW() WHERE ami_id='{$event->DBInstance->AMIID}' AND farmid='{$farminfo['id']}'");
					$this->DB->Execute("UPDATE zones SET ami_id='{$event->AMIID}', role_name='{$role_name}' WHERE ami_id='{$event->DBInstance->AMIID}' AND clientid='{$farminfo['clientid']}' AND farmid='{$farminfo['id']}'");
				}
				
				$this->DB->Execute("UPDATE roles SET `replace`='' WHERE ami_id='{$event->AMIID}'");
			}
			else
			{
				if ($ami_info["replace"])
				{
					if ($old_ami_info["roletype"] == ROLE_TYPE::SHARED || $old_ami_info["name"] != $ami_info["name"])
						$this->DB->Execute("UPDATE farm_roles SET replace_to_ami='{$event->AMIID}', dtlastsync=NOW() WHERE ami_id='{$ami_info['replace']}' AND farmid='{$event->DBInstance->FarmID}'");
					else
					{
						// If new role name == old role name we need replace all instances on all farms with new ami
						$this->DB->Execute("UPDATE farm_roles SET replace_to_ami='{$event->AMIID}', dtlastsync=NOW() WHERE ami_id='{$ami_info['replace']}' AND farmid IN (SELECT id FROM farms WHERE clientid='{$farminfo['clientid']}')");
					}
				}
			}

			// Add record to log
			$roleid = $this->DB->GetOne("SELECT id FROM roles WHERE ami_id=?", array($event->AMIID));
			$this->DB->Execute("INSERT INTO rebundle_log SET roleid=?, dtadded=NOW(), message=?", array($roleid, _("Rebundle complete.")));
		}
		
		/**
		 * Called when scalr recived notify about rebundle failure from instance
		 *
		 * @param RebundleFailedEvent $event
		 */
		public function OnRebundleFailed(RebundleFailedEvent $event)
		{
			$roleinfo = $this->DB->GetRow("SELECT * FROM roles WHERE prototype_iid=? AND iscompleted='0'", 
				array($event->DBInstance->InstanceID)
			); 
			
			$this->DB->Execute("UPDATE roles SET iscompleted='2', `replace`='', fail_details=?, prototype_iid='' WHERE prototype_iid=? AND iscompleted='0'", 
				array(_("Rebundle script failed. See event log for more information."), $event->DBInstance->InstanceID)
			);
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			if ($farminfo['status'] == FARM_STATUS::SYNCHRONIZING && $farminfo['term_on_sync_fail'] == 0)
			{
				Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($this->FarmID, "Synchronization of role {$roleinfo['name']} failed. According to your preference, farm {$farminfo['name']} will not be terminated."));
				
				$this->DB->Execute("UPDATE farm_instances SET state=? WHERE farmid=? AND state=?",
					array(INSTANCE_STATE::RUNNING, $this->FarmID, INSTANCE_STATE::PENDING_TERMINATE)
				);
				
				$this->DB->Execute("UPDATE farms SET status=? WHERE id=?",
					array(FARM_STATUS::RUNNING, $this->FarmID)
				);
			}
		}

		/**
		 * Farm launched
		 *
		 * @param FarmLaunchedEvent $event
		 */
		public function OnFarmLaunched(FarmLaunchedEvent $event)
		{
			$DBFarm = DBFarm::LoadByID($this->FarmID);
			$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BCP_RUNNING, 0);
			$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BUNDLE_RUNNING, 0);

			
			// TODO: Refactoting -> Move to DBFarm class
			$this->DB->Execute("UPDATE farms SET status=?, dtlaunched=NOW() WHERE id=?",
				array(FARM_STATUS::RUNNING, $this->FarmID)
			);
		}
		
		/**
		 * Farm terminated
		 *
		 * @param FarmTerminatedEvent $event
		 */
		public function OnFarmTerminated(FarmTerminatedEvent $event)
		{
			$sync_instances = $this->DB->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state=? AND farmid=? AND dtshutdownscheduled IS NULL",
				array(INSTANCE_STATE::PENDING_TERMINATE, $this->FarmID)
			);
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$farm_status = ($sync_instances > 0 && $farminfo['status'] == FARM_STATUS::RUNNING) ? FARM_STATUS::SYNCHRONIZING : FARM_STATUS::TERMINATED; 
			
			$this->DB->Execute("UPDATE farms SET status=?, term_on_sync_fail=? WHERE id=?",
				array($farm_status, $event->TermOnSyncFail, $this->FarmID)
			);
		}
		
		/**
		 * Called when instance configured and upped
		 *
		 * @param HostUpEvent $event
		 */
		public function OnHostUp(HostUpEvent $event)
		{
			$this->DB->Execute("UPDATE farm_instances SET state=? WHERE id='{$event->DBInstance->ID}'", 
				array(INSTANCE_STATE::RUNNING)
			);
			
			if ($event->ReplUserPass)
			{
				$this->DB->Execute("UPDATE farm_instances SET mysql_stat_password = ? WHERE id = ?", 
					array($event->ReplUserPass, $event->DBInstance->ID)
				);
			}
		}
		
		/**
		 * Called when reboot complete
		 *
		 * @param RebootCompleteEvent $event
		 */
		public function OnRebootComplete(RebootCompleteEvent $event)
		{
			$this->DB->Execute("UPDATE farm_instances SET isrebootlaunched='0', dtrebootstart=NULL WHERE id='{$event->DBInstance->ID}'");
		}
		
		/**
		 * Called when instance receive reboot command
		 *
		 * @param RebootBeginEvent $event
		 */
		public function OnRebootBegin(RebootBeginEvent $event)
		{
			$this->DB->Execute("UPDATE farm_instances SET isrebootlaunched='1', dtrebootstart=NOW() WHERE id='{$event->DBInstance->ID}'");
		}
		
		/**
		 * Called when instance going down
		 *
		 * @param HostDownEvent $event
		 */
		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->DBInstance->IsRebootLaunched == 1)
				return;
			
			// Delete instance from database
			$this->DB->Execute("UPDATE farm_instances SET state = ? WHERE farmid=? AND instance_id=?", 
				array(INSTANCE_STATE::TERMINATED, $this->FarmID, $event->DBInstance->InstanceID)
			);

			$this->DB->Execute("UPDATE instances_history SET
	        	dtterminated	= ?,
	        	uptime = ".time()."-dtlaunched
	        	WHERE instance_id = ?
	        ", array(
	        	time(),
	        	$event->DBInstance->InstanceID
	        ));
			
	        $DBFarm = DBFarm::LoadByID($event->DBInstance->FarmID);
	        if ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BCP_INSTANCE_ID) == $event->DBInstance->InstanceID)
	        	$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BCP_RUNNING, 0);
	        
	        if ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BUNDLE_INSTANCE_ID) == $event->DBInstance->InstanceID)
	        	$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BUNDLE_RUNNING, 0);
	        							
			//
			// Check running synchronizations. Update synchronization status to failed
			//
			$sync_roles = $this->DB->GetAll("SELECT * FROM roles WHERE prototype_iid=? AND iscompleted='0'", 
				array($event->DBInstance->InstanceID)
			);
			foreach ($sync_roles as $sync_role)
			{
				$this->DB->Execute("UPDATE roles SET 
					iscompleted='2', 
					`replace`='', 
					prototype_iid='', 
					fail_details=? WHERE id='{$sync_role['id']}'", 
					array("Instance terminated during synchronization.")
				);
			}
			
			$sync_roles = $this->DB->GetAll("SELECT * FROM roles WHERE prototype_iid=? AND iscompleted='1'", array($event->DBInstance->InstanceID));
			foreach ($sync_roles as $sync_role)
			{
				$this->DB->Execute("UPDATE roles SET `replace`='', prototype_iid='' WHERE id='{$sync_role['id']}'");
			}
			
			// Update elastic IPs  mysql table, mark used IP as unused
			$this->DB->Execute("UPDATE elastic_ips SET state='0', instance_id='' WHERE instance_id=? AND farmid=?",
				array($event->DBInstance->InstanceID, $this->FarmID)
			);
			
			$this->DB->Execute("UPDATE farm_ebs SET state='Available', instance_id='', device='' WHERE instance_id=? AND farmid=?",
				array($event->DBInstance->InstanceID, $this->FarmID)
			);
			
			//
			//
			//
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
						
			if ($farminfo['status'] == FARM_STATUS::SYNCHRONIZING)
			{

				$event->DBInstance->SkipEBSObserver = true;
				
				$farm_instances_count = $this->DB->GetOne("SELECT COUNT(*) FROM farm_instances WHERE farmid=? and instance_id != ? and state != ?", 
					array($this->FarmID, $event->DBInstance->InstanceID, INSTANCE_STATE::TERMINATED)
				);
				
				if ($farm_instances_count == 0)
				{
					$this->DB->Execute("UPDATE farms SET status=? WHERE id=?", 
						array(FARM_STATUS::TERMINATED, $this->FarmID)
					);
				}
			}
		}
		
		public function OnIPAddressChanged(IPAddressChangedEvent $event)
		{
			Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($this->FarmID, "IP changed for instance {$event->DBInstance->InstanceID}. New IP address: {$event->NewIPAddress}"));
			$this->DB->Execute("UPDATE farm_instances SET external_ip=?, isipchanged='0', isactive='1' WHERE id=?",
				array($event->NewIPAddress, $event->DBInstance->ID)
			);
		}
		
		public function OnBeforeHostTerminate(BeforeHostTerminateEvent $event)
		{
			if ($event->DBInstance->State != INSTANCE_STATE::PENDING_TERMINATE)
			{ 
				$this->DB->Execute("UPDATE farm_instances SET dtshutdownscheduled=NOW(), state=? WHERE id=?",
					array(INSTANCE_STATE::PENDING_TERMINATE, $event->DBInstance->ID)
				);
			}
		}
	}
?>
