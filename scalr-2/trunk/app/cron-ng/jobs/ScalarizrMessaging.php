<?php

	class Scalr_Cronjob_ScalarizrMessaging extends Scalr_System_Cronjob_MultiProcess_DefaultWorker
    {
        static function getConfig () {
        	return array(
        		"description" => "Process ingoing Scalarizr messages",        	
        		"processPool" => array(
					"daemonize" => false,
        			"workerMemoryLimit" => 40000,   // 40Mb       	
        			"startupTimeout" => 10000, 		// 10 seconds
        			"size" => 3						// 3 workers
        		),
    			"waitPrevComplete" => true,        		
				"fileName" => __FILE__,
        		"getoptRules" => array(
        			'farm-id-s' => 'Affect only this farm'
        		)
        	);
        }
    	
        private $logger;
        
        private $db;
        
        private $serializer;
        
    	function __construct() {
        	$this->logger = Logger::getLogger(__CLASS__);
        	$this->serializer = new Scalr_Messaging_XmlSerializer();
        	$this->db = Core::GetDBInstance();
        }
        
    	function startForking ($workQueue) {
        	// Reopen DB connection after daemonizing
        	$this->db = Core::GetDBInstance(null, true);
        }        
        
    	function startChild () {
			// Reopen DB connection in child
			$this->db = Core::GetDBInstance(null, true);
        	// Reconfigure observers;
        	Scalr::ReconfigureObservers();					
		}        
        
        function enqueueWork ($workQueue) {
        	$this->logger->info("Fetching servers...");
        	$farmid = $this->runOptions['getopt']->getOption('farm-id');
        	
        	if ($farmid) {
        		$rows = $this->db->GetAll("SELECT distinct(m.server_id) FROM messages m 
	        			INNER JOIN servers s ON m.server_id = s.server_id
		        		WHERE m.type = ? AND m.status = ? AND m.isszr = ? AND s.farm_id = ?",
        				array("in", MESSAGE_STATUS::PENDING, 1, $farmid));
        	} else {
	        	$rows = $this->db->GetAll("SELECT distinct(server_id) FROM messages 
	        			WHERE type = ? AND status = ? AND isszr = ?",
	        			array("in", MESSAGE_STATUS::PENDING, 1));
        	}
        	
        	$this->logger->info("Found ".count($rows)." servers");
        	foreach ($rows as $row) {
        		$workQueue->put($row["server_id"]);
        	}
        }
        
        function handleWork ($serverId)
        {
            try {
            	$dbserver = DBServer::LoadByID($serverId);	
            } catch (ServerNotFoundException $e) {
            	$this->db->Execute("DELETE FROM messages WHERE server_id=?", array($serverId));
            	return;
            }
            $rs = $this->db->Execute("SELECT * FROM messages 
            		WHERE server_id = ? AND type = ? AND status = ? 
            		ORDER BY id ASC", 
            		array($serverId, "in", MESSAGE_STATUS::PENDING));
            
       		while ($row = $rs->FetchRow()) {
       			try {
       				$message = $this->serializer->unserialize($row["message"]);
       				$event = null;
       				
       				// Update scalarizr package version
					if ($message->meta[Scalr_Messaging_MsgMeta::SZR_VERSION]) {
						$dbserver->SetProperty(SERVER_PROPERTIES::SZR_VESION, 
								$message->meta[Scalr_Messaging_MsgMeta::SZR_VERSION]);
					}
       				
       				try {
	       				if ($message instanceof Scalr_Messaging_Msg_Hello) {
	       					$event = $this->onHello($message, $dbserver);
	       					
	       				} elseif ($message instanceof Scalr_Messaging_Msg_HostInit) {
							$event = $this->onHostInit($message, $dbserver);
	       					
	       				} elseif ($message instanceof Scalr_Messaging_Msg_HostUp) {
	       					$event = $this->onHostUp($message, $dbserver);
	       					
	       				} elseif ($message instanceof Scalr_Messaging_Msg_HostDown) {
	       					$event = new HostDownEvent($dbserver);
	       					
	       				} elseif ($message instanceof Scalr_Messaging_Msg_RebootStart) {
	       					$event = new RebootBeginEvent($dbserver);
	       				
	       				} elseif ($message instanceof Scalr_Messaging_Msg_RebootFinish) {
	       					$event = new RebootCompleteEvent($dbserver);
	       					
	       				} elseif ($message instanceof Scalr_Messaging_Msg_BlockDeviceAttached) {
	       					if ($dbserver->platform == SERVER_PLATFORMS::EC2) {
								$Client = Client::Load($dbserver->clientId);
								
								$ec2Client = Scalr_Service_Cloud_Aws::newEc2(
									$dbserver->GetProperty(EC2_SERVER_PROPERTIES::REGION),
									$dbserver->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
									$dbserver->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
								);
	
			    				$instanceId = $dbserver->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID);
			    				$volumes = $ec2Client->DescribeVolumes()->volumeSet->item;
			    				if (!is_array($volumes)) {
			    					$volumes = array($volumes);
			    				}
			    				foreach ($volumes as $volume) {
			    					if ($volume->status == AMAZON_EBS_STATE::IN_USE && 
			    						$volume->attachmentSet->item->instanceId == $instanceId) {
			    						$message->volumeId = $volume->volumeId;
			    					}
			    				}
	       					}
	       					
							$event = new EBSVolumeAttachedEvent(
								$dbserver,
								$message->deviceName,
								$message->volumeId
							);
		
	       				} elseif ($message instanceof Scalr_Messaging_Msg_BlockDeviceMounted) {
	       					if ($message->isArray) {
								// EBS array
								$this->db->Execute(
									"UPDATE ebs_arrays SET status=?, isfscreated='1' WHERE name=? AND serevr_id=?",
									array(EBS_ARRAY_STATUS::IN_USE, $message->name, $dbserver->serverId)
								);
	       					} else {
								// Single volume
								$ebsinfo = $this->db->GetRow("SELECT * FROM farm_ebs WHERE volumeid=?", array($message->volumeId));
								if ($ebsinfo) {
									$db->Execute(
										"UPDATE farm_ebs SET state=?, isfsexists='1' WHERE id=?", 
										array(AWS_SCALR_EBS_STATE::MOUNTED, $ebsinfo['id'])
									);
								}
	       					}
	       					$event = new EBSVolumeMountedEvent(
	       						$dbserver, 
	       						$message->mountpoint, 
	       						$message->volumeId, 
	       						$message->deviceName
	       					);
	       					
	       				} elseif ($message instanceof Scalr_Messaging_Msg_RebundleResult) {
	       					if ($message->status == Scalr_Messaging_Msg_RebundleResult::STATUS_OK) {
	       						
	       						$event = new RebundleCompleteEvent(
	       							$dbserver, 
	       							$message->snapshotId, 
	       							$message->bundleTaskId,
	       							array('os' => $message->os, 'software' => $message->software)
	       						);
	       					} else if ($message->status == Scalr_Messaging_Msg_RebundleResult::STATUS_FAILED) {
	       						$event = new RebundleFailedEvent($dbserver, $message->bundleTaskId);
	       					}
	       					
	       				} elseif ($message instanceof Scalr_Messaging_Msg_Mysql_PromoteToMasterResult) {
	       					$event = $this->onMysql_PromoteToMasterResult($message, $dbserver);
	       				} elseif ($message instanceof Scalr_Messaging_Msg_Mysql_CreatePmaUserResult) {
       						$farmRole = DBFarmRole::LoadByID($message->farmRoleId);
       						if ($message->status == "ok") {
	       						$farmRole->SetSetting(DbFarmRole::SETTING_MYSQL_PMA_USER, $message->pmaUser);
	       						$farmRole->SetSetting(DbFarmRole::SETTING_MYSQL_PMA_PASS, $message->pmaPassword);
       						} else {
       							$farmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_TIME, "");
       							$farmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_ERROR, $message->lastError);
       						}
	       				} elseif ($message instanceof Scalr_Messaging_Msg_Mysql_CreateDataBundleResult) {
	       					if ($message->status == "ok") {
	       						$event = new MysqlBackupCompleteEvent($dbserver, MYSQL_BACKUP_TYPE::BUNDLE, $message->snapshotId);
	       						$event->snapshotId = $message->snapshotId;
	       						$event->logFile = $message->logFile;
	       						$event->logPos = $message->logPos;
	       					} else {
	       						$event = new MysqlBackupFailEvent($dbserver, MYSQL_BACKUP_TYPE::BUNDLE);
	       						$event->lastError = $message->lastError;
	       					}
	       				} elseif ($message instanceof Scalr_Messaging_Msg_Mysql_CreateBackupResult) {
	       					if ($message->status == "ok") {
	       						$event = new MysqlBackupCompleteEvent($dbserver, MYSQL_BACKUP_TYPE::DUMP);
	       					} else {
	       						$event = new MysqlBackupFailEvent($dbserver, MYSQL_BACKUP_TYPE::DUMP);
	       						$event->lastError = $message->lastError;
	       					}
	       				}
	       				
	       				$handle_status = MESSAGE_STATUS::HANDLED;
       				} catch (Exception $e) {
       					$handle_status = MESSAGE_STATUS::FAILED;
       					$this->logger->error(sprintf("Cannot handle message '%s' (message_id: %s) "
       							. "from server '%s' (server_id: %s). %s", 
       							$message->getName(), $message->messageId, 
       							$dbserver->remoteIp, $dbserver->serverId, $e->getMessage()));
       				} 
       				
       				$this->db->Execute("UPDATE messages SET status = ? WHERE messageid = ?",
       						array($handle_status, $message->messageId));
       				
       				if ($event) {
       					Scalr::FireEvent($dbserver->farmId, $event);
       				}
       				
       			} catch (Exception $e) {
       				$this->logger->error($e->getMessage(), $e);
       			}
       		}
        }
        
        private function onHello($message, $dbserver) {
       		if ($dbserver->status == SERVER_STATUS::IMPORTING) {
       			
       			if ($dbserver->platform == SERVER_PLATFORMS::EC2)
       			{
	       			$dbserver->SetProperties(array(
	       				EC2_SERVER_PROPERTIES::AMIID => $message->awsAmiId,
	       				EC2_SERVER_PROPERTIES::INSTANCE_ID => $message->awsInstanceId,
	       				EC2_SERVER_PROPERTIES::INSTANCE_TYPE => $message->awsInstanceType,
	       				EC2_SERVER_PROPERTIES::AVAIL_ZONE => $message->awsAvailZone,
	       				EC2_SERVER_PROPERTIES::REGION => substr($message->awsAvailZone, 0, -1),
	       				SERVER_PROPERTIES::ARCHITECTURE => $message->architecture
	       			));
       			}
       			elseif ($dbserver->platform == SERVER_PLATFORMS::EUCALYPTUS)
       			{
       				$dbserver->SetProperties(array(
       					EUCA_SERVER_PROPERTIES::EMIID => $message->awsAmiId,
	       				EUCA_SERVER_PROPERTIES::INSTANCE_ID => $message->awsInstanceId,
	       				EUCA_SERVER_PROPERTIES::INSTANCE_TYPE => $message->awsInstanceType,
	       				EUCA_SERVER_PROPERTIES::AVAIL_ZONE => $message->awsAvailZone,
	       				EUCA_SERVER_PROPERTIES::REGION => 'Eucalyptus',
	       				SERVER_PROPERTIES::ARCHITECTURE => $message->architecture
       					
       				));
       			}
    			
       			// Bundle image
       			$creInfo = new ServerSnapshotCreateInfo(
       				$dbserver, 
       				$dbserver->GetProperty(SERVER_PROPERTIES::SZR_IMPORTING_ROLE_NAME),
       				SERVER_REPLACEMENT_TYPE::NO_REPLACE
       			);
       			$bundleTask = BundleTask::Create($creInfo);
       		}
        }
        
        private function onHostInit($message, $dbserver) {
       		if ($dbserver->status == SERVER_STATUS::PENDING) {
       			// Update server crypto key
       			$srv_props = array();	
       			if ($message->cryptoKey) {
       				if ($dbserver->GetProperty(SERVER_PROPERTIES::SZR_KEY_TYPE) == SZR_KEY_TYPE::ONE_TIME) {
       					$srv_props[SERVER_PROPERTIES::SZR_KEY] = trim($message->cryptoKey);
       					$srv_props[SERVER_PROPERTIES::SZR_KEY_TYPE] = SZR_KEY_TYPE::PERMANENT;
       				} else {
       					$this->logger->warn("Strange situation. Received crypto key in HostInit message"
       							. " from server '{$dbserver->serverId}' ({$message->remoteIp})"
       							. " while this server has permanent key");
       				}
       			}

       			$srv_props[SERVER_PROPERTIES::SZR_SNMP_PORT] = $message->snmpPort;
       			
				
       			// MySQL specific
       			$dbFarmRole = $dbserver->GetFarmRoleObject();
       			if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL)) {
                    $master = $dbFarmRole->GetFarmObject()->GetMySQLInstances(true);
                    // If no masters in role this server becomes it
                    if (!$master[0] && 
                    	!(int)$dbFarmRole->GetSetting(DbFarmRole::SETTING_MYSQL_SLAVE_TO_MASTER)) {
                    	$srv_props[SERVER_PROPERTIES::DB_MYSQL_MASTER] = 1;
                    }
       			}
       			
       			$dbserver->SetProperties($srv_props);
       			
				return new HostInitEvent(
					$dbserver, 
					$message->localIp,
					$message->remoteIp,
					$message->sshPubKey
				);
       			
       		} else {
       			$this->logger->error("Strange situation. Received HostInit message"
       					. " from server '{$dbserver->serverId}' ({$message->remoteIp})"
       					. " with state {$dbserver->status}!");
       		}        
        }
        
        /**
         * @param Scalr_Messaging_Msg $message
         * @param DBServer $dbserver
         */
	    private function onHostUp ($message, $dbserver) {
       		if ($dbserver->status == SERVER_STATUS::INIT) {
       			$event = new HostUpEvent($dbserver, "");
       			
       			$dbFarmRole = $dbserver->GetFarmRoleObject();
       			if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL)) {
       				if (!$message->mysql) {
       					$this->logger->error(sprintf(
       							"Strange situation. HostUp message from MySQL behavior doesn't contains `mysql` property. Server %s (%s)", 
       							$dbserver->serverId, $dbserver->remoteIp));
       					return;
       				}
       				$mysqlData = $message->mysql;
       				
                    if ($dbserver->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER)) {
                   		if ($mysqlData->rootPassword) {
	       					$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_REPL_PASSWORD, $mysqlData->replPassword);                    		
		       				$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_ROOT_PASSWORD, $mysqlData->rootPassword);
		       				$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_STAT_PASSWORD, $mysqlData->statPassword);
                   		}

	       				$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_SNAPSHOT_ID, $mysqlData->snapshotId);                   		
                   		$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_LOG_FILE, $mysqlData->logFile);
                   		$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_LOG_POS, $mysqlData->logPos);
                   		
                    } else {
                    	$dbserver->SetProperty(EC2_SERVER_PROPERTIES::MYSQL_SLAVE_EBS_VOLUME_ID, $mysqlData->volumeId);
                    }
       			}
       			return $event;
       		} else {
       			$this->logger->error("Strange situation. Received HostUp message"
       					. " from server '{$dbserver->serverId}' ('{$message->remoteIp})"
       					. " with state {$dbserver->status}!");
       		}
	    }
	    
        /**
         * @param Scalr_Messaging_Msg_Mysql_PromoteToMasterResult $message
         * @param DBServer $dbserver
         */	    
	    private function onMysql_PromoteToMasterResult ($message, $dbserver) {
	    	if ($message->status == Scalr_Messaging_Msg_Mysql_PromoteToMasterResult::STATUS_OK) {
		    	$dbFarm = $dbserver->GetFarmObject();
		    	$dbFarmRole = $dbserver->GetFarmRoleObject();
				$oldMaster = $dbFarm->GetMySQLInstances(true);
	    		
				// TODO: delete old slave volume if new one was created
	    		$dbserver->SetProperty(EC2_SERVER_PROPERTIES::MYSQL_SLAVE_EBS_VOLUME_ID, $message->volumeId);
	    		$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_MASTER_EBS_VOLUME_ID, $message->volumeId);
	    		$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_SLAVE_TO_MASTER, 0);
	    					
				return new NewMysqlMasterUpEvent($dbserver, "", $oldMaster[0]);	    	
	    		
	    	} elseif ($message->status == Scalr_Messaging_Msg_Mysql_PromoteToMasterResult::STATUS_FAILED) {
	    		// XXX: Need to do smth
	    		$this->logger->error(sprintf("Promote to Master failed for server %s. Last error: %s", 
	    				$dbserver->serverId, $message->lastError));
	    	}
	    	
	    }
    }
    
