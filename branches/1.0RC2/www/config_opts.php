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
	        
	        $option = explode(".", $req_option);
	        
	        ob_start();
	        if ($farminfo)
	        {
	            switch ($option[0])
	            {
	            	case "https":
	            		
	            		$vhost_info = $db->GetRow("SELECT * FROM vhosts WHERE farmid=? AND issslenabled='1'", array($req_FarmID));
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
	            				if ($vhost_info)
	            				{
		            				$template = CONFIG::$NGINX_HTTPS_VHOST_TEMPLATE;
		            				$vars = array(
		            					"host" 			=> $vhost_info['name']
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
	            					$template = CONFIG::$HTTP_VHOST_TEMPLATE;
	            					break;
	            					
	            				case "https":
	            					$vhost_info = $db->GetRow("SELECT * FROM vhosts WHERE farmid=? AND issslenabled='1'", array($req_FarmID));
	            					$template = CONFIG::$HTTPS_VHOST_TEMPLATE;
	            					break;
	            					
	            				case "list":
	            					
	            					$vhosts = $db->GetAll("SELECT name FROM vhosts WHERE farmid=?", array($req_FarmID));
	            					foreach ($vhosts as $vhost)
		            					print "{$vhost['name']}\n";
	            					
	            					break;
	            			}
	            			
	            			if ($vhost_info)
	            			{
	            				$vars = array(
	            					"host" 			=> $vhost_info['name'],
	            					"document_root" => $vhost_info['document_root_dir'],
	            					"server_admin"	=> $vhost_info['server_admin'],
	            					"logs_dir"		=> $vhost_info['logs_dir']
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
								$instanses = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state=? AND ami_id=?", array($farm_id, INSTANCE_STATE::RUNNING, $ami["ami_id"]));
								foreach ($instanses as $instanse)
									print "{$instanse['internal_ip']}\n";                                    
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