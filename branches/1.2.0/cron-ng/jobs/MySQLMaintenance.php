<?php

class Scalr_Cronjob_MySQLMaintenance extends Scalr_System_Cronjob_MultiProcess_DefaultWorker {

	static function getConfig () {
		return array(
			"description" => "Maintenance mysql role on farms",
			"processPool" => array(
				"workTimeout" => 180000 // 3 min
			)
		);
	}
	
	private $logger;
	
	private $db;
	
	function __construct () {
		$this->logger = LoggerManager::getLogger(__CLASS__);
		$this->db = Core::GetDBInstance();
	}
	
	function startChild () {
		// Reopen DB connection in child
		$this->db = Core::GetDBInstance(null, true);
		// Reconfigure observers;
        Scalr::ReconfigureObservers();				
	}
	
	function enqueueWork ($workQueue) {
		$this->logger->info("Fetching MySQL farm roles...");
		
		$rows = $this->db->GetAll(
			"SELECT id FROM farm_roles WHERE ami_id IN (SELECT ami_id FROM roles WHERE alias=?)", 
			array(ROLE_ALIAS::MYSQL)
		);
		$this->logger->info("Found ".count($rows)." farm roles");
		
		foreach ($rows as $row) {
			$workQueue->put($row["id"]);
		}
	}	
	
	/**
	 * 
	 * @param string $message
	 * @return void
	 */
	function handleWork ($farmRoleId) {
		
		$GLOBALS["SUB_TRANSACTIONID"] = abs(crc32(posix_getpid().$farmRoleId));
		$this->logger->info("[{$GLOBALS["SUB_TRANSACTIONID"]}] Handle farm role id: {$message}");
		
		$mysql_farm_ami = $this->db->GetRow(
			"SELECT * FROM farm_roles WHERE id = ?", 
			array($farmRoleId)
		);
		

        	
        $Shell = ShellFactory::GetShellInstance();
            
        $SNMP = new SNMP();
        	
        if ($mysql_farm_ami['replace_to_ami'] != '')
        	return;
            	
        $DBFarm = DBFarm::LoadByID($mysql_farm_ami['farmid']);
                
        // Skip terminated farms
        if ($DBFarm->Status != FARM_STATUS::RUNNING)
        	return;
                    
        // Check replication status
		$this->logger->info("[FarmID: {$DBFarm->ID}] Checking replication status");
		$instances = $this->db->GetAll(
			"SELECT * FROM farm_instances WHERE farm_roleid=? AND isdbmaster='0' AND state=?",
			array($mysql_farm_ami['id'], INSTANCE_STATE::RUNNING)
		);
		
		if (count($instances) > 0)
		{
			foreach ($instances as $instance)
			{
               	try
	   			{
	   				$this->logger->info("[FarmID: {$DBFarm->ID}] {$instance['external_ip']} -> SLAVE STATUS");
	   				
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
	   					$this->logger->warn(new FarmLogMessage($DBFarm->ID, sprintf(_("Scalr cannot connect to instance %s:3306 (%s) and check replication status. (Error (%s):%s)"), $instance['external_ip'], $instance['instance_id'], $err, socket_strerror($err))));
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
		   					Scalr::FireEvent($farminfo['id'], new MySQLReplicationFailEvent(DBInstance::LoadByID($instance['id'])));
		   				else
		   					Scalr::FireEvent($farminfo['id'], new MySQLReplicationRecoveredEvent(DBInstance::LoadByID($instance['id'])));
		   			}
	   			}
	   			catch(Exception $e)
	   			{
	   				$this->logger->warn(
	   					new FarmLogMessage(
	   						$DBFarm->ID, 
	   						"Cannot retrieve replication status. {$e->getMessage()}"
	   					)
	   				);
	   			}
            }
		}
		else
			$this->logger->info("[FarmID: {$DBFarm->ID}] There are no running slave hosts.");
                      
        //
        // Check backups and mysql bandle procedures
        //

