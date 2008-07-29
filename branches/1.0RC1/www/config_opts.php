<?
    define("NO_AUTH", true);
    define("NO_TEMPLATES", true);
    include("src/prepend.inc.php");

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
            	case "roles":
            		
            		switch($option[1])
                    {
                        case "list":
                        	
                        $farm_amis = $db->GetAll("SELECT ami_id FROM farm_amis WHERE farmid='{$farm_id}'");
                        foreach ($farm_amis as $farm_ami)
                        	print $db->GetOne("SELECT name FROM ami_roles WHERE ami_id='{$farm_ami['ami_id']}'")."\n";
                        	
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
                    $amis = $db->GetAll("SELECT ami_id FROM ami_roles WHERE alias=?", array($role));
                    
                    if ($amis)
                    {
                        foreach ($amis as $ami)
                        {
							$instanses = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state='Running' AND ami_id=?", array($farm_id, $ami["ami_id"]));
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
?>