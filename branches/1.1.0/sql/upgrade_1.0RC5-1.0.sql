create table `farm_role_settings`( `id` int(11) NOT NULL AUTO_INCREMENT , `farm_roleid` int(11) , `name` varchar(255) , `value` varchar(255) , PRIMARY KEY (`id`));

create table `sensor_data`( `id` int(11) , `farm_roleid` int(11) , `sensor_name` varchar(255) , `sensor_value` varchar(255) , `dtlastupdate` int , `raw_sensor_data` varchar(255) );

alter table `farms` add column `farm_roles_launch_order` tinyint(1) DEFAULT '0' NULL after `mysql_ebs_size`;

alter table `farm_amis` add column `launch_index` int(5) DEFAULT '0' NULL after `ari_id`;

alter table `default_records` change `rtype` `rtype` enum('NS','MX','CNAME','A','TXT') character set latin1 collate latin1_swedish_ci NULL;

alter table `clients` add column `comments` text NULL after `scalr_api_key`;