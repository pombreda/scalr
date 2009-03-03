<?
	class MySQLMaintenanceProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Maintenance mysql role on farms";
        public $Logger;
        
        public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = LoggerManager::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
            
            // Reconfigure observers;
        	Scalr::ReconfigureObservers();
            
            $Shell = ShellFactory::GetShellInstance();
            
            $SNMP = new SNMP();
            
            $mysql_farm_amis = $db->GetAll("SELECT * FROM farm_amis WHERE ami_id IN (SELECT ami_id FROM ami_roles WHERE alias=?)", 
            	array(ROLE_ALIAS::MYSQL)
            );
            
            foreach ($mysql_farm_amis as $mysql_farm_ami)
            {
                if ($mysql_farm_ami['replace_to_ami'] != '')
                	continue;
            	
            	$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", 
                	array($mysql_farm_ami['farmid'])
                );
                
                // skip terminated farms
                if ($farminfo["status"] == 0)
                    continue;
                    
                //
                // Check replication status
                //
                $this->Logger->debug("[FarmID: {$farminfo['id']}] Checking replication status");
                $instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? 
                	AND ami_id=? AND isdbmaster='0' AND state=?",
                	array($farminfo['id'], $mysql_farm_ami['ami_id'], INSTANCE_STATE::RUNNING)
                );
                if (count($instances) > 0)
                {
                	foreach ($instances as $instance)
                	{
                		try
		   				{
		   					// Connect to Mysql on slave
		   					$conn = &NewADOConnection("mysqli");
		                    $conn->Connect($instance['external_ip'], CONFIG::$MYSQL_STAT_USERNAME, $instance['mysql_stat_password'], null);
		   					$conn->SetFetchMode(ADODB_FETCH_ASSOC); 
		                    
		   					// Get Slave status
		   					$r = $conn->GetRow("SHOW SLAVE STATUS");
		   					
		   					// Check slave replication running or not
		   					if ($r['Slave_IO_Running'] == 'Yes' && $r['Slave_SQL_Running'] == 'Yes')
		   						$replication_status = 1;
		   					else
		   						$replication_status = 0;
		   						
			   				if ($replication_status != $instance['mysql_replication_status'])
			   				{
			   					if ($replication_status == 0)
			   						Scalr::FireEvent($farminfo['id'], new MySQLReplicationFailEvent($instance));
			   					else
			   						Scalr::FireEvent($farminfo['id'], new MySQLReplicationRecoveredEvent($instance));
			   				}
		   				}
		   				catch(Exception $e)
		   				{
		   					$this->Logger->warn(
		   						new FarmLogMessage(
		   							$farminfo['id'], 
		   							"Cannot retrieve replication status. {$e->getMessage()}"
		   						)
		   					);
		   				}
                	}
                }
                else
                	$this->Logger->info("[FarmID: {$farminfo['id']}] There are no running slave hosts.");
                      
                //
                // Check backups and mysql bandle procedures
                //
                                        
                // Backups
                if ($farminfo["mysql_bcp"] == 1 && $farminfo["mysql_bcp_every"] != 0)
                {
	                if ($farminfo["isbcprunning"] == 1)
	                {
	                    // Wait for timeout time * 2 (Example: NIVs problem with big mysql snapshots)
	                    // We must wait for running bundle process.
	                	$bcp_timeout = $farminfo["mysql_bcp_every"]*(60*2);
	                    if ($farminfo["dtlastbcp"]+$bcp_timeout < time())
	                    	$bcp_timeouted = true;
	                    	
		                if ($bcp_timeouted)
		                {
		                	$db->Execute("UPDATE farms SET isbcprunning='0' WHERE id=?", array($farminfo["id"]));
		                	
		                	$this->Logger->info("[FarmID: {$farminfo['id']}] MySQL Backup already running. Timeout. Clear lock.");
		                }
	                }
                	else
                	{
	                	$timeout = $farminfo["mysql_bcp_every"]*60;
	                    if ($farminfo["dtlastbcp"]+$timeout < time())
	                    {
	                        $this->Logger->info("[FarmID: {$farminfo['id']}] Need new backup");
	                        
	                    	$instance = $db->GetRow("SELECT * FROM farm_instances WHERE state=? 
	                        	AND ami_id='{$mysql_farm_ami['ami_id']}' AND farmid='{$farminfo['id']}' 
								ORDER BY isdbmaster ASC
							", array(INSTANCE_STATE::RUNNING));
								
							if (!$instance)
							{
								//TODO: Make this query better
								$instance = $db->GetRow("SELECT * FROM farm_instances WHERE state=? 
	                            	AND role_name IN ('mysql', 'mysql64', 'mysqllvm', 'mysqllvm64') AND farmid='{$farminfo['id']}' 
									ORDER BY isdbmaster ASC
								", array(INSTANCE_STATE::RUNNING));							
							}
	                            
	                        if ($instance)
	                        {
	                            $SNMP->Connect($instance['external_ip'], null, $farminfo['hash']);
	                            $trap = vsprintf(SNMP_TRAP::MYSQL_START_BACKUP, array());
	                        	$res = $SNMP->SendTrap($trap);
	                            $this->Logger->info("[FarmID: {$farminfo['id']}] Sending SNMP Trap startBackup ({$trap}) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})");
	                            
	                            $db->Execute("UPDATE farms SET isbcprunning='1', bcp_instance_id='{$instance['instance_id']}' WHERE id='{$farminfo['id']}'");
	                        }
	                        else 
	                            $this->Logger->info("[FarmID: {$farminfo['id']}] There is no running mysql instances for run backup procedure!");
	                    }
                	}
                }
                
                if ($farminfo["mysql_bundle"] == 1 && $farminfo["mysql_rebundle_every"] != 0)
                {
	                if ($farminfo["isbundlerunning"] == 1)
	                {	                    
	                    // Wait for timeout time * 2 (Example: NIVs problem with big mysql snapshots)
	                    // We must wait for running bundle process.
	                	$bundle_timeout = $farminfo["mysql_rebundle_every"]*(3600*2);
		                if ($farminfo["dtlastrebundle"]+$bundle_timeout < time())
		                	$bundle_timeouted = true;
	                    	
		                if ($bundle_timeouted)
		                {
		                	$db->Execute("UPDATE farms SET isbundlerunning='0' WHERE id=?", array($farminfo["id"]));
		                	
		                	$this->Logger->info("[FarmID: {$farminfo['id']}] MySQL Bundle already running. Timeout. Clear lock.");
		                }
	                }
                	else
                	{
	                	$timeout = $farminfo["mysql_rebundle_every"]*3600;
		                if ($farminfo["dtlastrebundle"]+$timeout < time())
		                {
		                    $this->Logger->info("[FarmID: {$farminfo['id']}] Need mySQL bundle procedure");
		                    
		                	// Rebundle
		               		$instance = $db->GetRow("SELECT * FROM farm_instances WHERE state=? 
	                        	AND ami_id='{$mysql_farm_ami['ami_id']}' AND farmid='{$farminfo['id']}' 
								AND isdbmaster='1'
							", array(INSTANCE_STATE::RUNNING));

		                	if (!$instance)
							{
								//TODO: Make this query better
								$instance = $db->GetRow("SELECT * FROM farm_instances WHERE state=? 
	                            	AND role_name IN ('mysql', 'mysql64', 'mysqllvm', 'mysqllvm64') AND farmid='{$farminfo['id']}' 
									AND isdbmaster='1'
								", array(INSTANCE_STATE::RUNNING));							
							}
							
							if ($instance)
		                    {
		                        $SNMP->Connect($instance['external_ip'], null, $farminfo['hash']);
	                            $trap = vsprintf(SNMP_TRAP::MYSQL_START_REBUNDLE, array());
	                        	$res = $SNMP->SendTrap($trap);
		                        $this->Logger->info("[FarmID: {$farminfo['id']}] Sending SNMP Trap startBundle ({$res}) to '{$instance['instance_id']}' ('{$instance['external_ip']}') complete ({$res})");
		                        
		                        $db->Execute("UPDATE farms SET isbundlerunning='1', bcp_instance_id='{$instance['instance_id']}' WHERE id='{$farminfo['id']}'");
		                    }
		                    else 
		                        $this->Logger->info("[FarmID: {$farminfo['id']}] There is no running mysql master instances for run bundle procedure!");
						}
	                }
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