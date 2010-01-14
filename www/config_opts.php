<?
    define("NO_AUTH", true);
    try
    {
	    include("src/prepend.inc.php");
	    
    	if ($req_FarmID && $req_Hash)
	    {
	        $farm_id = (int)$req_FarmID;
	        $hash = preg_replace("/[^A-Za-z0-9]+/", "", $req_Hash);
	        
	        $Logger->warn("Instance={$req_InstanceID} from FarmID={$farm_id} with Hash={$hash} trying to view option '{$req_option}'");
	                
	        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND hash=?", array($farm_id, $hash));
	        $instance_info = $db->GetRow("SELECT * FROM farm_instances WHERE instance_id=?", array($req_InstanceID));
	        	        
	        $option = explode(".", $req_option);
	        	        
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
	            	//TODO: ���������� ��� ��� ���, ��� �� �������� � �������.
	            	//
	            		            	            	
	            	case "roles":
	            		
	            		switch($option[1])
	                    {
	                        case "list":
	                        	
	                        $farm_roles = $db->GetAll("SELECT ami_id FROM farm_roles WHERE farmid='{$farm_id}'");
	                        $aliases = array();
	                        foreach ($farm_roles as $farm_ami)
	                        {
	                        	$info = $db->GetRow("SELECT alias FROM ami_roles WHERE ami_id='{$farm_ami['ami_id']}'");
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
	                            
	                            $havemaster = $db->GetOne("SELECT instance_id FROM farm_instances WHERE farmid=? AND isdbmaster='1' AND state != ?", array($farm_id, INSTANCE_STATE::TERMINATED));
	                            
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
	                                
	                                    print $db->GetOne("SELECT internal_ip FROM farm_instances WHERE isdbmaster='1' AND farmid=? AND state != ?", array($farm_id, INSTANCE_STATE::TERMINATED));   
	                                
	                                break;
	                            }
	                            
	                        break;
	                    }
	                    
	                    break;
	                
	                case "servers":
	                    
	                    $role = $option[1];
	                    $amis = $db->GetAll("SELECT ami_id FROM roles WHERE name=? OR alias=?", array($role, $role));
	                    
	                    if ($amis)
	                    {
	                        foreach ($amis as $ami)
	                        {
								$instances = $db->GetAll("SELECT internal_ip FROM farm_instances WHERE farmid=? AND state=? AND ami_id=?", array($farm_id, INSTANCE_STATE::RUNNING, $ami["ami_id"]));
								foreach ($instances as $instance)
									print "{$instance['internal_ip']}\n";                                    
	                        }
	                    }
	                    
	                    break;
	            }
	        }
	        $contents = ob_get_contents();
	        ob_end_clean();
	        
	        $Logger->info("config_opts.php output: {$contents}");
	        
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