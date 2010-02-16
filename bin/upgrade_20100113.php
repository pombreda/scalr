<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20100113();
	$ScalrUpdate->Run();
	
	class Update20100113
	{
		function Run()
		{
			$this->UpdateAutosnapSettings();
		}
		
		function UpdateAutosnapSettings()
		{
			global $db;
			
			$time = microtime(true);
			
			$db->BeginTrans();
			
			print "Altering database...\n";
			
			// create new  columns objectid and object_type
			$db->Execute("alter table `autosnap_settings` 
				add column `objectid` varchar(20) NULL after `arrayid`, 
   				add column `object_type` varchar(20) NULL after `objectid`"
			);
			
			$db->Execute("CREATE TABLE IF NOT EXISTS `scheduler_tasks` (
				  `id` int(11) NOT NULL auto_increment,
				  `task_name` varchar(255) default NULL,
				  `task_type` varchar(255) default NULL,
				  `target_id` varchar(255) default NULL,
				  `target_type` varchar(255) default NULL,
				  `start_time_date` datetime default NULL,
				  `end_time_date` datetime default NULL,
				  `last_start_time` datetime default NULL,
				  `restart_every` int(11) default '0',
				  `task_config` text,
				  `order_index` int(11) default NULL,
				  `client_id` int(11) default NULL,
				  `status` varchar(11) default NULL,
				  PRIMARY KEY  (`id`)
				) ENGINE=INNODB AUTO_INCREMENT=1
			");
			
			$db->Execute("CREATE TABLE IF NOT EXISTS `instances_history` (
				  `id` int(11) NOT NULL auto_increment,
				  `instance_id` varchar(20) default NULL,
				  `dtlaunched` int(11) default NULL,
				  `dtterminated` int(11) default NULL,
				  `uptime` int(11) default NULL,
				  `instance_type` varchar(20) default NULL,
				  PRIMARY KEY  (`id`)
				) ENGINE=MyISAM AUTO_INCREMENT=1");
			
			// create rds_snap_info table
			$db->Execute("CREATE TABLE IF NOT EXISTS `rds_snaps_info` (
   				`id` INT(11) NOT NULL AUTO_INCREMENT,
   				`snapid` VARCHAR(50) DEFAULT NULL,
   				`comment` VARCHAR(255) DEFAULT NULL,
   				`dtcreated` DATETIME DEFAULT NULL,   
   				`region` VARCHAR(255) DEFAULT 'us-east-1',
   				`autosnapshotid` INT(11) DEFAULT '0',
   				PRIMARY KEY  (`id`)
 				) ENGINE=INNODB AUTO_INCREMENT=1
 			");

			print "done.\n";
			
			print "Upgrading autosnap_settings table...\n";
			
			try
			{				
				$snapshotSettings = $db->GetAll("SELECT id,volumeid, arrayid FROM `autosnap_settings`");
				
			// converts data from old volumeid and arrayid to new objectid and set object_type
				foreach($snapshotSettings as $row)
				{					
					if($row['volumeid']) // it's EBS 
					{
						$db->Execute("UPDATE `autosnap_settings` SET
							`object_type`		= ?,
							`objectid`		= ?
							WHERE `id` = ?
						", array(
							AUTOSNAPSHOT_TYPE::EBSSnap,
							$row['volumeid'],
							$row['id']
						));
					
					}
					elseif($row['arrayid']) // it's an EBS array
					{
						$db->Execute("UPDATE `autosnap_settings` SET
							`object_type`		= ?,
							`objectid`		= ?
							WHERE `id` = ?
						", array(
							AUTOSNAPSHOT_TYPE::EBSArraySnap,
							$row['arrayid'],
							$row['id']
						));
						
					}					
					
				}		
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				die("ERROR (autosnap_settings): {$e->getMessage()}");
			}

			try
			{	
			// drop unuseful volumeid &  arrayid columns
				$db->Execute("alter table `autosnap_settings` 
						drop column `volumeid`,
						drop column `arrayid` 
				");

			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				die("ERROR (autosnap_settings): {$e->getMessage()}");
			}
			
			$db->CommitTrans();
			
			print "tables successfully upgraded.\n";			
			
			$t = round(microtime(true)-$time, 2);
			
			print "Upgrade process takes {$t} seconds";
		}
	}
?>