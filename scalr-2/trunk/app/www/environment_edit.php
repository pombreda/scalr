<?
	require_once('src/prepend.inc.php');

	Scalr_Session::getInstance()->getAuthToken()->hasAccessEx(
		Scalr_AuthToken::SCALR_ADMIN | Scalr_AuthToken::ACCOUNT_ADMIN,
		Scalr_AuthToken::MODULE_ENVIRONMENTS
	);

	try {
		$env = Scalr_Model::init(Scalr_Model::ENVIRONMENT)->loadById($req_env_id);
		if ($env->clientId != Scalr_Session::getInstance()->getClientId())
			throw new Exception('Not found');
	} catch (Exception $e) {
		$errmsg = _("Environment not found");
		UI::redirect("environments.php");
	}

	if ($_POST) {
		$context = 6;
		$enable_json = true;

		$pars = array();
		$err = array();
		$checkErr = array();
		$glErr = array();
		$glCheckErr = array();
		$enabled = array();
		$post = isset($_POST['var']) ? $_POST['var'] : array();

		// check for settings
		if (isset($post[ENVIRONMENT_SETTINGS::MAX_INSTANCES_LIMIT]) && $post[ENVIRONMENT_SETTINGS::MAX_INSTANCES_LIMIT]) {
			$pars[ENVIRONMENT_SETTINGS::MAX_INSTANCES_LIMIT] = intval($post[ENVIRONMENT_SETTINGS::MAX_INSTANCES_LIMIT]);
		} else {
			$pars[ENVIRONMENT_SETTINGS::MAX_INSTANCES_LIMIT] = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::MAX_INSTANCES_LIMIT);
			if (! $pars[ENVIRONMENT_SETTINGS::MAX_INSTANCES_LIMIT])
				$err[ENVIRONMENT_SETTINGS::MAX_INSTANCES_LIMIT] = "Max instances limit required";
		}

		if (isset($post[ENVIRONMENT_SETTINGS::MAX_EIPS_LIMIT]) && $post[ENVIRONMENT_SETTINGS::MAX_EIPS_LIMIT]) {
			$pars[ENVIRONMENT_SETTINGS::MAX_EIPS_LIMIT] = intval($post[ENVIRONMENT_SETTINGS::MAX_EIPS_LIMIT]);
		} else {
			$pars[ENVIRONMENT_SETTINGS::MAX_EIPS_LIMIT] = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::MAX_EIPS_LIMIT);
			if (! $pars[ENVIRONMENT_SETTINGS::MAX_EIPS_LIMIT])
				$err[ENVIRONMENT_SETTINGS::MAX_EIPS_LIMIT] = "Max elastic ips limit required";
		}

		if (isset($post[ENVIRONMENT_SETTINGS::SYNC_TIMEOUT]) && $post[ENVIRONMENT_SETTINGS::SYNC_TIMEOUT]) {
			$pars[ENVIRONMENT_SETTINGS::SYNC_TIMEOUT] = intval($post[ENVIRONMENT_SETTINGS::SYNC_TIMEOUT]);
		} else {
			$pars[ENVIRONMENT_SETTINGS::SYNC_TIMEOUT] = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::SYNC_TIMEOUT);
			if (! $pars[ENVIRONMENT_SETTINGS::SYNC_TIMEOUT])
				$err[ENVIRONMENT_SETTINGS::SYNC_TIMEOUT] = "Sync timeout required";
		}

		if (isset($post[ENVIRONMENT_SETTINGS::TIMEZONE]) && $post[ENVIRONMENT_SETTINGS::TIMEZONE]) {
			$pars[ENVIRONMENT_SETTINGS::TIMEZONE] = $post[ENVIRONMENT_SETTINGS::TIMEZONE];
		} else {
			$pars[ENVIRONMENT_SETTINGS::TIMEZONE] = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::TIMEZONE);
			if (! $pars[ENVIRONMENT_SETTINGS::TIMEZONE])
				$err[ENVIRONMENT_SETTINGS::TIMEZONE] = "Timezone required";
		}

		$pars[ENVIRONMENT_SETTINGS::API_ENABLED] = $post[ENVIRONMENT_SETTINGS::API_ENABLED] ? 1 : 0;
		$pars[ENVIRONMENT_SETTINGS::API_ALLOWED_IPS] = $post[ENVIRONMENT_SETTINGS::API_ALLOWED_IPS];

		// check for EC2
		if (isset($post[SERVER_PLATFORMS::EC2 . '.is_enabled']) && ($post[SERVER_PLATFORMS::EC2 . '.is_enabled'] == 'on')) {
			$enabled[SERVER_PLATFORMS::EC2] = true;

			if (isset($post[Modules_Platforms_Ec2::ACCOUNT_ID]) && $post[Modules_Platforms_Ec2::ACCOUNT_ID]) {
				if (! is_numeric($post[Modules_Platforms_Ec2::ACCOUNT_ID]) or strlen($post[Modules_Platforms_Ec2::ACCOUNT_ID]) != 12)
					//$err[Modules_Platforms_Ec2::ACCOUNT_ID] = _("AWS numeric account ID required (See <a href='/faq.html'>FAQ</a> for info on where to get it).");
					$err[Modules_Platforms_Ec2::ACCOUNT_ID] = _("AWS numeric account ID required");
				else
					$pars[Modules_Platforms_Ec2::ACCOUNT_ID] = preg_replace("/[^0-9]+/", "", $post[Modules_Platforms_Ec2::ACCOUNT_ID]);
			} else {
				$pars[Modules_Platforms_Ec2::ACCOUNT_ID] = $env->getPlatformConfigValue(Modules_Platforms_Ec2::ACCOUNT_ID);
				if (! $pars[Modules_Platforms_Ec2::ACCOUNT_ID])
					$err[Modules_Platforms_Ec2::ACCOUNT_ID] = "AWS Key ID required";
			}

			if (isset($post[Modules_Platforms_Ec2::ACCESS_KEY]) && $post[Modules_Platforms_Ec2::ACCESS_KEY]) {
				$pars[Modules_Platforms_Ec2::ACCESS_KEY] = $post[Modules_Platforms_Ec2::ACCESS_KEY];
			} else {
				$pars[Modules_Platforms_Ec2::ACCESS_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY);
				if (! $pars[Modules_Platforms_Ec2::ACCESS_KEY])
					$err[Modules_Platforms_Ec2::ACCESS_KEY] = "AWS Access Key required";
			}

			if (isset($post[Modules_Platforms_Ec2::SECRET_KEY]) && $post[Modules_Platforms_Ec2::SECRET_KEY] && $post[Modules_Platforms_Ec2::SECRET_KEY] != '******') {
				$pars[Modules_Platforms_Ec2::SECRET_KEY] = $post[Modules_Platforms_Ec2::SECRET_KEY];
			} else {
				$pars[Modules_Platforms_Ec2::SECRET_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY);
				if (! $pars[Modules_Platforms_Ec2::ACCESS_KEY])
					$err[Modules_Platforms_Ec2::SECRET_KEY] = "AWS Secret Key required";
			}

			if (!
				(isset($_FILES['var']['tmp_name'][Modules_Platforms_Ec2::PRIVATE_KEY]) &&
				(($pars[Modules_Platforms_Ec2::PRIVATE_KEY] = @file_get_contents($_FILES['var']['tmp_name'][Modules_Platforms_Ec2::PRIVATE_KEY])) != ''))
			) {
				$pars[Modules_Platforms_Ec2::PRIVATE_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY);
				if (! $pars[Modules_Platforms_Ec2::PRIVATE_KEY])
					$err[Modules_Platforms_Ec2::PRIVATE_KEY] = "AWS x.509 Private Key required";
			}

			if (!
				(isset($_FILES['var']['tmp_name'][Modules_Platforms_Ec2::CERTIFICATE]) &&
				(($pars[Modules_Platforms_Ec2::CERTIFICATE] = @file_get_contents($_FILES['var']['tmp_name'][Modules_Platforms_Ec2::CERTIFICATE])) != ''))
			) {
				$pars[Modules_Platforms_Ec2::CERTIFICATE] = $env->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE);
				if (! $pars[Modules_Platforms_Ec2::CERTIFICATE])
					$err[Modules_Platforms_Ec2::CERTIFICATE] = "AWS x.509 Certificate required";
			}

			if (! count($err)) {
				if (
					$pars[Modules_Platforms_Ec2::ACCOUNT_ID] != $env->getPlatformConfigValue(Modules_Platforms_Ec2::ACCOUNT_ID) or
					$pars[Modules_Platforms_Ec2::ACCESS_KEY] != $env->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY) or
					$pars[Modules_Platforms_Ec2::SECRET_KEY] != $env->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY) or
					$pars[Modules_Platforms_Ec2::PRIVATE_KEY] != $env->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY) or
					$pars[Modules_Platforms_Ec2::CERTIFICATE] != $env->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
 				) {
					try {
						$AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
							'us-east-1',
							$pars[Modules_Platforms_Ec2::PRIVATE_KEY],
							$pars[Modules_Platforms_Ec2::CERTIFICATE]
						);
						$AmazonEC2Client->describeInstances();
					} catch (Exception $e) {
						$checkErr[] = sprintf(_("Failed to verify your EC2 certificate and private key. %s"), $e->getMessage());
					}

					try {
						$AmazonS3 = new AmazonS3($pars[Modules_Platforms_Ec2::ACCESS_KEY], $pars[Modules_Platforms_Ec2::SECRET_KEY]);
						$buckets = $AmazonS3->ListBuckets();
					} catch(Exception $e) {
						$checkErr[] = sprintf(_("Failed to verify your EC2 access key and secret key. %s"), $e->getMessage());
					}
				}
			}

			$glErr = array_merge($glErr, $err);
			$glCheckErr = array_merge($glCheckErr, $checkErr);
		} else {
			$enabled[SERVER_PLATFORMS::EC2] = false;
		}

		// check for RDS
		if (isset($post[SERVER_PLATFORMS::RDS . '.is_enabled']) && $post[SERVER_PLATFORMS::RDS . '.is_enabled'] == 'on') {
			$enabled[SERVER_PLATFORMS::RDS] = true;

			if (isset($post[SERVER_PLATFORMS::RDS . '.the_same_as_ec2']) && $post[SERVER_PLATFORMS::RDS . '.the_same_as_ec2'] == 'on') {
				$pars[Modules_Platforms_Rds::ACCOUNT_ID] = $pars[Modules_Platforms_Ec2::ACCOUNT_ID];
				$pars[Modules_Platforms_Rds::ACCESS_KEY] = $pars[Modules_Platforms_Ec2::ACCESS_KEY];
				$pars[Modules_Platforms_Rds::SECRET_KEY] = $pars[Modules_Platforms_Ec2::SECRET_KEY];
				$pars[Modules_Platforms_Rds::PRIVATE_KEY] = $pars[Modules_Platforms_Ec2::PRIVATE_KEY];
				$pars[Modules_Platforms_Rds::CERTIFICATE] = $pars[Modules_Platforms_Ec2::CERTIFICATE];
			} else {
				$err = array();
				$checkErr = array();
				if (isset($post[Modules_Platforms_Rds::ACCOUNT_ID]) && $post[Modules_Platforms_Rds::ACCOUNT_ID]) {
					if (! is_numeric($post[Modules_Platforms_Rds::ACCOUNT_ID]) or strlen($post[Modules_Platforms_Rds::ACCOUNT_ID]) != 12)
						$err[Modules_Platforms_Rds::ACCOUNT_ID] = _("AWS numeric account ID required (See <a href='/faq.html'>FAQ</a> for info on where to get it).");
					else
						$pars[Modules_Platforms_Rds::ACCOUNT_ID] = preg_replace("/[^0-9]+/", "", $post[Modules_Platforms_Rds::ACCOUNT_ID]);
				} else {
					$pars[Modules_Platforms_Rds::ACCOUNT_ID] = $env->getPlatformConfigValue(Modules_Platforms_Rds::ACCOUNT_ID);
					if (! $pars[Modules_Platforms_Rds::ACCOUNT_ID])
						$err[Modules_Platforms_Rds::ACCOUNT_ID] = "AWS Key ID required";
				}

				if (isset($post[Modules_Platforms_Rds::ACCESS_KEY]) && $post[Modules_Platforms_Rds::ACCESS_KEY]) {
					$pars[Modules_Platforms_Rds::ACCESS_KEY] = $post[Modules_Platforms_Rds::ACCESS_KEY];
				} else {
					$pars[Modules_Platforms_Rds::ACCESS_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Rds::ACCESS_KEY);
					if (! $pars[Modules_Platforms_Rds::ACCESS_KEY])
						$err[Modules_Platforms_Rds::ACCESS_KEY] = "AWS Access Key required";
				}

				if (isset($post[Modules_Platforms_Rds::SECRET_KEY]) && $post[Modules_Platforms_Rds::SECRET_KEY] && $post[Modules_Platforms_Rds::SECRET_KEY] != '******') {
					$pars[Modules_Platforms_Rds::SECRET_KEY] = $post[Modules_Platforms_Rds::SECRET_KEY];
				} else {
					$pars[Modules_Platforms_Rds::SECRET_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Rds::SECRET_KEY);
					if (! $pars[Modules_Platforms_Rds::ACCESS_KEY])
						$err[Modules_Platforms_Rds::SECRET_KEY] = "AWS Secret Key required";
				}

				if (!
					(isset($_FILES['var']['tmp_name'][Modules_Platforms_Rds::PRIVATE_KEY]) &&
					($pars[Modules_Platforms_Rds::PRIVATE_KEY] = @file_get_contents($_FILES['var']['tmp_name'][Modules_Platforms_Rds::PRIVATE_KEY])) != '')
				) {
					$pars[Modules_Platforms_Rds::PRIVATE_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Rds::PRIVATE_KEY);
					if (! $pars[Modules_Platforms_Rds::PRIVATE_KEY])
						$err[Modules_Platforms_Rds::PRIVATE_KEY] = "AWS x.509 Private Key required";
				}

				if (!
					(isset($_FILES['var']['tmp_name'][Modules_Platforms_Rds::CERTIFICATE]) &&
					($pars[Modules_Platforms_Rds::CERTIFICATE] = @file_get_contents($_FILES['var']['tmp_name'][Modules_Platforms_Rds::CERTIFICATE])) != '')
				) {
					$pars[Modules_Platforms_Rds::CERTIFICATE] = $env->getPlatformConfigValue(Modules_Platforms_Rds::CERTIFICATE);
					if (! $pars[Modules_Platforms_Rds::CERTIFICATE])
						$err[Modules_Platforms_Rds::CERTIFICATE] = "AWS x.509 Certificate required";
				}
			}


			if (! count($err)) {
				/* TODO: check
				*/
			}

			$glErr = array_merge($glErr, $err);
			$glCheckErr = array_merge($glCheckErr, $checkErr);
		} else {
			$enabled[SERVER_PLATFORMS::RDS] = false;
		}

		// check for Rackspace
		if (isset($post[SERVER_PLATFORMS::RACKSPACE . '.is_enabled']) && ($post[SERVER_PLATFORMS::RACKSPACE . '.is_enabled'] == 'on')) {
			$enabled[SERVER_PLATFORMS::RACKSPACE] = true;

			if (isset($post[Modules_Platforms_Rackspace::USERNAME]) && $post[Modules_Platforms_Rackspace::USERNAME]) {
				$pars[Modules_Platforms_Rackspace::USERNAME] = $post[Modules_Platforms_Rackspace::USERNAME];
			} else {
				$pars[Modules_Platforms_Rackspace::USERNAME] = $env->getPlatformConfigValue(Modules_Platforms_Rackspace::USERNAME);
				if (! $pars[Modules_Platforms_Rackspace::USERNAME])
					$err[Modules_Platforms_Rackspace::USERNAME] = "Username required";
			}

			if (isset($post[Modules_Platforms_Rackspace::API_KEY]) && $post[Modules_Platforms_Rackspace::API_KEY]) {
				$pars[Modules_Platforms_Rackspace::API_KEY] = $post[Modules_Platforms_Rackspace::API_KEY];
			} else {
				$pars[Modules_Platforms_Rackspace::API_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Rackspace::API_KEY);
				if (! $pars[Modules_Platforms_Rackspace::API_KEY])
					$err[Modules_Platforms_Rackspace::API_KEY] = "API Key required";
			}

			if (! count($err)) {
				// TODO: check Rackspace's credentials
			}

			$glErr = array_merge($glErr, $err);
			$glCheckErr = array_merge($glCheckErr, $checkErr);
		} else {
			$enabled[SERVER_PLATFORMS::RACKSPACE] = false;
		}

		// check for Eucalyptus
		if (isset($post[SERVER_PLATFORMS::EUCALYPTUS . '.is_enabled']) && $post[SERVER_PLATFORMS::EUCALYPTUS . '.is_enabled'] == 'on') {
			$enabled[SERVER_PLATFORMS::EUCALYPTUS] = true;

			$err = array();
			$checkErr = array();
			if (isset($post[Modules_Platforms_Eucalyptus::ACCOUNT_ID]) && $post[Modules_Platforms_Eucalyptus::ACCOUNT_ID]) {
				if (! is_numeric($post[Modules_Platforms_Eucalyptus::ACCOUNT_ID]))
					$err[Modules_Platforms_Eucalyptus::ACCOUNT_ID] = _("AWS numeric account ID required");
				else
					$pars[Modules_Platforms_Eucalyptus::ACCOUNT_ID] = preg_replace("/[^0-9]+/", "", $post[Modules_Platforms_Eucalyptus::ACCOUNT_ID]);
			} else {
				$pars[Modules_Platforms_Eucalyptus::ACCOUNT_ID] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::ACCOUNT_ID);
				if (! $pars[Modules_Platforms_Eucalyptus::ACCOUNT_ID])
					$err[Modules_Platforms_Eucalyptus::ACCOUNT_ID] = "Account ID required";
			}

			if (isset($post[Modules_Platforms_Eucalyptus::ACCESS_KEY]) && $post[Modules_Platforms_Eucalyptus::ACCESS_KEY]) {
				$pars[Modules_Platforms_Eucalyptus::ACCESS_KEY] = $post[Modules_Platforms_Eucalyptus::ACCESS_KEY];
			} else {
				$pars[Modules_Platforms_Eucalyptus::ACCESS_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::ACCESS_KEY);
				if (! $pars[Modules_Platforms_Eucalyptus::ACCESS_KEY])
					$err[Modules_Platforms_Eucalyptus::ACCESS_KEY] = "Access Key required";
			}

			if (isset($post[Modules_Platforms_Eucalyptus::EC2_URL]) && $post[Modules_Platforms_Eucalyptus::EC2_URL]) {
				$pars[Modules_Platforms_Eucalyptus::EC2_URL] = $post[Modules_Platforms_Eucalyptus::EC2_URL];
			} else {
				$pars[Modules_Platforms_Eucalyptus::EC2_URL] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::EC2_URL);
				if (! $pars[Modules_Platforms_Eucalyptus::EC2_URL])
					$err[Modules_Platforms_Eucalyptus::EC2_URL] = "EC2 URL required";
			}

			if (isset($post[Modules_Platforms_Eucalyptus::S3_URL]) && $post[Modules_Platforms_Eucalyptus::S3_URL]) {
				$pars[Modules_Platforms_Eucalyptus::S3_URL] = $post[Modules_Platforms_Eucalyptus::S3_URL];
			} else {
				$pars[Modules_Platforms_Eucalyptus::S3_URL] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::S3_URL);
				if (! $pars[Modules_Platforms_Eucalyptus::S3_URL])
					$err[Modules_Platforms_Eucalyptus::S3_URL] = "S3 URL required";
			}

			if (isset($post[Modules_Platforms_Eucalyptus::SECRET_KEY]) && $post[Modules_Platforms_Eucalyptus::SECRET_KEY] && $post[Modules_Platforms_Eucalyptus::SECRET_KEY] != '******') {
				$pars[Modules_Platforms_Eucalyptus::SECRET_KEY] = $post[Modules_Platforms_Eucalyptus::SECRET_KEY];
			} else {
				$pars[Modules_Platforms_Eucalyptus::SECRET_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::SECRET_KEY);
				if (! $pars[Modules_Platforms_Eucalyptus::SECRET_KEY])
					$err[Modules_Platforms_Eucalyptus::SECRET_KEY] = "Secret Key required";
			}

			if (!
				(isset($_FILES['var']['tmp_name'][Modules_Platforms_Eucalyptus::PRIVATE_KEY]) &&
				($pars[Modules_Platforms_Eucalyptus::PRIVATE_KEY] = @file_get_contents($_FILES['var']['tmp_name'][Modules_Platforms_Eucalyptus::PRIVATE_KEY])) != '')
			) {
				$pars[Modules_Platforms_Eucalyptus::PRIVATE_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::PRIVATE_KEY);
				if (! $pars[Modules_Platforms_Eucalyptus::PRIVATE_KEY])
					$err[Modules_Platforms_Eucalyptus::PRIVATE_KEY] = "x.509 Private Key required";
			}

			if (!
				(isset($_FILES['var']['tmp_name'][Modules_Platforms_Eucalyptus::CERTIFICATE]) &&
				($pars[Modules_Platforms_Eucalyptus::CERTIFICATE] = @file_get_contents($_FILES['var']['tmp_name'][Modules_Platforms_Eucalyptus::CERTIFICATE])) != '')
			) {
				$pars[Modules_Platforms_Eucalyptus::CERTIFICATE] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::CERTIFICATE);
				if (! $pars[Modules_Platforms_Eucalyptus::CERTIFICATE])
					$err[Modules_Platforms_Eucalyptus::CERTIFICATE] = "x.509 Certificate required";
			}


			if (!
				(isset($_FILES['var']['tmp_name'][Modules_Platforms_Eucalyptus::CLOUD_CERTIFICATE]) &&
				($pars[Modules_Platforms_Eucalyptus::CLOUD_CERTIFICATE] = @file_get_contents($_FILES['var']['tmp_name'][Modules_Platforms_Eucalyptus::CLOUD_CERTIFICATE])) != '')
			) {
				$pars[Modules_Platforms_Eucalyptus::CLOUD_CERTIFICATE] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::CLOUD_CERTIFICATE);
				if (! $pars[Modules_Platforms_Eucalyptus::CLOUD_CERTIFICATE])
					$err[Modules_Platforms_Eucalyptus::CLOUD_CERTIFICATE] = "x.509 Cloud Certificate required";
			}

			if (! count($err)) {
				/* TODO: check
				*/
			}

			$glErr = array_merge($glErr, $err);
			$glCheckErr = array_merge($glCheckErr, $checkErr);
		} else {
			$enabled[SERVER_PLATFORMS::EUCALYPTUS] = false;
		}

		if (count($glErr)) {
			/*foreach ($glErr as $key => $value) {
				$glErr['var[' . $key . ']'] = $value;
				unset($glErr[$key]);
			}*/
			print json_encode(array('success' => false, 'errors' => $glErr));
		} elseif (count($glCheckErr)) {
			print json_encode(array('success' => false, 'error' => $glCheckErr));
		} else {
			$db->BeginTrans();
			try {
				foreach ($enabled as $key => $flag) {
					$env->enablePlatform($key, $flag);
				}
				$env->setPlatformConfig($pars);
				print json_encode(array('success' => true));
			} catch (Exception $e) {
				$db->RollbackTrans();
				print json_encode(array('success' => false, 'error' => _('Failed to save AWS settings')));
			}
			$db->CommitTrans();
		}
	} else {
		$params = array();

		$params[ENVIRONMENT_SETTINGS::MAX_INSTANCES_LIMIT] = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::MAX_INSTANCES_LIMIT, false);
		$params[ENVIRONMENT_SETTINGS::MAX_EIPS_LIMIT] = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::MAX_EIPS_LIMIT, false);
		$params[ENVIRONMENT_SETTINGS::SYNC_TIMEOUT] = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::SYNC_TIMEOUT, false);
		$params[ENVIRONMENT_SETTINGS::TIMEZONE] = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::TIMEZONE, false);

		$params[ENVIRONMENT_SETTINGS::API_ENABLED] = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::API_ENABLED, false);
		$params[ENVIRONMENT_SETTINGS::API_ALLOWED_IPS] = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::API_ALLOWED_IPS, false);
		$params[ENVIRONMENT_SETTINGS::API_KEYID] = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::API_KEYID, false);
		$params[ENVIRONMENT_SETTINGS::API_ACCESS_KEY] = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::API_ACCESS_KEY, false);

		foreach ($env->getEnabledPlatforms() as $platform) {
			$params[$platform . '.is_enabled'] = true;
			if ($platform == SERVER_PLATFORMS::EC2) {
				$params[Modules_Platforms_Ec2::ACCOUNT_ID] = $env->getPlatformConfigValue(Modules_Platforms_Ec2::ACCOUNT_ID);
				$params[Modules_Platforms_Ec2::ACCESS_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY);
				$params[Modules_Platforms_Ec2::SECRET_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY) != '' ? '******' : false;
				$params[Modules_Platforms_Ec2::PRIVATE_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY) != '' ? true : false;
				$params[Modules_Platforms_Ec2::CERTIFICATE] = $env->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE) != '' ? true : false;

			} elseif ($platform == SERVER_PLATFORMS::RDS) {
				$params[Modules_Platforms_Rds::ACCOUNT_ID] = $env->getPlatformConfigValue(Modules_Platforms_Rds::ACCOUNT_ID);
				$params[Modules_Platforms_Rds::ACCESS_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Rds::ACCESS_KEY);
				$params[Modules_Platforms_Rds::SECRET_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Rds::SECRET_KEY) != '' ? '******' : false;
				$params[Modules_Platforms_Rds::PRIVATE_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Rds::PRIVATE_KEY) != '' ? true : false;
				$params[Modules_Platforms_Rds::CERTIFICATE] = $env->getPlatformConfigValue(Modules_Platforms_Rds::CERTIFICATE) != '' ? true : false;

				if (
					$env->getPlatformConfigValue(Modules_Platforms_Ec2::ACCOUNT_ID) == $env->getPlatformConfigValue(Modules_Platforms_Rds::ACCOUNT_ID) &&
					$env->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY) == $env->getPlatformConfigValue(Modules_Platforms_Rds::ACCESS_KEY) &&
					$env->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY) == $env->getPlatformConfigValue(Modules_Platforms_Rds::SECRET_KEY) &&
					$env->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY) == $env->getPlatformConfigValue(Modules_Platforms_Rds::PRIVATE_KEY) &&
					$env->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE) == $env->getPlatformConfigValue(Modules_Platforms_Rds::CERTIFICATE)
				)
					$params['rds.the_same_as_ec2'] = true;

			} elseif ($platform == SERVER_PLATFORMS::RACKSPACE) {
				$params[Modules_Platforms_Rackspace::USERNAME] = $env->getPlatformConfigValue(Modules_Platforms_Rackspace::USERNAME);
				$params[Modules_Platforms_Rackspace::API_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Rackspace::API_KEY);

			} elseif ($platform == SERVER_PLATFORMS::EUCALYPTUS) {
				$params[Modules_Platforms_Eucalyptus::ACCOUNT_ID] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::ACCOUNT_ID);
				$params[Modules_Platforms_Eucalyptus::ACCESS_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::ACCESS_KEY);
				$params[Modules_Platforms_Eucalyptus::EC2_URL] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::EC2_URL);
				$params[Modules_Platforms_Eucalyptus::S3_URL] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::S3_URL);
				$params[Modules_Platforms_Eucalyptus::SECRET_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::SECRET_KEY) != '' ? '******' : false;
				$params[Modules_Platforms_Eucalyptus::PRIVATE_KEY] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::PRIVATE_KEY) != '' ? true : false;
				$params[Modules_Platforms_Eucalyptus::CLOUD_CERTIFICATE] = $env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::CLOUD_CERTIFICATE) != '' ? true : false;
			}
		}

		$display['load_extjs'] = true;
		$display['title'] = 'Environments&nbsp;&raquo;&nbsp;Edit';
		$display['env'] = $env;
		$display['envParams'] = json_encode($params);

		$timezones = array();
		$timezoneAbbreviationsList = timezone_abbreviations_list();
		foreach ($timezoneAbbreviationsList as $timezoneAbbreviations) {
			foreach ($timezoneAbbreviations as $value) {
				if (preg_match( '/^(America|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific|Australia)\//', $value['timezone_id']))
					$timezones[$value['timezone_id']] = $value['offset'];
			}
		}
		ksort($timezones);
		$display['timezones'] = json_encode(array_keys($timezones));

		require_once ("src/append.inc.php");
	}
?>
