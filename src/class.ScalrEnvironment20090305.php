<?

	class ScalrEnvironment20090305 extends ScalrEnvironment20081216
    {    	
    	protected function ListRoleParams()
    	{
    		$ResponseDOMDocument = parent::ListRoleParams();
    		
    		$instance_info = $this->DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=? AND state != ?",
    			array($this->GetArg("instanceid"), INSTANCE_STATE::PENDING_TERMINATE)
    		);
    		
    		$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?",
    			array($instance_info['farmid'])
    		);
    		
    		//TODO:
    		
    		$alias = $this->DB->GetOne("SELECT alias FROM ami_roles WHERE ami_id=?", array($instance_info['ami_id']));
    		if ($alias == ROLE_ALIAS::MYSQL)
    		{
    			$DOMXPath = new DOMXPath($ResponseDOMDocument);
    			$ParamsDOMNode = $DOMXPath->query("//params")->item(0);
    			
    			$mysql_options = array(
    				"mysql_data_storage_engine" 	=> $farminfo['mysql_data_storage_engine'],
    				"mysql_master_ebs_volume_id" 	=> $farminfo['mysql_master_ebs_volume_id']
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