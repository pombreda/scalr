<? 
	require("src/prepend.inc.php"); 
	
	$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=?", array($req_name));
	if ($_SESSION['uid'] != 0 && $zoneinfo['clientid'] != $_SESSION['uid'])
		UI::Redirect("sites_view.php");
	
	$display["title"] = "Apache virtual host settings";
	
	$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($zoneinfo['clientid']));
	
	$display["can_use_ssl"] = !(bool)$db->GetOne("SELECT id FROM vhosts WHERE issslenabled='1' AND farmid=? AND name!=?",
		array($zoneinfo['farmid'], $req_name)
	);
	
	if ($_POST)
	{
		$Validator = new Validator();
		
		if (!$Validator->IsNotEmpty($post_document_root_dir))
			$err[] = "Document root required";
			
		if (!$Validator->IsNotEmpty($post_logs_dir))
			$err[] = "Logs directory required";
			
		if (!$Validator->IsNotEmpty($post_server_admin))
			$err[] = "Server admin required";
			
		if ($display["can_use_ssl"])
		{
			if ($post_issslenabled == 1)
			{
				$info = $db->GetOne("SELECT id FROM vhosts WHERE name=?", array($req_name));
				if (!$info)
				{
					if (!$_FILES['ssl_cert']['size'])
						$err[] = "Certificate file required for SSL";
						
					if (!$_FILES['ssl_pk']['size'])
						$err[] = "Private key file required for SSL";
				}
			}
		}
			
		if (count($err) == 0)
		{
			$ssl_cert = "";
			$ssl_pkey = "";
			$issslenabled = 0;
			
			if ($display["can_use_ssl"])
			{
				$issslenabled = ($post_issslenabled) ? 1 : 0;
				if ($issslenabled)
				{
					$ssl_cert = @file_get_contents($_FILES['ssl_cert']['tmp_name']);
					$ssl_pkey = @file_get_contents($_FILES['ssl_pk']['tmp_name']);
				}
			}
			
			$db->Execute("REPLACE INTO vhosts SET
					name				= ?,
					document_root_dir	= ?,
					server_admin		= ?,
					issslenabled		= ?,
					farmid				= ?,
					logs_dir			= ?,
					ssl_cert			= ?,
					ssl_pkey			= ?,
					aliases				= ?
				", 
				array($req_name, $post_document_root_dir, $post_server_admin,
					$issslenabled, $zoneinfo['farmid'], $post_logs_dir, $ssl_cert, $ssl_pkey,
					$post_aliases
				)
			);
			
			$SNMP = new SNMP();
			$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($zoneinfo['farmid']));
			$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=?", array($zoneinfo['farmid']));
			foreach ((array)$instances as $instance)
			{
				$SNMP->Connect($instance['external_ip'], null, $farminfo['hash']);
                $trap = vsprintf(SNMP_TRAP::VHOST_RECONFIGURE, array($req_name, $issslenabled));
                $res = $SNMP->SendTrap($trap);
                $Logger->info("[FarmID: {$zoneinfo['farmid']}] Sending SNMP Trap vhostReconfigure ({$trap}) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})");
			}
			
			$okmsg = "Virtual host settings successfully updated";
			UI::Redirect("sites_view.php");
		}
	}
	
	$display["vhost"] = $db->GetRow("SELECT * FROM vhosts WHERE name=?", array($req_name));
	if (!$display["vhost"])
	{
		$display["vhost"]["name"] = $req_name;
		$display["vhost"]["server_admin"] = $clientinfo['email'];
		
		$display["vhost"]["document_root_dir"] = CONFIG::$APACHE_DOCROOT_DIR;
		$display["vhost"]["logs_dir"] = CONFIG::$APACHE_LOGS_DIR;
	}
	else
	{
		$info = @openssl_x509_parse($display["vhost"]['ssl_cert'], false);
		$display["cert_name"] = $info["name"]; 
	}
	
			
	$display["button2_name"] = "Save";
	
	require("src/append.inc.php");
?>