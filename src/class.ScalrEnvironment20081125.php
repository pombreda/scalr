<?

	class ScalrEnvironment20081125 extends ScalrEnvironment
    {    	
    	protected function ListScripts()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();
    		
    		$ScriptsDOMNode = $ResponseDOMDocument->createElement("scripts");
    		
    		/************/
    		// Get and Validate Event name
    		$instance_events = array(
            	"hostInit" 			=> "HostInit",
            	"hostUp" 			=> "HostUp",
            	"rebootFinish" 		=> "RebootComplete",
            	"newMysqlMaster"	=> "NewMysqlMasterUp"
            );

            $Reflect = new ReflectionClass("EVENT_TYPE");
            $scalr_events = $Reflect->getConstants();
            
            if (!in_array($this->GetArg("event"), $scalr_events))
            	$event_name = $instance_events[$this->GetArg("event")];

            if (!$event_name && preg_match("/^CustomEvent-[0-9]+-[0-9]+$/si", $this->GetArg("event")))
            	$custom_event_name = $this->GetArg("event");
            	
    		/************/
            	
            
			/***********************************************************/
            $instance_info = $this->DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=?",
    			array($this->GetArg("instanceid"))
    		);
    		
    		$farm_ami_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE (ami_id=? OR replace_to_ami=?)", 
				array($instance_info['ami_id'], $instance_info['ami_id'])
			);

			if ($custom_event_name)
			{
				$scripts = $this->DB->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? AND event_name=?",
					array($this->GetArg("farmid"), $custom_event_name)
				);
			}
			else
			{
	            // Check context and get list of scripts
	    		if (!$this->GetArg("target_ip"))
	            {      	
	            	//
	            	// Build a list of scripts to be executed on that particular instance
	            	//
	            	
	            	if ($event_name == EVENT_TYPE::HOST_INIT)
	            	{
	            		$instance_info['internal_ip'] = $this->GetArg('local_ip');
	            		$instance_info['external_ip'] = $_SERVER['REMOTE_ADDR'];
	            	}
	            		
	            	$scripts = $this->DB->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? AND (ami_id=? OR ami_id=?) 
	            		AND event_name=?",
						array($this->GetArg("farmid"), $farm_ami_info['ami_id'], $farm_ami_info['replace_to_ami'], $event_name)
					);
	            }
	            else
	            {
	            	//
	            	// Build a list of scripts to be executed upon event from another instance.
	            	//
	            	
	            	$target_instance_info = $this->DB->GetRow("SELECT * FROM farm_instances WHERE internal_ip=? AND farmid=?",
	            		array($this->GetArg("target_ip"), $this->GetArg("farmid"))
	            	);		
	            	if (!$target_instance_info)
	            		exit();
	            		
	            	$target_ami_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE ami_id=? OR replace_to_ami=?",
						array($target_instance_info['ami_id'], $target_instance_info['ami_id'])
					);
					$target_role_name = $this->DB->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($farm_ami_info['ami_id']));
					
					$scripts = $this->DB->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? AND (ami_id=? OR ami_id=?) 
	            		AND event_name=? AND (target = ? OR (target = ? AND ami_id=?))",
						array(
							$this->GetArg("farmid"), 
							$target_ami_info['ami_id'], 
							$target_ami_info['replace_to_ami'], 
							$event_name, 
							SCRIPTING_TARGET::FARM, 
							SCRIPTING_TARGET::ROLE,
							$instance_info['ami_id']
						)
					);
	            }
			}
            
    		/***********************************************************/
            // Build XML list of scripts
    		if (count($scripts) > 0)
    		{
	    		foreach ($scripts as $script)
	            {
	            	if ($script['version'] == 'latest')
					{
						$version = (int)$this->DB->GetOne("SELECT MAX(revision) FROM script_revisions WHERE scriptid=?",
							array($script['scriptid'])
						);
					}
					else
						$version = (int)$script['version'];
					
					$template = $this->DB->GetRow("SELECT * FROM scripts WHERE id=?", 
						array($script['scriptid'])
					);
					
					$template['script'] = $this->DB->GetOne("SELECT script FROM script_revisions WHERE scriptid=? AND revision=?",
						array($template['id'], $version)
					);
					
					if (!$template['script'])
						throw new Exception("Script {$template['name']}:{$version} doesn't exist or inactive. Make sure that is does exist and is approved.");	
					
					if ($template)
					{
						$params = array_merge($instance_info, unserialize($script['params']));
						
						// Prepare keys array and array with values for replacement in script
						$keys = array_keys($params);
						$f = create_function('$item', 'return "%".$item."%";');
						$keys = array_map($f, $keys);
						$values = array_values($params);
						
						// Generate script contents
						$script_contents = str_replace($keys, $values, $template['script']);
						$name = preg_replace("/[^A-Za-z0-9]+/", "_", $template['name']);
						
						$ScriptDOMNode = $ResponseDOMDocument->createElement("script");
						$ScriptDOMNode->setAttribute("asynchronous", ($script['issync'] == 1) ? '0' : '1');
						$ScriptDOMNode->setAttribute("exec-timeout", $script['timeout']);
						$ScriptDOMNode->setAttribute("name", $name);
						
						$BodyDOMNode = $ResponseDOMDocument->createElement("body");
						$BodyDOMNode->appendChild($ResponseDOMDocument->createCDATASection($script_contents));
						
						$ScriptDOMNode->appendChild($BodyDOMNode);
					}
					else
						throw new Exception(sprintf(_("Script template ID: %s not found."), $script['scriptid']));
						
					$ScriptsDOMNode->appendChild($ScriptDOMNode);
	            }
    		}
    		
    		$ResponseDOMDocument->documentElement->appendChild($ScriptsDOMNode);
    		
    		return $ResponseDOMDocument;
    	}
    	
    	protected function ListVirtualhosts()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();
    		
    		$Smarty = Core::GetSmartyInstance();
    		
    		$type = $this->GetArg("type");
    		$name = $this->GetArg("name");
    		$https = $this->GetArg("https");
    		
    		$virtual_hosts = $this->DB->GetAll("SELECT * FROM vhosts WHERE farmid=?",
    			array($this->GetArg("farmid"))
    		);
    		
    		$VhostsDOMNode = $ResponseDOMDocument->createElement("vhosts");
    		
    		$instance_info = $this->DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=?",
    			array($this->GetArg("instanceid"))
    		);
    		
    		$farm_ami_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE (ami_id=? OR replace_to_ami=?)", 
				array($instance_info['ami_id'], $instance_info['ami_id'])
			);
			
			$role_info = $this->DB->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($farm_ami_info['ami_id']));
			
			if ($role_info['alias'] != ROLE_ALIAS::APP && $role_info['alias'] != ROLE_ALIAS::WWW)
				throw new Exception("Virtualhosts list not available for non app or www roles.");
    		
    		while (count($virtual_hosts) > 0)
    		{
    			$virtualhost = array_shift($virtual_hosts);
    			
    			if ($virtualhost['issslenabled'])
    			{
    				$nonssl_vhost = $virtualhost;
    				$nonssl_vhost['issslenabled'] = 0;
    				array_push($virtual_hosts, $nonssl_vhost);
    			}
    			
    			//Filter by name
    			if ($this->GetArg("name") && $this->GetArg("name") != $virtualhost['name'])
    				continue;
    				
    			// Filter by https
    			if ($this->GetArg("https") !== null && $virtualhost['issslenabled'] != $this->GetArg("https"))
    				continue;
    				
    			// Check Role Name
    			if ($role_info['name'] != $virtualhost['role_name'])
    				continue;
    				
    			$type = ($role_info['alias'] == ROLE_ALIAS::APP) ? "apache" : "nginx";
				    				
    			$VhostDOMNode =  $ResponseDOMDocument->createElement("vhost");
    			$VhostDOMNode->setAttribute("hostname", $virtualhost['name']);
    			$VhostDOMNode->setAttribute("https", $virtualhost['issslenabled']);
    			$VhostDOMNode->setAttribute("type", $type);
    			
    			$template_option_name = "{$type}_http".($virtualhost['issslenabled'] ? "s" : "")."_vhost_template";
    			
    			$template = $this->DB->GetOne("SELECT value FROM farm_role_options WHERE hash=? AND farmid=? AND ami_id=?", 
            		array($template_option_name, $this->GetArg("farmid"), $farm_ami_info['ami_id'])
            	);
            	if (!$template)
            	{
            		$alias_ami_id = $db->GetOne("SELECT ami_id FROM ami_roles WHERE name=?", array($role_info['alias']));
            		$template = $db->GetOne("SELECT defval FROM role_options WHERE ami_id=? AND hash=?", 
            			array($alias_ami_id, $template_option_name)
            		);
            	}
            	
            	if ($template)
            	{
            		$vars = array(
            			"host" 			=> $virtualhost['name'],
            			"document_root" => $virtualhost['document_root_dir'],
            			"server_admin"	=> $virtualhost['server_admin'],
            			"logs_dir"		=> $virtualhost['logs_dir'],
            			"server_alias"	=> $virtualhost['aliases']
            		);
            		
            		$Smarty->assign($vars);
            		
            		$contents = $Smarty->fetch("string:{$template}");
            	}
            	else
            		throw new Exception("Virtualhost template ({$template_option_name}) not found in database. (ami-id: {$farm_ami_info['ami_id']}, farmid: {$farm_ami_info['farmid']})");
    			
    			$RawDOMNode = $ResponseDOMDocument->createElement("raw");
    			$RawDOMNode->appendChild($ResponseDOMDocument->createCDATASection($contents));
    			
    			$VhostDOMNode->appendChild($RawDOMNode);
    			$VhostsDOMNode->appendChild($VhostDOMNode);
    		}
    		
    		$ResponseDOMDocument->documentElement->appendChild($VhostsDOMNode);
    		
    		return $ResponseDOMDocument;
    	}
    	
    	protected function ListRoleParams()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();
    		
    		$instance_info = $this->DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=?",
    			array($this->GetArg("instanceid"))
    		);
    		
    		$sql_query = "SELECT * FROM farm_role_options WHERE farmid=? AND ami_id=?";
    		$sql_params = array($this->GetArg("farmid"), $instance_info['ami_id']);
    		
			if ($this->GetArg("name"))
			{
				$sql_query .= " AND hash=?";
				array_push($sql_params, $this->GetArg("name"));
			}
    		
			$options = $this->DB->GetAll($sql_query, $sql_params);
    		
    		$ParamsDOMNode = $ResponseDOMDocument->createElement("params");
    		
    		if (count($options) > 0)
    		{
    			foreach ($options as $option)
    			{
    				$ParamDOMNode = $ResponseDOMDocument->createElement("param");
    				$ParamDOMNode->setAttribute("name", $option['hash']);
    				
    				$ValueDomNode = $ResponseDOMDocument->createElement("value");
    				$ValueDomNode->appendChild($ResponseDOMDocument->createCDATASection($option['value']));
    				
    				$ParamDOMNode->appendChild($ValueDomNode);
    				$ParamsDOMNode->appendChild($ParamDOMNode);
    			}
    		}
    		
    		$ResponseDOMDocument->documentElement->appendChild($ParamsDOMNode);
    		
    		return $ResponseDOMDocument;
    	}
    	
    	/**
    	 * Return HTTPS certificate and private key
    	 * @return DOMDocument
    	 */
    	protected function GetHttpsCertificate()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();
    		
    		$instance_info = $this->DB->GetRow("SELECT * FROM farm_instances WHERE instance_id=?",
    			array($this->GetArg("instanceid"))
    		);
    		
    		$farm_ami_info = $this->DB->GetRow("SELECT * FROM farm_amis WHERE (ami_id=? OR replace_to_ami=?) AND farmid=?",
				array($instance_info['ami_id'], $instance_info['ami_id'], $this->GetArg("farmid"))
			);
			
			$role_name = $this->DB->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($farm_ami_info['ami_id']));

			$vhost_info = $this->DB->GetRow("SELECT * FROM vhosts WHERE farmid=? AND issslenabled='1' AND role_name=?", 
            	array($this->GetArg("farmid"), $role_name)
            );
            
            if ($vhost_info)
            {
				$ResponseDOMDocument->documentElement->appendChild(
					$ResponseDOMDocument->createElement("cert", $vhost_info['ssl_cert'])
				);
				$ResponseDOMDocument->documentElement->appendChild(
					$ResponseDOMDocument->createElement("pkey", $vhost_info['ssl_pkey'])
				);            	
            }
            
            return $ResponseDOMDocument;
    	}
    	
    	/**
    	 * List farm roles and hosts list for each role
    	 * Allowed args: role=(String Role Name) | behaviour=(app|www|mysql|base|memcached)
    	 * @return DOMDocument
    	 */
    	protected function ListRoles()
    	{
			$ResponseDOMDocument = $this->CreateResponse();
    		
			$RolesDOMNode = $ResponseDOMDocument->createElement('roles');
			$ResponseDOMDocument->documentElement->appendChild($RolesDOMNode);
			
    		$sql_query = "SELECT ami_id FROM farm_amis WHERE farmid=?";
			$sql_query_args = array($this->GetArg("farmid"));
    		
			// Filter by behaviour
			if ($this->GetArg("behaviour"))
			{
				$sql_query .= " AND ami_id IN (SELECT ami_id FROM ami_roles WHERE alias=? AND iscompleted='1')";
				array_push($sql_query_args, $this->GetArg("behaviour"));
			}
			
    		// Filter by role
			if ($this->GetArg("role"))
			{
				$sql_query .= " AND ami_id IN (SELECT ami_id FROM ami_roles WHERE name=? AND iscompleted='1')";
				array_push($sql_query_args, $this->GetArg("role"));
			}
			
    		$farm_roles = $this->DB->GetAll($sql_query, $sql_query_args);
    		foreach ($farm_roles as $farm_role)
    		{
    			// Get full information about role from database
    			$role_info = $this->DB->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($farm_role['ami_id']));
    			
    			// Create role node
    			$RoleDOMNode = $ResponseDOMDocument->createElement('role');
    			$RoleDOMNode->setAttribute('behaviour', $role_info['alias']);
    			$RoleDOMNode->setAttribute('name', $role_info['name']);
    			
    			$HostsDomNode = $ResponseDOMDocument->createElement('hosts');
    			$RoleDOMNode->appendChild($HostsDomNode);
    			
    			// List instances (hosts)
    			$instances = $this->DB->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND ami_id=? AND state=?",
    				array($this->GetArg("FarmID"), $role_info['ami_id'], INSTANCE_STATE::RUNNING)
    			);
    			
    			// Add hosts to response
    			if (count($instances) > 0)
    			{
    				foreach ($instances as $instance)
    				{
    					$HostDOMNode = $ResponseDOMDocument->createElement("host");
    					$HostDOMNode->setAttribute('internal-ip', $instance['internal_ip']);
    					$HostDOMNode->setAttribute('external-ip', $instance['external_ip']);
    					if ($role_info['alias'] == ROLE_ALIAS::MYSQL)
    						$HostDOMNode->setAttribute('replication-master', $instance['isdbmaster']);
    						
    					$HostsDomNode->appendChild($HostDOMNode);
    				}
    			}
    			
    			// Add role node to roles node
    			$RolesDOMNode->appendChild($RoleDOMNode);
    		}
    		
    		return $ResponseDOMDocument;
    	}
    }

?>