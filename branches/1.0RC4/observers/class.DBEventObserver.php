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
			if ($event->Operation == MYSQL_BACKUP_TYPE::DUMP)
				$this->DB->Execute("UPDATE farms SET dtlastbcp=?, isbcprunning='0' WHERE id=?",
					array(time(), $this->FarmID)
				);
			elseif ($event->Operation == MYSQL_BACKUP_TYPE::BUNDLE)
				$this->DB->Execute("UPDATE farms SET dtlastrebundle=?, isbundlerunning='0' WHERE id=?",
					array(time(), $this->FarmID)
				);
		}
		
		/**
		 * Update database when 'mysqlBckFail' event recieved from instance
		 *
		 * @param MysqlBackupFailEvent $event
		 */
		public function OnMysqlBackupFail(MysqlBackupFailEvent $event)
		{
			if ($event->Operation == MYSQL_BACKUP_TYPE::DUMP)
				$this->DB->Execute("UPDATE farms SET isbcprunning='0' WHERE id=?", 
					array($this->FarmID)
				);
			elseif ($event->Operation == MYSQL_BACKUP_TYPE::BUNDLE)
				$this->DB->Execute("UPDATE farms SET isbundlerunning='0' WHERE id=?", 
					array($this->FarmID)
				);
		}
		
		/**
		 * Update database when replication was broken on slave
		 *
		 * @param MySQLReplicationFailEvent $event
		 */
		public function OnMySQLReplicationFail(MySQLReplicationFailEvent $event)
		{
			$this->DB->Execute("UPDATE farm_instances SET mysql_replication_status='0' 
				WHERE id=?", array($event->InstanceInfo['id'])
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
				WHERE id=?", array($event->InstanceInfo['id'])
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
			
			$this->DB->Execute("UPDATE farms SET dtlastrebundle=? WHERE id=?",
				array(time(), $this->FarmID)
			);
			
			$this->DB->Execute("UPDATE farm_instances SET isdbmaster='1' WHERE farmid=? AND instance_id=?", 
				array($this->FarmID, $event->InstanceInfo['instance_id'])
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
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", 
				array($this->FarmID)
			);
			
			$this->DB->Execute("UPDATE farm_instances SET internal_ip=?, external_ip=?, state=? WHERE id=?", 
				array($event->InternalIP, $event->ExternalIP, INSTANCE_STATE::INIT, $event->InstanceInfo['id'])
			);
		
			if (!$farminfo["public_key"])
			{
				$this->DB->Execute("UPDATE farms SET public_key=? WHERE id=?", 
					array($event->PublicKey, $this->FarmID)
				);
			}
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
			
			$this->DB->Execute("UPDATE ami_roles SET ami_id=?, iscompleted='1', dtbuilt=NOW(), prototype_role=? 
				WHERE prototype_iid=? AND iscompleted='0'", 
				array($event->AMIID, $event->InstanceInfo['role_name'], $event->InstanceInfo['instance_id'])
			);
	
			$old_ami_info = $this->DB->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", 
				array($event->InstanceInfo['ami_id'])
			);
			
			$ami_info = $this->DB->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($event->AMIID));
			
			//  Update params list //
			$this->DB->Execute("INSERT INTO role_options (name, type, isrequired, defval, allow_multiple_choice, options, ami_id, hash)
				SELECT name, type, isrequired, defval, allow_multiple_choice, options, ?, hash FROM role_options WHERE ami_id=?",
				array($event->AMIID, $event->InstanceInfo['ami_id'])
			);
			
			if ($ami_info["replace"])
			{
				$this->DB->Execute("INSERT INTO farm_role_options (farmid, ami_id, name, value, hash)
					SELECT ?, ?, name, value, hash FROM farm_role_options WHERE farmid=? AND ami_id=?",
					array($this->FarmID, $event->AMIID, $this->FarmID, $event->InstanceInfo['ami_id'])
				);
			}
			/////////////////////////
			
			if ($event->InstanceInfo['state'] == INSTANCE_STATE::PENDING_TERMINATE)
			{
				$role_name = $this->DB->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", 
					array($event->AMIID)
				);

				$this->DB->Execute("UPDATE elastic_ips SET role_name=? WHERE farmid=? AND role_name=?",
					array($role_name, $this->FarmID, $event->InstanceInfo['role_name'])
				);

				$this->DB->Execute("UPDATE farm_ebs SET role_name=? WHERE farmid=? AND role_name=?",
					array($role_name, $this->FarmID, $event->InstanceInfo['role_name'])
                );
				
                $this->DB->Execute("UPDATE farm_ebs SET state=?, instance_id='' WHERE instance_id=? AND state=?", 
					array(FARM_EBS_STATE::AVAILABLE, $event->InstanceInfo['instance_id'], FARM_EBS_STATE::ATTACHED)
				);
                
                $this->DB->Execute("UPDATE vhosts SET role_name=? WHERE farmid=? AND role_name=?",
					array($role_name, $this->FarmID, $event->InstanceInfo['role_name'])
                );
                
				if ($old_ami_info["roletype"] != "SHARED")
				{
					$this->DB->Execute("UPDATE farm_amis SET ami_id=?, replace_to_ami='', dtlastsync=NOW() 
						WHERE ami_id=? AND farmid IN (SELECT id FROM farms WHERE clientid=?)",
						array($event->AMIID, $event->InstanceInfo['ami_id'], $farminfo['clientid'])
					);
					
					$this->DB->Execute("UPDATE zones SET ami_id=?, role_name=? WHERE ami_id=? AND clientid=?", 
						array($event->AMIID, $role_name, $event->InstanceInfo['ami_id'], $farminfo['clientid'])
					);
					
					// Update ami in role scripts
					$this->DB->Execute("UPDATE farm_role_scripts SET ami_id='{$event->AMIID}' WHERE ami_id='{$event->InstanceInfo['ami_id']}' AND farmid IN (SELECT id FROM farms WHERE clientid='{$farminfo['clientid']}')");
					
					if ($role_name == $this->DB->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($event->InstanceInfo['ami_id'])))
                    {
						$this->Logger->info("Deleting old role AMI ('{$event->InstanceInfo['ami_id']}') from database.");
						$this->DB->Execute("DELETE FROM ami_roles WHERE ami_id=?", array($event->InstanceInfo['ami_id']));
					}
				}
				else
				{
					$this->DB->Execute("UPDATE farm_amis SET ami_id='{$event->AMIID}', replace_to_ami='', dtlastsync=NOW() WHERE ami_id='{$event->InstanceInfo['ami_id']}' AND farmid='{$farminfo['id']}'");
					$this->DB->Execute("UPDATE zones SET ami_id='{$event->AMIID}', role_name='{$role_name}' WHERE ami_id='{$event->InstanceInfo['ami_id']}' AND clientid='{$farminfo['clientid']}' AND farmid='{$farminfo['id']}'");
					$this->DB->Execute("UPDATE farm_role_scripts SET ami_id='{$event->AMIID}' WHERE ami_id='{$event->InstanceInfo['ami_id']}' AND farmid='{$farminfo['id']}'");
				}
				
				$this->DB->Execute("UPDATE ami_roles SET `replace`='' WHERE ami_id='{$event->AMIID}'");
			}
			else
			{
				if ($ami_info["replace"])
				{
					if ($old_ami_info["roletype"] == ROLE_TYPE::SHARED || $old_ami_info["name"] != $ami_info["name"])
						$this->DB->Execute("UPDATE farm_amis SET replace_to_ami='{$event->AMIID}', dtlastsync=NOW() WHERE ami_id='{$ami_info['replace']}' AND farmid='{$event->InstanceInfo['farmid']}'");
					else
					{
						// If new role name == old role name we need replace all instances on all farms with new ami
						$this->DB->Execute("UPDATE farm_amis SET replace_to_ami='{$event->AMIID}', dtlastsync=NOW() WHERE ami_id='{$ami_info['replace']}' AND farmid IN (SELECT id FROM farms WHERE clientid='{$farminfo['clientid']}')");
					}
				}
			}

			// Add record to log
			$roleid = $this->DB->GetOne("SELECT id FROM ami_roles WHERE ami_id=?", array($event->AMIID));
			$this->DB->Execute("INSERT INTO rebundle_log SET roleid=?, dtadded=NOW(), message=?", array($roleid, _("Rebundle complete.")));
		}
		
		/**
		 * Called when scalr recived notify about rebundle failure from instance
		 *
		 * @param RebundleFailedEvent $event
		 */
		public function OnRebundleFailed(RebundleFailedEvent $event)
		{
			$roleinfo = $this->DB->GetRow("SELECT * FROM ami_roles WHERE prototype_iid=? AND iscompleted='0'", 
				array($event->InstanceInfo["instance_id"])
			); 
			
			$this->DB->Execute("UPDATE ami_roles SET iscompleted='2', `replace`='', fail_details=? WHERE prototype_iid=? AND iscompleted='0'", 
				array(_("Rebundle script failed. See event log for more information."), $event->InstanceInfo["instance_id"])
			);
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			if ($farminfo['status'] == FARM_STATUS::SYNCHRONIZING && $farminfo['term_on_sync_fail'] == 0)
			{
				$this->Logger->warn(new FarmLogMessage($this->FarmID, "Synchronization of role {$roleinfo['name']} failed. According to your preference, farm {$farminfo['name']} will not be terminated."));
				
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
			$this->DB->Execute("UPDATE farms SET status=?, isbcprunning='0', isbundlerunning='0', dtlaunched=NOW() WHERE id=?",
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
			$sync_instances = $this->DB->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state=? AND farmid=?",
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
			$this->DB->Execute("UPDATE farm_instances SET state=? WHERE id='{$event->InstanceInfo['id']}'", 
				array(INSTANCE_STATE::RUNNING)
			);
			
			$this->DB->Execute("UPDATE farm_instances SET mysql_stat_password = ? WHERE id = ?", 
				array($event->ReplUserPass, $event->InstanceInfo['id'])
			);
		}
		
		/**
		 * Called when reboot complete
		 *
		 * @param RebootCompleteEvent $event
		 */
		public function OnRebootComplete(RebootCompleteEvent $event)
		{
			$this->DB->Execute("UPDATE farm_instances SET isrebootlaunched='0', dtrebootstart=NULL WHERE id='{$event->InstanceInfo['id']}'");
		}
		
		/**
		 * Called when instance receive reboot command
		 *
		 * @param RebootBeginEvent $event
		 */
		public function OnRebootBegin(RebootBeginEvent $event)
		{
			$this->DB->Execute("UPDATE farm_instances SET isrebootlaunched='1', dtrebootstart=NOW() WHERE id='{$event->InstanceInfo['id']}'");
		}
		
		/**
		 * Called when instance going down
		 *
		 * @param HostDownEvent $event
		 */
		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->InstanceInfo['isrebootlaunched'] == 1)
				return;
			
			// Delete instance from database
			$this->DB->Execute("DELETE FROM farm_instances WHERE farmid=? AND instance_id=?", 
				array($this->FarmID, $event->InstanceInfo["instance_id"])
			);
			
			// Update backup status for farm
			$this->DB->Execute("UPDATE farms SET isbcprunning='0', isbundlerunning='0' WHERE bcp_instance_id=?", 
				array($event->InstanceInfo["instance_id"])
			);
						
			//
			// Check running synchronizations. Update synchronization status to failed
			//
			$sync_roles = $this->DB->GetAll("SELECT * FROM ami_roles WHERE prototype_iid=? AND iscompleted='0'", 
				array($event->InstanceInfo["instance_id"])
			);
			foreach ($sync_roles as $sync_role)
			{
				$this->DB->Execute("UPDATE ami_roles SET 
					iscompleted='2', 
					`replace`='', 
					prototype_iid='', 
					fail_details=? WHERE id='{$sync_role['id']}'", 
					array("Instance terminated during synchronization.")
				);
			}
			
			$sync_roles = $this->DB->GetAll("SELECT * FROM ami_roles WHERE prototype_iid=? AND iscompleted='1'", array($event->InstanceInfo["instance_id"]));
			foreach ($sync_roles as $sync_role)
			{
				$this->DB->Execute("UPDATE ami_roles SET `replace`='', prototype_iid='' WHERE id='{$sync_role['id']}'");
			}
			
			// Update elastic IPs  mysql table, mark used IP as unused
			$this->DB->Execute("UPDATE elastic_ips SET state='0', instance_id='' WHERE instance_id=? AND farmid=?",
				array($event->InstanceInfo['instance_id'], $this->FarmID)
			);
			
			//
			//
			//
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
						
			if ($farminfo['status'] == FARM_STATUS::SYNCHRONIZING)
			{

				$event->InstanceInfo['skip_ebs_observer'] = true;
				
				$farm_instances_count = $this->DB->GetOne("SELECT COUNT(*) FROM farm_instances WHERE farmid=? and instance_id != ?", 
					array($this->FarmID, $event->InstanceInfo["instance_id"])
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
			$this->Logger->warn(new FarmLogMessage($this->FarmID, "IP changed for instance {$event->InstanceInfo['instance_id']}. New IP address: {$event->NewIPAddress}"));
			$this->DB->Execute("UPDATE farm_instances SET external_ip=?, isipchanged='0', isactive='1' WHERE id=?",
				array($event->NewIPAddress, $event->InstanceInfo["id"])
			);
		}
	}
?>
