<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = "Import server";
	
	
	$req_step = (int)$req_step ? (int)$req_step : 1;
	if ($_SERVER["REQUEST_METHOD"] == "GET") {
		unset($_SESSION["importing_server_id"]);	
	}
	
	$behaviours = array(
		ROLE_ALIAS::BASE => ROLE_ALIAS::GetTypeByAlias(ROLE_ALIAS::BASE),
		//ROLE_ALIAS::WWW => ROLE_ALIAS::GetTypeByAlias(ROLE_ALIAS::WWW),		
		ROLE_ALIAS::APP => ROLE_ALIAS::GetTypeByAlias(ROLE_ALIAS::APP),
		ROLE_ALIAS::MYSQL => ROLE_ALIAS::GetTypeByAlias(ROLE_ALIAS::MYSQL)
		//ROLE_ALIAS::MEMCACHED => ROLE_ALIAS::GetTypeByAlias(ROLE_ALIAS::MEMCACHED)
	);
	
	$platforms = array(
		'ec2' => 'Amazon EC2'
	);
	
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
						$ismine = $db->GetOne("SELECT COUNT(*) FROM farms WHERE clientid = ? AND id = ?", 
								array($_SESSION["uid"], $req_farmid));
						if (!$ismine) {
							$validator->AddError(null, null, null, 
									_("Farm doesn't exists or doesn't belongs to your account"));
						}
					}
				}
				if (!in_array($req_platform, array("ec2", "vps"))) {
					$validator->AddError(null, null, null, _("Invalid 'Platform'. Only 'ec2', 'vps' allowed"));
				}
				if (!in_array($req_behaviour, array_keys($behaviours))) {
					$validator->AddError(null, null, null, _("Invalid 'Behavior'"));
				}
				
				// Find server in the database
				$existingServer = $db->GetRow("SELECT * FROM servers WHERE remote_ip = ?", array($req_remote_ip));
				if ($existingServer["client_id"] == $_SESSION["uid"]) {
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
					$creInfo->clientId = $_SESSION["uid"];
					$creInfo->farmId = (int)$req_farmid;
					$creInfo->remoteIp = $req_remote_ip;
					$creInfo->SetProperties(array(
						SERVER_PROPERTIES::SZR_IMPORTING_ROLE_NAME => $req_role_name,
						SERVER_PROPERTIES::SZR_IMPORTING_BEHAVIOUR => $req_behaviour,
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
				$display["command"] = sprintf('scalarizr --import -y'
						. ' -o server-id=%s -o role-name=%s -o crypto-key=%s'
						. ' -o platform=%s -o behaviour=%s'
						. ' -o queryenv-url=%s -o messaging-p2p.producer-url=%s',
						$dbServer->serverId, $req_role_name, $cryptoKey,
						$req_platform, $req_behaviour == ROLE_ALIAS::BASE ? '' : $req_behaviour, 
						"http://".$_SERVER['HTTP_HOST']. "/query-env", "http://".$_SERVER['HTTP_HOST']. "/messaging");
				
				$req_step = 2;
			}
		}
	}

	if (!$template_name)
		$template_name = "szr_server_import_step{$req_step}.tpl";
	$display['behaviours'] = $behaviours;
	
	require("src/append.inc.php"); 
?>