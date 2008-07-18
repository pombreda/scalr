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
        
        if ($farminfo)
        {
            switch ($option[0])
            {
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
                                
                            exit();
                            
                        break;
                        
                        case "master":
                            
                            switch($option[3])
                            {
                                case "ip":
                                
                                    print $db->GetOne("SELECT internal_ip FROM farm_instances WHERE isdbmaster='1' AND farmid=?", array($farm_id));   
                                
                                break;
                            }
                            
                            exit();
                            
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
                            if ($db->GetOne("SELECT * FROM farm_amis WHERE farmid=? AND ami_id=?", array($farm_id, $ami["ami_id"])))
                            {
                                $instanses = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND state='Running' AND ami_id=?", array($farm_id, $ami["ami_id"]));
                                foreach ($instanses as $instanse)
                                    print "{$instanse['internal_ip']}\n";
                                    
                                $real_servers = $db->GetAll("SELECT * FROM real_servers WHERE farmid=? AND ami_id=?", array($farm_id, $ami["ami_id"]));
                                foreach ($real_servers as $real_server)
                                    print "{$real_server['ipaddress']}\n";
                            }
                        }
                        
                        exit();
                    }
                    
                    break;
            }
        }
        
        exit();
    }
?>