        //Backups
		if ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BCP_ENABLED) && $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BCP_EVERY) != 0)
		{
			if ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_IS_BCP_RUNNING) == 1)
			{
                // Wait for timeout time * 2 (Example: NIVs problem with big mysql snapshots)
                // We must wait for running bundle process.
               	$bcp_timeout = $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BCP_EVERY)*(60*2);
                if ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_LAST_BCP_TS)+$bcp_timeout < time())
                   	$bcp_timeouted = true;
                    	
	            if ($bcp_timeouted)
	            {
	               	$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BCP_RUNNING, 0);
	               	$this->logger->info("[FarmID: {$DBFarm->ID}] MySQL Backup already running. Timeout. Clear lock.");
	            }
			}
            else
            {
               	$timeout = $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BCP_EVERY)*60;
                if ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_LAST_BCP_TS)+$timeout < time())
                {
                    $this->logger->info("[FarmID: {$DBFarm->ID}] Need new backup");
                        
                  	$instance = $this->db->GetRow("SELECT * FROM farm_instances WHERE state=? AND farm_roleid=? ORDER BY isdbmaster ASC", 
                   		array(INSTANCE_STATE::RUNNING, $mysql_farm_ami['id'])
                   	);
							                            
                    if ($instance)
                    {
                        $DBInstance = DBInstance::LoadByID($instance['id']);
                        $DBInstance->SendMessage(new MakeMySQLBackupScalrMessage());
                        	
                        $DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BCP_RUNNING, 1);
                        $DBFarm->SetSetting(DBFarm::SETTING_MYSQL_BCP_INSTANCE_ID, $instance['instance_id']);
                    }
                    else 
                        $this->logger->info("[FarmID: {$DBFarm->ID}] There is no running mysql instances for run backup procedure!");
                }
            }
		}
		
		if ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BUNDLE_ENABLED) && $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BUNDLE_EVERY) != 0)
		{
			if ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_IS_BUNDLE_RUNNING) == 1)
            {	                    
                // Wait for timeout time * 2 (Example: NIVs problem with big mysql snapshots)
                // We must wait for running bundle process.
               	$bundle_timeout = $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BUNDLE_EVERY)*(3600*2);
	            if ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_LAST_BUNDLE_TS)+$bundle_timeout < time())
	               	$bundle_timeouted = true;
                    	
	            if ($bundle_timeouted)
	            {
	               	$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BUNDLE_RUNNING, 0);
	               	$this->logger->info("[FarmID: {$DBFarm->ID}] MySQL Bundle already running. Timeout. Clear lock.");
	            }
            }
            else
            {
				$timeout = $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_BUNDLE_EVERY)*3600;
				if ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_LAST_BUNDLE_TS)+$timeout < time())
				{
	                $this->logger->info("[FarmID: {$DBFarm->ID}] Need mySQL bundle procedure");
	                    
	              	// Rebundle
	           		$instance = $this->db->GetRow(
	           			"SELECT * FROM farm_instances WHERE state=? AND farm_roleid=? AND isdbmaster='1'", 
	           			array(INSTANCE_STATE::RUNNING, $mysql_farm_ami['id'])
	           		);
	           		
	           		try
	           		{
						if ($instance)
		                {
		                	$DBInstance = DBInstance::LoadByID($instance['id']);
	                        $DBInstance->SendMessage(new MakeMySQLDataBundleScalrMessage());
	                        
	                        $DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BUNDLE_RUNNING, 1);
	                        $DBFarm->SetSetting(DBFarm::SETTING_MYSQL_BUNDLE_INSTANCE_ID, $instance['instance_id']);
		                }
		                else 
		                    $this->logger->info("[FarmID: {$DBFarm->ID}] There is no running mysql master instances for run bundle procedure!");
	           		}
	           		catch (Exception $e)
	           		{
	           			$this->logger->fatal($e->getMessage());
	           		}
				}
	        }
		}
	}
}