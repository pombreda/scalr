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
		 * @param string $operation (backup or bundle)
		 */
		public function OnMysqlBackupComplete($operation)
		{
			if ($operation == MYSQL_BACKUP_TYPE::DUMP)
				$this->DB->Execute("UPDATE farms SET dtlastbcp=?, isbcprunning='0' WHERE id=?",
					array(time(), $this->FarmID)
				);
			elseif ($operation == MYSQL_BACKUP_TYPE::BUNDLE)
				$this->DB->Execute("UPDATE farms SET dtlastrebundle=?, isbundlerunning='0' WHERE id=?",
					array(time(), $this->FarmID)
				);
		}
		
		/**
		 * Update database when 'mysqlBckFail' event recieved from instance
		 *
		 * @param string $operation (backup or bundle)
		 */
		public function OnMysqlBackupFail($operation)
		{
			if ($operation == MYSQL_BACKUP_TYPE::DUMP)
				$this->DB->Execute("UPDATE farms SET isbcprunning='0' WHERE id=?", 
					array($this->FarmID)
				);
			elseif ($operation == MYSQL_BACKUP_TYPE::BUNDLE)
				$this->DB->Execute("UPDATE farms SET isbundlerunning='0' WHERE id=?", 
					array($this->FarmID)
				);
		}
		
		/**
		 * Update database when replication was broken on slave
		 *
		 * @param array $instanceinfo
		 */
		public function OnMySQLReplicationFail($instanceinfo)
		{
			$this->DB->Execute("UPDATE farm_instances SET mysql_replication_status='0' 
				WHERE id=?", array($instanceinfo['id'])
			);
		}
		
		/**
		 * Update database when replication was recovered on slave
		 *
		 * @param array $instanceinfo
		 */
		public function OnMySQLReplicationRecovered($instanceinfo)
		{
			$this->DB->Execute("UPDATE farm_instances SET mysql_replication_status='1' 
				WHERE id=?", array($instanceinfo['id'])
			);
		}
		
		/**
		 * Update database when 'newMysqlMaster' event recieved from instance
		 *
		 * @param array $instanceinfo
		 * @param string $snapurl
		 */
		public function OnNewMysqlMasterUp($instanceinfo, $snapurl)
		{
			$this->DB->Execute("UPDATE farm_instances SET isdbmaster='0' WHERE farmid=?", 
				array($this->FarmID)
			);
			
			$this->DB->Execute("UPDATE farms SET dtlastrebundle=? WHERE id=?",
				array(time(), $this->FarmID)
			);
			
			$this->DB->Execute("UPDATE farm_instances SET isdbmaster='1' WHERE farmid=? AND instance_id=?", 
				array($this->FarmID, $instanceinfo['instance_id'])
			);
		}
		
		/**
		 * Update database when 'hostInit' event recieved from instance
		 *
		 * @param array $instanceinfo
		 * @param string $local_ip
		 * @param string $remote_ip
		 * @param string $public_key
		 * 
		 */
		public function OnHostInit($instanceinfo, $local_ip, $remote_ip, $public_key)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", 
				array($this->FarmID)
			);
			
			$this->DB->Execute("UPDATE farm_instances SET internal_ip=?, external_ip=?, state=? WHERE id=?", 
				array($local_ip, $remote_ip, INSTANCE_STATE::INIT, $instanceinfo['id'])
			);
		
			if (!$farminfo["public_key"])
			{
				$this->DB->Execute("UPDATE farms SET public_key=? WHERE id=?", 
					array($public_key, $this->FarmID)
				);
			}
		}
		
		/**
		 * Update database when instance crached
		 *
		 * @param array $instanceinfo
		 * 
		 */
		public function OnHostCrash($instanceinfo)
		{
			$this->OnHostDown($instanceinfo);
		}
	
		/**
		 * Update database when 'newAMI' event recieved from instance
		 *
		 * @param string $ami_id
		 * @param array $instanceinfo
		 * 
		 */
		public function OnRebundleComplete($ami_id, $instanceinfo)
		{
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$this->DB->Execute("UPDATE ami_roles SET ami_id=?, iscompleted='1', dtbuilt=NOW() 
				WHERE prototype_iid=? AND iscompleted='0'", 
				array($ami_id, $instanceinfo['instance_id'])
			);
	
			$old_ami_info = $this->DB->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", 
				array($instanceinfo['ami_id'])
			);
			
			if ($instanceinfo['state'] == INSTANCE_STATE::PENDING_TERMINATE)
			{
				$role_name = $this->DB->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", 
					array($ami_id)
				);

				$this->DB->Execute("UPDATE elastic_ips SET role_name=? WHERE farmid=? AND role_name=?",
					array($role_name, $this->FarmID, $instanceinfo['role_name'])
				);
                        
				if ($old_ami_info["roletype"] != "SHARED")
				{
					$this->DB->Execute("UPDATE farm_amis SET ami_id=?, replace_to_ami='', dtlastsync=NOW() 
						WHERE ami_id=? AND farmid IN (SELECT id FROM farms WHERE clientid=?)",
						array($ami_id, $instanceinfo['ami_id'], $farminfo['clientid'])
					);
					
					$this->DB->Execute("UPDATE zones SET ami_id=?, role_name=? WHERE ami_id=? AND clientid=?", 
						array($ami_id, $role_name, $instanceinfo['ami_id'], $farminfo['clientid'])
					);
					
					if ($role_name == $this->DB->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($instanceinfo['ami_id'])))
                    {
						$this->Logger->info("Deleting old role AMI ('{$instanceinfo['ami_id']}') from database.");
						$this->DB->Execute("DELETE FROM ami_roles WHERE ami_id=?", array($instanceinfo['ami_id']));
					}
				}
				else
				{
					$this->DB->Execute("UPDATE farm_amis SET ami_id='{$ami_id}', replace_to_ami='', dtlastsync=NOW() WHERE ami_id='{$instanceinfo['ami_id']}' AND farmid='{$farminfo['id']}'");
					$this->DB->Execute("UPDATE zones SET ami_id='{$ami_id}', role_name='{$role_name}' WHERE ami_id='{$instanceinfo['ami_id']}' AND clientid='{$farminfo['clientid']}' AND farmid='{$farminfo['id']}'");
				}

				$this->DB->Execute("UPDATE elastic_ips SET role_name=? WHERE role_name=? AND farmid=?",
					array($role_name, $instanceinfo['role_name'], $this->FarmID)
				);
				
				$this->DB->Execute("UPDATE ami_roles SET `replace`='' WHERE ami_id='{$ami_id}'");
			}
			else
			{
				$ami_info = $this->DB->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($ami_id));
				if ($ami_info["replace"])
				{
					if ($old_ami_info["roletype"] == ROLE_TYPE::SHARED || $old_ami_info["name"] != $ami_info["name"])
						$this->DB->Execute("UPDATE farm_amis SET replace_to_ami='{$ami_id}', dtlastsync=NOW() WHERE ami_id='{$ami_info['replace']}' AND farmid='{$instanceinfo['farmid']}'");
					else
					{
						// If new role name == old role name we need replace all instances on all farms with new ami
						$this->DB->Execute("UPDATE farm_amis SET replace_to_ami='{$ami_id}', dtlastsync=NOW() WHERE ami_id='{$ami_info['replace']}' AND farmid IN (SELECT id FROM farms WHERE clientid='{$farminfo['clientid']}')");
					}
				}
			}

			// Add record to log
			$roleid = $this->DB->GetOne("SELECT id FROM ami_roles WHERE ami_id=?", array($ami_id));
			$this->DB->Execute("INSERT INTO rebundle_log SET roleid=?, dtadded=NOW(), message=?", array($roleid, "Rebundle complete."));
		}
		
		/**
		 * Called when scalr recived notify about rebundle failure from instance
		 *
		 * @param array $instanceinfo
		 */
		public function OnRebundleFailed($instanceinfo)
		{
			$roleinfo = $this->DB->GetRow("SELECT * FROM ami_roles WHERE prototype_iid=? AND iscompleted='0'", 
				array($instanceinfo["instance_id"])
			); 
			
			$this->DB->Execute("UPDATE ami_roles SET iscompleted='2', `replace`='', fail_details=? WHERE prototype_iid=? AND iscompleted='0'", 
				array("Rebundle script failed. See event log for more information.", $instanceinfo["instance_id"])
			);
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			if ($farminfo['status'] == FARM_STATUS::SYNCHRONIZING && $farminfo['term_on_sync_fail'] == 0)
			{
				$this->Logger->warn(new FarmLogMessage($this->FarmID, "Synchronization of role {$roleinfo['name']} failed. According to your preference, farm {$farminfo['name']} will not be terminated."));
				$this->DB->Execute("UPDATE farms SET status=? WHERE id=?",
					array(FARM_STATUS::RUNNING, $this->FarmID)
				);
				
				$this->DB->Execute("UPDATE farm_instances SET state=? WHERE farmid=? AND state=?",
					array(INSTANCE_STATE::RUNNING, $this->FarmID, INSTANCE_STATE::PENDING_TERMINATE)
				);
			}
		}

		/**
		 * Farm launched
		 *
		 * @param bool $mark_instances_as_active
		 */
		public function OnFarmLaunched($mark_instances_as_active)
		{
			$this->DB->Execute("UPDATE farms SET status=?, isbcprunning='0', isbundlerunning='0', dtlaunched=NOW() WHERE id=?",
				array(FARM_STATUS::RUNNING, $this->FarmID)
			);
		}
		
		/**
		 * Farm terminated
		 *
		 * @param bool $remove_zone_from_DNS
		 */
		public function OnFarmTerminated($remove_zone_from_DNS, $keep_elastic_ips, $term_on_sync_fail)
		{
			$sync_instances = $this->DB->GetOne("SELECT COUNT(*) FROM farm_instances WHERE state=? AND farmid=?",
				array(INSTANCE_STATE::PENDING_TERMINATE, $this->FarmID)
			);
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			
			$farm_status = ($sync_instances > 0 && $farminfo['status'] == FARM_STATUS::RUNNING) ? FARM_STATUS::SYNCHRONIZING : FARM_STATUS::TERMINATED; 
			
			$this->DB->Execute("UPDATE farms SET status=?, term_on_sync_fail=? WHERE id=?",
				array($farm_status, $term_on_sync_fail, $this->FarmID)
			);
		}
		
		/**
		 * Called when instance configured and upped
		 *
		 * @param array $instanceinfo
		 */
		public function OnHostUp($instanceinfo)
		{
			$this->DB->Execute("UPDATE farm_instances SET state=? WHERE id='{$instanceinfo['id']}'", array(INSTANCE_STATE::RUNNING));
		}
		
		/**
		 * Called when reboot complete
		 *
		 * @param array $instanceinfo
		 */
		public function OnRebootComplete($instanceinfo)
		{
			$this->DB->Execute("UPDATE farm_instances SET isrebootlaunched='0', dtrebootstart=NULL WHERE id='{$instanceinfo['id']}'");
		}
		
		/**
		 * Called when instance receive reboot command
		 *
		 * @param array $instanceinfo
		 */
		public function OnRebootBegin($instanceinfo)
		{
			$this->DB->Execute("UPDATE farm_instances SET isrebootlaunched='1', dtrebootstart=NOW() WHERE id='{$instanceinfo['id']}'");
		}
		
		/**
		 * Called when instance going down
		 *
		 * @param array $instanceinfo
		 */
		public function OnHostDown($instanceinfo)
		{
			// Delete instance from database
			$this->DB->Execute("DELETE FROM farm_instances WHERE farmid=? AND instance_id=?", 
				array($this->FarmID, $instanceinfo["instance_id"])
			);
			
			// Update backup status for farm
			$this->DB->Execute("UPDATE farms SET isbcprunning='0', isbundlerunning='0' WHERE bcp_instance_id=?", 
				array($instanceinfo["instance_id"])
			);
						
			//
			// Check running synchronizations. Update synchronization status to failed
			//
			$sync_roles = $this->DB->GetAll("SELECT * FROM ami_roles WHERE prototype_iid=? AND iscompleted='0'", 
				array($instanceinfo["instance_id"])
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
			
			$sync_roles = $this->DB->GetAll("SELECT * FROM ami_roles WHERE prototype_iid=? AND iscompleted='1'", array($instanceinfo["instance_id"]));
			foreach ($sync_roles as $sync_role)
			{
				$this->DB->Execute("UPDATE ami_roles SET `replace`='', prototype_iid='' WHERE id='{$sync_role['id']}'");
			}
			
			// Update elastic IPs  mysql table, mark used IP as unused
			$this->DB->Execute("UPDATE elastic_ips SET state='0', instance_id='' WHERE instance_id=? AND farmid=?",
				array($instanceinfo['instance_id'], $this->FarmID)
			);
			
			//
			//
			//
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
			if ($farminfo['status'] == FARM_STATUS::SYNCHRONIZING)
			{
				$farm_instances_count = $this->DB->GetOne("SELECT COUNT(*) FROM farm_instances WHERE farmid=?", 
					array($this->FarmID)
				);
				
				if ($farm_instances_count == 0)
				{
					$this->DB->Execute("UPDATE farms SET status=? WHERE id=?", 
						array(FARM_STATUS::TERMINATED, $this->FarmID)
					);
				}
			}
		}
		
		public function OnIPAddressChanged($instanceinfo, $new_ip_address)
		{
			$this->Logger->warn(new FarmLogMessage($this->FarmID, "IP changed for instance {$instanceinfo['instance_id']}. New IP address: {$new_ip_address}"));
			$this->DB->Execute("UPDATE farm_instances SET external_ip=?, isipchanged='0', isactive='1' WHERE id=?",
				array($new_ip_address, $instanceinfo["id"])
			);
		}
	}
?>
