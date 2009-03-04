<?
    define("NO_AUTH", true);
    include("src/prepend.inc.php");

    try
    {
	    if ($req_FarmID && $req_Hash)
	    {
	        $farm_id = (int)$req_FarmID;
	        $hash = preg_replace("/[^A-Za-z0-9]+/", "", $req_Hash);
	        
	        $Logger->debug("Instance={$req_InstanceID} from FarmID={$farm_id} with Hash={$hash} trying to view option '{$req_option}'");
	                
	        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND hash=?", array($farm_id, $hash));
	        $instance_info = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($req_InstanceID));
	        	        
	        $option = explode(".", $req_option);
	        
	        $farm_ami_info = $db->GetRow("SELECT * FROM farm_amis WHERE ami_id=? OR replace_to_ami=?",
				array($instance_info['ami_id'], $instance_info['ami_id'])
			);
			$role_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($farm_ami_info['ami_id']));
			
			$role_name = $role_info["name"];
	        
			// Parse additional data
	    	$chunks = explode(";", $req_Data);
			foreach ($chunks as $chunk)
			{
				$dt = explode(":", $chunk);
				$data[$dt[0]] = trim($dt[1]);
			}
			
			ob_start();
	        if ($farminfo)
	        {
	            switch ($option[0])
	            {
					//
	            	//TODO: переписать это все так, как мы обсудили с ћаратом.
	            	//
	            	
	            	case "scripting":
	            		
	            		if (!$data['target_ip'])
	            		{
		            		$instance_events = array(
		            			"hostInit" 			=> "HostInit",
		            			"hostUp" 			=> "HostUp",
		            			"rebootFinish" 		=> "RebootComplete",
		            			"IPAddressChanged" 	=> "IPAddressChanged",
		            			"newMysqlMaster"	=> "NewMysqlMasterUp"
		            		);
		            			            		
		            		$instance_event_name = $option[1];
		            		
		            		$event_name = $instance_events[$instance_event_name];	            		
		            		if (!$event_name)
		            			exit();
		            		
		            		if ($event_name == EVENT_TYPE::HOST_INIT)
		            		{
		            			$instance_info['internal_ip'] = $data['local_ip'];
		            			$instance_info['external_ip'] = $_SERVER['REMOTE_ADDR'];
		            		}
		            			
		            		$scripts = $db->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? AND (ami_id=? OR ami_id=?) 
		            			AND event_name=?",
								array($farm_id, $farm_ami_info['ami_id'], $farm_ami_info['replace_to_ami'], $event_name)
							);

							if (count($scripts) == 0)
								exit();
	            		}
	            		else
	            		{
	            			$instance_event_name = $option[1];
	            			
	            			$target_instance_info = $db->GetRow("SELECT * FROM farm_instances WHERE internal_ip=? AND farmid=?",
	            				array($data['target_ip'], $farm_id)
	            			);		
	            			if (!$target_instance_info)
	            				exit();
	            				
	            			$target_ami_info = $db->GetRow("SELECT * FROM farm_amis WHERE ami_id=? OR replace_to_ami=?",
								array($target_instance_info['ami_id'], $target_instance_info['ami_id'])
							);
							$target_role_name = $db->GetOne("SELECT name FROM ami_roles WHERE ami_id=?", array($farm_ami_info['ami_id']));
							
							$scripts = $db->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? AND (ami_id=? OR ami_id=?) 
		            			AND event_name=? AND (target = 'farm' OR (target = 'role' AND ami_id=?))",
								array($farm_id, $target_ami_info['ami_id'], $target_ami_info['replace_to_ami'], $instance_event_name, $instance_info['ami_id'])
							);
							
							if (count($scripts) == 0)
								exit();
	            		}
					
						
	            		$Response = new DOMDocument('1.0', 'UTF-8');
	            		$Response->loadXML("<scripting><{$instance_event_name}></{$instance_event_name}></scripting>");
	            		
	            		foreach ($scripts as $script)
	            		{
	            			if ($script['version'] == 'latest')
							{
								$version = (int)$db->GetOne("SELECT MAX(revision) FROM script_revisions WHERE scriptid=?",
									array($script['scriptid'])
								);
							}
							else
								$version = (int)$script['version'];
							
							$template = $db->GetRow("SELECT * FROM scripts WHERE id=?", 
								array($script['scriptid'])
							);
							
							$template['script'] = $db->GetOne("SELECT script FROM script_revisions WHERE scriptid=? AND revision=?",
								array($template['id'], $version)
							);
							
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
								
								$dom_script = $Response->createElement("script");
								$dom_script->appendChild($Response->createElement("name", $name));
								$dom_script->appendChild($Response->createElement("issync", $script['issync']));
								$dom_script->appendChild($Response->createElement("timeout", $script['timeout']));
								$body = $Response->createElement("body");
								$body->appendChild($Response->createCDATASection($script_contents));
								$dom_script->appendChild($body);
								$Response->getElementsByTagName($instance_event_name)->item(0)->appendChild($dom_script);
							}
							else
								throw new Exception(sprintf(_("Script template ID: %s not found."), $script['scriptid']));
	            		}
	            		
	            		header("Content-type: text/xml");
	            		print $Response->saveXML();
	            		
	            		break;
	            		            	
	            	case "options":
	            		
	            		$opt_name =	$option[1]; 
	            		
	            		if (!$opt_name || $opt_name == 'all')
	            		{
		            		$options = $db->GetAll("SELECT * FROM farm_role_options WHERE farmid=? AND ami_id=?", 
		            			array($farm_id, $instance_info['ami_id'])
		            		);
		            		
		            		$Response = new DOMDocument('1.0', 'UTF-8');
	            			$Response->loadXML("<options></options>");
	            			
	            			foreach ($options as $option)
	            			{
	            				$dom_option = $Response->createElement("option");
								$dom_option->appendChild($Response->createElement("name", $option['hash']));
								$body = $Response->createElement("value");
								$body->appendChild($Response->createCDATASection($option['value']));
								$dom_option->appendChild($body);
								
								$Response->getElementsByTagName('options')->item(0)->appendChild($dom_option);
	            			}
	            			
	            			header("Content-type: text/xml");
	            			print $Response->saveXML();
	            		}
	            		else
	            		{
	            			$value = $db->GetOne("SELECT value FROM farm_role_options WHERE hash=? AND farmid=? AND ami_id=?", 
		            			array($opt_name, $farm_id, $instance_info['ami_id'])
		            		);
		            		
		            		if (!$value)
		            		{
		            			$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($instance_info['ami_id']));
		            			$alias_ami_id = $db->GetOne("SELECT ami_id FROM ami_roles WHERE name=?", array($ami_info['alias']));
		            			$value = $db->GetOne("SELECT defval FROM role_options WHERE ami_id=? AND hash=?", 
			            			array($alias_ami_id, $opt_name)
			            		);
		            		}	
		            		
							print $value;
	            		}
		            		
	            		break;
	            	
	            	case "https":
	            		
	            		$vhost_info = $db->GetRow("SELECT * FROM vhosts WHERE farmid=? AND issslenabled='1' AND role_name=?", 
	            			array($req_FarmID, $role_name)
	            		);
	            		if ($vhost_info)
	            		{
	            			//$option[1] = cert OR pkey
	            			print $vhost_info["ssl_{$option[1]}"];
	            		}
	            		
	            		break;
	            	
	            	case "nginx":
	            		
	            		if ($option[1] == "vhost")
	            		{
	            			if ($option[2] == "https")
	            			{
	            				$vhost_info = $db->GetRow("SELECT * FROM vhosts WHERE farmid=? AND issslenabled='1'", array($req_FarmID));
	            				
	            				$vhost_role_info = $db->GetRow("SELECT * FROM ami_roles WHERE name=? AND (clientid=? OR clientid='0') AND iscompleted='1' AND region=?", 
            						array($vhost_info['role_name'], $farminfo['clientid'], $farminfo['region'])
            					);
            					
            					if ($vhost_role_info['alias'] != ROLE_ALIAS::WWW && $vhost_info['role_name'] != $role_name)
            						exit();
	            				
	            				if ($vhost_info)
	            				{
		            				// Get virtualhost template
		            				$template = $db->GetOne("SELECT value FROM farm_role_options WHERE hash=? AND farmid=? AND ami_id=?", 
				            			array("nginx_https_host_template", $farm_id, $farm_ami_info['ami_id'])
				            		);
	            					if (!$template)
				            		{
				            			$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($farm_ami_info['ami_id']));
				            			$alias_ami_id = $db->GetOne("SELECT ami_id FROM ami_roles WHERE name=?", array($ami_info['alias']));
				            			$template = $db->GetOne("SELECT defval FROM role_options WHERE ami_id=? AND hash=?", 
					            			array($alias_ami_id, "nginx_https_host_template")
					            		);
				            		}
	            					
				            		if (!$template)
				            		{
				            			Logger::getLogger("ConfigOpts")->fatal("Template virtualhost for nginx not found in database. (ami-id: {$instance_info['ami_id']}, farmid: {$farm_id})");
				            			throw new Exception(_("Internal Server Error"));
				            		}
	            							            				
		            				
		            				$vars = array(
		            					"host" 			=> $vhost_info['name'],
		            					"server_alias"	=> $vhost_info['aliases']
		            				);
		            				
		            				$Smarty->assign($vars);
		            				
		            				print $Smarty->fetch("string:{$template}");
	            				}
	            			}
	            		}
	            		
	            		break;
	            		
	            	case "apache":
	            		
	            		if ($option[1] == "vhost")
	            		{
	            			$virtual_host_name = implode(".", array_slice($option, 3));
	            			
	            			switch($option[2])
	            			{
	            				case "http":
	            					$vhost_info = $db->GetRow("SELECT * FROM vhosts WHERE farmid=? AND name=?", array($req_FarmID, $virtual_host_name));
	            					
	            					$vhost_role_info = $db->GetRow("SELECT * FROM ami_roles WHERE name=? AND (clientid=? OR clientid='0') AND iscompleted='1' AND region=?", 
	            						array($vhost_info['role_name'], $farminfo['clientid'], $farminfo['region'])
	            					);
            						
	            					if ($vhost_role_info['alias'] != ROLE_ALIAS::WWW && $vhost_info['role_name'] != $role_name)
	            						exit();
	            					
	            					// Get virtualhost template
		            				$template = $db->GetOne("SELECT value FROM farm_role_options WHERE hash=? AND farmid=? AND ami_id=?", 
				            			array("apache_http_vhost_template", $farm_id, $farm_ami_info['ami_id'])
				            		);
	            					if (!$template)
				            		{
				            			$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($farm_ami_info['ami_id']));
				            			$alias_ami_id = $db->GetOne("SELECT ami_id FROM ami_roles WHERE name=?", array($ami_info['alias']));
				            			$template = $db->GetOne("SELECT defval FROM role_options WHERE ami_id=? AND hash=?", 
					            			array($alias_ami_id, "apache_http_vhost_template")
					            		);
				            		}
	            					
				            		if (!$template)
				            		{
				            			Logger::getLogger("ConfigOpts")->fatal("Template virtualhost for apache not found in database. (ami-id: {$farm_ami_info['ami_id']}, farmid: {$farm_id})");
				            			throw new Exception(_("Internal Server Error"));
				            		}
	            					
	            					break;
	            					
	            				case "https":
	            					$vhost_info = $db->GetRow("SELECT * FROM vhosts WHERE farmid=? AND issslenabled='1'", array($req_FarmID));
	            					
	            					$vhost_role_info = $db->GetRow("SELECT * FROM ami_roles WHERE name=? AND (clientid=? OR clientid='0') AND iscompleted='1' AND region=?", 
	            						array($vhost_info['role_name'], $farminfo['clientid'], $farminfo['region'])
	            					);
            						
	            					if ($vhost_role_info['alias'] != ROLE_ALIAS::WWW && $vhost_info['role_name'] != $role_name)
	            						exit();
	            					
	            					// Get virtualhost template
		            				$template = $db->GetOne("SELECT value FROM farm_role_options WHERE hash=? AND farmid=? AND ami_id=?", 
				            			array("apache_https_vhost_template", $farm_id, $farm_ami_info['ami_id'])
				            		);
	            					if (!$template)
				            		{
				            			$ami_info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id=?", array($farm_ami_info['ami_id']));
				            			$alias_ami_id = $db->GetOne("SELECT ami_id FROM ami_roles WHERE name=?", array($ami_info['alias']));
				            			$template = $db->GetOne("SELECT defval FROM role_options WHERE ami_id=? AND hash=?", 
					            			array($alias_ami_id, "apache_https_vhost_template")
					            		);
				            		}
	            					
				            		if (!$template)
				            		{
				            			Logger::getLogger("ConfigOpts")->fatal("Template virtualhost for apache not found in database. (ami-id: {$farm_ami_info['ami_id']}, farmid: {$farm_id})");
				            			throw new Exception(_("Internal Server Error"));
				            		}
				            		
	            					break;
	            					
	            				case "list":
	            					
	            					$vhosts = $db->GetAll("SELECT * FROM vhosts WHERE farmid=?", array($req_FarmID));
	            					foreach ($vhosts as $vhost)
	            					{
		            					$vhost_role_info = $db->GetRow("SELECT * FROM ami_roles WHERE name=? AND (clientid=? OR clientid='0') AND iscompleted='1' AND region=?", 
		            						array($vhost['role_name'], $farminfo['clientid'], $farminfo['region'])
		            					);
	            						
		            					if ($vhost_role_info['alias'] != ROLE_ALIAS::WWW && $vhost['role_name'] != $role_name)
		            						continue;
		            					
	            						print "{$vhost['name']}\n";
	            					}
	            					
	            					break;
	            			}
	            			
	            			if ($vhost_info)
	            			{
	            				$vars = array(
	            					"host" 			=> $vhost_info['name'],
	            					"document_root" => $vhost_info['document_root_dir'],
	            					"server_admin"	=> $vhost_info['server_admin'],
	            					"logs_dir"		=> $vhost_info['logs_dir'],
	            					"server_alias"	=> $vhost_info['aliases']
	            				);
	            				
	            				$Smarty->assign($vars);
	            				
	            				print $Smarty->fetch("string:{$template}");
	            			}
	            		}
	            		
	            		break;
	            	            	
	            	case "roles":
	            		
	            		switch($option[1])
	                    {
	                        case "list":
	                        	
	                        $farm_amis = $db->GetAll("SELECT ami_id FROM farm_amis WHERE farmid='{$farm_id}'");
	                        $aliases = array();
	                        foreach ($farm_amis as $farm_ami)
	                        {
	                        	$info = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$farm_ami['ami_id']}'");
	                        	if (!$aliases[$info['alias']])
	                        	{
	                        		$aliases[$info['alias']] = true;
	                        		print "{$info["alias"]}\n";
	                        	}
	                        	
	                        	if ($info['alias'] != $info["name"])
	                        		print "{$info["name"]}\n";
	                        }
	                        	
	                        break;
	                    }
	            		
	            		break;
	            	
	            	case "db":
	                    
	                    $rolename = $option[1];
	                    
	                    switch($option[2])
	                    {
	                        case "role":
	                            
	                            $havemaster = $db->GetOne("SELECT instance_id FROM farm_instances WHERE farmid=? AND isdbmaster='1'", array($farm_id));
	                            
	                            if ($havemaster == $req_InstanceID)
	                            	print "master";
	                            elseif (!$havemaster)
	                            {
	                                $db->Execute("UPDATE farm_instances SET isdbmaster='1' WHERE farmid=? AND instance_id=?", array($farm_id, $req_InstanceID));
	                                print "master";
	                            }
	                            else 
	                                print "slave";
	                            
	                        break;
	                        
	                        case "master":
	                            
	                            switch($option[3])
	                            {
	                                case "ip":
	                                
	                                    print $db->GetOne("SELECT internal_ip FROM farm_instances WHERE isdbmaster='1' AND farmid=?", array($farm_id));   
	                                
	                                break;
	                            }
	                            
	                        break;
	                    }
	                    
	                    break;
	                
	                case "servers":
	                    
	                    $role = $option[1];
	                    $amis = $db->GetAll("SELECT ami_id FROM ami_roles WHERE name=? OR alias=?", array($role, $role));
	                    
	                    if ($amis)
	                    {
	                        foreach ($amis as $ami)
	                        {
								$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state=? AND ami_id=?", array($farm_id, INSTANCE_STATE::RUNNING, $ami["ami_id"]));
								foreach ($instances as $instance)
									print "{$instance['internal_ip']}\n";                                    
	                        }
	                    }
	                    
	                    break;
	            }
	        }
	        $contents = ob_get_contents();
	        ob_end_clean();
	        
	        $Logger->debug("config_opts.php output: {$contents}");
	        
	        print $contents;
	        
	        exit();
	    }
    }
    catch(Exception $e)
    {
    	header("HTTP/1.0 500 Internal Server Error");
    	die($e->getMessage());
    }
?>