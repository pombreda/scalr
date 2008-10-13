<?
    require("src/prepend.inc.php"); 
    
    if ($_SESSION['uid'] == 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));

    if (!$farminfo)
        UI::Redirect("farms_view.php");
        
	$display["title"] = "Farm&nbsp;&raquo;&nbsp;Mysql information";
	$display["farminfo"] = $farminfo;
		
	if ($display["farminfo"]["dtlastbcp"])
		$display["mysql_last_backup"] = date("d M Y \a\\t H:i:s", $display["farminfo"]["dtlastbcp"]);
                
	if ($display["farminfo"]["dtlastrebundle"])
		$display["mysql_last_bundle"] = date("d M Y \a\\t H:i:s", $display["farminfo"]["dtlastrebundle"]);
	
   	$mysql_instances = $db->GetAll("SELECT * FROM farm_instances WHERE ami_id IN (SELECT ami_id FROM ami_roles WHERE alias='mysql') AND farmid=? ORDER BY isdbmaster DESC",
   		array($farminfo['id'])
   	);
   			
   	$slave_num = 0;
   	foreach ($mysql_instances as $mysql_instance)
   	{
   		try
   		{
   			$conn = &NewADOConnection("mysqli");
            $conn->Connect($mysql_instance['external_ip'], CONFIG::$MYSQL_STAT_USERNAME, $mysql_instance['mysql_stat_password'], null);
   			$conn->SetFetchMode(ADODB_FETCH_ASSOC); 
			if ($mysql_instance['isdbmaster'] == 1)
			{
   				$r = $conn->GetRow("SHOW MASTER STATUS");
   				$MasterPosition = $r['Position'];
   				$master_ip = $mysql_instance['external_ip'];
   				$master_iid = $mysql_instance['instance_id'];
			}
   			else
   			{
   				$r = $conn->GetRow("SHOW SLAVE STATUS");
   				$SlaveNumber = ++$slave_num;
   				$SlavePosition = $r['Exec_Master_Log_Pos'];
   			}
   				
   			$display["replication_status"][$mysql_instance['instance_id']] = 
   			array(
   				"data" => $r, 
   				"MasterPosition" => $MasterPosition, 
   				"SlavePosition" => $SlavePosition,
   				"IsMaster"		=> $mysql_instance['isdbmaster'],
   				"SlaveNumber"	=> $SlaveNumber
   			);
   		}
   		catch(Exception $e)
   		{
   			$display["replication_status"][$mysql_instance['instance_id']] = array(
   				"error" => $e->getMessage()
   			);
   		}
   	}
	
   	if ($_POST)
	{
		$SNMP = new SNMP();
		
		if ($master_ip)
		{
			if ($post_run_bcp)
			{
				$SNMP->Connect($master_ip, null, $farminfo['hash']);
				$trap = vsprintf(SNMP_TRAP::MYSQL_START_BACKUP, array());
				$res = $SNMP->SendTrap($trap);
	            $Logger->info("[FarmID: {$farminfo['id']}] Sending SNMP Trap startBackup ({$trap}) to '{$master_iid}' ('{$master_ip}') complete ({$res})");
	                            
	            $db->Execute("UPDATE farms SET isbcprunning='1', bcp_instance_id='{$master_iid}' WHERE id='{$farminfo['id']}'");
	            
	            $okmsg = "Backup request successfully sent to instance";
	            UI::Redirect("farm_mysql_info.php?farmid={$farminfo['id']}");
			}
			elseif ($post_run_bundle)
			{
				$SNMP->Connect($master_ip, null, $farminfo['hash']);
				$trap = vsprintf(SNMP_TRAP::MYSQL_START_REBUNDLE, array());
				$res = $SNMP->SendTrap($trap);
	            $Logger->info("[FarmID: {$farminfo['id']}] Sending SNMP Trap startBundle ({$trap}) to '{$master_iid}' ('{$master_ip}') complete ({$res})");
	                            
	            $db->Execute("UPDATE farms SET isbundlerunning='1', bcp_instance_id='{$master_iid}' WHERE id='{$farminfo['id']}'");
	            
	            $okmsg = "Mysql data bundle request successfully sent to instance";
	            UI::Redirect("farm_mysql_info.php?farmid={$farminfo['id']}");
			}
		}
		else
			$errmsg = "There is no running mysql master instance.";
	}
   	
	require_once("src/append.inc.php");
?>