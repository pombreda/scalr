<?php
	class Modules_Platforms_Rds implements IPlatformModule
	{
		private $db;
		
		/**
		 * 
		 * @var AmazonRDS
		 */
		private $instancesListCache;
		
		public function __construct()
		{
			$this->db = Core::GetDBInstance();
		}	
		
		public function GetServerID(DBServer $DBServer)
		{
			return $DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID);
		}
		
		public function IsServerExists(DBServer $DBServer)
		{
			return in_array(
				$DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID), 
				array_keys($this->GetServersList($DBServer->GetClient(), $DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)))
			);
		}
		
		public function GetServerIPAddresses(DBServer $DBServer)
		{
			$Client = $DBServer->GetClient();
			
			$RDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
		    $RDSClient->SetRegion($DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION));
	        
	        
	        $iinfo = $RDSClient->DescribeDBInstances($DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID));
	        $iinfo = $iinfo->DescribeDBInstancesResult->DBInstances->DBInstance;
	        
	        $hostname = (string)$iinfo->Endpoint->Address;
	        
		    $ip = @gethostbyname($hostname);
		    if ($ip != $hostname)
		    {
			    return array(
			    	'localIp'	=> $ip,
			    	'remoteIp'	=> $ip
			    );
		    }
		}
		
		private function GetServersList(Client $Client, $region, $skipCache = false)
		{
			if (!isset($this->instancesListCache[$Client->AWSAccountID][$region]))
			{
				$RDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
		        $RDSClient->SetRegion($region);
				
		        try
				{
		            $results = $RDSClient->DescribeDBInstances();
		            $results = $results->DescribeDBInstancesResult;
				}
				catch(Exception $e)
				{
					throw new Exception(sprintf("Cannot get list of servers for platfrom rds: %s", $e->getMessage()));
				}
			
				if ($results->DBInstances)
	            	foreach ($results->DBInstances->children() as $item)
	                	$this->instancesListCache[$Client->AWSAccountID][$region][(string)$item->DBInstanceIdentifier] = (string)$item->DBInstanceStatus;
			}
			
			return $this->instancesListCache[$Client->AWSAccountID][$region];
		}
		
		public function GetServerRealStatus(DBServer $DBServer)
		{
			$Client = $DBServer->GetClient();

			$region = $DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION);
			
			if (!$this->instancesListCache[$Client->AWSAccountID][$region][$DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID)])
			{
		        try {
					$RDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
			        $RDSClient->SetRegion($region);
			        
			        $iinfo = $RDSClient->DescribeDBInstances($DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID));
			        $iinfo = $iinfo->DescribeDBInstancesResult->DBInstances->DBInstance;
			        $status = (string)$iinfo->DBInstanceStatus;
		        }
		        catch(Exception $e)
		        {
		        	if (stristr($e->getMessage(), "not found"))
		        		$status = 'not-found';
		        	else
		        		throw $e;
		        }
			}
			else
			{
				$status = $this->instancesListCache[$Client->AWSAccountID][$region][$DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID)];
			}
			
	        return Modules_Platforms_Rds_Adapters_Status::load($status);
		}
		
		public function TerminateServer(DBServer $DBServer)
		{
			$Client = $DBServer->GetClient();
			
	        $RDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
	        $RDSClient->SetRegion($DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION));     

	        //TODO: Snapshot
	        $RDSClient->DeleteDBInstance($DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID));
	        
	        return true;
		}
		
		public function RebootServer(DBServer $DBServer)
		{
			$Client = $DBServer->GetClient();
			
	        $RDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
	        $RDSClient->SetRegion($DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)); 
	        
	        $RDSClient->RebootDBInstance($DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID));
	        
	        return true;
		}
		
		public function RemoveServerSnapshot(DBRole $DBRole)
		{
			$Client = Client::Load($DBRole->clientId);
			
			$RDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
			$RDSClient->SetRegion($DBRole->region);
			
			$RDSClient->DeleteDBSnapshot($DBRole->imageId);
		}
		
		public function CheckServerSnapshotStatus(BundleTask $BundleTask)
		{
			$DBServer = DBServer::LoadByID($BundleTask->serverId);
			
			$Client = $DBServer->GetClient();
			
	        $RDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
			$RDSClient->SetRegion($DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION));
			
			try
			{
				$info = $RDSClient->DescribeDBSnapshots(null, $BundleTask->snapshotId);
				$info = $info->DescribeDBSnapshotsResult->DBSnapshots->DBSnapshot;
				
				if ($info->Status == 'available')
				{
					$BundleTask->SnapshotCreationComplete($BundleTask->snapshotId);
				}
				elseif ($info->Status == 'creating')
				{
					return;
				}
				else
				{
					Logger::getLogger(__CLASS__)->error("CheckServerSnapshotStatus ({$BundleTask->id}) status = {$info->Status}");
				}
			}
			catch(Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal("CheckServerSnapshotStatus ({$BundleTask->id}): {$e->getMessage()}");
			}
		}
		
		public function CreateServerSnapshot(BundleTask $BundleTask)
		{
			$DBServer = DBServer::LoadByID($BundleTask->serverId);
			
			$Client = $DBServer->GetClient();
			
	        $RDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
			$RDSClient->SetRegion($DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)); 
	        
	        try
	        {
	        	$RDSClient->CreateDBSnapshot($BundleTask->roleName, $DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID));
	        	
	        	$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::IN_PROGRESS;
	        	$BundleTask->bundleType = SERVER_SNAPSHOT_CREATION_TYPE::RDS_SPT;
	        	$BundleTask->snapshotId = $BundleTask->roleName;
	        	
	        	$BundleTask->Log(sprintf(_("Snapshot creation initialized. SnapshotID: %s"), $BundleTask->snapshotId));
		        
		        $BundleTask->setDate('started');
		        
		        $BundleTask->Save();
	        }
	        catch(Exception $e)
	        {
	        	$BundleTask->SnapshotCreationFailed($e->getMessage());
	        }
		}
		
		public function GetServerConsoleOutput(DBServer $DBServer)
		{
			throw new Exception("Not supported by RDS platform module");
		}
		
		public function GetServerExtendedInformation(DBServer $DBServer)
		{
			try
			{
				$Client = $DBServer->GetClient();
				
		        $RDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
		        $RDSClient->SetRegion($DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)); 
		        
		        $iinfo = $RDSClient->DescribeDBInstances($DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID));		        
		        $iinfo = $iinfo->DescribeDBInstancesResult->DBInstances->DBInstance;
		        
		        if ($iinfo)
		        {
		        	$groups = array();
			        if ($iinfo->DBParameterGroups->DBParameterGroup->DBParameterGroupName)
			        	$groups[] = $iinfo->DBParameterGroups->DBParameterGroup->DBParameterGroupName;
			        else
			        {
			        	foreach ($iinfo->DBParameterGroups->DBParameterGroup as $item)
			        		$groups[] = $item->DBParameterGroupName;
			        }

		        	$sgroups = array();
			        if ($iinfo->DBSecurityGroups->DBSecurityGroup->DBParameterGroupName)
			        	$sgroups[] = $iinfo->DBSecurityGroups->DBSecurityGroup->DBSecurityGroupName;
			        else
			        {
			        	foreach ($iinfo->DBSecurityGroups->DBSecurityGroup as $item)
			        		$sgroups[] = $item->DBSecurityGroupName;
			        }
			        
			        return array(
			        	'Instance ID'			=> $DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID),
			        	'Engine'				=> $iinfo->Engine,
			        	'Image ID (Snapshot)'	=> $DBServer->GetProperty(RDS_SERVER_PROPERTIES::SNAPSHOT_ID),
			        	'Backup Retention Period'	=> $iinfo->BackupRetentionPeriod,
			        	'Status'				=> $iinfo->DBInstanceStatus,
			        	'Preferred Backup Window' => $iinfo->PreferredBackupWindow,
			        	'Preferred Maintenance Window'	=> $iinfo->PreferredMaintenanceWindow,
			        	'Availability Zone'		=> $iinfo->AvailabilityZone,
			        	'Allocated Storage'		=> $iinfo->AllocatedStorage,
			        	'Instance Class'		=> $iinfo->DBInstanceClass,
			        	'Master Username'		=> $iinfo->MasterUsername,
			        	'Port'					=> $iinfo->Endpoint->Port,
			        	'Hostname'				=> $iinfo->Endpoint->Address,
			        	'Create Time'			=> $iinfo->InstanceCreateTime,
			        	'Parameter groups'		=> implode(", ", $groups),
			        	'Security groups'		=> implode(", ", $sgroups)
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
			
	        $RDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
	        $RDSClient->SetRegion($DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)); 
	        
	        $DBRole = DBRole::loadById($DBServer->roleId);
	        
	        $server_id = "scalr-{$DBServer->serverId}";
	        
	        $avail_zone = $DBServer->GetProperty(RDS_SERVER_PROPERTIES::AVAIL_ZONE) ? $DBServer->GetProperty(RDS_SERVER_PROPERTIES::AVAIL_ZONE) : $DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)."a";
	        
	        try
	        {
		        if ($DBRole->imageId == 'ScalrEmpty')
		        {
			        $RDSClient->CreateDBInstance(
			        	$server_id,
			        	$DBServer->GetProperty(RDS_SERVER_PROPERTIES::STORAGE),
			        	$DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_CLASS),
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ENGINE),				
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::MASTER_USER),
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::MASTER_PASS),
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::PORT),
						null, //DBName
						null, //DBParameterGroup
						null, //$DBSecurityGroups
						$avail_zone,
						null, //$PreferredMaintenanceWindow  = null,
						null, //$BackupRetentionPeriod	= null ,
						null //$PreferredBackupWindow	= null
			        );
		        }
		        else
		        {
		        	$RDSClient->RestoreDBInstanceFromDBSnapshot(
		        		$DBRole->imageId,
		        		$server_id,
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_CLASS),
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::PORT),
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::AVAIL_ZONE)
		        	);
		        }
		        
		        $DBServer->SetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID, $server_id);
		        $DBServer->SetProperty(RDS_SERVER_PROPERTIES::SNAPSHOT_ID, $DBRole->imageId);
		        return $DBServer;
	        }
	        catch(Exception $e)
	        {
	        	throw new Exception(sprintf(_("Cannot launch new instance. %s"), $e->getMessage()));
	        }
		}
		
		public function PutAccessData(DBServer $DBServer, Scalr_Messaging_Msg $message)
		{
		}
	}

?>