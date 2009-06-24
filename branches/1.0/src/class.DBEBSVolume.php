<?php

	class DBEBSVolume
	{
		public $ID;
		public $FarmID;
		public $RoleName;
		public $VolumeID;
		public $State;
		public $InstanceID;
		public $AvailZone;
		public $Device;
		public $IsFSExists;
		public $InstanceIndex;
		public $EBSArrayID = 0;
		public $IsManual = 0;
		public $EBSArrayPart = 1;
		public $Region = 'us-east-1';
		public $Mount;
		public $MountPoint;
		
		private $DB;
		
		private static $FieldPropertyMap = array(
			'id' 			=> 'ID',
			'farmid'		=> 'FarmID',
			'role_name'		=> 'RoleName',
			'volumeid'		=> 'VolumeID',
			'state' 		=> 'State',
			'instance_id'	=> 'InstanceID',
			'avail_zone'	=> 'AvailZone',
			'device'		=> 'Device',
			'isfsexists'	=> 'IsFSExists',
			'instance_index'=> 'InstanceIndex',
			'ebs_arrayid'	=> 'EBSArrayID',
			'ismanual'		=> 'IsManual',
			'ebs_array_part'=> 'EBSArrayPart',
			'region'		=> 'Region',
			'mount'			=> 'Mount',
			'mountpoint'	=> 'Mountpoint'
		);
		
		/**
		 * Constructor
		 */
		public function __construct($volumeid)
		{
			$this->VolumeID = $volumeid;
			
			$this->DB = Core::GetDBInstance();
		}		
		
		/**
		 * Load EBS Object from Database by VolumeID
		 * @param integer $id
		 * @return DBEBSVolume $DBEBS
		 */
		public static function Load($volumeid)
		{
			
			$db = Core::GetDBInstance();
			
			$ebsinfo = $db->GetRow("SELECT * FROM farm_ebs WHERE volumeid=?", array($volumeid));
			if (!$ebsinfo)
				throw new Exception(sprintf(_("Volume with VolID#%s not found in database"), $volumeid));
				
			$DBEBSVolume = new DBEBSVolume($volumeid);

			foreach(self::$FieldPropertyMap as $k=>$v)
			{
				if ($ebsinfo[$k])
					$DBEBSVolume->{$v} = $ebsinfo[$k];
			}
				
			return $DBEBSVolume;
		}
		
		/**
		 * Delete information about EBS volume from database
		 */
		public function Delete()
		{
			if ($this->ID)
				$this->DB->Execute("DELETE FROM farm_ebs WHERE id=?", array($this->ID));
		}
		
		/**
		 * Save EBS info in database
		 */
		public function Save()
		{
			$skip_fields = array('id', 'ebs_arrayid');
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
					
				$this->DB->Execute("UPDATE farm_ebs SET {$update_fields}
					WHERE id={$this->ID}", 
					$values
				);
			}
			else
			{
				$this->DB->Execute("INSERT INTO farm_ebs (" . implode(", ", $fields) . ", ebs_arrayid) VALUES (" . str_repeat("?, ", count($fields)) . " '{$this->EBSArrayID}')", 
					$values
				);
				
				$this->ID = $this->DB->Insert_ID();
			}
		}
	}
	
?>