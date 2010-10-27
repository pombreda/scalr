<?php
	
	class DBRole
	{			
		public
			$id,
			$platform,
			$name,
			$imageId,
			$clientId,
			$architecture;
		
		private $db;
		
		
		/*Temp*/
		public $instanceType;
		
		private static $FieldPropertyMap = array(
			'id' 			=> 'id',
			'platform'		=> 'platform',
			'clientid'		=> 'clientId',
			'name'			=> 'name',
			'ami_id'		=> 'imageId',
			'architecture'	=> 'architecture',
		
			/**** TODO *****/
			'instance_type'	=> 'instanceType',
			'region'		=> 'region'
		
		);
		
		public function __construct($id)
		{
			$this->id = $id;
			$this->db = Core::GetDBInstance();
		}
		
		/**
		 * @return DBRole
		 * @param unknown_type $id
		 */
		public static function loadById($id)
		{
			$db = Core::GetDBInstance();
			
			$roleinfo = $db->GetRow("SELECT * FROM roles WHERE id=?", array($id));
			if (!$roleinfo)
				throw new Exception(sprintf(_("Role ID#%s not found in database"), $id));
				
			$DBRole = new DBRole($id);
			
			foreach(self::$FieldPropertyMap as $k=>$v)
			{
				if (isset($roleinfo[$k]))
					$DBRole->{$v} = $roleinfo[$k];
			}
			
			return $DBRole;
		}
		
		public function remove($removeImage = false)
		{
			if ($removeImage)
			{
				PlatformFactory::NewPlatform($this->platform)->RemoveServerSnapshot($this);
			}
			
			$this->db->Execute("DELETE FROM roles WHERE id = ?", array($this->id));
			$this->db->Execute("DELETE FROM role_options WHERE ami_id = ?", array($this->imageId)); //FIXME:
		}
		
		public function isUsed()
		{
			return (bool)$this->db->GetOne("SELECT id FROM farm_roles WHERE role_id=? OR new_role_id=?", 
				array($this->id, $this->id)
			);
		}
		
		public static function createFromBundleTask(BundleTask $BundleTask)
		{
			$db = Core::GetDBInstance();
			
			if ($BundleTask->prototypeRoleId)
			{
				$proto_role = $db->GetRow("SELECT * FROM roles WHERE id=?", array($BundleTask->prototypeRoleId));
			}
			else
			{
				$DBServer = DBServer::LoadByID($BundleTask->serverId);
				if ($DBServer->platform == SERVER_PLATFORMS::EC2)
				{
					$proto_role = array(
						"alias" => $DBServer->GetProperty(SERVER_PROPERTIES::SZR_IMPORTING_BEHAVIOUR),
						"instance_type" => $DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_TYPE),
						"architecture" => $DBServer->GetProperty(SERVER_PROPERTIES::ARCHITECTURE),
						"name" => "*import*",
						"region" => $DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION)
					);
				}
			}
			
			
			$db->Execute("INSERT INTO roles SET
				ami_id			= ?,
				name			= ?,
				roletype		= ?,
				clientid		= ?,
				comments		= ?,
				dtbuilt			= NOW(),
				description		= ?,
				default_minLA	= '2',
				default_maxLA	= '5',
				alias			= ?,
				instance_type	= ?,
				architecture	= ?,
				isstable		= '1',
				prototype_role	= ?,
				approval_state	= ?,
				region			= ?,
				default_ssh_port = ?,
				platform		= ?
			
			", array(
				$BundleTask->snapshotId,
				$BundleTask->roleName,
				ROLE_TYPE::CUSTOM,
				$BundleTask->clientId,
				"",
				$BundleTask->description,
				$proto_role['alias'],
				$proto_role['instance_type'],
				$proto_role['architecture'],
				$proto_role['name'],
				"",
				$proto_role['region'],
				$proto_role['default_ssh_port'],
				$BundleTask->platform
			));
			
			$role_id = $db->Insert_Id();
			
			if ($proto_role['ami_id'])
			{
				$db->Execute("INSERT INTO role_options (`name`, `type`, `isrequired`, `defval`, `allow_multiple_choice`, `options`, `ami_id`, `hash`, `issystem`)
					SELECT `name`, `type`, `isrequired`, `defval`, `allow_multiple_choice`, `options`, '{$BundleTask->snapshotId}', `hash`, `issystem`
					FROM role_options WHERE ami_id='{$proto_role['ami_id']}'
				");
			}
			
			$BundleTask->roleId = $role_id;
			$BundleTask->Save();
			
			$BundleTask->Log(sprintf("Created new role. Role name: %s. Role ID: %s", 
				$BundleTask->roleName, $BundleTask->roleId
			));
			
			return self::loadById($role_id);
		}
	}
?>