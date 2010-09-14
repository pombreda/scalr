<?php
	class Modules_Platforms_Ec2 implements IPlatformModule
	{
		private $db;
		
		/**
		 * 
		 * @var AmazonEC2
		 */
		private $instancesListCache = array();
		
		public function __construct()
		{
			$this->db = Core::GetDBInstance();
		}	
		
		public function GetServerID(DBServer $DBServer)
		{
			return $DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID);
		}
		
		public function IsServerExists(DBServer $DBServer, $debug = false)
		{
			return in_array(
				$DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID), 
				array_keys($this->GetServersList($DBServer->GetClient(), $DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)))
			);
		}
		
		public function GetServerIPAddresses(DBServer $DBServer)
		{
			$Client = $DBServer->GetClient();
			
			$EC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)));
	        $EC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
	        
	        $iinfo = $EC2Client->DescribeInstances($DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID));
		    $iinfo = $iinfo->reservationSet->item->instancesSet->item;
		    
		    return array(
		    	'localIp'	=> $iinfo->privateIpAddress,
		    	'remoteIp'	=> $iinfo->ipAddress
		    );
		}
		
		public function GetServersList(Client $Client, $region, $skipCache = false)
		{
			if (!$this->instancesListCache[$Client->AWSAccountID][$region] || $skipCache)
			{
				$EC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region));
		        $EC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		        
		        try
				{
		            $results = $EC2Client->DescribeInstances();
		            $results = $results->reservationSet;
				}
				catch(Exception $e)
				{
					throw new Exception(sprintf("Cannot get list of servers for platfrom ec2: %s", $e->getMessage()));
				}


				if ($results->item)
				{					
					if ($results->item->reservationId)
						$this->instancesListCache[$Client->AWSAccountID][$region][(string)$results->item->instancesSet->item->instanceId] = (string)$results->item->instancesSet->item->instanceState->name;
					else
					{
						foreach ($results->item as $item)
							$this->instancesListCache[$Client->AWSAccountID][$region][(string)$item->instancesSet->item->instanceId] = (string)$item->instancesSet->item->instanceState->name;
					}
				}
			}
	        
			return $this->instancesListCache[$Client->AWSAccountID][$region];
		}
		
		public function GetServerRealStatus(DBServer $DBServer)
		{
			$Client = $DBServer->GetClient();

			$region = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION);
			
			$iid = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID);
			if (!$iid)
			{
				$status = 'not-found';
			}
			elseif (!$this->instancesListCache[$Client->AWSAccountID][$region][$iid])
			{
		        $EC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region));
		        $EC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		        
		        try {
		        	$iinfo = $EC2Client->DescribeInstances($iid);
			        $iinfo = $iinfo->reservationSet->item;
			        
			        if ($iinfo)
			        	$status = (string)$iinfo->instancesSet->item->instanceState->name;
			        else
			        	$status = 'not-found';
		        }
		        catch(Exception $e)
		        {
		        	if (stristr($e->getMessage(), "does not exist"))
		        		$status = 'not-found';
		        	else
		        		throw $e;
		        }
			}
			else
			{
				$status = $this->instancesListCache[$Client->AWSAccountID][$region][$DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID)];
			}
			
			return Modules_Platforms_Ec2_Adapters_Status::load($status);
		}
		
		public function TerminateServer(DBServer $DBServer)
		{
			$Client = $DBServer->GetClient();
			
	        $EC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)));
	        $EC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
	        
	        $EC2Client->TerminateInstances(array($DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID)));
	        
	        return true;
		}
		
		public function RebootServer(DBServer $DBServer)
		{
			$Client = $DBServer->GetClient();
			
	        $EC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)));
	        $EC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
	        
	        $EC2Client->RebootInstances(array($DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID)));
	        
	        return true;
		}
		
		public function RemoveServerSnapshot(DBRole $DBRole)
		{
			$Client = Client::Load($DBRole->clientId);
			
	        $EC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBRole->region));
	        $EC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
        
	        
	        $DescribeImagesType = new DescribeImagesType();
			$DescribeImagesType->imagesSet->item[] = array("imageId" => $DBRole->imageId);
	        $ami_info = $EC2Client->DescribeImages($DescribeImagesType);
	        
	        $platfrom = (string)$ami_info->imagesSet->item->platform;
	        $rootDeviceType = (string)$ami_info->imagesSet->item->rootDeviceType;
	        
	        if ($rootDeviceType == 'ebs')
	        {
	        	//TODO:	
	        }
	        else
	        {
		        if ($platfrom == 'windows')
		        {
					//TODO:		        	
		        }
		        else
		        {    		    	
    		    	$image_path = (string)$ami_info->imagesSet->item->imageLocation;
    		    	
    		    	$chunks = explode("/", $image_path);
    		    	
    		    	$bucket_name = $chunks[0];
    		    	if (count($chunks) == 3)
    		    		$prefix = $chunks[1];
    		    	else
    		    		$prefix = str_replace(".manifest.xml", "", $chunks[1]);
    		    	
    		    	try
    		    	{
    		    		$bucket_not_exists = false;
    		    		$S3Client = new AmazonS3($Client->AWSAccessKeyID, $Client->AWSAccessKey);
    		    		$objects = $S3Client->ListBucket($bucket_name, $prefix);
    		    	}
    		    	catch(Exception $e)
    		    	{
    		    		if (stristr($e->getMessage(), "The specified bucket does not exist"))
    		    			$bucket_not_exists = true;
    		    	}	
    		    			    			    	
    		    	if ($ami_info)
    		    	{
    		    		if (!$bucket_not_exists)
    			    	{
    			    		foreach ($objects as $object)
    			    			$S3Client->DeleteObject($object->Key, $bucket_name);
    			    			
    			    		$bucket_not_exists = true;
    			    	}
    		    		
    		    		if ($bucket_not_exists)
    			    		$EC2Client->DeregisterImage($DBRole->imageId);
    		    	}
		        }
	        }
		}
		
		public function CheckServerSnapshotStatus(BundleTask $BundleTask)
		{
			if ($BundleTask->bundleType == SERVER_SNAPSHOT_CREATION_TYPE::EC2_EBS)
			{
				try
				{
					$DBServer = DBServer::LoadByID($BundleTask->serverId);
					
					$Client = $DBServer->GetClient();
					
			        $EC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)));
			        $EC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
			        
			        $DescribeImagesType = new DescribeImagesType();
					$DescribeImagesType->imagesSet->item[] = array("imageId" => $BundleTask->snapshotId);
			        $ami_info = $EC2Client->DescribeImages($DescribeImagesType);
			        $ami_info = $ami_info->imagesSet->item;
			        
			        $BundleTask->Log(sprintf("Checking snapshot creation status: %s", $ami_info->imageState));
			        
			        if ($ami_info->imageState == 'available')
			        {
			        	$BundleTask->SnapshotCreationComplete($BundleTask->snapshotId);
			        }
			        elseif ($ami_info->imageState == 'failed')
			        {
			        	$BundleTask->SnapshotCreationFailed("AWS returned status 'failed' for EBS image");
			        }
			        else
			        {
			        	Logger::getLogger(__CLASS__)->error("CheckServerSnapshotStatus ({$BundleTask->id}) status = {$ami_info->imageState}");
			        }
				}
				catch(Exception $e)
				{
					Logger::getLogger(__CLASS__)->fatal("CheckServerSnapshotStatus ({$BundleTask->id}): {$e->getMessage()}");
				}
			}	
	        
		}
		
		public function CreateServerSnapshot(BundleTask $BundleTask)
		{
			$DBServer = DBServer::LoadByID($BundleTask->serverId);
			
			$Client = $DBServer->GetClient();
			
	        $EC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)));
	        $EC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
	        
	        if (!$BundleTask->prototypeRoleId)
	        {
	        	$proto_ami_id = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::AMIID);
	        }
	        else
	        {
	        	$proto_ami_id = $this->db->GetOne("SELECT ami_id FROM roles WHERE id=?", array($BundleTask->prototypeRoleId));
	        }	        
	        
	        $DescribeImagesType = new DescribeImagesType();
			$DescribeImagesType->imagesSet->item[] = array("imageId" => $proto_ami_id);
	        $ami_info = $EC2Client->DescribeImages($DescribeImagesType);
	        
	        $platfrom = (string)$ami_info->imagesSet->item->platform;
	        $rootDeviceType = (string)$ami_info->imagesSet->item->rootDeviceType;
	        
	        if ($rootDeviceType == 'ebs')
	        {
	        	$BundleTask->bundleType = SERVER_SNAPSHOT_CREATION_TYPE::EC2_EBS;
	        	
	        	$BundleTask->Log(sprintf(_("Selected platfrom snapshoting type: %s"), $BundleTask->bundleType));
	        	
	        	try
	        	{
		        	$CreateImageType = new CreateImageType(
		        		$DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID),
		        		$BundleTask->roleName,
		        		$BundleTask->roleName,
		        		false
		        	);
		        	
		        	$result = $EC2Client->CreateImage($CreateImageType);
		        			        	
		        	$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::IN_PROGRESS;
		        	$BundleTask->snapshotId = $result->imageId;
		        	
		        	$BundleTask->Log(sprintf(_("Snapshot creating initialized (AMIID: %s). Bundle task status changed to: %s"), 
		        		$BundleTask->snapshotId, $BundleTask->status
		        	));
	        	}
	        	catch(Exception $e)
	        	{
	        		$BundleTask->SnapshotCreationFailed($e->getMessage());
	        		return;
	        	}
	        	
	        }
	        else
	        {
		        if ($platfrom == 'windows')
		        {
        	
		        	//TODO: Windows platfrom is not supported yet.
		        	
		        	$BundleTask->bundleType = SERVER_SNAPSHOT_CREATION_TYPE::EC2_WIN;
		        	
		        	$BundleTask->Log(sprintf(_("Selected platfrom snapshoting type: %s"), $BundleTask->bundleType));
		        	
		        	$BundleTask->SnapshotCreationFailed("Not supported yet");
		        	return;
		        }
		        else
		        {
		        	$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::IN_PROGRESS;
		        	$BundleTask->bundleType = SERVER_SNAPSHOT_CREATION_TYPE::EC2_S3I;
		        	
		        	$BundleTask->Save();
		        	
		        	$BundleTask->Log(sprintf(_("Selected platfrom snapshoting type: %s"), $BundleTask->bundleType));
		        	
		        	$msg = new Scalr_Messaging_Msg_Rebundle(
		        		$BundleTask->id,
						$BundleTask->roleName,
						array()
		        	);

	
	        		if (!$DBServer->SendMessage($msg))
	        		{
	        			$BundleTask->SnapshotCreationFailed("Cannot send rebundle message to server. Please check event log for more details.");
	        			return;
	        		}
		        	else
		        	{
			        	$BundleTask->Log(sprintf(_("Snapshot creating initialized (MessageID: %s). Bundle task status changed to: %s"), 
			        		$msg->messageId, $BundleTask->status
			        	));
		        	}
		        }
	        }
	        
	        $BundleTask->setDate('started');
	        $BundleTask->Save();
		}
		
		private function ApplyAccessData(Scalr_Messaging_Msg $msg)
		{
			
			
		}
		
		public function GetServerConsoleOutput(DBServer $DBServer)
		{
			$Client = $DBServer->GetClient();
			
	        $EC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)));
	        $EC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
	        
	        $c = $EC2Client->GetConsoleOutput($DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID));
	        
	        if ($c->output)
	        	return $c->output;
	        else
	        	return false;
		}
		
		public function GetServerExtendedInformation(DBServer $DBServer)
		{
			try
			{
				$Client = $DBServer->GetClient();
				
				try {
		        	$EC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)));
		        	$EC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
		        
		        	$iinfo = $EC2Client->DescribeInstances($DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID));
		        	$iinfo = $iinfo->reservationSet->item;
				}
				catch(Exception $e) {}
		        
		        if ($iinfo)
		        {
			        $groups = array();
			        if ($iinfo->groupSet->item->groupId)
			        	$groups[] = $iinfo->groupSet->item->groupId;
			        else
			        {
			        	foreach ($iinfo->groupSet->item as $item)
			        		$groups[] = $item->groupId;
			        }
			        
			        return array(
			        	'Instance ID'			=> $DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID),
			        	'Owner ID'				=> $iinfo->ownerId,
			        	'Image ID (AMI)'		=> $iinfo->instancesSet->item->imageId,
			        	'Private DNS name'		=> $iinfo->instancesSet->item->privateDnsName,
			        	'Public DNS name'		=> $iinfo->instancesSet->item->dnsName,
			        	'Private IP address'	=> $iinfo->instancesSet->item->privateIpAddress,
			        	'Public IP address'		=> $iinfo->instancesSet->item->ipAddress,
			        	'Key name'				=> $iinfo->instancesSet->item->keyName,
			        	'AMI launch index'		=> $iinfo->instancesSet->item->amiLaunchIndex,
			        	'Instance type'			=> $iinfo->instancesSet->item->instanceType,
			        	'Launch time'			=> $iinfo->instancesSet->item->launchTime,
			        	'Architecture'			=> $iinfo->instancesSet->item->architecture,
			        	'Root device type'		=> $iinfo->instancesSet->item->rootDeviceType,
			        	'Instance state'		=> $iinfo->instancesSet->item->instanceState->name." ({$iinfo->instancesSet->item->instanceState->code})",
			        	'Placement'				=> $iinfo->instancesSet->item->placement->availabilityZone,
			        	'Monitoring (CloudWatch)'	=> $iinfo->instancesSet->item->monitoring->state,
			        	'Security groups'		=> implode(', ', $groups)
			        );
		        }
			}
			catch(Excpetion $e)
			{
				
			}
			
			return false;
		}
		
		public function LaunchServer(DBServer $DBServer)
		{
			$Client = $DBServer->GetClient();
			
	        $EC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)));
	        $EC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
	        
	        $RunInstancesType = new RunInstancesType();
	        
	        //TODO:
	        $RunInstancesType->ConfigureRootPartition();
	        
	        // Set Cloudwatch monitoring
	        $RunInstancesType->SetCloudWatchMonitoring(
	        	$DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_AWS_ENABLE_CW_MONITORING)
	        );

	        $DBRole = DBRole::loadById($DBServer->roleId);
	        $DBServer->SetProperty(SERVER_PROPERTIES::ARCHITECTURE, $DBRole->architecture);
	        
	        
	        // Set AMI, AKI and ARI ids
	        $RunInstancesType->imageId = $DBRole->imageId;
	        
	        $akiId = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::AKIID);
	        if (!$akiId)
	        	$akiId = $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_AWS_AKI_ID);
	        		
	        if ($akiId)
	        	$RunInstancesType->kernelId = $akiId;
	        
	        	        
	        $ariId = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::ARIID);
	        if (!$ariId)
	        	$ariId = $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_AWS_ARI_ID);
	        		
	        if ($ariId)
	        	$RunInstancesType->ramdiskId = $ariId;
	        	
	        $RunInstancesType->minCount = 1;
	        $RunInstancesType->maxCount = 1;
	        
	        // Set Security groups
	        foreach ($this->GetServerSecurityGroupsList($DBServer, $EC2Client) as $sgroup)
	        	$RunInstancesType->AddSecurityGroup($sgroup);
	        	
	        // Set availability zone
	        $RunInstancesType->SetAvailabilityZone($this->GetServerAvailZone($DBServer, $EC2Client));
	        
	        $i_type = $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_AWS_INSTANCE_TYPE);
	        if (!$i_type)
	        {
	        	$i_type = $this->db->GetOne("SELECT instance_type FROM roles WHERE id=?", array(
		        	$DBServer->roleId
		        ));
	        }
	        
	        // Set instance type
	        $RunInstancesType->instanceType = $i_type;
	        
	        // Set additional info
	       	$RunInstancesType->additionalInfo = "";
	       	
	       	//set key name
	       	//TODO:
	        $RunInstancesType->keyName = $DBServer->GetFarmObject()->GetSetting(DBFarm::SETTING_AWS_KEYPAIR_NAME);
	        
	        
	        // Set user data
	        if (!$DBServer->GetFarmObject()->GetSetting(DBFarm::SETTING_AWS_S3_BUCKET_NAME))
	        	$bucket_name = "FARM-{$DBServer->farmId}-{$Client->AWSAccountID}";
	        else
	        	$bucket_name = $DBServer->GetFarmObject()->GetSetting(DBFarm::SETTING_AWS_S3_BUCKET_NAME);
	        
	        $user_data = array(
	        	"farmid" 			=> $DBServer->farmId,
	        	"role"				=> $DBServer->GetFarmRoleObject()->GetRoleAlias(),
	        	"eventhandlerurl"	=> CONFIG::$EVENTHANDLER_URL,
	        	"hash"				=> $DBServer->GetFarmObject()->Hash,
	        	"s3bucket"			=> $bucket_name,
	        	"realrolename"		=> $DBServer->GetFarmRoleObject()->GetRoleName(),
	        	"httpproto"			=> CONFIG::$HTTP_PROTO,
	        	"region"			=> $DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION),
	        	
	        	/*** For Scalarizr ***/
	        	"szr_key"			=> $DBServer->GetKey(),
	        	"serverid"			=> $DBServer->serverId,
	        	'p2p_producer_endpoint'	=> CONFIG::$HTTP_PROTO."://".CONFIG::$EVENTHANDLER_URL."/messaging",
				'queryenv_url'		=> CONFIG::$HTTP_PROTO."://".CONFIG::$EVENTHANDLER_URL."/environment.php"
	        );
	        
	        foreach ($user_data as $k=>$v)
	        	$u_data .= "{$k}={$v};";
	        	
	        $RunInstancesType->SetUserData(trim($u_data, ";"));
	        
			try
	        {
	        	$result = $EC2Client->RunInstances($RunInstancesType);
	        }
	        catch(Exception $e)
	        {
	        	try
	        	{
		        	if (stristr($e->getMessage(), "The key pair") && stristr($e->getMessage(), "does not exist"))
		        	{
						$result = $EC2Client->CreateKeyPair($RunInstancesType->keyName);
						if ($result->keyMaterial)
							$DBServer->GetFarmObject()->SetSetting(DBFarm::SETTING_AWS_PRIVATE_KEY, $result->keyMaterial);
		        	}
	        	}
	        	catch(Exception $e)
	        	{
				
	        	}
	        	
	            throw $e;
	        }
	        
	        if ($result->instancesSet)
	        {
	        	$DBServer->SetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE, (string)$result->instancesSet->item->placement->availabilityZone);
	        	$DBServer->SetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID, (string)$result->instancesSet->item->instanceId);
	        	$DBServer->SetProperty(EC2_SERVER_PROPERTIES::INSTANCE_TYPE, $RunInstancesType->instanceType);
	        	$DBServer->SetProperty(EC2_SERVER_PROPERTIES::AMIID, $RunInstancesType->imageId);
	        	
		        return $DBServer;
	        }
	        else 
	            throw new Exception(sprintf(_("Cannot launch new instance. %s"), $result->faultstring));
		}
		
		/*********************************************************************/
		/*********************************************************************/
		/*********************************************************************/
		/*********************************************************************/
		/*********************************************************************/
		
		private function GetServerSecurityGroupsList(DBServer $DBServer, $EC2Client)
		{
			// Add default security group
			$retval = array('default');
			
			try {
				$aws_sgroups_list_t = $EC2Client->DescribeSecurityGroups();
				$aws_sgroups_list_t = $aws_sgroups_list_t->securityGroupInfo->item;
		        if ($aws_sgroups_list_t instanceof stdClass)
		        	$aws_sgroups_list_t = array($aws_sgroups_list_t);
	
		        $aws_sgroups = array();
		        foreach ($aws_sgroups_list_t as $sg)
		        	$aws_sgroups[$sg->groupName] = $sg;
		        	
		        unset($aws_sgroups_list_t);
			}
			catch(Exception $e) {
				throw new Exception("GetServerSecurityGroupsList failed: {$e->getMessage()}");
			}
			
			// Add Role security group
			$role_sec_group = CONFIG::$SECGROUP_PREFIX.$DBServer->GetFarmRoleObject()->GetRoleName();
			$partent_sec_group = CONFIG::$SECGROUP_PREFIX.$DBServer->GetFarmRoleObject()->GetRolePrototype();
			array_push($retval, $role_sec_group);
			
			if (!$aws_sgroups[$role_sec_group])
			{
				try {
					$EC2Client->CreateSecurityGroup($role_sec_group, $DBServer->GetFarmRoleObject()->GetRoleName());
				}
				catch(Exception $e) {
					throw new Exception("GetServerSecurityGroupsList failed: {$e->getMessage()}");
				}
					                        
		    	$IpPermissionSet = new IpPermissionSetType();
				
		    	$group_rules = $this->db->GetAll("SELECT * FROM security_rules WHERE roleid=?", array(
		    		$DBServer->GetFarmRoleObject()->GetRoleID()
		    	));
		    	
		    	//
				// Check parent security group
				//
				if ($aws_sgroups[$partent_sec_group] && count($group_rules) == 0)
					$IpPermissionSet->item = $aws_sgroups[$partent_sec_group]->ipPermissions->item; 
				else
				{
					if (count($group_rules) == 0)
					{
						$group_rules = array(
							array('rule' => 'tcp:22:22:0.0.0.0/0'),
							array('rule' => 'tcp:8013:8013:0.0.0.0/0'), // For Scalarizr
							array('rule' => 'udp:8014:8014:0.0.0.0/0'), // For Scalarizr
							array('rule' => 'udp:161:162:0.0.0.0/0'),
							array('rule' => 'icmp:-1:-1:0.0.0.0/0')
						);                        
					}
					
		            foreach ($group_rules as $rule)
		            {
		            	$group_rule = explode(":", $rule["rule"]);
		                $IpPermissionSet->AddItem($group_rule[0], $group_rule[1], $group_rule[2], null, array($group_rule[3]));
		            }
				}
	
	            // Create security group
	            $EC2Client->AuthorizeSecurityGroupIngress($DBServer->GetClient()->AWSAccountID, $role_sec_group, $IpPermissionSet);	
			}
			
			
			// Add MySQL Security group
			if ($DBServer->GetFarmRoleObject()->GetRoleAlias() == ROLE_ALIAS::MYSQL)
			{
				array_push($retval, CONFIG::$MYSQL_STAT_SEC_GROUP);
				if (!$aws_sgroups[CONFIG::$MYSQL_STAT_SEC_GROUP])
				{
					try {
						$EC2Client->CreateSecurityGroup(CONFIG::$MYSQL_STAT_SEC_GROUP, "Security group for access to mysql replication status from Scalr app");
					}
					catch(Exception $e) {
						throw new Exception("GetServerSecurityGroupsList failed: {$e->getMessage()}");
					}
					
					// Get permission rules for group
		            $IpPermissionSet = new IpPermissionSetType();
		            //$ipProtocol, $fromPort, $toPort, $groups, $ipRanges
		            $ips = explode(",", CONFIG::$APP_SYS_IPADDRESS);
		            
		            foreach ($ips as $ip)
		            {
		            	if ($ip != '')
		            		$IpPermissionSet->AddItem("tcp", "3306", "3306", null, array(trim($ip)."/32"));
		            }
		
		            // Create security group
		            $EC2Client->AuthorizeSecurityGroupIngress($DBServer->GetClient()->AWSAccountID, CONFIG::$MYSQL_STAT_SEC_GROUP, $IpPermissionSet);
				}
			}
	         
			return $retval;
		}
		
		private function GetServerAvailZone(DBServer $DBServer, $EC2Client)
		{
			$server_avail_zone = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE);
			
			if ($server_avail_zone && $server_avail_zone != 'x-scalr-diff')
				return $server_avail_zone; 
			
			$role_avail_zone = $this->db->GetOne("SELECT ec2_avail_zone FROM ec2_ebs WHERE server_index=? AND farm_roleid=?",
        		array($DBServer->index, $DBServer->farmRoleId)
        	);
        	
        	if (!$role_avail_zone)
        		$role_avail_zone = $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_AWS_AVAIL_ZONE);
        		
        	if (!$role_avail_zone || $role_avail_zone == "x-scalr-diff")
        	{
        		//TODO: Elastic Load Balancer
        		
        		// Get list of all available zones
        		$avail_zones_resp = $EC2Client->DescribeAvailabilityZones();
			    $avail_zones = array();
			    foreach ($avail_zones_resp->availabilityZoneInfo->item as $zone)
			    {
			    	if (strstr($zone->zoneState,'available')) //TODO:
			    		array_push($avail_zones, (string)$zone->zoneName);
			    }
        		
			    if (!$role_avail_zone)
			    	$zone_index = rand(0, count($avail_zones)-1);
			    else
			    {
				    // Get count of curently running instances
	        		$instance_count = $this->db->GetOne("SELECT COUNT(*) FROM servers WHERE farm_roleid=? AND status NOT IN (?,?)", 
	        			array($DBServer->farmRoleId, SERVER_STATUS::PENDING_TERMINATE, SERVER_STATUS::TERMINATED)
	        		);
	        		
	        		// Get zone index.
	        		$zone_index = ($instance_count-1) % count($avail_zones);
			    }
        		
        		return $avail_zones[$zone_index];
        	}
        	else
        		return $role_avail_zone;
		}
		
		public function PutAccessData(DBServer $DBServer, Scalr_Messaging_Msg $message)
		{
			$put = false;
			$put |= $message instanceof Scalr_Messaging_Msg_Rebundle;
			$put |= $message instanceof Scalr_Messaging_Msg_HostInitResponse && $DBServer->GetFarmRoleObject()->GetRoleAlias() == ROLE_ALIAS::MYSQL;
			$put |= $message instanceof Scalr_Messaging_Msg_Mysql_PromoteToMaster;
			$put |= $message instanceof Scalr_Messaging_Msg_Mysql_CreateDataBundle;
			$put |= $message instanceof Scalr_Messaging_Msg_Mysql_CreateBackup;
			
			
			if ($put) {
				$Client = $DBServer->GetClient();
	        	$accessData = new stdClass();
	        	$accessData->accountId = $Client->AWSAccountID;
	        	$accessData->keyId = $Client->AWSAccessKeyID;
	        	$accessData->key = $Client->AWSAccessKey;
	        	$accessData->cert = $Client->AWSCertificate;
	        	$accessData->pk = $Client->AWSPrivateKey;
	        	
	        	$message->platformAccessData = $accessData;
			}
		}
	}

	
	
?>