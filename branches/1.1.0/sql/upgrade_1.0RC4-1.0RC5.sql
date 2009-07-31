alter table `elastic_ips` add column `instance_index` int(11) DEFAULT '0' NULL after `clientid`;

alter table `clients` add column `scalr_api_keyid` varchar(16) NULL after `dtlastloginattempt`, add column `scalr_api_key` varchar(250) NULL after `scalr_api_keyid`;

alter table `farm_instances` add column `dtshutdownscheduled` datetime NULL after `scalarizr_pkg_version`;

alter table `farms` add column `scalarizr_pkey` text NULL after `region`, add column `scalarizr_cert` text NULL after `scalarizr_pkey`;

alter table `farm_ebs` add column `mount` tinyint(1) DEFAULT '0' NULL after `region`, add column `mountpoint` varchar(255) NULL after `mount`;

alter table `role_options` add column `issystem` tinyint(1) DEFAULT '0' NULL after `hash`;

alter table `farms` add column `mysql_master_ebs_volume_id` varchar(255) NULL after `scalarizr_cert`, add column `mysql_data_storage_engine` varchar(5) DEFAULT 'lvm' NULL after `mysql_master_ebs_volume_id`;

alter table `farms` add column `mysql_ebs_size` int(7) DEFAULT '100' NULL after `mysql_data_storage_engine`;