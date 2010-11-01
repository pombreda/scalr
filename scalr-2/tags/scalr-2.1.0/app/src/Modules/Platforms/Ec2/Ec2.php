<?php
	class Modules_Platforms_Ec2 extends Modules_Platforms_Aws implements IPlatformModule
	{
		private $db;
		
		/** Properties **/
		const ACCOUNT_ID 	= 'ec2.account_id';
		const ACCESS_KEY	= 'ec2.access_key';
		const SECRET_KEY	= 'ec2.secret_key';
		const PRIVATE_KEY	= 'ec2.private_key';
		const CERTIFICATE	= 'ec2.certificate';
		
		/**
		 * 
		 * @var AmazonEC2
		 */
		private $instancesListCache = array();
		
		public function __construct()
		{
			$this->db = Core::GetDBInstance();
		}
		
		public function getPropsList()
		{
			return array(
				self::ACCOUNT_ID	=> 'AWS Account ID',
				self::ACCESS_KEY	=> 'AWS Access Key',
				self::SECRET_KEY	=> 'AWS Secret Key',
				self::CERTIFICATE	=> 'AWS x.509 Certificate',
				self::PRIVATE_KEY	=> 'AWS x.509 Private Key'
			);
		}
		
		public function GetServerCloudLocation(DBServer $DBServer)
		{
			return $DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION);
		}
		
		public function GetServerID(DBServer $DBServer)
		{
			return $DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID);
		}
		
		public function IsServerExists(DBServer $DBServer, $debug = false)
		{
			return in_array(
				$DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID), 
				array_keys($this->GetServersList(
					$DBServer->GetEnvironmentObject(), 
					$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)
				))
			);
		}
		
		public function GetServerIPAddresses(DBServer $DBServer)
		{
			$EC2Client = Scalr_Service_Cloud_Aws::newEc2(
				$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION), 
				$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::PRIVATE_KEY),
				$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::CERTIFICATE)
			);
	        
	        $iinfo = $EC2Client->DescribeInstances($DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID));
		    $iinfo = $iinfo->reservationSet->item->instancesSet->item;
		    
		    return array(
		    	'localIp'	=> $iinfo->privateIpAddress,
		    	'remoteIp'	=> $iinfo->ipAddress
		    );
		}
		
		public function GetServersList(Scalr_Environment $environment, $region, $skipCache = false)
		{
			if (!$this->instancesListCache[$environment->id][$region] || $skipCache)
			{
				$EC2Client = Scalr_Service_Cloud_Aws::newEc2(
					$region, 
					$environment->getPlatformConfigValue(self::PRIVATE_KEY),
					$environment->getPlatformConfigValue(self::CERTIFICATE)
				);
		        
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
						$this->instancesListCache[$environment->id][$region][(string)$results->item->instancesSet->item->instanceId] = (string)$results->item->instancesSet->item->instanceState->name;
					else
					{
						foreach ($results->item as $item)
							$this->instancesListCache[$environment->id][$region][(string)$item->instancesSet->item->instanceId] = (string)$item->instancesSet->item->instanceState->name;
					}
				}
			}
	        
			return $this->instancesListCache[$environment->id][$region];
		}
		
		public function GetServerRealStatus(DBServer $DBServer)
		{
			$region = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION);
			
			$iid = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID);
			if (!$iid)
			{
				$status = 'not-found';
			}
			elseif (!$this->instancesListCache[$DBServer->GetEnvironmentObject()->id][$region][$iid])
			{
		        $EC2Client = Scalr_Service_Cloud_Aws::newEc2(
					$region, 
					$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::PRIVATE_KEY),
					$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::CERTIFICATE)
				);
		        
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
				$status = $this->instancesListCache[$DBServer->GetEnvironmentObject()->id][$region][$DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID)];
			}
			
			return Modules_Platforms_Ec2_Adapters_Status::load($status);
		}
		
		public function TerminateServer(DBServer $DBServer)
		{
			$EC2Client = Scalr_Service_Cloud_Aws::newEc2(
				$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION), 
				$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::PRIVATE_KEY),
				$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::CERTIFICATE)
			);
	        
	        $EC2Client->TerminateInstances(array($DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID)));
	        
	        return true;
		}
		
		public function RebootServer(DBServer $DBServer)
		{
			$EC2Client = Scalr_Service_Cloud_Aws::newEc2(
				$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION), 
				$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::PRIVATE_KEY),
				$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::CERTIFICATE)
			);
	        
	        $EC2Client->RebootInstances(array($DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID)));
	        
	        return true;
		}
		
		public function RemoveServerSnapshot(DBRole $DBRole)
		{
			/*
			$EC2Client = Scalr_Service_Cloud_Aws::newEc2(
				"CLOUD_LOCATION", 
				$DBRole->getEnvironmentObject()->getPlatformConfigValue(self::PRIVATE_KEY),
				$DBRole->getEnvironmentObject()->getPlatformConfigValue(self::CERTIFICATE)
			);
			*/
        
	        //TODO:
			
			/*
	        $DescribeImagesType = new DescribeImagesType();
			$DescribeImagesType->imagesSet->item[] = array("imageId" => $DBRole->getImageId(SERVER_PLATFORMS::EC2));
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
    		    		$S3Client = new AmazonS3(
    		    			$DBRole->getEnvironmentObject()->getPlatformConfigValue(self::ACCESS_KEY), 
    		    			$DBRole->getEnvironmentObject()->getPlatformConfigValue(self::SECRET_KEY)
    		    		);
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
    			    		$EC2Client->DeregisterImage($DBRole->getImageId(SERVER_PLATFORMS::EC2));
    		    	}
		        }
	        }
	        */
		}
		
		public function CheckServerSnapshotStatus(BundleTask $BundleTask)
		{
			
		}
		
		public function CreateServerSnapshot(BundleTask $BundleTask)
		{
			$DBServer = DBServer::LoadByID($BundleTask->serverId);
			
			$EC2Client = Scalr_Service_Cloud_Aws::newEc2(
				$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION), 
				$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::PRIVATE_KEY),
				$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::CERTIFICATE)
			);
	        
	        if (!$BundleTask->prototypeRoleId)
	        {
	        	$proto_image_id = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::AMIID);
	        }
	        else
	        {
	        	$proto_image_id = DBRole::loadById($BundleTask->prototypeRoleId)->getImageId(
	        		SERVER_PLATFORMS::EC2, 
	        		$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)
	        	);
	        }	        
	        
	        $DescribeImagesType = new DescribeImagesType();
			$DescribeImagesType->imagesSet->item[] = array("imageId" => $proto_image_id);
	        $ami_info = $EC2Client->DescribeImages($DescribeImagesType);
	        
	        $platfrom = (string)$ami_info->imagesSet->item->platform;
	        
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
	        	$BundleTask->bundleType = (string)$ami_info->imagesSet->item->rootDeviceType == 'ebs' ?
	        			SERVER_SNAPSHOT_CREATION_TYPE::EC2_EBS :
	        			SERVER_SNAPSHOT_CREATION_TYPE::EC2_S3I;
	        	
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
	        
	        $BundleTask->setDate('started');
	        $BundleTask->Save();
		}
		
		private function ApplyAccessData(Scalr_Messaging_Msg $msg)
		{
			
			
		}
		
		public function GetServerConsoleOutput(DBServer $DBServer)
		{
			$EC2Client = Scalr_Service_Cloud_Aws::newEc2(
				$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION), 
				$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::PRIVATE_KEY),
				$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::CERTIFICATE)
			);
	        
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
				try {
		        	$EC2Client = Scalr_Service_Cloud_Aws::newEc2(
						$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION), 
						$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::PRIVATE_KEY),
						$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::CERTIFICATE)
					);
		        
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
			         */
			        
			        $monitoring = $iinfo->instancesSet->item->monitoring->state;
			        if ($monitoring == 'disabled')
			        {
			        	$monitoring = "Disabled
							&nbsp;(<a href='aws_ec2_cw_manage.php?action=Enable&server_id={$DBServer->serverId}'>Enable</a>)";
			        }
			        else 
			        {
			        	$monitoring = "<a href='/aws_cw_monitor.php?ObjectId=".$DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID)."&Object=InstanceId&NameSpace=AWS/EC2'>Enabled</a>
							&nbsp;(<a href='aws_ec2_cw_manage.php?action=Disable&server_id={$DBServer->serverId}'>Disable</a>)";
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
			        	'Monitoring (CloudWatch)'	=> $monitoring,
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
			$EC2Client = Scalr_Service_Cloud_Aws::newEc2(
				$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION), 
				$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::PRIVATE_KEY),
				$DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::CERTIFICATE)
			);
	        
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
	        $RunInstancesType->imageId = $DBRole->getImageId(
	        	SERVER_PLATFORMS::EC2, 
	        	$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)
	        );
	        
	        $akiId = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::AKIID);
	        if (!$akiId)
	        	$akiId = $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_AWS_AKI_ID);
	        		
	        if ($akiId)
	        	$RunInstancesType->kernelId = $akiId;
	        
	        $vpcPrivateIp = $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_AWS_VPC_PRIVATE_IP);
	        $vpcSubnetId = $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_AWS_VPC_SUBNET_ID);
	        if ($vpcPrivateIp && $vpcSubnetId)
	        {
	        	$RunInstancesType->subnetId = $vpcSubnetId;
	        	$RunInstancesType->privateIpAddress = $vpcPrivateIp;
	        }
	        else
	        {
	        	// Set Security groups
				foreach ($this->GetServerSecurityGroupsList($DBServer, $EC2Client) as $sgroup)
	        		$RunInstancesType->AddSecurityGroup($sgroup);
	        }
	        	        
	        $ariId = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::ARIID);
	        if (!$ariId)
	        	$ariId = $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_AWS_ARI_ID);
	        		
	        if ($ariId)
	        	$RunInstancesType->ramdiskId = $ariId;
	        	
	        $RunInstancesType->minCount = 1;
	        $RunInstancesType->maxCount = 1;
	        	
	        // Set availability zone
	        $avail_zone = $this->GetServerAvailZone($DBServer, $EC2Client);
	        if ($avail_zone)
	        	$RunInstancesType->SetAvailabilityZone($avail_zone);
	        
	        $i_type = $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_AWS_INSTANCE_TYPE);
	        if (!$i_type)
	        {
	        	$DBRole = DBRole::loadById($DBServer->roleId);
	        	$i_type = $DBRole->getProperty(EC2_SERVER_PROPERTIES::INSTANCE_TYPE);
	        }
	        
	        // Set instance type
	        $RunInstancesType->instanceType = $i_type;
	        
	        // Set additional info
	       	$RunInstancesType->additionalInfo = "";
	       	
	       	
	        $RunInstancesType->keyName = Scalr_Model::init(Scalr_Model::SSH_KEY)->loadGlobalByFarmId(
	        	$DBServer->farmId, 
	        	$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)
	        )->cloudKeyName;
	        
	        foreach ($DBServer->GetCloudUserData() as $k=>$v)
	        	$u_data .= "{$k}={$v};";
	        	
	        $RunInstancesType->SetUserData(trim($u_data, ";"));
	        
			$result = $EC2Client->RunInstances($RunInstancesType);
	        
	        if ($result->instancesSet)
	        {
	        	$DBServer->SetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE, (string)$result->instancesSet->item->placement->availabilityZone);
	        	$DBServer->SetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID, (string)$result->instancesSet->item->instanceId);
	        	$DBServer->SetProperty(EC2_SERVER_PROPERTIES::INSTANCE_TYPE, $RunInstancesType->instanceType);
	        	$DBServer->SetProperty(EC2_SERVER_PROPERTIES::AMIID, $RunInstancesType->imageId);
	        	
	        	try
	        	{
	        		$CreateTagsType = new CreateTagsType(
	        			array((string)$result->instancesSet->item->instanceId),
	        			array(
	        				"scalr-farm-id"			=> $DBServer->farmId,
	        				"scalr-farm-name"		=> $DBServer->GetFarmObject()->Name,
	        				"scalr-farm-role-id"	=> $DBServer->farmRoleId,
	        				"scalr-role-name"		=> $DBServer->GetFarmRoleObject()->GetRoleObject()->name,
	        				"scalr-server-id"		=> $DBServer->serverId
	        			)
	        		);
	        		
	        		$EC2Client->CreateTags($CreateTagsType);
	        	}
	        	catch(Exception $e){ }
	        	
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
		        	$aws_sgroups[strtolower($sg->groupName)] = $sg;
		        	
		        unset($aws_sgroups_list_t);
			}
			catch(Exception $e) {
				throw new Exception("GetServerSecurityGroupsList failed: {$e->getMessage()}");
			}
			
			// Add Role security group
			$role_sec_group = CONFIG::$SECGROUP_PREFIX.$DBServer->GetFarmRoleObject()->GetRoleObject()->name;
			$partent_sec_group = CONFIG::$SECGROUP_PREFIX.$DBServer->GetFarmRoleObject()->GetRoleObject()->getRoleHistory();
			array_push($retval, $role_sec_group);
			
			if (!$aws_sgroups[strtolower($role_sec_group)])
			{
				try {
					$EC2Client->CreateSecurityGroup($role_sec_group, $DBServer->GetFarmRoleObject()->GetRoleObject()->name);
				}
				catch(Exception $e) {
					throw new Exception("GetServerSecurityGroupsList failed: {$e->getMessage()}");
				}
					                        
		    	$IpPermissionSet = new IpPermissionSetType();
				
		    	$group_rules = $DBServer->GetFarmRoleObject()->GetRoleObject()->getSecurityRules();
		    	
		    	//
				// Check parent security group
				//
				if ($partent_sec_group && $aws_sgroups[$partent_sec_group] && count($group_rules) == 0)
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
	            $EC2Client->AuthorizeSecurityGroupIngress($DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::ACCOUNT_ID), $role_sec_group, $IpPermissionSet);	
			}
			
			
			// Add MySQL Security group
			if ($DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL))
			{
				array_push($retval, CONFIG::$MYSQL_STAT_SEC_GROUP);
				if (!$aws_sgroups[CONFIG::$MYSQL_STAT_SEC_GROUP])
				{
					try {
						$EC2Client->CreateSecurityGroup(strtolower(CONFIG::$MYSQL_STAT_SEC_GROUP), "Security group for access to mysql replication status from Scalr app");
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
		            $EC2Client->AuthorizeSecurityGroupIngress($DBServer->GetEnvironmentObject()->getPlatformConfigValue(self::ACCOUNT_ID), CONFIG::$MYSQL_STAT_SEC_GROUP, $IpPermissionSet);
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
			    	return false;
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
			$put |= $message instanceof Scalr_Messaging_Msg_HostInitResponse && $DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL);
			$put |= $message instanceof Scalr_Messaging_Msg_Mysql_PromoteToMaster;
			$put |= $message instanceof Scalr_Messaging_Msg_Mysql_CreateDataBundle;
			$put |= $message instanceof Scalr_Messaging_Msg_Mysql_CreateBackup;
			
			
			if ($put) {
				$environment = $DBServer->GetEnvironmentObject();
	        	$accessData = new stdClass();
	        	$accessData->accountId = $environment->getPlatformConfigValue(self::ACCOUNT_ID);
	        	$accessData->keyId = $environment->getPlatformConfigValue(self::ACCESS_KEY);
	        	$accessData->key = $environment->getPlatformConfigValue(self::SECRET_KEY);
	        	$accessData->cert = $environment->getPlatformConfigValue(self::CERTIFICATE);
	        	$accessData->pk = $environment->getPlatformConfigValue(self::PRIVATE_KEY);
	        	
	        	$message->platformAccessData = $accessData;
			}
		}
		
		public function ClearCache ()
		{
			$this->instancesListCache = array();
		}
	}

	
	
?>