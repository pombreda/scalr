<?php
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new UpdateTo1_2();
	$ScalrUpdate->Run();
	
	class UpdateTo1_2
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			
			$this->db = $db;
			
			$this->Prepare();
			
			$this->Step1();
			$this->Step2();
			$this->Step3();
			$this->Step4();
			$this->Step5();
			$this->Step6();
			$this->Step7();
			$this->Step8();
			$this->Step9();
			$this->Step10();
			$this->Step11();
			
		}
		
		private function Prepare()
		{
			
		}
		
		private function Step1()
		{
			print "Step 1:\n";
			
			$this->db->BeginTrans();
			try
			{
				$this->db->Execute("CREATE TABLE `events` (
				  `id` int(11) NOT NULL auto_increment,
				  `farmid` int(11) default NULL,
				  `type` varchar(25) default NULL,
				  `dtadded` datetime default NULL,
				  `message` varchar(255) default NULL,
				  `ishandled` tinyint(1) default '0',
				  `short_message` varchar(255) default NULL,
				  `event_object` text,
				  `event_id` varchar(36) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `farmid` (`farmid`)
				) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;");
				
				$this->db->Execute("alter table `farm_role_scripts` drop key `UniqueIndex`;");
				$this->db->Execute("alter table `farm_instances` add column `farm_roleid` int(11) NULL after `dtshutdownscheduled`;");
				$this->db->Execute("alter table `farm_role_options` add column `farm_roleid` int(11) NULL after `hash`;");
				$this->db->Execute("alter table `farm_role_scripts` add column `farm_roleid` int(11) NULL after `order_index`;");
				$this->db->Execute("alter table `farm_ebs` add column `farm_roleid` int(11) NULL after `mountpoint`;");
				$this->db->Execute("alter table `ebs_arrays` add column `farm_roleid` int(11) NULL after `region`;");
				$this->db->Execute("alter table `elastic_ips` add column `farm_roleid` int(11) NULL after `instance_index`;");
				$this->db->Execute("alter table `vhosts` add column `farm_roleid` int(11) NULL after `role_name`;");
				$this->db->Execute("alter table `farm_instances` add index `farm_roleid` (`farm_roleid`);");
				$this->db->Execute("alter table `farm_role_options` add index `farmid` (`farmid`);");
				$this->db->Execute("alter table `farm_role_options` add index `farm_roleid` (`farm_roleid`);");
				$this->db->Execute("alter table `farm_role_scripts` add unique `UniqueIndex` (`scriptid`, `farmid`, `event_name`, `farm_roleid`);");
				$this->db->Execute("alter table `farm_ebs` add index `farmid` (`farmid`);");
				$this->db->Execute("alter table `farm_ebs` add index `farm_roleid` (`farm_roleid`);");
				$this->db->Execute("alter table `ebs_arrays` add index `farm_roleid` (`farm_roleid`);");
				$this->db->Execute("alter table `ebs_arrays` add index `farmid` (`farmid`);");
				$this->db->Execute("alter table `elastic_ips` add index `farmid` (`farmid`);");
				$this->db->Execute("alter table `elastic_ips` add index `farm_roleid` (`farm_roleid`);");
				$this->db->Execute("alter table `vhosts` add index `farm_roleid` (`farm_roleid`);");
				$this->db->Execute("alter table `ami_roles` add column `default_ssh_port` int(5) DEFAULT '22' NULL after `region`;");
			}
			catch(Exception $e)
			{
				$this->db->RollbackTrans();
				print "ERROR: {$e->getMessage()}\n";
				$error = true;
			}
			
			if (!$error)
			{
				$this->db->CommitTrans();
				
				print "OK.\n";
			}
		} 
		
		private function Step2()
		{
			print "Step 2:\n";
			
			try
			{
				$farm_roles = $this->db->GetAll("SELECT id, instance_type, avail_zone FROM farm_amis");
				foreach ($farm_roles as $farm_role)
				{
					if ($farm_role['instance_type'])
					{
						$this->db->Execute("REPLACE INTO farm_role_settings SET name=?, value=?, farm_roleid=?",
							array('aws.instance_type', $farm_role['instance_type'], $farm_role['id'])
						);
					}
						
					if ($farm_role['avail_zone'])
					{
						$this->db->Execute("REPLACE INTO farm_role_settings SET name=?, value=?, farm_roleid=?",
							array('aws.availability_zone', $farm_role['avail_zone'], $farm_role['id'])
						);
					}
				}
				
				$this->db->Execute("alter table `farm_amis` drop column `instance_type`, drop column `avail_zone`");
			}
			catch(Exception $e)
			{
				$this->db->RollbackTrans();
				print "ERROR: {$e->getMessage()}\n";
				$error = true;
			}
			
			if (!$error)
			{
				$this->db->CommitTrans();
				
				print "OK.\n";
			}
		}
		
		private function Step3()
		{
			$this->db->BeginTrans();
			print "Step 3:\n";
			
			try
			{
				$farm_instances = $this->db->Execute("SELECT farmid, instance_id, id, ami_id FROM farm_instances");
				while ($farm_instance = $farm_instances->FetchRow())
				{
					try
					{
						$DBFarmRole = DBFarmRole::Load($farm_instance['farmid'], $farm_instance['ami_id']);
						
						$this->db->Execute("UPDATE farm_instances SET farm_roleid=? WHERE id=?", array(
							$DBFarmRole->ID, $farm_instance['id']
						));
					}	
					catch (Exception $e)
					{
						print "Error with instance {$farm_instance['instance_id']} on farm {$farm_instance['farmid']}: {$e->getMessage()}";
						continue;
					}
				}
			}
			catch(Exception $e)
			{
				$this->db->RollbackTrans();
				print "ERROR: {$e->getMessage()}\n";
				$error = true;
			}
			
			if (!$error)
			{
				$this->db->CommitTrans();
				
				print "OK.\n";
			}
		}
		
		private function Step4()
		{
			$this->db->BeginTrans();
			print "Step 4:\n";
			
			try
			{
				$r = 0;
				$farm_options = $this->db->Execute("SELECT farmid, ami_id, id FROM farm_role_options");
				while ($farm_option = $farm_options->FetchRow())
				{
					$farminfo = $this->db->GetRow("SELECT * FROM farms WHERE id=?", array($farm_option['farmid']));
					if ($farminfo)
					{
						try
						{
							$DBFarmRole = DBFarmRole::Load($farm_option['farmid'], $farm_option['ami_id']);
							
							$this->db->Execute("UPDATE farm_role_options SET farm_roleid=? WHERE id=?", array(
								$DBFarmRole->ID, $farm_option['id']
							));
						}	
						catch (Exception $e)
						{
							//print "Error with option {$farm_option['id']} on farm {$farm_option['farmid']}: {$e->getMessage()}\n";
							$this->db->Execute("DELETE FROM farm_role_options WHERE id=?", array($farm_option['id']));
							$r++;
						}
					}
					else
					{
						$this->db->Execute("DELETE FROM farm_role_options WHERE id=?", array($farm_option['id']));
						$r++;
					}
				}
				
			}
			catch(Exception $e)
			{
				$this->db->RollbackTrans();
				print "ERROR: {$e->getMessage()}\n";
				$error = true;
			}
			
			if (!$error)
			{
				$this->db->CommitTrans();
				
				print "OK.\n";
			}
		}
		
		private function Step5()
		{
			$this->db->BeginTrans();
			print "Step 5:\n";
			
			try
			{
				$r = 0;
				$farm_ebss = $this->db->Execute("SELECT farmid, role_name, id, volumeid FROM farm_ebs");
				while ($farm_ebs = $farm_ebss->FetchRow())
				{
					try
					{
						$farminfo = $this->db->GetRow("SELECT * FROM farms WHERE id=?", array($farm_ebs['farmid']));
						if ($farminfo)
						{
							$ami_id = $this->db->GetOne("SELECT ami_id FROM ami_roles WHERE name=? AND (clientid='0' OR clientid=?)",
								array($farm_ebs['role_name'], $farminfo['clientid'])
							);
							
							if ($ami_id)
							{
								$DBFarmRole = DBFarmRole::Load($farm_ebs['farmid'], $ami_id);
								
								$this->db->Execute("UPDATE farm_ebs SET farm_roleid=? WHERE id=?", array(
									$DBFarmRole->ID, $farm_ebs['id']
								));
							}
						}
						else
						{
							$this->db->Execute("DELETE FROM farm_ebs WHERE id=?", array($farm_ebs['id']));
							$r++;
						}
					}	
					catch (Exception $e)
					{
						print "\nError with ebs {$farm_ebs['volumeid']} on farm {$farm_ebs['farmid']}: {$e->getMessage()}\n";
						continue;
					}
				}
			}
			catch(Exception $e)
			{
				$this->db->RollbackTrans();
				print "ERROR: {$e->getMessage()}\n";
				$error = true;
			}
			
			if (!$error)
			{
				$this->db->CommitTrans();
				
				print "OK.\n";
			}
		}
		
		private function Step6()
		{
			$this->db->BeginTrans();
			print "Step 6:\n";
			
			try
			{
				$farm_arrays = $this->db->Execute("SELECT farmid, role_name, id, clientid FROM ebs_arrays");
				while ($farm_array = $farm_arrays->FetchRow())
				{
					try
					{
						if (!$farm_array['role_name'])
							continue;
						
						$ami_id = $this->db->GetOne("SELECT ami_id FROM ami_roles WHERE name=? AND (clientid='0' OR clientid=?)",
							array($farm_array['role_name'], $farm_array['clientid'])
						);
						
						if ($ami_id)
						{
							$DBFarmRole = DBFarmRole::Load($farm_array['farmid'], $ami_id);
							
							$this->db->Execute("UPDATE ebs_arrays SET farm_roleid=? WHERE id=?", array(
								$DBFarmRole->ID, $farm_array['id']
							));
						}
					}	
					catch (Exception $e)
					{
						print "Error with array {$farm_array['id']} on farm {$farm_array['farmid']}: {$e->getMessage()}\n";
						continue;
					}
				}
			}
			catch(Exception $e)
			{
				$this->db->RollbackTrans();
				print "ERROR: {$e->getMessage()}\n";
				$error = true;
			}
			
			if (!$error)
			{
				$this->db->CommitTrans();
				
				print "OK.\n";
			}
		}
		
		private function Step7()
		{
			$this->db->BeginTrans();
			print "Step 7:\n";
			
			try
			{
				$ips = $this->db->Execute("SELECT farmid, role_name, ipaddress, clientid FROM elastic_ips");
				while ($ip = $ips->FetchRow())
				{
					try
					{
						if (!$ip['role_name'])
							continue;
						
						$ami_id = $this->db->GetOne("SELECT ami_id FROM ami_roles WHERE name=? AND (clientid='0' OR clientid=?)",
							array($ip['role_name'], $ip['clientid'])
						);
						
						if ($ami_id)
						{
							$DBFarmRole = DBFarmRole::Load($ip['farmid'], $ami_id);
							
							$this->db->Execute("UPDATE elastic_ips SET farm_roleid=? WHERE id=?", array(
								$DBFarmRole->ID, $ip['id']
							));
						}
					}	
					catch (Exception $e)
					{
						print "Error with IP {$ip['ipaddress']} on farm {$ip['farmid']}: {$e->getMessage()}\n";
						continue;
					}
				}
			}
			catch(Exception $e)
			{
				$this->db->RollbackTrans();
				print "ERROR: {$e->getMessage()}\n";
				$error = true;
			}
			
			if (!$error)
			{
				$this->db->CommitTrans();
				
				print "OK.\n";
			}
		}
		
		private function Step8()
		{
			$this->db->BeginTrans();
			print "Step 8:\n";
			
			try
			{
				$vhosts = $this->db->Execute("SELECT farmid, role_name, id, name FROM vhosts");
				while ($vhost = $vhosts->FetchRow())
				{
					try
					{
						$farminfo = $this->db->GetRow("SELECT * FROM farms WHERE id=?", array($vhost['farmid']));
						if ($farminfo)
						{
							if (!$vhost['role_name'])
								continue;
						
							$ami_id = $this->db->GetOne("SELECT ami_id FROM ami_roles WHERE name=? AND (clientid='0' OR clientid=?)",
								array($vhost['role_name'], $farminfo['clientid'])
							);
							
							if ($ami_id)
							{
								$DBFarmRole = DBFarmRole::Load($vhost['farmid'], $ami_id);
								
								$this->db->Execute("UPDATE vhosts SET farm_roleid=? WHERE id=?", array(
									$DBFarmRole->ID, $vhost['id']
								));
							}
						}
					}	
					catch (Exception $e)
					{
						print "Error with Vhost {$vhost['name']} on farm {$vhost['farmid']}: {$e->getMessage()}\n";
						continue;
					}
				}
			}
			catch(Exception $e)
			{
				$this->db->RollbackTrans();
				print "ERROR: {$e->getMessage()}\n";
				$error = true;
			}
			
			if (!$error)
			{
				$this->db->CommitTrans();
				
				print "OK.\n";
			}
		}
		
		private function Step9()
		{
			print "Step 9:\n";
			
			$this->db->BeginTrans();
			try
			{
				$this->db->Execute("create table IF NOT EXISTS `farm_settings`( `id` int(11) NOT NULL AUTO_INCREMENT , `farmid` int(11) , `name` varchar(50) , `value` varchar(50) , PRIMARY KEY (`id`))  Engine=InnoDB");
				$this->db->Execute("alter table `farm_settings` add unique `farmid_name` (`farmid`, `name`(50))");
				$this->db->Execute("alter table `farm_settings` change `value` `value` text NULL");
				$this->db->Execute("alter table `farms` ADD COLUMN `comments` TEXT NULL AFTER `farm_roles_launch_order`;");				
				$this->db->Execute("rename table `ami_roles` to `roles`");
				$this->db->Execute("rename table `farm_amis` to `farm_roles`");
				$this->db->Execute("alter table `zones` add column `allow_manage_system_records` tinyint(1) DEFAULT '0' NULL after `isobsoleted`");
			}
			catch(Exception $e)
			{
				$this->db->RollbackTrans();
				print "ERROR: {$e->getMessage()}\n";
				$error = true;
			}
			
			if (!$error)
			{
				$this->db->CommitTrans();
				
				print "OK.\n";
			}
		}
		
		private function Step10()
		{
			print "Step 10:\n";
			
			$this->db->BeginTrans();
			try
			{
				$farm_roles = $this->db->GetAll("SELECT id, use_elastic_ips, use_ebs, ebs_size, ebs_snapid, ebs_mountpoint, ebs_mount FROM farm_roles");
				foreach ($farm_roles as $farm_role)
				{
					if ($farm_role['use_elastic_ips'])
					{
						$this->db->Execute("REPLACE INTO farm_role_settings SET name=?, value=?, farm_roleid=?",
							array('aws.use_elastic_ips', $farm_role['use_elastic_ips'], $farm_role['id'])
						);
					}
						
					if ($farm_role['use_ebs'])
					{
						$this->db->Execute("REPLACE INTO farm_role_settings SET name=?, value=?, farm_roleid=?",
							array('aws.use_ebs', $farm_role['use_ebs'], $farm_role['id'])
						);
					}
						
					if ($farm_role['ebs_size'])
					{
						$this->db->Execute("REPLACE INTO farm_role_settings SET name=?, value=?, farm_roleid=?",
							array('aws.ebs_size', $farm_role['ebs_size'], $farm_role['id'])
						);
					}
						
					if ($farm_role['ebs_snapid'])
					{
						$this->db->Execute("REPLACE INTO farm_role_settings SET name=?, value=?, farm_roleid=?",
							array('aws.ebs_snapid', $farm_role['ebs_snapid'], $farm_role['id'])
						);
					}
						
					if ($farm_role['ebs_mountpoint'])
					{
						$this->db->Execute("REPLACE INTO farm_role_settings SET name=?, value=?, farm_roleid=?",
							array('aws.ebs_mountpoint', $farm_role['ebs_mountpoint'], $farm_role['id'])
						);
					}
						
					if ($farm_role['ebs_mount'])
					{
						$this->db->Execute("REPLACE INTO farm_role_settings SET name=?, value=?, farm_roleid=?",
							array('aws.ebs_mount', $farm_role['ebs_mount'], $farm_role['id'])
						);
					}
				}
				
				$this->db->Execute("alter table `farm_roles` drop column `use_elastic_ips`");
				$this->db->Execute("alter table `farm_roles` drop column `use_ebs`, drop column `ebs_size`, drop column `ebs_snapid`, drop column `ebs_mountpoint`, drop column `ebs_mount`");
				$this->db->Execute("alter table `farm_roles` drop column `aki_id`, drop column `ari_id`");
			}
			catch(Exception $e)
			{
				$this->db->RollbackTrans();
				print "ERROR: {$e->getMessage()}\n";
				$error = true;
			}
			
			if (!$error)
			{
				$this->db->CommitTrans();
				
				print "OK.\n";
			}
		}
		
		private function Step11()
		{
			print "Step 11:\n";
			
			$this->db->BeginTrans();
			try
			{
				$farms = $this->db->GetAll("SELECT * FROM farms");
				foreach ($farms as $farm)
				{
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'aws.ssh_private_key', $farm['private_key'])
					);
					
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'aws.ssh_public_key', $farm['public_key'])
					);
					
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'aws.s3_bucket_name', $farm['bucket_name'])
					);
					
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'aws.keypair_name', $farm['private_key_name'])
					);
							
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'mysql.enable_bcp', $farm['mysql_bcp'])
					);

					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'mysql.bcp_every', $farm['mysql_bcp_every'])
					);
					
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'mysql.enable_bundle', $farm['mysql_bundle'])
					);

					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'mysql.bundle_every', $farm['mysql_rebundle_every'])
					);
					
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'mysql.dt_last_bcp', $farm['dtlastbcp'])
					);
					
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'mysql.dt_last_bundle', $farm['dtlastrebundle'])
					);

					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'mysql.isbcprunning', $farm['isbcprunning'])
					);
					
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'mysql.isbundlerunning', $farm['isbundlerunning'])
					);
					
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'mysql.bcp_instance_id', $farm['bcp_instance_id'])
					);
					
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'mysql.bundle_instance_id', '')
					);
					
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'mysql.data_storage_engine', $farm['mysql_data_storage_engine'])
					);
					
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'mysql.master_ebs_volume_id', $farm['mysql_master_ebs_volume_id'])
					);
					
					$this->db->Execute("REPLACE INTO farm_settings SET `farmid`=?, `name`=?, `value`=?",
						array($farm['id'], 'mysql.ebs_volume_size', $farm['mysql_ebs_size'])
					);
				}

				$this->db->Execute("alter table `farms` 
					drop column `private_key`, 
					drop column `public_key`, 
					drop column `private_key_name` ,
					drop column `bucket_name` ,
					drop column `mysql_bcp` ,
					drop column `mysql_bcp_every` ,
					drop column `mysql_bundle` ,
					drop column `mysql_rebundle_every` ,
					drop column `dtlastbcp` ,
					drop column `dtlastrebundle` ,
					drop column `isbcprunning` ,
					drop column `isbundlerunning` ,
					drop column `bcp_instance_id` ,
					drop column `mysql_data_storage_engine` ,
					drop column `mysql_master_ebs_volume_id` ,
					drop column `mysql_ebs_size` 
				");
			}
			catch(Exception $e)
			{
				$this->db->RollbackTrans();
				print "ERROR: {$e->getMessage()}\n";
				$error = true;
			}
			
			if (!$error)
			{
				$this->db->CommitTrans();
				
				print "OK.\n";
			}
		}
	}
?>