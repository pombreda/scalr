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
			$this->Step12();
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
				$this->db->Execute("alter table `events` add column `event_object` text NULL after `short_message`, add column `event_id` varchar(36) NULL after `event_object`;");
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
		
		private function Step12()
		{
			print "Step 12:\n";
			
			$this->db->BeginTrans();
			try
			{
				$this->db->Execute("INSERT INTO `roles` VALUES(null, 'ami-ed3f6ea8', 'base', 'SHARED', 0, NULL, 1, '', NULL, 'Bare AMI that isn\'t involved in web serving. Suitable for batch job workers like media encoders etc.', '', 1, 4, 'base', 'm1.small', 'i386', NULL, 1, NULL, 1, NULL, NULL, 0, 'us-west-1', 22);");
				$id = $this->db->Insert_Id();
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'udp:161:162:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:22:22:0.0.0.0/0'));
				
				$this->db->Execute("INSERT INTO `roles` VALUES(null, 'ami-f73f6eb2', 'lb-nginx', 'SHARED', 0, NULL, 1, '', NULL, 'Frontend web server/load balancer, running nginx. Proxies all requests to all instances of Application Server role.', '', 1, 5, 'www', 'm1.small', 'i386', NULL, 1, NULL, 1, NULL, NULL, 0, 'us-west-1', 22);");
				$id = $this->db->Insert_Id();
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'udp:161:162:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:22:22:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:80:80:0.0.0.0/0'));
				
				$this->db->Execute("INSERT INTO `roles` VALUES(null, 'ami-d93f6e9c', 'app-apache', 'SHARED', 0, NULL, 1, '1 ', NULL, '<b>Apache2 + PHP5</b><br/>\r\nCan act as a backend (if farm contains load balancer role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\n<a target=\"blank\" href=\'http://httpd.apache.org/\'>Apache  HTTP server.</a><br/>\r\n <a target=\"blank\" href=\'http://php.net\'>PHP</a><br/><br/>\r\n<b>Essential paths:</b><br/>\r\nWebroot:  <code>/var/www</code><br/>\r\nDefault virtual host config: <code>/etc/apache2/sites-enabled/000-default</code><br/>', '', 1, 5, 'app', 'm1.small', 'i386', NULL, 1, NULL, 1, NULL, NULL, 0, 'us-west-1', 22);");
				$id = $this->db->Insert_Id();
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'udp:161:162:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:22:22:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:80:80:0.0.0.0/0'));
								
				$this->db->Execute("INSERT INTO `roles` VALUES(null, 'ami-e33f6ea6', 'app-apache64', 'SHARED', 0, NULL, 1, NULL, NULL, '<b>Apache2 + PHP5</b><br/>\r\nCan act as a backend (if farm contains Load Balancer role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\n<a target=\"blank\" href=\'http://httpd.apache.org/\'>Apache  HTTP server.</a>\r\n<br/> <a target=\"blank\" href=\'http://php.net\'>PHP</a><br/><br/>\r\n<b>Essential paths:</b><br/>\r\nWebroot:  <code>/var/www</code><br/>\r\nDefault virtual host config: <code>/etc/apache2/sites-enabled/000-default</code><br/>', '', 1, 5, 'app', 'm1.large', 'x86_64', NULL, 1, NULL, 1, NULL, NULL, 0, 'us-west-1', 22);");
				$id = $this->db->Insert_Id();
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'udp:161:162:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:22:22:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:80:80:0.0.0.0/0'));
				
				$this->db->Execute("INSERT INTO `roles` VALUES(null, 'ami-ef3f6eaa', 'base64', 'SHARED', 0, NULL, 1, NULL, NULL, 'Bare AMI that doesn\'t involved in web serving. Suitable for batch job workers like media encoders etc.', '', 1, 5, 'base', 'm1.large', 'x86_64', NULL, 1, NULL, 1, NULL, NULL, 0, 'us-west-1', 22);");
				$id = $this->db->Insert_Id();
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'udp:161:162:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:22:22:0.0.0.0/0'));				
				
				$this->db->Execute("INSERT INTO `roles` VALUES(null, 'ami-f13f6eb4', 'lb-nginx64', 'SHARED', 0, NULL, 1, NULL, NULL, 'Frontend web server/load balancer, running nginx. Proxies all requests to all instances of Application servers role.', '', 1, 5, 'www', 'm1.large', 'x86_64', NULL, 1, NULL, 1, NULL, NULL, 0, 'us-west-1', 22);");
				$id = $this->db->Insert_Id();
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'udp:161:162:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:22:22:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:80:80:0.0.0.0/0'));
								
				$this->db->Execute("INSERT INTO `roles` VALUES(null, 'ami-eb3f6eae', 'mysqllvm', 'SHARED', 0, NULL, 1, NULL, NULL, 'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing. Users LVM to quicker perform backup snapshots and support huge databases.', NULL, 1, 5, 'mysql', 'm1.small', 'i386', NULL, 0, NULL, 1, NULL, NULL, 0, 'us-west-1', 22);");
				$id = $this->db->Insert_Id();
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'udp:161:162:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:22:22:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:3306:3306:0.0.0.0/0'));
				
				$this->db->Execute("INSERT INTO `roles` VALUES(null, 'ami-f53f6eb0', 'mysqllvm64', 'SHARED', 0, NULL, 1, NULL, NULL, 'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing. Uses LVM to quicker perform backup snapshots and support huge databases.', NULL, 1, 5, 'mysql', 'm1.large', 'x86_64', NULL, 0, NULL, 1, NULL, NULL, 0, 'us-west-1', 22);");
				$id = $this->db->Insert_Id();
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'udp:161:162:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:22:22:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:3306:3306:0.0.0.0/0'));
				
				$this->db->Execute("INSERT INTO `roles` VALUES(null, 'ami-db3f6e9e', 'app-rails', 'SHARED', 0, '', 1, NULL, '2008-09-22 08:05:56', '<b>Apache2 + mod_rails + Rails 2.1.1</b><br/>\r\nCan act as a backend (if farm contains load balancer role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\n <a target=\"blank\" href=\'http://www.modrails.com/documentation/Users guide.html\'>Phusion Passenger</a><br/>\r\n <a target=\"blank\" href=\"http://revolutiononrails.blogspot.com/2007/04/plugin-release-actsasreadonlyable.html\">ActsAsReadonlyable</a>\r\n<br/><br/>\r\n<b>Essential paths:</b><br/>\r\nWebroot:  <code>/var/www</code> - symlinks to <code>/usr/rails/scalr-placeholder/public</code><br/>\r\nDefault virtual host config: <code>/etc/apache2/sites-enabled/000-default</code><br/>', '', 2, 5, 'app', 'm1.small', 'i386', '2008-09-22 07:58:46', 1, NULL, 1, NULL, NULL, 0, 'us-west-1', 22);");
				$id = $this->db->Insert_Id();
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'udp:161:162:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:22:22:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:80:80:0.0.0.0/0'));
								
				$this->db->Execute("INSERT INTO `roles` VALUES(null, 'ami-e93f6eac', 'memcached', 'SHARED', 0, NULL, 1, NULL, NULL, '<b>Memcached</b><br/><br/>\r\n\r\n<b>Notes</b><br/>\r\n Consumes up to 1.5GB of memory.<br/>\r\n By default only it allows connections from all instances in the same farm. External IPs can be added on the Options tab for a role.\r\n<br/><br/>\r\n<b>References:</b><br>\r\n <a target=_\"blank\" href=\'http://code.google.com/p/scalr/wiki/ScalingMemcached\'>Scaling memcached</a> \r\n <a target=\"_blank\" href=\'http://www.danga.com/memcached/\'>memcached: a distributed memory object caching system</a> ', NULL, 2, 5, 'memcached', 'm1.small', 'i386', NULL, 0, NULL, 1, NULL, NULL, 0, 'us-west-1', 22);");
				$id = $this->db->Insert_Id();
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'udp:161:162:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:22:22:0.0.0.0/0'));
								
				$this->db->Execute("INSERT INTO `roles` VALUES(null, 'ami-e53f6ea0', 'app-rails64', 'SHARED', 0, NULL, 1, NULL, NULL, '<b>Apache2 + mod_rails + Rails 2.1.1</b><br/>\r\nCan act as a backend (if farm contains load balancer role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\n <a target=\"blank\" href=\'http://www.modrails.com/documentation/Users guide.html\'>Phusion Passenger</a><br/>\r\n <a target=\"blank\" href=\"http://revolutiononrails.blogspot.com/2007/04/plugin-release-actsasreadonlyable.html\">ActsAsReadonlyable</a>\r\n<br/><br/>\r\n<b>Essential paths:</b><br/>\r\nWebroot:  <code>/var/www</code> - symlinks to <code>/usr/rails/scalr-placeholder/public</code><br/>\r\nDefault virtual host config: <code>/etc/apache2/sites-enabled/000-default</code><br/>', NULL, 1, 5, 'app', 'm1.small', 'x86_64', NULL, 0, NULL, 1, NULL, NULL, 0, 'us-west-1', 22);");
				$id = $this->db->Insert_Id();
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'udp:161:162:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:22:22:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:80:80:0.0.0.0/0'));
								
				$this->db->Execute("INSERT INTO `roles` VALUES(null, 'ami-e73f6ea2', 'app-tomcat', 'SHARED', 0, NULL, 1, NULL, NULL, '<b>Tomcat 5.5</b><br/>\r\nCan act as a backend (if farm contains load balancer role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\nâ€¢ <a target=\"blank\" href=\'http://tomcat.apache.org/tomcat-5.5-doc/index.html\'>The Apache Tomcat 5.5 Servlet/JSP Container</a>\r\n<br/><br/>\r\n<b>Essential paths:</b><br/>\r\nConfig: <code>/etc/tomcat5.5/</code>\r\nWebapps:  <code>/var/lib/tomcat5.5/webapps</code><br/>\r\nDefault context: <code>/var/lib/tomcat5.5/ROOT</code><br/>', NULL, 1, 5, 'app', 'm1.small', 'i386', NULL, 0, NULL, 1, NULL, NULL, 0, 'us-west-1', 22);");
				$id = $this->db->Insert_Id();
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'udp:161:162:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:22:22:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:80:80:0.0.0.0/0'));
								
				$this->db->Execute("INSERT INTO `roles` VALUES(null, 'ami-e13f6ea4', 'app-tomcat6', 'SHARED', 0, NULL, 1, NULL, NULL, '<b>Tomcat 6.0</b><br/>\r\nCan act as a backend (if farm contains load balancer role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\nâ€¢ <a target=\"blank\" href=\'http://tomcat.apache.org/tomcat-6.0-doc/index.html\'>The Apache Tomcat 6.0 Servlet/JSP Container</a>\r\n<br/><br/>\r\n<b>Essential paths:</b><br/>\r\nConfig: <code>/usr/local/tomcat/conf</code>\r\nWebapps:  <code>/usr/local/tomcat/webapps</code><br/>\r\nDefault context: <code>/usr/local/tomcat/webapps/ROOT</code><br/>', NULL, 1, 7, 'app', 'm1.small', 'x86_64', NULL, 0, NULL, 1, NULL, NULL, 0, 'us-west-1', 22);");
				$id = $this->db->Insert_Id();
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'udp:161:162:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:22:22:0.0.0.0/0'));
				$this->db->Execute("INSERT INTO security_rules SET roleid=?, rule=?", array($id, 'tcp:80:80:0.0.0.0/0'));
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