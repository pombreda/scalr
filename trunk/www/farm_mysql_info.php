<?
    require("src/prepend.inc.php"); 
    
    if ($_SESSION['uid'] == 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_farmid));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_farmid, $_SESSION['uid']));

    if (!$farminfo)
        UI::Redirect("farms_view.php");

    $DBFarm = DBFarm::LoadByID($farminfo['id']);
        
	$display["title"] = "Farm '<a href='farms_view.php?id={$DBFarm->ID}'>{$DBFarm->Name}</a>'&nbsp;&raquo;&nbsp;Mysql information";		
	
	$display['mysql_data_storage_engine'] = $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_DATA_STORAGE_ENGINE);
	$display['mysql_master_ebs_volume_id'] = $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_MASTER_EBS_VOLUME_ID);
	
	if ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_LAST_BCP_TS))
		$display["mysql_last_backup"] = date("d M Y \a\\t H:i:s", $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_LAST_BCP_TS));
                
	if ($DBFarm->GetSetting(DBFarm::SETTING_MYSQL_LAST_BUNDLE_TS))
		$display["mysql_last_bundle"] = date("d M Y \a\\t H:i:s", $DBFarm->GetSetting(DBFarm::SETTING_MYSQL_LAST_BUNDLE_TS));
	
   	$mysql_instances = $db->GetAll("SELECT * FROM farm_instances WHERE ami_id IN (SELECT ami_id FROM roles WHERE alias=?) AND farmid=? AND state=? ORDER BY isdbmaster DESC",
   		array(ROLE_ALIAS::MYSQL, $DBFarm->ID, INSTANCE_STATE::RUNNING)
   	);
   	
   	$slave_num = 0;
   	foreach ($mysql_instances as $mysql_instance)
   	{
   		if ($mysql_instance['isdbmaster'] == 1)
		{			
			$DBFarmRole = DBFarmRole::LoadByID($mysql_instance['farm_roleid']);
			
			$display['mysql_bundle_running'] = $DBFarmRole->GetFarmObject()->GetSetting(DBFarm::SETTING_MYSQL_IS_BUNDLE_RUNNING);
			$display['mysql_bundle_instance_id'] = $DBFarmRole->GetFarmObject()->GetSetting(DBFarm::SETTING_MYSQL_BUNDLE_INSTANCE_ID);
			
   			if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_PMA_USER))
   				$display['mysql_pma_credentials'] = true;
   			else
   			{
   				$time = $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_TIME);   				
   				if ($time)
   				{
   					if ($time+3600 < time())
   						$errmsg = _("Scalr didn't receive auth info from MySQL instance. Please check that MySQL running and Scalr has access to it.");
   					else
   						$display['mysql_pma_processing_access_request'] = true;
   				}
   			}
		}
   		
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
   				
   				//TODO:
   				$DBFarmRole = DBFarmRole::LoadByID($mysql_instance['farm_roleid']);
   				if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_PMA_USER))
   					$display['mysql_pma_credentials'] = true;
   				else
   				{
   					$errmsg = $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_ERROR);
   					if (!$errmsg)
   					{
	   					$time = $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_TIME);
	   					if ($time)
	   					{
	   						if ($time+3600 < time())
	   							$errmsg = _("Scalr didn't receive auth info from MySQL instance. Please check that MySQL running and Scalr has access to it.");
	   						else
	   							$display['mysql_pma_processing_access_request'] = true;
	   					}
   					}
   				}
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
		$req_farmid = (int)$req_farmid;
		
		if ($post_remove_mysql_data_bundle)
		{
			if ($post_remove_mysql_data_bundle_confirm)
			{
				
			}
			else
			{
				$Smarty->assign($display);
			    $Smarty->display("mysql_data_bundle_clear_confirm.tpl");
				exit();
			}
		}
		
		if ($post_pma_request_credentials)
		{
			$instance_id = $db->GetOne("SELECT id FROM farm_instances WHERE farmid=? AND isdbmaster='1'", array($req_farmid));
			
			if ($instance_id)
			{
				$DBInstance = DBInstance::LoadByID($instance_id);	
				$DBFarmRole = $DBInstance->GetDBFarmRoleObject();
				
				$time = $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_TIME); 
				
				if (!$time || $time+3600 < time())
				{
					$DBInstance->SendMessage(new BasicScalrMessage(BASIC_MESSAGE_NAMES::MYSQL_PMA_CREDENTIALS, $DBFarmRole->ID, CONFIG::$PMA_INSTANCE_IP_ADDRESS));
					$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_TIME, time());
					$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_ERROR, "");
					
					$okmsg = _("MySQL access credentials for PMA requested. Please wait a few minutes...");
					UI::Redirect("/farm_mysql_info.php?farmid={$req_farmid}");
				}
				else
				{
					$errmsg = _("MySQL access credentials for PMA already requested. Please wait...");
					UI::Redirect("/farm_mysql_info.php?farmid={$req_farmid}");
				}
			}
			else
			{
				$errmsg = _("There is no running MySQL master. Please wait until master starting up.");
				UI::Redirect("/farm_mysql_info.php?farmid={$req_farmid}");
			}
		}
		
		if ($post_pma_launch)
			UI::Redirect("/externals/pma_redirect.php?farmid={$req_farmid}");
		
		if ($post_update_volumeid && $_SESSION['uid'] == 0)
		{
			$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_MASTER_EBS_VOLUME_ID, $post_mysql_master_ebs);
			
			$okmsg = _("Volume ID successfully updated");
	        UI::Redirect("farm_mysql_info.php?farmid={$DBFarm->ID}");
		}
		
		if ($post_run_bcp)
		{
			$mysql_slave = $db->GetRow("SELECT * FROM farm_instances WHERE farmid=? AND state=? AND isdbmaster='0' AND ami_id IN (SELECT ami_id FROM roles WHERE alias=?)",
				array($req_farmid, INSTANCE_STATE::RUNNING, ROLE_ALIAS::MYSQL)
			);
			
			if (!$mysql_slave)
				$errmsg = _("There is no running mysql slave instance.");
			else
			{
				$DBInstance = DBInstance::LoadByID($mysql_slave['id']);
				$DBInstance->SendMessage(new MakeMySQLBackupScalrMessage());
				
				$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BCP_RUNNING, 1);
				$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_BCP_INSTANCE_ID, $mysql_slave['instance_id']);
					            
	            $okmsg = _("Backup request successfully sent to instance");
	            UI::Redirect("farm_mysql_info.php?farmid={$DBFarm->ID}");
			}
		}
		elseif ($post_run_bundle)
		{
			$mysql_master = $db->GetRow("SELECT * FROM farm_instances WHERE farmid=? AND state=? AND isdbmaster='1' AND ami_id IN (SELECT ami_id FROM roles WHERE alias=?)",
				array($req_farmid, INSTANCE_STATE::RUNNING, ROLE_ALIAS::MYSQL)
			);
			
			if (!$mysql_master)
				$errmsg = _("There is no running mysql master instance.");
			else
			{
				$DBInstance = DBInstance::LoadByID($mysql_master['id']);
				$DBInstance->SendMessage(new MakeMySQLDataBundleScalrMessage());

				$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_IS_BUNDLE_RUNNING, 1);
				$DBFarm->SetSetting(DBFarm::SETTING_MYSQL_BUNDLE_INSTANCE_ID, $mysql_master['instance_id']);
				
	            $okmsg = _("Mysql data bundle request successfully sent to instance");
	            UI::Redirect("farm_mysql_info.php?farmid={$DBFarm->ID}");
			}
		}
	}
   	
	require_once("src/append.inc.php");
?>