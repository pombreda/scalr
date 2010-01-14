<?

	class ScalrEnvironment20090305 extends ScalrEnvironment20081216
    {    	
    	protected function ListRoleParams()
    	{
    		$ResponseDOMDocument = parent::ListRoleParams();
    		
    		$instance_info = $this->DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=? AND state NOT IN (?,?)",
    			array($this->GetArg("instanceid"), INSTANCE_STATE::PENDING_TERMINATE, INSTANCE_STATE::TERMINATED)
    		);
    		
    		$DBFarm = DBFarm::LoadByID($instance_info['farmid']);
    		
    		//TODO:
    		
    		$alias = $this->DB->GetOne("SELECT alias FROM roles WHERE ami_id=?", array($instance_info['ami_id']));
    		if ($alias == ROLE_ALIAS::MYSQL)
    		{
    			$DOMXPath = new DOMXPath($ResponseDOMDocument);
    			$ParamsDOMNode = $DOMXPath->query("//params")->item(0);
    			
    			$mysql_options = array(
    				"mysql_data_storage_engine" 	=> $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_DATA_STORAGE_ENGINE),
    				"mysql_master_ebs_volume_id" 	=> $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_MASTER_EBS_VOLUME_ID)
    			);
    			
    			foreach ($mysql_options as $k=>$v)
    			{
    				if (!$this->GetArg("name") || $this->GetArg("name") == $k)
					{
	    				$ParamDOMNode = $ResponseDOMDocument->createElement("param");
	    				$ParamDOMNode->setAttribute("name", $k);
	    				
	    				$ValueDomNode = $ResponseDOMDocument->createElement("value");
	    				$ValueDomNode->appendChild($ResponseDOMDocument->createCDATASection($v));
	    				
	    				$ParamDOMNode->appendChild($ValueDomNode);
	    				$ParamsDOMNode->appendChild($ParamDOMNode);
					}
    			}
    		}
    		    		
    		return $ResponseDOMDocument;
    	}
    }
?>