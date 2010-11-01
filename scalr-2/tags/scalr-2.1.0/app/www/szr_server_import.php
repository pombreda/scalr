<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Import server";
	
	
	$req_step = (int)$req_step ? (int)$req_step : 1;
	if ($_SERVER["REQUEST_METHOD"] == "GET") {
		unset($_SESSION["importing_server_id"]);	
	}
	
	$behaviors = array(
		ROLE_BEHAVIORS::BASE => ROLE_BEHAVIORS::GetName(ROLE_BEHAVIORS::BASE),
		ROLE_BEHAVIORS::APACHE => ROLE_BEHAVIORS::GetName(ROLE_BEHAVIORS::APACHE),
		ROLE_BEHAVIORS::MYSQL => ROLE_BEHAVIORS::GetName(ROLE_BEHAVIORS::MYSQL),
		ROLE_BEHAVIORS::NGINX => ROLE_BEHAVIORS::GetName(ROLE_BEHAVIORS::NGINX),
		ROLE_BEHAVIORS::MEMCACHED => ROLE_BEHAVIORS::GetName(ROLE_BEHAVIORS::MEMCACHED)
	);
	
	$platforms = array(
		SERVER_PLATFORMS::EC2 => 'Amazon EC2',
		//SERVER_PLATFORMS::RACKSPACE => 'Rackspace',
		SERVER_PLATFORMS::EUCALYPTUS => 'Eucalyptus'
	);

	$platforms = array();	
	$env = Scalr_Model::init(Scalr_Model::ENVIRONMENT);
	$env->loadById(Scalr_Session::getInstance()->getEnvironmentId());
	$enabledPlatforms = $env->getEnabledPlatforms();
	foreach (SERVER_PLATFORMS::getList() as $k => $v) {
		if (in_array($k, $enabledPlatforms)) {
			$platforms[$k] = $v;
		}
	}
	unset($platforms['rds']);

	if ($_POST)
	{
		$validator = new Validator();
		
		if ($req_step == 1) {
			if (!$_SESSION["importing_server_id"]) {
				// Validate input data
				if ($validator->IsDomain($req_remote_ip))
				{
					$req_remote_ip = gethostbyname($req_remote_ip);
				}
				//$validator->IsExternalIPAddress($req_remote_ip, "Server IP address must be external");
				$validator->IsIPAddress($req_remote_ip, _("Server IP address"));

				$validator->IsNotEmpty($req_role_name, _("Role name"));
				
				if ($req_add2farm) {
					// Validate farm
					$validator->IsNotEmpty($req_farmid, _("Farm"));
					if ($req_farmid) {
						$ismine = $db->GetOne("SELECT COUNT(*) FROM farms WHERE env_id = ? AND id = ?", 
								array(Scalr_Session::getInstance()->getEnvironmentId(), $req_farmid));
						if (!$ismine) {
							$validator->AddError(null, null, null, 
									_("Farm doesn't exists or doesn't belongs to your account"));
						}
					}
				}
				if (!in_array($req_platform, array_keys($platforms))) {
					$validator->AddError(null, null, null, _("Unknown cloud platform"));
				}
				if (!in_array($req_behavior, array_keys($behaviors))) {
					$validator->AddError(null, null, null, _("Invalid 'Behavior'"));
				}
				
				// Find server in the database
				$existingServer = $db->GetRow("SELECT * FROM servers WHERE remote_ip = ?", array($req_remote_ip));
				if ($existingServer["client_id"] == Scalr_Session::getInstance()->getClientId()) {
					$validator->AddError(null, null, null, 
							sprintf(_("Server %s is already in Scalr with a server_id: %s"), 
							$req_remote_ip, $existingServer["server_id"]));
				} else if ($existingServer) {
					$validator->AddError(null, null, null, 
							sprintf(_("Server %s is already in Scalr"), $req_remote_ip));
				}
	
				
				if (!$validator->HasErrors()) {
					$cryptoKey = Scalr::GenerateRandomKey(40);
					
					$creInfo = new ServerCreateInfo($req_platform, null, 0, 0);
					$creInfo->clientId = Scalr_Session::getInstance()->getClientId();
					$creInfo->envId = Scalr_Session::getInstance()->getEnvironmentId();
					$creInfo->farmId = (int)$req_farmid;
					$creInfo->remoteIp = $req_remote_ip;
					$creInfo->envId = Scalr_Session::getInstance()->getEnvironmentId();
					$creInfo->SetProperties(array(
						SERVER_PROPERTIES::SZR_IMPORTING_ROLE_NAME => $req_role_name,
						SERVER_PROPERTIES::SZR_IMPORTING_BEHAVIOR => $req_behavior,
						SERVER_PROPERTIES::SZR_KEY => $cryptoKey,
						SERVER_PROPERTIES::SZR_KEY_TYPE => SZR_KEY_TYPE::PERMANENT,
						SERVER_PROPERTIES::SZR_VESION => "0.5-1",
					));
					
					$dbServer = DBServer::Create($creInfo, true);
					$_SESSION["importing_server_id"] = $dbServer->serverId;
					
				} else {
					$err = $validator->Errors;
					$display = array_merge($display, $_REQUEST);
				}
				
			} else {
				$dbServer = DBServer::LoadByID($_SESSION["importing_server_id"]);
				$cryptoKey = $dbServer->GetKey();
			}
			
			if (!$err) {
				$options = array(
					'server-id' => $dbServer->serverId,
					'role-name' => $req_role_name,
					'crypto-key' => $cryptoKey,
					'platform' => $req_platform,
					'behaviour' => $req_behavior == ROLE_BEHAVIORS::BASE ? '' : $req_behavior,
					'queryenv-url' => "http://".$_SERVER['HTTP_HOST']. "/query-env",
					'messaging-p2p.producer-url' => "http://".$_SERVER['HTTP_HOST']. "/messaging"
				);

				$command = 'scalarizr --import -y';
				foreach ($options as $k => $v) {
					$command .= sprintf(' -o %s=%s', $k, $v);
				}
				$display['command'] = $command;
				
				$req_step = 2;
			}
		}
	}

	if (!$template_name)
		$template_name = "szr_server_import_step{$req_step}.tpl";
		
	$display['behaviors'] = $behaviors;
	$display['platforms'] = $platforms;	
	
	require("src/append.inc.php"); 
?>
