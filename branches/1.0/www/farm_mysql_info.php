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
	
   	$mysql_instances = $db->GetAll("SELECT * FROM farm_instances WHERE ami_id IN (SELECT ami_id FROM ami_roles WHERE alias='mysql') AND farmid=? AND state=? ORDER BY isdbmaster DESC",
   		array($farminfo['id'], INSTANCE_STATE::RUNNING)
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
   				"error" => ($e->msg) ? $e->msg : $e->getMessage(),
   				"IsMaster"		=> $mysql_instance['isdbmaster']
   			);
   		}
   	}
	
   	if ($_POST)
	{
		if ($post_run_bcp)
		{
			$mysql_slave = $db->GetRow("SELECT * FROM farm_instances WHERE farmid=? AND state=? AND isdbmaster='0' AND ami_id IN (SELECT ami_id FROM ami_roles WHERE alias='mysql')",
				array($req_farmid, INSTANCE_STATE::RUNNING)
			);
			
			if (!$mysql_slave)
				$errmsg = _("There is no running mysql slave instance.");
			else
			{
				$DBInstance = DBInstance::LoadByID($mysql_slave['id']);
				$DBInstance->SendMessage(new MakeMySQLBackupScalrMessage());
				
				$db->Execute("UPDATE farms SET isbcprunning='1', bcp_instance_id='{$mysql_slave['instance_id']}' WHERE id='{$farminfo['id']}'");
	            
	            $okmsg = _("Backup request successfully sent to instance");
	            UI::Redirect("farm_mysql_info.php?farmid={$farminfo['id']}");
			}
		}
		elseif ($post_run_bundle)
		{
			$mysql_master = $db->GetRow("SELECT * FROM farm_instances WHERE farmid=? AND state=? AND isdbmaster='1' AND ami_id IN (SELECT ami_id FROM ami_roles WHERE alias='mysql')",
				array($req_farmid, INSTANCE_STATE::RUNNING)
			);
			
			if (!$mysql_master)
				$errmsg = _("There is no running mysql master instance.");
			else
			{
				$DBInstance = DBInstance::LoadByID($mysql_master['id']);
				$DBInstance->SendMessage(new MakeMySQLDataBundleScalrMessage());
	                            
	            $db->Execute("UPDATE farms SET isbundlerunning='1', bcp_instance_id='{$mysql_master['instance_id']}' WHERE id='{$farminfo['id']}'");
	            
	            $okmsg = _("Mysql data bundle request successfully sent to instance");
	            UI::Redirect("farm_mysql_info.php?farmid={$farminfo['id']}");
			}
		}
	}
   	
	require_once("src/append.inc.php");
?>