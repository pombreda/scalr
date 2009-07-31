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
			
			$this->ThreadArgs = $db->GetAll("SELECT * FROM farm_amis WHERE ami_id IN (SELECT ami_id FROM ami_roles WHERE alias=?)", 
            	array(ROLE_ALIAS::MYSQL)
            );
        }
        
        public function OnEndForking()
        {
            
        }
        
        public function StartThread($mysql_farm_ami)
        {
        	// Reconfigure observers;
        	Scalr::ReconfigureObservers();
        	
        	$db = Core::GetDBInstance();
            
            $Shell = ShellFactory::GetShellInstance();
            
            $SNMP = new SNMP();
        	
        	if ($mysql_farm_ami['replace_to_ami'] != '')
                return;
            	
            $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", 
                array($mysql_farm_ami['farmid'])
			);
                
			//skip terminated farms
			if ($farminfo["status"] == FARM_STATUS::TERMINATED)
				return;
                    
			//
            // Check replication status
            //
			$this->Logger->info("[FarmID: {$farminfo['id']}] Checking replication status");
			$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND ami_id=? AND isdbmaster='0' AND state=?",
                array($farminfo['id'], $mysql_farm_ami['ami_id'], INSTANCE_STATE::RUNNING)
			);
			if (count($instances) > 0)
			{
                foreach ($instances as $instance)
                {
                	try
	   				{
	   					$this->Logger->info("[FarmID: {$farminfo['id']}] {$instance['external_ip']} -> SLAVE STATUS");
	   					
	   					$sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
						@socket_set_nonblock($sock);
						
	   					$time = time();
	   					$res = true;
					    while (!@socket_connect($sock,$instance['external_ip'], 3306))
					    {
							$err = @socket_last_error($sock);
							if ($err == 115 || $err == 114 || $err == 36 || $err == 37)
							{
								if ((time() - $time) >= 5)
								{
									@socket_close($sock);
					        		$res = false;
					        		break;
					        	}
					        	
					        	sleep(1);
					        	continue;
					      	}
					      	else
					      	{
					      		$res = ($err == 56) ? true : false;
					      		break;
					      	}
					    }
						
	   					if (!$res)
	   					{
	   						$this->Logger->warn(new FarmLogMessage($farminfo['id'], sprintf(_("Scalr cannot connect to instance %s:3306 (%s) and check replication status. (Error (%s):%s)"), $instance['external_ip'], $instance['instance_id'], $err, socket_strerror($err))));
	   						continue;
	   					}
	   					
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
                            $DBInstance = DBInstance::LoadByID($instance['id']);
                            $DBInstance->SendMessage(new MakeMySQLBackupScalrMessage());
                        	
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
	                        $DBInstance = DBInstance::LoadByID($instance['id']);
                            $DBInstance->SendMessage(new MakeMySQLDataBundleScalrMessage());
	                        
	                        $db->Execute("UPDATE farms SET isbundlerunning='1', bcp_instance_id='{$instance['instance_id']}' WHERE id='{$farminfo['id']}'");
	                    }
	                    else 
	                        $this->Logger->info("[FarmID: {$farminfo['id']}] There is no running mysql master instances for run bundle procedure!");
					}
                }
			}
        }
    }
?>