<?
	class MySQLMaintenanceProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Maintenance mysql role on farms";
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance(null, true);
            
            $Shell = ShellFactory::GetShellInstance();
            
            $mysql_ami = $db->GetOne("SELECT ami_id FROM ami_roles WHERE name='mysql'");
            $mysql_farm_amis = $db->GetAll("SELECT * FROM farm_amis WHERE ami_id='{$mysql_ami}'");
            foreach ($mysql_farm_amis as $mysql_farm_ami)
            {
                $farminfo = $db->GetRow("SELECT * FROM farms WHERE id='{$mysql_farm_ami['farmid']}'");
                
                // skip terminated farms
                if ($farminfo["status"] == 0)
                    continue;
                    
                if ($farminfo["isbcprunning"] == 1)
                {
                    Log::Log("[FarmID: {$farminfo['id']}] MySQL Backup or rebundle already running", E_USER_WARNING);
                    continue;
                }
                    
                // Backups
                if ($farminfo["mysql_bcp"] == 1)
                {
                    $timeout = $farminfo["mysql_bcp_every"]*3600;
                    if ($farminfo["dtlastbcp"]+$timeout < time())
                    {
                        Log::Log("[FarmID: {$farminfo['id']}] Need new backup", E_USER_WARNING);
                        
                        $instance = $db->GetRow("SELECT * FROM farm_instances WHERE state='Running' 
                                                    AND ami_id='{$mysql_ami}' 
                                                    AND farmid='{$farminfo['id']}' 
                                                    AND isdbmaster='0'");
                        if (!$instance)
                            $instance = $db->GetRow("SELECT * FROM farm_instances WHERE state='Running' 
                                                    AND ami_id='{$mysql_ami}' 
                                                    AND farmid='{$farminfo['id']}' 
                                                    AND isdbmaster='1'");
                            
                        if ($instance)
                        {
                            $res = $Shell->QueryRaw(CF_SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$instance['external_ip'].' "" SNMPv2-MIB::snmpTrap.12.2 SNMPv2-MIB::sysName.0 s "backup" 2>&1', true);
                            Log::Log("[FarmID: {$farminfo['id']}] Sending SNMP Trap 12.2 (startBackup) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})", E_USER_NOTICE);
                            
                            $db->Execute("UPDATE farms SET isbcprunning='1' WHERE id='{$farminfo['id']}'");
                        }
                        else 
                            Log::Log("[FarmID: {$farminfo['id']}] There is no running mysql instances for run backup procedure!", E_USER_WARNING);
                            
                        continue;
                    }
                }
                
                $timeout = $farminfo["mysql_rebundle_every"]*3600;
                if ($farminfo["dtlastrebundle"]+$timeout < time())
                {
                    Log::Log("[FarmID: {$farminfo['id']}] Need mySQL bundle procedure", E_USER_WARNING);
                    
                    // Rebundle
                    $instance = $db->GetRow("SELECT * FROM farm_instances WHERE state='Running' 
                                                        AND ami_id='{$mysql_ami}' 
                                                        AND farmid='{$farminfo['id']}' 
                                                        AND isdbmaster='1'");
                    if ($instance)
                    {
                        $res = $Shell->QueryRaw(CF_SNMPTRAP_PATH.' -v 2c -c '.$farminfo['hash'].' '.$instance['external_ip'].' "" SNMPv2-MIB::snmpTrap.12.2 SNMPv2-MIB::sysName.0 s "bundle" 2>&1', true);
                        Log::Log("[FarmID: {$farminfo['id']}] Sending SNMP Trap 12.2 (startBundle) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})", E_USER_NOTICE);
                        
                        $db->Execute("UPDATE farms SET isbcprunning='1' WHERE id='{$farminfo['id']}'");
                    }
                    else 
                        Log::Log("[FarmID: {$farminfo['id']}] There is no running mysql master instances for run bundle procedure!", E_USER_WARNING);
                }
            }
        }
        
        public function OnEndForking()
        {
            
        }
        
        public function StartThread($farminfo)
        {
            
        }
    }
?>