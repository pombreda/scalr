<?
	require("src/prepend.inc.php");

	$params = array('platforms' => array());
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
		$ePlatforms = Scalr_Session::getInstance()->getEnvironment()->getEnabledPlatforms();
	else
		$ePlatforms = array_keys(SERVER_PLATFORMS::GetList());
	
	$lPlatforms = SERVER_PLATFORMS::GetList();

	$llist = array();
	foreach ($ePlatforms as $platform) {
		$locations = array();
		foreach (PlatformFactory::NewPlatform($platform)->getLocations() as $key => $loc) {
			$locations[] = array('id' => $key, 'name' => $loc);
			$llist[$key] = $loc; 
		}

		$params['platforms'][] = array(
			'id' => $platform,
			'name' => $lPlatforms[$platform],
			'locations' => $locations
		);
	}

	$display['params'] = json_encode($params);
	
	if ($req_id)
	{
		try {
			$dbRole = DBRole::loadById($req_id);
			
			if (Scalr_Session::getInstance()->getClientId() != 0) {
				if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($dbRole->envId))
					throw new Exception ("No access");
			}
			
			$images = array();
			foreach ($dbRole->getImages() as $platform => $locations) {
				foreach ($locations as $location => $imageId)
					$images[] = array(
						'image_id' 		=> $imageId, 
						'platform' 		=> $platform, 
						'location' 		=> $location,
						'platform_name' => SERVER_PLATFORMS::GetName($platform),
						'location_name'	=> $llist[$location]
					);
			}
			
			$display['role'] = json_encode(array(
				'id'			=> $dbRole->id,
				'name'			=> $dbRole->name,
				'arch'			=> $dbRole->architecture,
				'os'			=> $dbRole->os,
				'agent'			=> $dbRole->generation,
				'description'	=> $dbRole->description,
				'behaviors'		=> $dbRole->getBehaviors(),
				'properties'	=> array(DBRole::PROPERTY_SSH_PORT => $dbRole->getProperty(DBRole::PROPERTY_SSH_PORT)),
				'images'		=> $images,
				'parameters'	=> $dbRole->getParameters()
			));
		}
		catch(Exception $e) {
			UI::Redirect("/roles_view.php");
		}
	}
	else
	{
		if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
			UI::Redirect("/roles_view.php");
			
		$display['role'] = json_encode(array(
			'id'			=> 0,
			'name'			=> "",
			'arch'			=> "i386",
			'agent'			=> 2,
			'description'	=> "",
			'behaviors'		=> array(),
			'properties'	=> array(DBRole::PROPERTY_SSH_PORT => 22),
			'images'		=> array(),
			'parameters'	=> array()
		));
	}

	
	if ($_POST)
	{				
		if ($_POST['id'] == 0)
		{
			if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
				UI::Redirect("/roles_view.php");
			
			$dbRole = new DBRole(0);

			$dbRole->generation = ($post_agent != 'scalarizer') ? 1 : 2;
			$dbRole->architecture = $post_arch;
			$dbRole->origin = ROLE_TYPE::SHARED;
			$dbRole->envId = 0;
			$dbRole->clientId = 0;
			$dbRole->name = $post_name;
			$dbRole->os = $post_os;
			
			$rules = array(
				'icmp:-1:-1:0.0.0.0/0',
				'tcp:22:22:0.0.0.0/0',
				'tcp:8013:8013:0.0.0.0/0',
				'udp:8014:8014:0.0.0.0/0',
				'udp:161:162:0.0.0.0/0'
			);
			
			foreach ($post_behaviors as $behavior)
			{
				if ($behavior == ROLE_BEHAVIORS::NGINX)
				{
					$rules[] = "tcp:80:80:0.0.0.0/0";
					$rules[] = "tcp:443:443:0.0.0.0/0";
					
					if (!empty($post_parameters))
					{
						$param = new stdClass();
						$param->name = 'Nginx HTTPS Vhost Template';
						$param->required = '1';
						$param->defval = @file_get_contents(dirname(__FILE__)."/../templates/services/nginx/ssl.vhost.tpl");
						$param->type = 'text';
						$post_parameters = json_encode(array($param));
					}
				}
				
				if ($behavior == ROLE_BEHAVIORS::APACHE)
				{
					$rules[] = "tcp:80:80:0.0.0.0/0";
					$rules[] = "tcp:443:443:0.0.0.0/0";
				}
				
				if ($behavior == ROLE_BEHAVIORS::MYSQL)
				{
					$rules[] = "tcp:3306:3306:0.0.0.0/0";
				}
				
				if ($behavior == ROLE_BEHAVIORS::CASSANDRA)
				{
					$rules[] = "tcp:9160:9160:0.0.0.0/0";
				}
			}
			
			$dbRole->save();
			
			foreach ($rules as $rule)
			{
				$db->Execute("INSERT INTO role_security_rules SET `role_id`=?, `rule`=?", array(
					$dbRole->id, $rule
				));
			}
			
			$soft = explode("\n", trim($post_software));
			$software = array();
			if (count($soft) > 0) {
				foreach ($soft as $softItem) {
					$itm = explode("=", $softItem);
					$software[trim($itm[0])] = trim($itm[1]);
				}
				
				$dbRole->setSoftware($software);
			}
			
			$dbRole->setBehaviors(array_values($post_behaviors));
		}
		
		$dbRole->description = $post_description;
		
		$remove_images = json_decode($post_remove_images);
		foreach ($remove_images as $imageId)
			$dbRole->removeImage($imageId);
		
		$images = json_decode($post_images);
		foreach ($images as $image)
			$dbRole->setImage($image->image_id, $image->platform, $image->location);
		
		$props = json_decode($post_properties);
		foreach ($props as $k=>$v)
			$dbRole->setProperty($k, $v);
			
		$dbRole->setParameters(json_decode($post_parameters));
		
		$dbRole->save();
		
		$result = array('success' => true);
		
		$result = json_encode($result);
	    header("Content-length: ".strlen($result));
	    print $result;
	    exit();
	}

	if (Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::SCALR_ADMIN))
	{
		$display['isScalrAdmin'] = true;
	}
	
	require("src/append.inc.php");
?>
