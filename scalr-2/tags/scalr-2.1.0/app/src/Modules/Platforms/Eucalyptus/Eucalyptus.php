<?php

	class Modules_Platforms_Eucalyptus implements IPlatformModule
	{
		private $db;
		
		/** Properties **/
		const ACCOUNT_ID 		= 'eucalyptus.account_id';
		const ACCESS_KEY		= 'eucalyptus.access_key';
		const SECRET_KEY		= 'eucalyptus.secret_key';
		const PRIVATE_KEY		= 'eucalyptus.private_key';
		const CERTIFICATE		= 'eucalyptus.certificate';
		const CLOUD_CERTIFICATE = 'eucalyptus.cloud_certificate';
		const EC2_URL			= 'eucalyptus.ec2_url';
		const S3_URL			= 'eucalyptus.s3_url';
		
		/**
		 * 
		 * @var AmazonEC2
		 */
		private $instancesListCache = array();
		
		public function getPropsList()
		{
			return array(
				self::ACCOUNT_ID		=> 'Account ID',
				self::ACCESS_KEY		=> 'Access Key',
				self::SECRET_KEY		=> 'Secret Key',
				self::CERTIFICATE		=> 'x.509 Certificate',
				self::PRIVATE_KEY		=> 'x.509 Private Key',
				self::EC2_URL  			=> 'EC2 URL (eg. http://192.168.1.1:8773/services/Eucalyptus)',
				self::S3_URL 			=> 'S3 URL (eg. http://192.168.1.1:8773/services/Walrus)',
				self::CLOUD_CERTIFICATE => 'Cloud Certificate' 
			);
		}
		
		public function getLocations()
		{
			return array(
				'euca-default' => 'Eucalyptus / Default'
			);
		}
		
		public function __construct()
		{
			$this->db = Core::GetDBInstance();
		}	
		
		public function GetServerCloudLocation(DBServer $DBServer)
		{
			return $DBServer->GetProperty(EUCA_SERVER_PROPERTIES::REGION);
		}
		
		public function GetServerID(DBServer $DBServer)
		{
			return $DBServer->GetProperty(EUCA_SERVER_PROPERTIES::INSTANCE_ID);
		}
		
		public function IsServerExists(DBServer $DBServer, $debug = false)
		{
			return in_array(
				$DBServer->GetProperty(EUCA_SERVER_PROPERTIES::INSTANCE_ID), 
				array_keys($this->GetServersList($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(EUCA_SERVER_PROPERTIES::REGION)))
			);
		}
		
		/**
		 * @return Scalr_Service_Cloud_Eucalyptus_Client
		 * Enter description here ...
		 */
		private function getEucaClient(Scalr_Environment $environment)
		{
			return Scalr_Service_Cloud_Eucalyptus::newCloud(
				$environment->getPlatformConfigValue(self::SECRET_KEY),
				$environment->getPlatformConfigValue(self::ACCESS_KEY),
				$environment->getPlatformConfigValue(self::EC2_URL)
			);
		}
		
		public function GetServerIPAddresses(DBServer $DBServer)
		{
			$eucaClient = $this->getEucaClient($DBServer->GetEnvironmentObject());
	        
	        $iinfo = $eucaClient->DescribeInstances($DBServer->GetProperty(EUCA_SERVER_PROPERTIES::INSTANCE_ID));
		    $iinfo = $iinfo->reservationSet->item->instancesSet->item;
		    
		    return array(
		    	'localIp'	=> $iinfo->privateDnsName,
		    	'remoteIp'	=> $iinfo->dnsName
		    );
		}
		
		public function GetServersList(Scalr_Environment $environment, $region, $skipCache = false)
		{
			if (!$this->instancesListCache[$environment->id][$region] || $skipCache)
			{
				$eucaClient = $this->getEucaClient($environment);
		        
		        try
				{
		            $results = $eucaClient->describeInstances();
		            $results = $results->reservationSet;
				}
				catch(Exception $e)
				{
					throw new Exception(sprintf("Cannot get list of servers for platfrom eucalyptus: %s", $e->getMessage()));
				}
				
				if ($results->item)
				{					
					foreach ($results->item as $item)
						$this->instancesListCache[$environment->id][$region][(string)$item->instancesSet->item[0]->instanceId] = (string)$item->instancesSet->item[0]->instanceState->name;
				}
			}
			
			return $this->instancesListCache[$environment->id][$region];
		}
		
		public function GetServerRealStatus(DBServer $DBServer)
		{
			$region = $DBServer->GetProperty(EUCA_SERVER_PROPERTIES::REGION);
			
			$environment = $DBServer->GetEnvironmentObject();
			
			$iid = $DBServer->GetProperty(EUCA_SERVER_PROPERTIES::INSTANCE_ID);
			if (!$iid)
			{
				$status = 'not-found';
			}
			elseif (!$this->instancesListCache[$environment->id][$region][$iid])
			{
		       	$eucaClient = $this->getEucaClient($environment);
		        
		        try {
		        	$iinfo = $eucaClient->describeInstances(array($iid));
			        $iinfo = $iinfo->reservationSet->item[0];
			        
			        if ($iinfo)
			        	$status = (string)$iinfo->instancesSet->item[0]->instanceState->name;
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
				$status = $this->instancesListCache[$environment->id][$region][$DBServer->GetProperty(EUCA_SERVER_PROPERTIES::INSTANCE_ID)];
			}
			
			return Modules_Platforms_Eucalyptus_Adapters_Status::load($status);
		}
		
		public function TerminateServer(DBServer $DBServer)
		{
			$eucaClient = $this->getEucaClient($DBServer->GetEnvironmentObject());
			
	        $eucaClient->terminateInstances(array($DBServer->GetProperty(EUCA_SERVER_PROPERTIES::INSTANCE_ID)));
	        
	        return true;
		}
		
		public function RebootServer(DBServer $DBServer)
		{
			$eucaClient = $this->getEucaClient($DBServer->GetEnvironmentObject());
	        $eucaClient->rebootInstances(array($DBServer->GetProperty(EUCA_SERVER_PROPERTIES::INSTANCE_ID)));
	        
	        return true;
		}
		
		public function RemoveServerSnapshot(DBRole $DBRole)
		{
			//TODO:
			
			/*
			$eucaClient = $this->getEucaClient($DBRole->getEnvironmentObject());
        
	        $ami_info = $eucaClient->describeImages(null, $DBRole->getImageId(SERVER_PLATFORMS::EUCALYPTUS), null);
	        $ami_info = $ami_info->imagesSet->item[0];
	        
	        $platfrom = $ami_info->platform;
	        $rootDeviceType = $ami_info->rootDeviceType;
	        
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
    		    	$image_path = (string)$ami_info->imageLocation;
    		    	
    		    	//TODO: Remove image	
    		    			    			    	
    		    	if ($ami_info)
    			    	$eucaClient->deregisterImage($DBRole->getImageId(SERVER_PLATFORMS::EUCALYPTUS));
		        }
	        }
	        */
		}
		
		public function CheckServerSnapshotStatus(BundleTask $BundleTask)
		{
			//	NOT SUPPORTED
		}
		
		public function CreateServerSnapshot(BundleTask $BundleTask)
		{
			$DBServer = DBServer::LoadByID($BundleTask->serverId);
			
			$eucaClient = $this->getEucaClient($DBServer->GetEnvironmentObject());	
	        
	        if (!$BundleTask->prototypeRoleId)
	        {
	        	$proto_image_id = $DBServer->GetProperty(EUCA_SERVER_PROPERTIES::EMIID);
	        }
	        else
	        {
	        	$proto_image_id = DBROle::loadById($BundleTask->prototypeRoleId)->getImageId(
	        		SERVER_PLATFORMS::EUCALYPTUS, 
	        		$DBServer->GetProperty(EUCA_SERVER_PROPERTIES::REGION)
	        	);
	        }	        
	        
	       	$ami_info = $eucaClient->describeImages(null, $proto_image_id, null);
	        $ami_info = $ami_info->imagesSet->item[0];
	        
	        $platfrom = $ami_info->platform;
	        $rootDeviceType = $ami_info->rootDeviceType;
	        
	        if ($rootDeviceType == 'ebs')
	        {
	        	$BundleTask->bundleType = SERVER_SNAPSHOT_CREATION_TYPE::EUCA_EBS;
	        	
	        	$BundleTask->Log(sprintf(_("Selected platfrom snapshoting type: %s"), $BundleTask->bundleType));
		        	
	        	$BundleTask->SnapshotCreationFailed("Not supported yet");
	        	return;
	        	
	        }
	        else
	        {
		        if ($platfrom == 'windows')
		        {
        	
		        	//TODO: Windows platfrom is not supported yet.
		        	
		        	$BundleTask->bundleType = SERVER_SNAPSHOT_CREATION_TYPE::EUCA_WIN;
		        	
		        	$BundleTask->Log(sprintf(_("Selected platfrom snapshoting type: %s"), $BundleTask->bundleType));
		        	
		        	$BundleTask->SnapshotCreationFailed("Not supported yet");
		        	return;
		        }
		        else
		        {
		        	$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::IN_PROGRESS;
		        	$BundleTask->bundleType = SERVER_SNAPSHOT_CREATION_TYPE::EUCA_WSI;
		        	
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
			$eucaClient = $this->getEucaClient($DBServer->GetEnvironmentObject());
	        return $eucaClient->getConsoleOutput($DBServer->GetProperty(EUCA_SERVER_PROPERTIES::INSTANCE_ID));
		}
		
		public function GetServerExtendedInformation(DBServer $DBServer)
		{
			try
			{	
				try {
		        	$eucaClient = $this->getEucaClient($DBServer->GetEnvironmentObject());
		        
		        	$iinfo = $eucaClient->describeInstances(array($DBServer->GetProperty(EUCA_SERVER_PROPERTIES::INSTANCE_ID)));
		        	$iinfo = $iinfo->reservationSet->item[0];
				}
				catch(Exception $e) {}
		        
		        if ($iinfo)
		        {
			        $groups = array();
		        	foreach ($iinfo->groupSet->item as $item)
		        		$groups[] = $item->groupId;
			        
			        return array(
			        	'Instance ID'			=> $DBServer->GetProperty(EUCA_SERVER_PROPERTIES::INSTANCE_ID),
			        	'Owner ID'				=> $iinfo->ownerId,
			        	'Image ID (EMI)'		=> $iinfo->instancesSet->item[0]->imageId,
			        	'Private DNS name'		=> $iinfo->instancesSet->item[0]->privateDnsName,
			        	'Public DNS name'		=> $iinfo->instancesSet->item[0]->dnsName,
			        	'Key name'				=> $iinfo->instancesSet->item[0]->keyName,
			        	'Instance type'			=> $iinfo->instancesSet->item[0]->instanceType,
			        	'Launch time'			=> $iinfo->instancesSet->item[0]->launchTime,
			        	'Instance state'		=> $iinfo->instancesSet->item[0]->instanceState->name." ({$iinfo->instancesSet->item[0]->instanceState->code})",
			        	'Placement'				=> $iinfo->instancesSet->item[0]->placement->availabilityZone,
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
			$DBRole = DBRole::loadById($DBServer->roleId);
			
	        $eucaClient = $this->getEucaClient($DBServer->GetEnvironmentObject());
	        
	        $i_type = $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_EUCA_INSTANCE_TYPE);
	        
	        foreach ($DBServer->GetCloudUserData() as $k=>$v)
	        	$u_data .= "{$k}={$v};";
	        	
	        /*
	         $imageId, $instanceType, $keyName = null, $availZone = null, $securityGroup = array(), $userData = "", 
			 $minCount = 1, $maxCount = 1, $kernelId = null, $ramdiskId = null, $monitoring = false
	         */
	       
	        $DBServer->SetProperty(SERVER_PROPERTIES::ARCHITECTURE, $DBRole->architecture);
	        
	        $key_pair_name = Scalr_Model::init(Scalr_Model::SSH_KEY)->loadGlobalByFarmId(
	        	$DBServer->farmId, 
	        	$DBServer->GetProperty(EUCA_SERVER_PROPERTIES::REGION)
	        )->cloudKeyName;
	        
	        $result = $eucaClient->runInstances(
	        	$DBRole->getImageId(SERVER_PLATFORMS::EUCALYPTUS, $DBServer->GetProperty(EUCA_SERVER_PROPERTIES::REGION)),
	        	$i_type,
	        	$key_pair_name,
	        	$this->GetServerAvailZone($DBServer),
	        	$this->GetServerSecurityGroupsList($DBServer, $eucaClient),
	        	trim($u_data, ";")
	        );
	        
	        if ($result->instancesSet)
	        {
	        	$DBServer->SetProperty(EUCA_SERVER_PROPERTIES::AVAIL_ZONE, (string)$result->instancesSet->item[0]->placement->availabilityZone);
	        	$DBServer->SetProperty(EUCA_SERVER_PROPERTIES::INSTANCE_ID, (string)$result->instancesSet->item[0]->instanceId);
	        	$DBServer->SetProperty(EUCA_SERVER_PROPERTIES::INSTANCE_TYPE, $i_type);
	        	$DBServer->SetProperty(EUCA_SERVER_PROPERTIES::EMIID, $DBRole->getImageId(SERVER_PLATFORMS::EUCALYPTUS, $DBServer->GetProperty(EUCA_SERVER_PROPERTIES::REGION)));
	        	
		        return $DBServer;
	        }
		}
		
		/*********************************************************************/
		/*********************************************************************/
		/*********************************************************************/
		/*********************************************************************/
		/*********************************************************************/
		
		private function GetServerSecurityGroupsList(DBServer $DBServer, $eucaCl)
		{
			// Add default security group
			$retval = array('default');
	         
			return $retval;
		}
		
		private function GetServerAvailZone(DBServer $DBServer)
		{
			return $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_EUCA_AVAIL_ZONE);
		}
		
		public function PutAccessData(DBServer $DBServer, Scalr_Messaging_Msg $message)
		{
			$put = false;
			$put |= $message instanceof Scalr_Messaging_Msg_Rebundle;
			$put |= $message instanceof Scalr_Messaging_Msg_HostInitResponse && $DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL);
			$put |= $message instanceof Scalr_Messaging_Msg_Mysql_PromoteToMaster;
			$put |= $message instanceof Scalr_Messaging_Msg_Mysql_CreateDataBundle;
			$put |= $message instanceof Scalr_Messaging_Msg_Mysql_CreateBackup;
			
			$environment = $DBServer->GetEnvironmentObject();
			
			if ($put) {
	        	$accessData = new stdClass();
	        	$accessData->accountId = $environment->getPlatformConfigValue(self::ACCOUNT_ID);
	        	$accessData->keyId = $environment->getPlatformConfigValue(self::ACCESS_KEY);
	        	$accessData->key = $environment->getPlatformConfigValue(self::SECRET_KEY);
	        	$accessData->cert = $environment->getPlatformConfigValue(self::CERTIFICATE);
	        	$accessData->pk = $environment->getPlatformConfigValue(self::PRIVATE_KEY);
	        	$accessData->ec2_url = $environment->getPlatformConfigValue(self::EC2_URL);
	        	$accessData->s3_url = $environment->getPlatformConfigValue(self::S3_URL);
	        	$accessData->cloud_cert = $environment->getPlatformConfigValue(self::CLOUD_CERTIFICATE);
	        	
	        	$message->platformAccessData = $accessData;
			}
		}
		
		public function ClearCache ()
		{
			$this->instancesListCache = array();
		}
	}

	
	
?>