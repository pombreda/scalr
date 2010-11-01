<?php
	class Modules_Platforms_Rackspace implements IPlatformModule
	{
		private $db;
		
		/** Properties **/
		const USERNAME 		= 'rackspace.username';
		const API_KEY		= 'rackspace.api_key';
		
		private $instancesListCache = array();
		
		/**
		 * @return Scalr_Service_Cloud_Rackspace_CS
		 */
		private function getRsClient(Scalr_Environment $environment)
		{
			return Scalr_Service_Cloud_Rackspace::newRackspaceCS(
				$environment->getPlatformConfigValue(self::USERNAME),
				$environment->getPlatformConfigValue(self::API_KEY)
			);
		}
		
		public function __construct()
		{
			$this->db = Core::GetDBInstance();
		}
		
		public function getLocations()
		{
			return array(
				'rs-ORD1' => 'Rackspace / ORD1'
			);
		}
		
		public function getPropsList()
		{
			return array(
				self::USERNAME	=> 'Username',
				self::API_KEY	=> 'API Key',
			);
		}
		
		public function GetServerCloudLocation(DBServer $DBServer)
		{
			return $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER);
		}
		
		public function GetServerID(DBServer $DBServer)
		{
			return $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID);
		}
		
		public function IsServerExists(DBServer $DBServer, $debug = false)
		{
			return in_array(
				$DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID), 
				array_keys($this->GetServersList($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER)))
			);
		}
		
		public function GetServerIPAddresses(DBServer $DBServer)
		{
			$rsClient = $this->getRsClient($DBServer->GetEnvironmentObject());
			
			$result = $rsClient->getServerDetails($DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID));
		    
		    return array(
		    	'localIp'	=> $result->server->addresses->private[0],
		    	'remoteIp'	=> $result->server->addresses->public[0]
		    );
		}
		
		public function GetServersList(Scalr_Environment $environment, $cloudLocation, $skipCache = false)
		{
			if (!$this->instancesListCache[$environment->id][$cloudLocation] || $skipCache)
			{
				$rsClient = $this->getRsClient($environment);
				
				$result = $rsClient->listServers(true);
				foreach ($result->servers as $server)
					$this->instancesListCache[$environment->id][$cloudLocation][$server->id] = $server->status;
			}
	        
			return $this->instancesListCache[$environment->id][$cloudLocation];
		}
		
		public function GetServerRealStatus(DBServer $DBServer)
		{
			$cloudLocation = $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER);
			$environment = $DBServer->GetEnvironmentObject();
			
			$iid = $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID);
			if (!$iid)
			{
				$status = 'not-found';
			}
			elseif (!$this->instancesListCache[$environment->id][$cloudLocation][$iid])
			{
		        $rsClient = $this->getRsClient($environment);
				
		        try {
					$result = $rsClient->getServerDetails($DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID));
					$status = $result->server->status;
		        }
		        catch(Exception $e)
		        {
		        	if (stristr($e->getMessage(), "404"))
		        		$status = 'not-found';
		        }
			}
			else
			{
				$status = $this->instancesListCache[$environment->id][$cloudLocation][$DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID)];
			}
			
			return Modules_Platforms_Rackspace_Adapters_Status::load($status);
		}
		
		public function TerminateServer(DBServer $DBServer)
		{
			$rsClient = $this->getRsClient($DBServer->GetEnvironmentObject());
	        
	        $rsClient->deleteServer($DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID));
	        
	        return true;
		}
		
		public function RebootServer(DBServer $DBServer)
		{
			$rsClient = $this->getRsClient($DBServer->GetEnvironmentObject());
	        
	        $rsClient->rebootServer($DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID));
	        
	        return true;
		}
		
		public function RemoveServerSnapshot(DBRole $DBRole)
		{
			
		}
		
		public function CheckServerSnapshotStatus(BundleTask $BundleTask)
		{
			
	        
		}
		
		public function CreateServerSnapshot(BundleTask $BundleTask)
		{
			
		}
		
		private function ApplyAccessData(Scalr_Messaging_Msg $msg)
		{
			
			
		}
		
		public function GetServerConsoleOutput(DBServer $DBServer)
		{
			throw new Exception("Not supported by Rackspace");
		}
		
		public function GetServerExtendedInformation(DBServer $DBServer)
		{
			try
			{
				try	{
					$rsClient = $this->getRsClient($DBServer->GetEnvironmentObject());
					$iinfo = $rsClient->getServerDetails($DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID));
				}
				catch(Exception $e){}
	
		        if ($iinfo)
		        {
			        return array(
			        	'Server ID'				=> $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID),
			        	'Image ID'				=> $iinfo->server->imageId,
			        	'Flavor ID'				=> $iinfo->server->flavorId,
			        	'Status'				=> $iinfo->server->status,
			        	'Name'					=> $iinfo->server->name,
			        	'Host ID'				=> $iinfo->server->hostId,
			        	'Progress'				=> $iinfo->server->progress,
			        	'Public IP'				=> implode(", ", $iinfo->server->addresses->public),
			        	'Private IP'			=> implode(", ", $iinfo->server->addresses->private)
			        );
		        }
			}
			catch(Excpetion $e){}
			
			return false;
		}
		
		public function LaunchServer(DBServer $DBServer)
		{
			$rsClient = $this->getRsClient($DBServer->GetEnvironmentObject());
	        
			$DBRole = DBRole::loadById($DBServer->roleId);
			
			foreach ($DBServer->GetCloudUserData() as $k=>$v)
	        	$u_data .= "{$k}={$v};";
			
			$result = $rsClient->createServer(
				$DBServer->serverId,
				$DBRole->getImageId(SERVER_PLATFORMS::RACKSPACE, $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER)),
				$DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_RS_FLAVOR_ID),
				array(),
				array(
					'path'		=> '/etc/scalr/private.d/.user-data',
					'contents'	=> base64_encode(trim($u_data, ";"))
				)
			);
	        
	        if ($result->server)
	        {
	        	$DBServer->SetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID, $result->server->id);
	        	$DBServer->SetProperty(RACKSPACE_SERVER_PROPERTIES::IMAGE_ID, $result->server->imageId);
	        	$DBServer->SetProperty(RACKSPACE_SERVER_PROPERTIES::FLAVOR_ID, $result->server->flavorId);
	        	$DBServer->SetProperty(RACKSPACE_SERVER_PROPERTIES::ADMIN_PASS, $result->server->adminPass);
	        	$DBServer->SetProperty(RACKSPACE_SERVER_PROPERTIES::NAME, $DBServer->serverId);
	        	$DBServer->SetProperty(RACKSPACE_SERVER_PROPERTIES::HOST_ID, $result->server->hostId);
	        	
		        return $DBServer;
	        }
	        else 
	            throw new Exception(sprintf(_("Cannot launch new instance. %s"), $result->faultstring));
		}
		
		public function PutAccessData(DBServer $DBServer, Scalr_Messaging_Msg $message)
		{
			
		}
		
		public function ClearCache ()
		{
			$this->instancesListCache = array();
		}
	}

	
	
?>