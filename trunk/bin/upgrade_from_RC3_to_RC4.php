<?php
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new EUUpdate();
	$ScalrUpdate->Run();
	
	class EUUpdate
	{
		function Run()
		{
			$this->AddSharedRoles();
			$this->SetIndexes();
			$this->UpdateEBS();
		}
		
		function UpdateEBS()
		{
			global $db;
			
			$ebs = $db->GetAll("SELECT * FROM farm_ebs");
			foreach ($ebs as $ebs_volume)
			{
				if ($ebs_volume['insatnce_id'])
				{
					$db->Execute("UPDATE farm_ebs SET instance_index = (SELECT `index` FROM farm_instances WHERE instance_id='{$ebs_volume['insatnce_id']}') WHERE id='{$ebs_volume['id']}'");
				}
				else
				{
					$index = $db->GetOne("SELECT MAX(instance_index) FROM farm_ebs WHERE role_name=? AND farmid=?", array($ebs_volume['role_name'], $ebs_volume['farmid']));
					$index = $index+1;
					$db->Execute("UPDATE farm_ebs SET instance_index = '{$index}' WHERE id='{$ebs_volume['id']}'");
				}	
			}
		}
		
		function SetIndexes()
		{
			global $db;
			
			$farms = $db->GetAll("SELECT * FROM farms WHERE status=?", array(FARM_STATUS::RUNNING));
			foreach ($farms as $farm)
			{
				$roles = $db->GetAll("SELECT * FROM farm_amis WHERE farmid=?", array($farm['id']));
				foreach ($roles as $role)
				{
					$i = 1;
					$instances = $db->GetAll("SELECT * FROM farm_instances WHERE farmid=? AND ami_id=?", array($farm['id'], $role['ami_id']));
					foreach ($instances as $instance)
					{
						$db->Execute("UPDATE farm_instances SET `index`='{$i}' WHERE id=?", array($instance['id']));
						$i++;
					}
				}
			}
		}
		
		function AddSharedRoles()
		{
			global $db;
			
			$roles_map = array(
				"ami-2cf21645" => "ami-221c3456", //mysql
				"ami-e8c62281" => "ami-201c3454", //mysql64
				"ami-bac420d3" => "ami-3c1c3448", //app
				"ami-0ac62263" => "ami-481c343c", //app64
				"ami-72f2161b" => "ami-441c3430", //www
				"ami-01ca2e68" => "ami-5a1c342e", //www64
				"ami-51f21638" => "ami-4c1c3438", //base
				"ami-03ca2e6a" => "ami-401c3434", //base64
				"ami-d09572b9" => "ami-161c3462", //mysqllvm
				"ami-21cf2b48" => "ami-241c3450", //mysqllvm64
				"ami-c2d034ab" => "ami-321c3446", //app-rails
				"ami-69d23600" => "ami-301c3444", //app-rails64
				"ami-cfd034a6" => "ami-421c3436", // memcached
				"ami-5a2fcb33" => "ami-341c3440", //app-tomcat
				"ami-6436d20d" => "ami-4a1c343e", //app-tomcat6
			);
			
			$db->BeginTrans();
			
			try
			{
				foreach ($roles_map as $us_amiid => $eu_amiid)
				{
					$res = $db->GetRow("SELECT * FROM ami_roles WHERE ami_id='{$us_amiid}'");
					if ($res)
					{
						$db->Execute("INSERT INTO ami_roles SET
						`ami_id` = ?, 
						`name` = ?, 
						`roletype` = ?, 
						`description` = ?, 
						`default_minLA` = ?,
						`default_maxLA` = ?, 
						`alias` = ?, 
						`instance_type` = ?, 
						`architecture` = ?, 
						`isstable` = ?, 
						`region` = ?,
						`clientid` = '0'
						", array($eu_amiid, $res['name'], $res['roletype'], $res['description'],
						$res['default_minLA'], $res['default_maxLA'], $res['alias'],
						$res['instance_type'], $res['architecture'], $res['isstable'], 'eu-west-1'));
						$roleid = $db->Insert_ID();
						
						$db->Execute("INSERT INTO security_rules (roleid, rule) SELECT '{$roleid}', rule FROM security_rules WHERE roleid='{$res['id']}'");
						$db->Execute("INSERT INTO role_options (name, type,isrequired,defval,allow_multiple_choice,options,ami_id,hash) 
							SELECT name, type,isrequired,defval,allow_multiple_choice,options,'{$eu_amiid}',hash FROM role_options WHERE ami_id='{$us_amiid}'");
					}
				}
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				print "Error: {$e->getMessage()}";
				exit();
			}
			
			$db->CommitTrans();
		}
	}
?>