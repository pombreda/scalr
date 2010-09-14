<?php

	class DBEBSArray
	{
		public $ID;
		public $Name;
		public $Size;
		public $VolumesCount;
		public $ClientID;
		public $Mountpoint;
		public $IsFSCreated;
		public $Status;
		public $InstanceID;
		public $CorruptReason;
		public $AvailZone;
		public $InstanceIndex = 1;
		public $AttachOnBoot;
		public $FarmID;
		public $FarmRoleID;
		public $Region;
		public $ServerID;
		
		private $Client;
		private $DB;
		
		private static $FieldPropertyMap = array(
			'id' 			=> 'ID',
			'name'			=> 'Name',
			'size'			=> 'Size',
			'volumes'		=> 'VolumesCount',
			'clientid' 		=> 'ClientID',
			'mountpoint'	=> 'Mountpoint',
			'isfscreated'	=> 'IsFSCreated',
			'status'		=> 'Status',
			'instance_id'	=> 'InstanceID',
			'corrupt_reason' => 'CorruptReason',
			'avail_zone'	=> 'AvailZone',
			'instance_index'=> 'InstanceIndex',
			'attach_on_boot'=> 'AttachOnBoot',
			'farmid'		=> 'FarmID',
			'farm_roleid'	=> 'FarmRoleID',
			'region'		=> 'Region',
			'server_id'		=> 'ServerID'
		);
		
		/**
		 * Constructor
		 */
		public function __construct($name)
		{
			$this->Name = $name;
			
			$this->DB = Core::GetDBInstance();
		}		
		
		/**
		 * Load EBS Array from Database by ID
		 * @param integer $id
		 * @return DBEBSArray $DBEBSArray
		 */
		public static function Load($id)
		{
			
			$db = Core::GetDBInstance();
			
			$arrayinfo = $db->GetRow("SELECT * FROM ebs_arrays WHERE id=?", array($id));
			if (!$arrayinfo)
				throw new Exception(sprintf(_("EBS array with ID#%s not found in database"), $id));
				
			$DBEBSArray = new DBEBSArray($arrayinfo['name']);
			
			foreach(self::$FieldPropertyMap as $k=>$v)
			{
				if ($arrayinfo[$k])
					$DBEBSArray->{$v} = $arrayinfo[$k];
			}

			$DBEBSArray->Client = Client::Load($DBEBSArray->ClientID);
			
			return $DBEBSArray;
		}
		
		/**
		 * Delete information about EBS volume from database
		 */
		public function Delete()
		{
			if ($this->ID)
				$this->DB->Execute("DELETE FROM ebs_arrays WHERE id=?", array($this->ID));
		}
		
		public function CreateSnapshot($snapshot_name, $autosnapshotid = 0)
		{
			$this->DB->BeginTrans();
			
			$this->DB->Execute("INSERT INTO ebs_array_snaps SET description=?, dtcreated=NOW(), status=?, clientid=?, ebs_arrayid=?, autosnapshotid=?",
	    		array(
	    			$snapshot_name,
	    			EBS_ARRAY_SNAP_STATUS::PENDING,
	    			$this->ClientID,
	    			$this->ID,
	    			$autosnapshotid
	    		)
	    	);
	    		
	    	$ebs_array_snapid = $this->DB->Insert_ID();
	    	
	    	try
	    	{
		    	$volumes = $this->DB->GetAll("SELECT * FROM farm_ebs WHERE ebs_arrayid=?", array($this->ID));
		    	foreach ($volumes as $volume)
		    	{
		    		$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($volume['region'])); 
					$AmazonEC2Client->SetAuthKeys($this->Client->AWSPrivateKey, $this->Client->AWSCertificate);
		    		
		    		$res = $AmazonEC2Client->CreateSnapshot($volume['volumeid']);
		    		if ($res->snapshotId)
					{
						$this->DB->Execute("INSERT INTO ebs_snaps_info SET snapid=?, comment=?, dtcreated=NOW(), ebs_array_snapid=?, region=?",
							array(
								$res->snapshotId,
								sprintf(_("Snapshot for EBS array '%s'. Part #%s"), $this->Name, $volume['ebs_array_part']),
								$ebs_array_snapid,
								$volume['region']
							)
						);
					}
		    	}
		    	
		    	$this->DB->Execute("UPDATE ebs_array_snaps SET ebs_snaps_count=? WHERE id=?", array(
		    		count($volumes),
		    		$ebs_array_snapid
		    	));
	    	}
	    	catch(Exception $e)
	    	{
	    		$this->DB->RollbackTrans();
	    		throw new Exception (sprintf(_("Cannot create snapshot on volume %s: %s"), $volume['volumeid'], $e->getMessage()));
	    	}
	    	
	    	$this->DB->CommitTrans();
	    	
	    	return $ebs_array_snapid;
		}
		
		/**
		 * Save EBS info in database
		 */
		public function Save()
		{
			$skip_fields = array('id', 'clientid');
			$fields = array();
			$values = array();
			
			foreach(self::$FieldPropertyMap as $k=>$v)
			{
								
				if (!in_array($k, $skip_fields))
				{
					array_push($fields, $k);
					array_push($values, $this->{$v});
				}
			}
			
			if ($this->ID)
			{
				foreach($fields as $field)
					$update_fields .= "{$field} = ?, ";
				
				$update_fields = trim($update_fields, ", ");	
					
				$this->DB->Execute("UPDATE ebs_arrays SET {$update_fields}
					WHERE id={$this->ID}", 
					$values
				);
			}
			else
			{
				$this->DB->Execute("INSERT INTO ebs_arrays (" . implode(", ", $fields) . ", clientid) VALUES (" . str_repeat("?, ", count($fields)) . " '{$this->ClientID}')", 
					$values
				);
				
				$this->ID = $this->DB->Insert_ID();
			}
		}
	}
	
?>