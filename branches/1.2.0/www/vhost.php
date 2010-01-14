<? 
	require("src/prepend.inc.php"); 
	
	$zoneinfo = $db->GetRow("SELECT * FROM zones WHERE zone=?", array($req_name));
	
	if ($_SESSION['uid'] == 0 || $zoneinfo['clientid'] != $_SESSION['uid'])
		UI::Redirect("sites_view.php");
	
	$display["title"] = "Apache virtual host settings";
	
	$Client = Client::Load($zoneinfo['clientid']);
	
	$DBFarmRole = DBFarmRole::Load($zoneinfo['farmid'], $zoneinfo['ami_id']);
	
	$display["can_use_ssl"] = !(bool)$db->GetOne("SELECT id FROM vhosts WHERE issslenabled='1' AND farmid=? AND name!=? AND farm_roleid != ?",
		array($zoneinfo['farmid'], $req_name, $DBFarmRole->ID)
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
				$info = $db->GetRow("SELECT * FROM vhosts WHERE name=?", array($req_name));
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
					if ($_FILES['ssl_cert']['tmp_name'])
						$ssl_cert = @file_get_contents($_FILES['ssl_cert']['tmp_name']);
					else
						$ssl_cert = $info['ssl_cert'];
						
					if ($_FILES['ssl_pk']['tmp_name'])
						$ssl_pkey = @file_get_contents($_FILES['ssl_pk']['tmp_name']);
					else
						$ssl_pkey = $info['ssl_pkey'];
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
					aliases				= ?,
					farm_roleid			= ?
				", 
				array($req_name, $post_document_root_dir, $post_server_admin,
					$issslenabled, $zoneinfo['farmid'], $post_logs_dir, $ssl_cert, $ssl_pkey,
					$post_aliases, $DBFarmRole->ID
				)
			);
			
			$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($zoneinfo['farmid']));
			$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state IN (?,?)", 
				array($zoneinfo['farmid'], INSTANCE_STATE::INIT, INSTANCE_STATE::RUNNING)
			);
			foreach ((array)$instances as $instance)
			{
				$alias = $db->GetOne("SELECT alias FROM roles WHERE ami_id=?", array($instance['ami_id']));
				if ($alias != ROLE_ALIAS::APP && $alias != ROLE_ALIAS::WWW)
					continue;
					
				if ($DBFarmRole->GetRoleAlias() == ROLE_ALIAS::APP && $DBFarmRole->ID != $instance['farm_roleid'])
					continue;
				
				$DBInstance = DBInstance::LoadByID($instance['id']);
				$DBInstance->SendMessage(new VhostReconfigureScalrMessage());
			}
			
			$okmsg = "Virtual host settings successfully updated";
			UI::Redirect("sites_view.php");
		}
	}
	
	$display["vhost"] = $db->GetRow("SELECT * FROM vhosts WHERE name=?", array($req_name));
	if (!$display["vhost"])
	{
		$display["vhost"]["name"] = $req_name;
		$display["vhost"]["server_admin"] = $Client->Email;
		
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