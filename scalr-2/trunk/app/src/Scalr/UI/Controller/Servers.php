<?php

class Scalr_UI_Controller_Servers extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'serverId';
	
	public function hasAccess()
	{
		return $this->session->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER);
	}

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function xImportWaitHelloAction()
	{
    	try {
			
			$dbServer = DBServer::LoadByID($this->getParam('serverId'));
			if (!$this->session->getAuthToken()->hasAccessEnvironment($dbServer->envId))
				throw new Exception('Server not found');
			
			if ($dbServer->status != SERVER_STATUS::IMPORTING)
				throw new Exception('Server is not in importing state');
    		
    		$row = $this->db->GetRow("SELECT * FROM messages WHERE server_id = ? AND type = ?", 
    				array($dbServer->serverId, "in"));
    				
    		if ($row) {
    			$bundleTaskId = $this->db->GetOne(
    				"SELECT id FROM bundle_tasks WHERE server_id = ? ORDER BY dtadded DESC LIMIT 1", 
    				array($dbServer->serverId));
    		}

    		if ($bundleTaskId) {
    			$this->response->setJsonResponse(array('success' => true, 'bundleTaskId' => $bundleTaskId));
    		} else {
    			$this->response->setJsonResponse(array('success' => false));
    		}
    	} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}
	
	public function xImportStartAction()
	{
		$validator = new Validator();
		
		try {
			if ($validator->IsDomain($this->getParam('remoteIp'))) {
				$remoteIp = @gethostbyname($this->getParam('remoteIp'));
			} else {
				$remoteIp = $this->getParam('remoteIp');
			}
			
			if (!$validator->IsIPAddress($remoteIp, _("Server IP address")))
				$err['remoteIp'] = 'Server IP address is incorrect';
	
			if (!$validator->IsNotEmpty($this->getParam('roleName')))
				$err['roleName'] = 'Role name cannot be empty';
			
			if ($this->db->GetOne("SELECT id FROM roles WHERE name=? AND (env_id = '0' OR env_id = ?)", 
				array($this->getParam('roleName'), $this->session->getEnvironmentId()))
			)
				$err['roleName'] = 'Selected role name is already used. Please select another one.';
						
			if ($this->getParam('add2farm')) {
				
			}
			
			// Find server in the database
			$existingServer = $this->db->GetRow("SELECT * FROM servers WHERE remote_ip = ?", array($remoteIp));
			if ($existingServer["client_id"] == $this->session->getClientId())
				$err['remoteIp'] = sprintf(_("Server %s is already in Scalr with a server_id: %s"), $remote_ip, $existingServer["server_id"]);
			else if ($existingServer)
				$err['remoteIp'] = sprintf(_("Server with selected IP address cannot be imported"));
	
			
			if (count($err) == 0) {
				$cryptoKey = Scalr::GenerateRandomKey(40);
				
				$creInfo = new ServerCreateInfo($this->getParam('platform'), null, 0, 0);
				$creInfo->clientId = $this->session->getClientId();
				$creInfo->envId = $this->session->getEnvironmentId();
				$creInfo->farmId = (int)$this->getParam('farmId');
				$creInfo->remoteIp = $remoteIp;
				$creInfo->SetProperties(array(
					SERVER_PROPERTIES::SZR_IMPORTING_ROLE_NAME => $this->getParam('roleName'),
					SERVER_PROPERTIES::SZR_IMPORTING_BEHAVIOR => $this->getParam('behavior'),
					SERVER_PROPERTIES::SZR_KEY => $cryptoKey,
					SERVER_PROPERTIES::SZR_KEY_TYPE => SZR_KEY_TYPE::PERMANENT,
					SERVER_PROPERTIES::SZR_VESION => "0.5-1",
				));
				
				if ($this->getParam('platform') == SERVER_PLATFORMS::EUCALYPTUS)
					$creInfo->SetProperties(array(EUCA_SERVER_PROPERTIES::REGION => $this->getParam('cloud_location')));
					
				if ($this->getParam('platform') == SERVER_PLATFORMS::NIMBULA)
					$creInfo->SetProperties(array(NIMBULA_SERVER_PROPERTIES::CLOUD_LOCATION => 'nimbula-default'));
				
				$dbServer = DBServer::Create($creInfo, true);
				$this->response->setJsonResponse(array('success' => true, 'serverId' => $dbServer->serverId));
			} else {
				$this->response->setJsonResponse(array('success' => false, 'errors' => $err));
			}
		}
		catch(Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));	
		}
	}
	
	public function importCheckAction()
	{
		try {
			$dbServer = DBServer::LoadByID($this->getParam('serverId'));
			if (!$this->session->getAuthToken()->hasAccessEnvironment($dbServer->envId))
				throw new Exception('Server not found');
			
			if ($dbServer->status != SERVER_STATUS::IMPORTING)
				throw new Exception('Server is not in importing state');
			
			$cryptoKey = $dbServer->GetKey();
			
			$behavior = $dbServer->GetProperty(SERVER_PROPERTIES::SZR_IMPORTING_BEHAVIOR);
			
			$options = array(
				'server-id' 	=> $dbServer->serverId,
				'role-name' 	=> $dbServer->GetProperty(SERVER_PROPERTIES::SZR_IMPORTING_ROLE_NAME),
				'crypto-key' 	=> $cryptoKey,
				'platform' 		=> $dbServer->platform,
				'behaviour' 	=> $behavior == ROLE_BEHAVIORS::BASE ? '' : $behavior,
				'queryenv-url' 	=> CONFIG::$HTTP_PROTO."://".CONFIG::$EVENTHANDLER_URL."/query-env",
				'messaging-p2p.producer-url' => CONFIG::$HTTP_PROTO."://".CONFIG::$EVENTHANDLER_URL."/messaging"
			);

			$command = 'scalarizr --import -y';
			foreach ($options as $k => $v) {
				$command .= sprintf(' -o %s=%s', $k, $v);
			}
			
			$this->response->setJsonResponse(array(
				'success' => true,
				'moduleParams' => array(
					'serverId' => $this->getParam('serverId'),
					'cmd'	   => $command
				),
				'module' => $this->response->template->fetchJs('servers/import_step2.js')
			));
		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}
	
	public function importAction()
	{
		$behaviors = array(
			array(ROLE_BEHAVIORS::BASE, ROLE_BEHAVIORS::GetName(ROLE_BEHAVIORS::BASE)),
			array(ROLE_BEHAVIORS::APACHE, ROLE_BEHAVIORS::GetName(ROLE_BEHAVIORS::APACHE)),
			array(ROLE_BEHAVIORS::MYSQL, ROLE_BEHAVIORS::GetName(ROLE_BEHAVIORS::MYSQL)),
			array(ROLE_BEHAVIORS::NGINX, ROLE_BEHAVIORS::GetName(ROLE_BEHAVIORS::NGINX)),
			array(ROLE_BEHAVIORS::MEMCACHED, ROLE_BEHAVIORS::GetName(ROLE_BEHAVIORS::MEMCACHED))
		);
		
		$euca_locations = array();
		
		/*
			SERVER_PLATFORMS::EC2 => 'Amazon EC2',
			SERVER_PLATFORMS::RACKSPACE => 'Rackspace',
			SERVER_PLATFORMS::EUCALYPTUS => 'Eucalyptus',
			SERVER_PLATFORMS::NIMBULA => 'Nimbula'
		*/
	
		$platforms = array();	
		$env = Scalr_Model::init(Scalr_Model::ENVIRONMENT);
		$env->loadById($this->session->getEnvironmentId());
		$enabledPlatforms = $env->getEnabledPlatforms();
		foreach (SERVER_PLATFORMS::getList() as $k => $v) {
			if (in_array($k, $enabledPlatforms)) {
				$platforms[] = array($k, $v);
				if ($k == SERVER_PLATFORMS::EUCALYPTUS) {
					foreach (PlatformFactory::NewPlatform($k)->getLocations() as $lk=>$lv)
						$euca_locations[] = array($lk, $lv);	
				}
			}
		}
		unset($platforms['rds']);
		
		try {
			$this->response->setJsonResponse(array(
				'success' => true,
				'moduleParams' => array(
					'platforms' 	=> $platforms,
					'behaviors'		=> $behaviors,
					'euca_locations'=> $euca_locations
				),
				'module' => $this->response->template->fetchJs('servers/import_step1.js')
			));
		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}
	
	public function xResendMessageAction()
	{
		try {
		
			$message = $this->db->GetRow("SELECT * FROM messages WHERE server_id=? AND messageid=?",array(
				$this->getParam('serverId'), $this->getParam('messageId')
			));
			
			if ($message)
			{
				$serializer = new Scalr_Messaging_XmlSerializer();
				
				$msg = $serializer->unserialize($message['message']);
				
				$dbServer = DBServer::LoadByID($this->getParam('serverId'));
				if ($dbServer->status == SERVER_STATUS::RUNNING) {
					$this->db->Execute("UPDATE messages SET status=?, handle_attempts='0' WHERE id=?", array(MESSAGE_STATUS::PENDING, $message['id']));
					$dbServer->SendMessage($msg);
			    }
				else
					throw new Exception("Scalr unable to re-send message. Server should be in running state.");
					
				$this->response->setJsonResponse(array('success' => true));
			} else {
				throw new Exception("Message not found");
			}
			
		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}
	
	public function xListViewMessagesAction()
	{
		$this->request->defineParams(array(
			'serverId',
			'sort' => array('type' => 'string', 'default' => 'id'),
			'dir' => array('type' => 'string', 'default' => 'DESC')
		));
		
		try {
			
			$dbServer = DBServer::LoadByID($this->getParam('serverId'));
			if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($dbServer->envId))
				throw new Exception("Server not found");
			
			$sql = "SELECT * FROM messages WHERE server_id='{$dbServer->serverId}'";

			$response = $this->buildResponseFromSql($sql, array("server_id", "message", "messageid"));
			
			foreach ($response["data"] as &$row) {
				preg_match("/^<\?xml [^>]+>[^<]*<message(.*?)name=\"([A-Za-z0-9_]+)\"/si", $row['message'], $matches);
				$row['message_type'] = $matches[2];
			    $row['message'] = '';
			}

			$this->response->setJsonResponse($response);
		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}
	
	public function messagesAction()
	{
		try {
			$this->response->setJsonResponse(array(
				'success' => true,
				'moduleParams' => array('serverId' => $this->getParam('serverId')),
				'module' => $this->response->template->fetchJs('servers/messages.js')
			));
		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}
	
	public function viewAction()
	{
		try {
			$this->response->setJsonResponse(array(
				'success' => true,
				'module' => $this->response->template->fetchJs('servers/view.js')
			));
		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}

	public function sshConsoleAction()
	{
		try {
			$DBServer = DBServer::LoadByID($this->getParam('serverId'));

			if ($this->session->getAuthToken()->hasAccessEnvironment($DBServer->envId)) {
				if ($DBServer->remoteIp) {
					
					$DBFarm = $DBServer->GetFarmObject();
					$dbRole = DBRole::loadById($DBServer->roleId);

					$ssh_port = $dbRole->getProperty(DBRole::PROPERTY_SSH_PORT);
					if (!$ssh_port)
						$ssh_port = 22;

					try {
						$sshKey = Scalr_Model::init(Scalr_Model::SSH_KEY)->loadGlobalByFarmId(
							$DBServer->farmId,
							$DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_CLOUD_LOCATION)
						);
					} catch(Exception $e) {
						$this->response->template->assignParam('error', $e->getMessage());
					}

					$this->response->template->assignParam(
						array(
							"DBServer" => $DBServer,
							"DBFarm"	=> $DBServer->GetFarmObject(),
							"DBRole"	=> $DBServer->GetFarmRoleObject()->GetRoleObject(),
							"host" => $DBServer->remoteIp,
							"port" => $ssh_port,
							"key" => base64_encode($sshKey->getPrivate())
						)
					);
				}
				else
					$this->response->template->assignParam('error', _("Server not initialized yet"));
			} else
				$this->response->template->assignParam('error', _("Access denied"));
		} catch (Exception $e) {
			$this->response->template->assignParam('error', $e->getMessage());
		}
	}

	public function xServerCancelOperationAction()
	{
		$this->request->defineParams(array(
			'serverId'
		));	
		
		try {
			$dbServer = DBServer::LoadByID($this->getParam('serverId'));
			
			$bt_id = $this->db->GetOne("SELECT id FROM bundle_tasks WHERE server_id=? AND 
				prototype_role_id='0' AND status NOT IN (?,?,?)", array(
				$DBServer->serverId,
				SERVER_SNAPSHOT_CREATION_STATUS::FAILED,
				SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS,
				SERVER_SNAPSHOT_CREATION_STATUS::CANCELLED
			));
			if ($bt_id) {
				$BundleTask = BundleTask::LoadById($bt_id);
				$BundleTask->SnapshotCreationFailed("Server was terminated before snapshot was created.");
			}
			
			try {
				if ($dbServer->status == SERVER_STATUS::TEMPORARY) {
					if (PlatformFactory::NewPlatform($dbServer->platform)->IsServerExists($dbServer))
						PlatformFactory::NewPlatform($dbServer->platform)->TerminateServer($dbServer);
				}
			} catch (Exception $e) {}
			
			$dbServer->Delete();

			$this->response->setJsonResponse(array('success' => true, 'message' => _("Server importing successfully canceled. Server removed from database.")));

		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}
	
	public function xListViewServersAction()
	{
		$this->request->defineParams(array(
			'roleId' => array('type' => 'int'),
			'farmId' => array('type' => 'int'),
			'farmRoleId' => array('type' => 'int'),
			'serverId',
			'hideTerminated' => array('type' => 'bool'),
			'sort' => array('type' => 'string', 'default' => 'id'),
			'dir' => array('type' => 'string', 'default' => 'ASC')
		));

		try {
			$sql = "SELECT * FROM servers WHERE env_id='{$this->session->getEnvironmentId()}'";

			if ($this->getParam('farmId'))
				$sql .= " AND farm_id='{$this->getParam('farmId')}'";

			if ($this->getParam('farmRoleId'))
				$sql .= " AND farm_roleid='{$this->getParam('farmRoleId')}'";

			if ($this->getParam('roleId'))
				$sql .= " AND role_id='{$this->getParam('roleId')}'";

			if ($this->getParam('serverId'))
				$sql .= " AND server_id={$this->db->qstr($this->getParam('serverId'))}";

			if ($this->getParam('hideTerminated'))
				$sql .= " AND status != '".SERVER_STATUS::TERMINATED."'";

			$response = $this->buildResponseFromSql($sql, array("server_id", "farm_id", "remote_ip", "local_ip", "status"));
			
			foreach ($response["data"] as &$row) {
				try {
					$DBServer = DBServer::LoadByID($row['server_id']);

					$row['cloud_server_id'] = $DBServer->GetCloudServerID();
					$row['ismaster'] = $DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER);

					$row['location'] = $DBServer->GetCloudLocation();
					if ($DBServer->platform == SERVER_PLATFORMS::EC2)
						$row['location'] .= "/".substr($DBServer->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE), -1, 1);
				}
				catch(Exception $e){  }

				$row['farm_name'] = $this->db->GetOne("SELECT name FROM farms WHERE id=?", array($row['farm_id']));
				$row['role_name'] = $this->db->GetOne("SELECT name FROM roles WHERE id=?", array($row['role_id']));
				$row['isrebooting'] = $this->db->GetOne("SELECT value FROM server_properties WHERE server_id=? AND `name`=?", array(
					$row['server_id'], SERVER_PROPERTIES::REBOOTING
				));
				
				if ($DBServer->status == SERVER_STATUS::RUNNING) {
					$tm = (int)$DBServer->GetProperty(SERVER_PROPERTIES::INITIALIZED_TIME);
					
					if (!$tm) 
						$tm = (int)strtotime($row['dtadded']);
						
					if ($tm > 0) {
						$row['uptime'] = Formater::Time2HumanReadable(time() - $tm, false);
					}
				}
				else
					$row['uptime'] = '';
				
				$i_dns = $this->db->GetOne("SELECT value FROM server_properties WHERE server_id=? AND `name`=?", array(
					$row['server_id'], SERVER_PROPERTIES::EXCLUDE_FROM_DNS
				));

				$r_dns = $this->db->GetOne("SELECT value FROM farm_role_settings WHERE farm_roleid=? AND `name`=?", array(
					$row['farm_roleid'], DBFarmRole::SETTING_EXCLUDE_FROM_DNS
				));

				$row['excluded_from_dns'] = (!$i_dns && !$r_dns) ? false : true;
			}

			$this->response->setJsonResponse($response);
		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}

	public function extendedInfoAction()
	{
		try {
			if (! $this->getParam('serverId'))
				throw new Exception(_('Server not found'));

			$DBServer = DBServer::LoadByID($this->getParam('serverId'));

			if (! $this->session->getAuthToken()->hasAccessEnvironment($DBServer->envId))
				throw new Exception(_('No access'));

			$info = PlatformFactory::NewPlatform($DBServer->platform)->GetServerExtendedInformation($DBServer);

			$form = array(
				array(
					'xtype' => 'fieldset',
					'title' => 'General',
					'labelWidth' => 220,
					'items' => array(
						array(
							'xtype' => 'displayfield',
							'fieldLabel' => 'Server ID',
							'value' => $DBServer->serverId
						),
						array(
							'xtype' => 'displayfield',
							'fieldLabel' => 'Platform',
							'value' => $DBServer->platform
						),
						array(
							'xtype' => 'displayfield',
							'fieldLabel' => 'Remote IP',
							'value' => ($DBServer->remoteIp) ? $DBServer->remoteIp : '' 
						),
						array(
							'xtype' => 'displayfield',
							'fieldLabel' => 'Local IP',
							'value' => ($DBServer->localIp) ? $DBServer->localIp : '' 
						),
						array(
							'xtype' => 'displayfield',
							'fieldLabel' => 'Status',
							'value' => $DBServer->status
						),
						array(
							'xtype' => 'displayfield',
							'fieldLabel' => 'Index',
							'value' => $DBServer->index
						),
						array(
							'xtype' => 'displayfield',
							'fieldLabel' => 'Added at',
							'value' => $DBServer->dateAdded
						)
					)
				)
			);

			$it = array();
			if (is_array($info) && count($info)) {
				foreach ($info as $name => $value) {
					$it[] = array(
						'xtype' => 'displayfield',
						'fieldLabel' => $name,
						'value' => $value
					);
				}
			} else {
				$it[] = array(
					'xtype' => 'displayfield',
					'hideLabel' => true,
					'value' => 'Platform specific details not available for this server'
				);
			}

			$form[] = array(
				'xtype' => 'fieldset',
				'labelWidth' => 220,
				'title' => 'Platform specific details',
				'items' => $it
			);

/*

	<tr>
		<td width="20%">CloudWatch monitoring:</td>
		<td>{if $info->instancesSet->item->monitoring->state == 'enabled'}
				<a href="/aws_cw_monitor.php?ObjectId={$info->instancesSet->item->instanceId}&Object=InstanceId&NameSpace=AWS/EC2">{$info->instancesSet->item->monitoring->state}</a>
				&nbsp;(<a href="aws_ec2_cw_manage.php?action=Disable&iid={$info->instancesSet->item->instanceId}&region={$smarty.request.region}">Disable</a>)
			{else}
				{$info->instancesSet->item->monitoring->state}
				&nbsp;(<a href="aws_ec2_cw_manage.php?action=Enable&iid={$info->instancesSet->item->instanceId}&region={$smarty.request.region}">Enable</a>)
			{/if}
		</td>
	</tr>
	-->
	{include file="inc/intable_footer.tpl" color="Gray"}
*/


			if (count($DBServer->GetAllProperties())) {
				$it = array();
				foreach ($DBServer->GetAllProperties() as $name => $value) {
					$it[] = array(
						'xtype' => 'displayfield',
						'fieldLabel' => $name,
						'value' => $value
					);
				}

				$form[] = array(
					'xtype' => 'fieldset',
					'title' => 'Scalr internal server properties',
					'labelWidth' => 220,
					'items' => $it
				);
			}

			$this->response->setJsonResponse(array(
				'success' => true,
				'module' => $this->response->template->fetchJs('servers/extendedinfo.js'),
				'moduleParams' => $form
			));

		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}

	public function consoleOutputAction()
	{
		try {
			if (! $this->getParam('serverId'))
				throw new Exception(_('Server not found'));

			$DBServer = DBServer::LoadByID($this->getParam('serverId'));

			if (! $this->session->getAuthToken()->hasAccessEnvironment($DBServer->envId))
				throw new Exception(_('No access'));

			$output = PlatformFactory::NewPlatform($DBServer->platform)->GetServerConsoleOutput($DBServer);

			if ($output) {
				$output = trim(base64_decode($output));
				$output = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $output);
				$output = nl2br($output);

				$output = str_replace("\033[74G", "</span>", $output);
				$output = str_replace("\033[39;49m", "</span>", $output);
				$output = str_replace("\033[80G <br />", "<span style='padding-left:20px;'></span>", $output);
				$output = str_replace("\033[80G", "<span style='padding-left:20px;'>&nbsp;</span>", $output);
				$output = str_replace("\033[31m", "<span style='color:red;'>", $output);
				$output = str_replace("\033[33m", "<span style='color:brown;'>", $output);
			} else
				$output = 'Console output not available yet';

			$this->response->setJsonResponse(array(
				'success' => true,
				'module' => $this->response->template->fetchJs('servers/consoleoutput.js'),
				'moduleParams' => array(
					'name' => $DBServer->serverId,
					'content' => $output
				)
			));

		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}

	public function xServerExcludeFromDnsAction()
	{
		try {
			if (! $this->getParam('serverId'))
				throw new Exception(_('Server not found'));

			$DBServer = DBServer::LoadByID($this->getParam('serverId'));

			if (! $this->session->getAuthToken()->hasAccessEnvironment($DBServer->envId))
				throw new Exception(_('No access'));

			$DBServer->SetProperty(SERVER_PROPERTIES::EXCLUDE_FROM_DNS, 1);

			$zones = DBDNSZone::loadByFarmId($DBServer->farmId);
			foreach ($zones as $DBDNSZone)
			{
				$DBDNSZone->updateSystemRecords($DBServer->serverId);
				$DBDNSZone->save();
			}

			$this->response->setJsonResponse(array('success' => true, 'message' => _("Server successfully removed from DNS")));

		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}

	public function xServerIncludeInDnsAction()
	{
		try {
			if (! $this->getParam('serverId'))
				throw new Exception(_('Server not found'));

			$DBServer = DBServer::LoadByID($this->getParam('serverId'));

			if (! $this->session->getAuthToken()->hasAccessEnvironment($DBServer->envId))
				throw new Exception(_('No access'));

			$DBServer->SetProperty(SERVER_PROPERTIES::EXCLUDE_FROM_DNS, 0);

			$zones = DBDNSZone::loadByFarmId($DBServer->farmId);
			foreach ($zones as $DBDNSZone)
			{
				$DBDNSZone->updateSystemRecords($DBServer->serverId);
				$DBDNSZone->save();
			}

			$this->response->setJsonResponse(array('success' => true, 'message' => _("Server successfully added to DNS")));

		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}

	public function xServerCancelAction()
	{
		try {
			if (! $this->getParam('serverId'))
				throw new Exception(_('Server not found'));

			$DBServer = DBServer::LoadByID($this->getParam('serverId'));

			if (! $this->session->getAuthToken()->hasAccessEnvironment($DBServer->envId))
				throw new Exception(_('No access'));

			$bt_id = $this->db->GetOne("SELECT id FROM bundle_tasks WHERE server_id=? AND
				prototype_role_id='0' AND status NOT IN (?,?,?)", array(
				$DBServer->serverId,
				SERVER_SNAPSHOT_CREATION_STATUS::FAILED,
				SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS,
				SERVER_SNAPSHOT_CREATION_STATUS::CANCELLED
			));

			if ($bt_id) {
				$BundleTask = BundleTask::LoadById($bt_id);
				$BundleTask->SnapshotCreationFailed("Server was cancelled before snapshot was created.");
			}

			$DBServer->Delete();
			$this->response->setJsonResponse(array('success' => true, 'message' => _("Server successfully cancelled and removed from database.")));

		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}

	public function xServerRebootServersAction()
	{
		$this->request->defineParams(array(
			'servers' => array('type' => 'json')
		));

		foreach ($this->getParam('servers') as $serverId) {
			try {
				$DBServer = DBServer::LoadByID($serverId);
				if (! $this->session->getAuthToken()->hasAccessEnvironment($DBServer->envId))
					throw new Exception("no access");

				PlatformFactory::NewPlatform($DBServer->platform)->RebootServer($DBServer);
			}
			catch (Exception $e) {}
		}

		$this->response->setJsonResponse(array('success' => true));
	}

	public function xServerTerminateServersAction()
	{
		$this->request->defineParams(array(
			'servers' => array('type' => 'json'),
			'descreaseMinInstancesSetting' => array('type' => 'bool'),
			'forceTerminate' => array('type' => 'bool')
		));

		foreach ($this->getParam('servers') as $serverId) {
			$DBServer = DBServer::LoadByID($serverId);
			if (! $this->session->getAuthToken()->hasAccessEnvironment($DBServer->envId))
				throw new Exception();

			try {
				if (! $this->getParam('forceTerminate')) {
					Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($DBServer->farmId,
						sprintf("Scheduled termination for server %s (%s). It will be terminated in 3 minutes.",
							$DBServer->serverId,
							$DBServer->remoteIp
					)
					));
				}
				Scalr::FireEvent($DBServer->farmId, new BeforeHostTerminateEvent($DBServer, $this->getParam('forceTerminate')));

				$this->db->Execute("UPDATE servers_history SET
					dtterminated	= NOW(),
					terminate_reason	= ?
					WHERE server_id = ?
				", array(
					sprintf("Terminated via user interface"),
					$DBServer->serverId
				));
			} catch (Exception $e) {
				$this->Logger->fatal(sprintf("Can't terminate %s: %s",
					$instanceinfo['instance_id'],
					$e->getMessage()
				));
			}
		}

		if ($this->getParam('descreaseMinInstancesSetting'))
		{
			$servers = $this->getParam('servers');
			$DBServer = DBServer::LoadByID($servers[0]);
			$DBFarmRole = $DBServer->GetFarmRoleObject();

			$minInstances = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES);
			if ($minInstances > 1)
			{
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES,
					$minInstances-1
				);
			}
		}

		$this->response->setJsonResponse(array('success' => true));
	}

	public function xServerGetLaAction()
	{
		try {
			if (! $this->getParam('serverId'))
				throw new Exception(_('Server not found'));

			$DBServer = DBServer::LoadByID($this->getParam('serverId'));

			if (! $this->session->getAuthToken()->hasAccessEnvironment($DBServer->envId))
				throw new Exception(_('No access'));

    		$snmpClient = new Scalr_Net_Snmp_Client();

    		$port = 161;
    		if ($DBServer->GetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT))
    			$port = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT);

    		$snmpClient->connect($DBServer->remoteIp, $port, $DBServer->GetFarmObject()->Hash);

    		$this->response->setJsonResponse(array('success' => true, 'la' => $snmpClient->get('.1.3.6.1.4.1.2021.10.1.3.1')));
		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}

	public function createSnapshotAction()
	{
		try {
			if (! $this->getParam('serverId'))
				throw new Exception(_('Server not found'));

			$DBServer = DBServer::LoadByID($this->getParam('serverId'));

			if (! $this->session->getAuthToken()->hasAccessEnvironment($DBServer->envId))
				throw new Exception(_('No access'));

			$DBFarmRole = $DBServer->GetFarmRoleObject();
			$response = array('success' => true);

			if ($DBFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL))
				$response["showWarningMessage"] = _("You are about to synchronize MySQL instance. The bundle will not include MySQL data. <a href='/farm_mysql_info.php?farmid={$DBServer->farmId}'>Click here if you wish to bundle and save MySQL data</a>.");

			//Check for already running bundle on selected instance
			$chk = $this->db->GetOne("SELECT id FROM bundle_tasks WHERE server_id=? AND status NOT IN ('success', 'failed')",
				array($DBServer->serverId)
			);

			if ($chk)
				throw new Exception(sprintf(_("This server is already synchonizing. <a href='#/bundletasks/%s/logs'>Check status</a>."), $chk));

			if (!$DBServer->IsSupported("0.2-112"))
				throw new Exception(sprintf(_("You cannot create snapshot from selected server because scalr-ami-scripts package on it is too old.")));

			//Check is role already synchronizing...
			$chk = $this->db->GetOne("SELECT server_id FROM bundle_tasks WHERE prototype_role_id=? AND status NOT IN ('success', 'failed')", array(
				$DBServer->roleId
			));

			if ($chk && $chk != $DBServer->serverId) {
				try {
					$bDBServer = DBServer::LoadByID($chk);
				}
				catch(Exception $e) {}

				if ($bDBServer->farmId == $DBServer->farmId)
					throw new Exception(sprintf(_("This role is already synchonizing. <a href='#/bundletasks/%s/logs'>Check status</a>."), $chk));
			}

			$roleName = $DBServer->GetFarmRoleObject()->GetRoleObject()->name;
			$response['module'] = $this->response->template->fetchJs('servers/createsnapshot.js');
			$response['moduleParams'] = array(
				'serverId' 	=> $DBServer->serverId,
				'platform'	=> $DBServer->platform,
				'isVolumeSizeSupported'=> (int)$DBServer->IsSupported('0.7'),
				'farmId' => $DBServer->farmId,
				'farmName' => $DBServer->GetFarmObject()->Name,
				'roleName' => $roleName,
				'replaceNoReplace' => "<b>DO NOT REPLACE</b> any roles on any farms, just create new one.</td>",
				'replaceFarmReplace' => "Replace role '{$roleName}' with new one <b>ONLY</b> on current farm '{$DBServer->GetFarmObject()->Name}'</td>",
				'replaceAll' => "Replace role '{$roleName}' with new one on <b>ALL MY FARMS</b> <span style=\"font-style:italic;font-size:11px;\">(You will be able to bundle role with the same name. Old role will be renamed.)</span></td>"
			);

			$this->response->setJsonResponse($response);
		} catch (Exception $e) {
			$this->response->setJsonResponse(array('success' => false, 'error' => $e->getMessage()));
		}
	}

	public function xServerCreateSnapshotAction()
	{
		$this->request->defineParams(array(
			'rootVolumeSize' => array('type' => 'int')
		));
		
		sleep(5);
		try {
			if (! $this->getParam('serverId'))
				throw new Exception(_('Server not found'));

			$DBServer = DBServer::LoadByID($this->getParam('serverId'));

			if (! $this->session->getAuthToken()->hasAccessEnvironment($DBServer->envId))
				throw new Exception(_('No access'));

			$err = array();

			if (strlen($this->getParam('roleName')) < 3)
				$err[] = _("Role name should be greater than 3 chars");

			if (! preg_match("/^[A-Za-z0-9-]+$/si", $this->getParam('roleName')))
				$err[] = _("Role name is incorrect");

			$roleinfo = $this->db->GetRow("SELECT * FROM roles WHERE name=? AND (env_id=? OR env_id='0')", array($this->getParam('roleName'), $DBServer->envId));
			if ($this->getParam('replaceType') != SERVER_REPLACEMENT_TYPE::REPLACE_ALL) {
				if ($roleinfo)
					$err[] = _("Specified role name is already used by another role. You can use this role name only if you will replace old on on ALL your farms.");
			} else {
				if ($roleinfo && $roleinfo['env_id'] == 0)
					$err[] = _("Selected role name is reserved and cannot be used for custom role");
			}

			if (count($err))
				throw new Exception();

			$ServerSnapshotCreateInfo = new ServerSnapshotCreateInfo(
				$DBServer, 
				$this->getParam('roleName'), 
				$this->getParam('replaceType'), 
				false, 
				$this->getParam('roleDescription'), 
				$this->getParam('rootVolumeSize')
			);
			$BundleTask = BundleTask::Create($ServerSnapshotCreateInfo);

			$this->response->setJsonResponse(array('success' => true, 'message' => "Bundle task successfully created. <a href='#/bundletasks/{$BundleTask->id}/logs'>Click here for check status.</a>"));
		} catch (Exception $e) {
			if ($e->getMessage() != '')
				$err[] = $e->getMessage();

			$this->response->setJsonResponse(array('success' => false, 'error' => $err));
		}
	}
}
