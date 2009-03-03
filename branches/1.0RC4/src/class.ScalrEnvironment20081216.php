<?

	class ScalrEnvironment20081216 extends ScalrEnvironment20081125
    {    	
    	protected function GetLatestVersion()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();
    		$VersionDOMNode = $ResponseDOMDocument->createElement("version", "2008-12-16");
    		$ResponseDOMDocument->documentElement->appendChild($VersionDOMNode);
    		
    		return $ResponseDOMDocument;
    	}

    	protected function ListEBSMountpoints()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();
    		
    		$MountpointsDOMNode = $ResponseDOMDocument->createElement("mountpoints");
    		
    		$instance_info = $this->DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=?",
    			array($this->GetArg("instanceid"))
    		);
    		
    		//
    		// List EBS Arrays
    		//
    		$arrays = $this->DB->GetAll("SELECT * FROM ebs_arrays WHERE status IN (?,?,?) AND instance_id=?",
    		array(
    			EBS_ARRAY_STATUS::MOUNTING,
    			EBS_ARRAY_STATUS::IN_USE,
    			EBS_ARRAY_STATUS::CREATING_FS,
    			$instance_info['instance_id']
    		));
    		
    		foreach ($arrays as $array)
    		{
    			$mountpoints[] = array(
					'name'		=> $array['name'],
					'dir'		=> $array['mountpoint'],
    				'createfs' 	=> $array['isfscreated'] ? 0 : 1,
    				'volumes'	=> $this->DB->GetAll("SELECT * FROM farm_ebs WHERE ebs_arrayid=?", array($array['id'])),
    				'isarray'	=> 1
				);
    		}
    		
    		//
    		// List EBS Volumes
    		//
    		$volumes = $this->DB->GetAll("SELECT * FROM farm_ebs WHERE instance_id=? AND state IN (?,?) AND ebs_arrayid = '0'",
    		array(
    			$instance_info['instance_id'],
    			FARM_EBS_STATE::MOUNTING,
    			FARM_EBS_STATE::ATTACHED
    		));
    		
    		foreach ($volumes as $volume)
    		{
    			if ($volume['ismanual'] == 0)
				{
					$ami_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE (ami_id=? OR replace_to_ami=?) AND farmid=?",
					array(
						$instance_info['ami_id'],
						$instance_info['ami_id'],
						$instance_info['farmid']
					));
					
					$mountpoint = $ami_info['ebs_mount'] ? $ami_info['ebs_mountpoint'] : "";
					$createfs = $volume["isfsexists"] ? 0 : 1;
				}
				else
				{
					$mountpoint = "";
					$createfs = 0;
				}
    			
    			$mountpoints[] = array(
					'name'		=> $volume['volumeid'],
					'dir'		=> $mountpoint,
    				'createfs' 	=> $createfs,
    				'volumes'	=> array($volume),
    				'isarray'	=> 0
				);
    		}
    		
    		//
    		// Create response
    		//
    		
    		foreach ($mountpoints as $mountpoint)
    		{
    			$MountpointDOMNode = $ResponseDOMDocument->createElement("mountpoint");
				
				$MountpointDOMNode->setAttribute("name", $mountpoint['name']);
				$MountpointDOMNode->setAttribute("dir", $mountpoint['dir']);
				$MountpointDOMNode->setAttribute("createfs", $mountpoint['createfs']);
				$MountpointDOMNode->setAttribute("isarray", $mountpoint['isarray']);
				
				$VolumesDOMNode = $ResponseDOMDocument->createElement("volumes");
				
				foreach ($mountpoint['volumes'] as $volume)
				{
					$VolumeDOMNode = $ResponseDOMDocument->createElement("volume");
					$VolumeDOMNode->setAttribute("device", $volume['device']);
					$VolumeDOMNode->setAttribute("volume-id", $volume['volumeid']);
					
					$VolumesDOMNode->appendChild($VolumeDOMNode);
				}
				
				$MountpointDOMNode->appendChild($VolumesDOMNode);
				$MountpointsDOMNode->appendChild($MountpointDOMNode);
    		}
    		
    		$ResponseDOMDocument->documentElement->appendChild($MountpointsDOMNode);
    		
    		return $ResponseDOMDocument;
    	}
    }
?>