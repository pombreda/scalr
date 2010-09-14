/*
SQLyog Ultimate v8.4 
MySQL - 5.0.51a-24+lenny4-log : Database - scalr_dev_3
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `apache_vhosts` */

DROP TABLE IF EXISTS `apache_vhosts`;

CREATE TABLE `apache_vhosts` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) default NULL,
  `is_ssl_enabled` tinyint(1) default '0',
  `farm_id` int(11) default NULL,
  `farm_roleid` int(11) default NULL,
  `ssl_cert` text,
  `ssl_key` text,
  `ca_cert` text,
  `last_modified` datetime default NULL,
  `client_id` int(11) default NULL,
  `httpd_conf` text,
  `httpd_conf_vars` text,
  `advanced_mode` tinyint(1) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ix_name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `api_log` */

DROP TABLE IF EXISTS `api_log`;

CREATE TABLE `api_log` (
  `id` int(11) NOT NULL auto_increment,
  `transaction_id` varchar(36) default NULL,
  `dtadded` int(11) default NULL,
  `action` varchar(25) default NULL,
  `ipaddress` varchar(15) default NULL,
  `request` text,
  `response` text,
  `clientid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  KEY `client_index` (`clientid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `autosnap_settings` */

DROP TABLE IF EXISTS `autosnap_settings`;

CREATE TABLE `autosnap_settings` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `period` int(5) default NULL,
  `dtlastsnapshot` datetime default NULL,
  `rotate` int(11) default NULL,
  `last_snapshotid` varchar(50) default NULL,
  `region` varchar(50) default 'us-east-1',
  `objectid` varchar(20) default NULL,
  `object_type` varchar(20) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `aws_errors` */

DROP TABLE IF EXISTS `aws_errors`;

CREATE TABLE `aws_errors` (
  `guid` varchar(85) NOT NULL,
  `title` text,
  `pub_date` datetime default NULL,
  `description` text,
  PRIMARY KEY  (`guid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `aws_regions` */

DROP TABLE IF EXISTS `aws_regions`;

CREATE TABLE `aws_regions` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `api_url` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `billing_packages` */

DROP TABLE IF EXISTS `billing_packages`;

CREATE TABLE `billing_packages` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `cost` float(7,2) default NULL,
  `group` tinyint(2) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `bundle_task_log` */

DROP TABLE IF EXISTS `bundle_task_log`;

CREATE TABLE `bundle_task_log` (
  `id` int(11) NOT NULL auto_increment,
  `bundle_task_id` int(11) default NULL,
  `dtadded` datetime default NULL,
  `message` text,
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`bundle_task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `bundle_tasks` */

DROP TABLE IF EXISTS `bundle_tasks`;

CREATE TABLE `bundle_tasks` (
  `id` int(11) NOT NULL auto_increment,
  `prototype_role_id` int(11) default NULL,
  `client_id` int(11) default NULL,
  `server_id` varchar(36) default NULL,
  `replace_type` varchar(20) default NULL,
  `status` varchar(20) default NULL,
  `platform` varchar(20) default NULL,
  `rolename` varchar(50) default NULL,
  `failure_reason` varchar(255) default '0',
  `bundle_type` varchar(20) default NULL,
  `dtadded` datetime default NULL,
  `dtstarted` datetime default NULL,
  `dtfinished` datetime default NULL,
  `remove_proto_role` tinyint(1) default '0',
  `snapshot_id` varchar(50) default NULL,
  `platform_status` varchar(50) default NULL,
  `description` text,
  `role_id` int(11) default NULL,
  `farm_id` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `client_settings` */

DROP TABLE IF EXISTS `client_settings`;

CREATE TABLE `client_settings` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `key` varchar(255) default NULL,
  `value` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`clientid`,`key`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `clients` */

DROP TABLE IF EXISTS `clients`;

CREATE TABLE `clients` (
  `id` int(11) NOT NULL auto_increment,
  `email` varchar(255) default NULL,
  `password` varchar(64) default NULL,
  `aws_accesskeyid` varchar(255) default NULL,
  `aws_accountid` varchar(50) default NULL,
  `aws_accesskey` varchar(255) default NULL,
  `farms_limit` int(2) default '2',
  `isbilled` tinyint(1) default '0',
  `dtdue` datetime default NULL,
  `isactive` tinyint(1) default '0',
  `fullname` varchar(60) default NULL,
  `org` varchar(60) default NULL,
  `country` varchar(60) default NULL,
  `state` varchar(60) default NULL,
  `city` varchar(60) default NULL,
  `zipcode` varchar(60) default NULL,
  `address1` varchar(60) default NULL,
  `address2` varchar(60) default NULL,
  `phone` varchar(60) default NULL,
  `fax` varchar(60) default NULL,
  `aws_private_key_enc` text,
  `aws_certificate_enc` text,
  `dtadded` datetime default NULL,
  `iswelcomemailsent` tinyint(1) default '0',
  `login_attempts` int(5) default '0',
  `dtlastloginattempt` datetime default NULL,
  `scalr_api_keyid` varchar(16) default NULL,
  `scalr_api_key` varchar(250) default NULL,
  `comments` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `comments` */

DROP TABLE IF EXISTS `comments`;

CREATE TABLE `comments` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `object_owner` int(11) default NULL,
  `dtcreated` datetime default NULL,
  `object_type` varchar(50) default NULL,
  `comment` text,
  `objectid` int(11) default NULL,
  `isprivate` tinyint(1) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `config` */

DROP TABLE IF EXISTS `config`;

CREATE TABLE `config` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `key` varchar(255) default NULL,
  `value` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `countries` */

DROP TABLE IF EXISTS `countries`;

CREATE TABLE `countries` (
  `id` int(5) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `code` char(2) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `IDX_COUNTRIES_NAME` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `default_records` */

DROP TABLE IF EXISTS `default_records`;

CREATE TABLE `default_records` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default '0',
  `type` enum('NS','MX','CNAME','A','TXT') default NULL,
  `ttl` int(11) default '14400',
  `priority` int(11) default NULL,
  `value` varchar(255) default NULL,
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `distributions` */

DROP TABLE IF EXISTS `distributions`;

CREATE TABLE `distributions` (
  `id` int(11) NOT NULL auto_increment,
  `cfid` varchar(25) default NULL,
  `cfurl` varchar(255) default NULL,
  `cname` varchar(255) default NULL,
  `zone` varchar(255) default NULL,
  `bucket` varchar(255) default NULL,
  `clientid` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `dns_zone_records` */

DROP TABLE IF EXISTS `dns_zone_records`;

CREATE TABLE `dns_zone_records` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zone_id` int(10) unsigned NOT NULL default '0',
  `type` varchar(6) default NULL,
  `ttl` int(10) unsigned default NULL,
  `priority` int(10) unsigned default NULL,
  `value` varchar(255) default NULL,
  `name` varchar(255) default NULL,
  `issystem` tinyint(1) default NULL,
  `weight` int(10) default NULL,
  `port` int(10) default NULL,
  `server_id` varchar(36) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `zoneid` (`zone_id`,`type`(1),`value`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `dns_zones` */

DROP TABLE IF EXISTS `dns_zones`;

CREATE TABLE `dns_zones` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `client_id` int(11) default NULL,
  `farm_id` int(11) default NULL,
  `farm_roleid` int(11) default NULL,
  `zone_name` varchar(255) default NULL,
  `status` varchar(255) default NULL,
  `soa_owner` varchar(100) default NULL,
  `soa_ttl` int(10) unsigned default NULL,
  `soa_parent` varchar(100) default NULL,
  `soa_serial` int(10) unsigned default NULL,
  `soa_refresh` int(10) unsigned default NULL,
  `soa_retry` int(10) unsigned default NULL,
  `soa_expire` int(10) unsigned default NULL,
  `soa_min_ttl` int(10) unsigned default NULL,
  `dtlastmodified` datetime default NULL,
  `axfr_allowed_hosts` tinytext,
  `allow_manage_system_records` tinyint(1) default '0',
  `isonnsserver` tinyint(1) default '0',
  `iszoneconfigmodified` tinyint(1) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `zones_index3945` (`zone_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `ebs_array_snaps` */

DROP TABLE IF EXISTS `ebs_array_snaps`;

CREATE TABLE `ebs_array_snaps` (
  `id` int(11) NOT NULL auto_increment,
  `description` varchar(255) default NULL,
  `dtcreated` datetime default NULL,
  `status` varchar(50) default NULL,
  `clientid` int(11) default NULL,
  `ebs_arrayid` int(11) default NULL,
  `ebs_snaps_count` int(11) default NULL,
  `region` varchar(255) default 'us-east-1',
  `autosnapshotid` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `ebs_arrays` */

DROP TABLE IF EXISTS `ebs_arrays`;

CREATE TABLE `ebs_arrays` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  `size` int(11) default NULL,
  `volumes` int(2) default NULL,
  `clientid` int(11) default NULL,
  `mountpoint` varchar(255) default NULL,
  `isfscreated` tinyint(1) default '0',
  `status` varchar(60) default NULL,
  `instance_id` varchar(20) default NULL,
  `corrupt_reason` varchar(255) default NULL,
  `avail_zone` varchar(20) default NULL,
  `instance_index` int(5) default '1',
  `attach_on_boot` tinyint(1) default '0',
  `farmid` int(11) default NULL,
  `role_name` varchar(255) default NULL,
  `region` varchar(50) default 'us-east-1',
  `farm_roleid` int(11) default NULL,
  `server_id` varchar(36) default NULL,
  PRIMARY KEY  (`id`),
  KEY `farm_roleid` (`farm_roleid`),
  KEY `farmid` (`farmid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `ebs_snaps_info` */

DROP TABLE IF EXISTS `ebs_snaps_info`;

CREATE TABLE `ebs_snaps_info` (
  `id` int(11) NOT NULL auto_increment,
  `snapid` varchar(50) default NULL,
  `comment` varchar(255) default NULL,
  `dtcreated` datetime default NULL,
  `ebs_array_snapid` int(11) default '0',
  `region` varchar(255) default 'us-east-1',
  `autosnapshotid` int(11) default '0',
  `is_autoebs_master_snap` tinyint(1) default '0',
  `farm_roleid` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `ec2_ebs` */

DROP TABLE IF EXISTS `ec2_ebs`;

CREATE TABLE `ec2_ebs` (
  `id` int(11) NOT NULL auto_increment,
  `farm_id` int(11) default NULL,
  `farm_roleid` int(11) default NULL,
  `volume_id` varchar(15) default NULL,
  `server_id` varchar(36) default NULL,
  `attachment_status` varchar(30) default NULL,
  `mount_status` varchar(20) default NULL,
  `device` varchar(15) default NULL,
  `server_index` int(3) default NULL,
  `mount` tinyint(1) default '0',
  `mountpoint` varchar(50) default NULL,
  `ec2_avail_zone` varchar(30) default NULL,
  `ec2_region` varchar(30) default NULL,
  `isfsexist` tinyint(1) default '0',
  `ismanual` tinyint(1) default '0',
  `size` int(11) default NULL,
  `snap_id` varchar(50) default NULL,
  `ismysqlvolume` tinyint(1) default '0',
  `client_id` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `elastic_ips` */

DROP TABLE IF EXISTS `elastic_ips`;

CREATE TABLE `elastic_ips` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `role_name` varchar(100) default NULL,
  `ipaddress` varchar(15) default NULL,
  `state` tinyint(1) default '0',
  `instance_id` varchar(20) default NULL,
  `clientid` int(11) default NULL,
  `instance_index` int(11) default '0',
  `farm_roleid` int(11) default NULL,
  `server_id` varchar(36) default NULL,
  PRIMARY KEY  (`id`),
  KEY `farmid` (`farmid`),
  KEY `farm_roleid` (`farm_roleid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `events` */

DROP TABLE IF EXISTS `events`;

CREATE TABLE `events` (
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
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `farm_ebs` */

DROP TABLE IF EXISTS `farm_ebs`;

CREATE TABLE `farm_ebs` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `role_name` varchar(255) default NULL,
  `volumeid` varchar(255) default NULL,
  `state` varchar(255) default NULL,
  `instance_id` varchar(255) default NULL,
  `avail_zone` varchar(255) default NULL,
  `device` varchar(50) default NULL,
  `isfsexists` tinyint(1) default '0',
  `instance_index` tinyint(2) default '0',
  `ebs_arrayid` int(11) default '0',
  `ismanual` tinyint(1) default '0',
  `ebs_array_part` int(11) default '1',
  `region` varchar(50) default 'us-east-1',
  `mount` tinyint(1) default '0',
  `mountpoint` varchar(255) default NULL,
  `farm_roleid` int(11) default NULL,
  `server_id` varchar(36) default NULL,
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`farmid`),
  KEY `farm_roleid` (`farm_roleid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `farm_event_observers` */

DROP TABLE IF EXISTS `farm_event_observers`;

CREATE TABLE `farm_event_observers` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `event_observer_name` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`farmid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `farm_event_observers_config` */

DROP TABLE IF EXISTS `farm_event_observers_config`;

CREATE TABLE `farm_event_observers_config` (
  `id` int(11) NOT NULL auto_increment,
  `observerid` int(11) default NULL,
  `key` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`observerid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `farm_instances` */

DROP TABLE IF EXISTS `farm_instances`;

CREATE TABLE `farm_instances` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `instance_id` varchar(255) default NULL,
  `state` varchar(50) default 'Pending',
  `ami_id` varchar(50) default NULL,
  `internal_ip` varchar(15) default NULL,
  `external_ip` varchar(15) default NULL,
  `isdbmaster` tinyint(1) default '0',
  `replace_iid` varchar(255) default NULL,
  `dtadded` datetime default NULL,
  `dtrebootstart` datetime default NULL,
  `isrebootlaunched` tinyint(1) default NULL,
  `isactive` tinyint(1) default '1',
  `role_name` varchar(255) default NULL,
  `dtlastsync` int(11) default '0',
  `isipchanged` tinyint(1) default NULL,
  `ispendingshutdown` tinyint(1) default '0',
  `bwusage_in` bigint(20) default NULL,
  `bwusage_out` bigint(20) default NULL,
  `uptime` int(11) default NULL,
  `status` varchar(50) default NULL,
  `custom_elastic_ip` varchar(15) default NULL,
  `mysql_stat_password` varchar(255) default NULL,
  `mysql_replication_status` tinyint(1) default '1',
  `avail_zone` varchar(255) default NULL,
  `ishalted` tinyint(1) default '0',
  `index` tinyint(2) default '0',
  `region` varchar(50) default 'us-east-1',
  `dtlaststatusupdate` int(11) default NULL,
  `scalarizr_pkg_version` varchar(20) default NULL,
  `dtshutdownscheduled` datetime default NULL,
  `farm_roleid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `farmid` (`farmid`),
  KEY `farm_roleid` (`farm_roleid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `farm_role_options` */

DROP TABLE IF EXISTS `farm_role_options`;

CREATE TABLE `farm_role_options` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `ami_id` varchar(255) default NULL,
  `name` varchar(255) default NULL,
  `value` text,
  `hash` varchar(255) default NULL,
  `farm_roleid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `farmid` (`farmid`),
  KEY `farm_roleid` (`farm_roleid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `farm_role_scaling_times` */

DROP TABLE IF EXISTS `farm_role_scaling_times`;

CREATE TABLE `farm_role_scaling_times` (
  `id` int(11) NOT NULL auto_increment,
  `farm_roleid` int(11) default NULL,
  `start_time` int(11) default NULL,
  `end_time` int(11) default NULL,
  `days_of_week` varchar(75) default NULL,
  `instances_count` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `farm_role_scripts` */

DROP TABLE IF EXISTS `farm_role_scripts`;

CREATE TABLE `farm_role_scripts` (
  `id` int(11) NOT NULL auto_increment,
  `scriptid` int(11) default NULL,
  `farmid` int(11) default NULL,
  `ami_id` varchar(255) default NULL,
  `params` text,
  `event_name` varchar(255) default NULL,
  `target` varchar(50) default NULL,
  `version` varchar(20) default 'latest',
  `timeout` int(5) default '120',
  `issync` tinyint(1) default '0',
  `ismenuitem` tinyint(1) default '0',
  `order_index` int(5) default '0',
  `farm_roleid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `UniqueIndex` (`scriptid`,`farmid`,`event_name`,`farm_roleid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `farm_role_settings` */

DROP TABLE IF EXISTS `farm_role_settings`;

CREATE TABLE `farm_role_settings` (
  `id` int(11) NOT NULL auto_increment,
  `farm_roleid` int(11) default NULL,
  `name` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unique` (`farm_roleid`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `farm_roles` */

DROP TABLE IF EXISTS `farm_roles`;

CREATE TABLE `farm_roles` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `ami_id` varchar(255) default NULL,
  `replace_to_ami` varchar(255) default NULL,
  `dtlastsync` datetime default NULL,
  `reboot_timeout` int(10) default '300',
  `launch_timeout` int(10) default '300',
  `status_timeout` int(10) default '20',
  `launch_index` int(5) default '0',
  `role_id` int(11) default NULL,
  `new_role_id` int(11) default NULL,
  `platform` varchar(20) default NULL,
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`ami_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `farm_settings` */

DROP TABLE IF EXISTS `farm_settings`;

CREATE TABLE `farm_settings` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `name` varchar(50) default NULL,
  `value` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `farmid_name` (`farmid`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `farm_stats` */

DROP TABLE IF EXISTS `farm_stats`;

CREATE TABLE `farm_stats` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `bw_in` bigint(20) default '0',
  `bw_out` bigint(20) default '0',
  `bw_in_last` int(11) default '0',
  `bw_out_last` int(11) default '0',
  `month` int(2) default NULL,
  `year` int(4) default NULL,
  `dtlastupdate` int(11) default NULL,
  `m1_small` int(11) default '0',
  `m1_large` int(11) default '0',
  `m1_xlarge` int(11) default '0',
  `c1_medium` int(11) default '0',
  `c1_xlarge` int(11) default '0',
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`month`,`year`),
  KEY `NewIndex2` (`farmid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `farms` */

DROP TABLE IF EXISTS `farms`;

CREATE TABLE `farms` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `name` varchar(255) default NULL,
  `iscompleted` tinyint(1) default '0',
  `hash` varchar(25) default NULL,
  `dtadded` datetime default NULL,
  `status` tinyint(1) default '1',
  `dtlaunched` datetime default NULL,
  `term_on_sync_fail` tinyint(1) default '1',
  `region` varchar(255) default 'us-east-1',
  `farm_roles_launch_order` tinyint(1) default '0',
  `comments` text,
  PRIMARY KEY  (`id`),
  KEY `clientid` (`clientid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `garbage_queue` */

DROP TABLE IF EXISTS `garbage_queue`;

CREATE TABLE `garbage_queue` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `data` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`clientid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `init_tokens` */

DROP TABLE IF EXISTS `init_tokens`;

CREATE TABLE `init_tokens` (
  `id` int(11) NOT NULL auto_increment,
  `instance_id` varchar(255) default NULL,
  `token` varchar(255) default NULL,
  `dtadded` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `instances_history` */

DROP TABLE IF EXISTS `instances_history`;

CREATE TABLE `instances_history` (
  `id` int(11) NOT NULL auto_increment,
  `instance_id` varchar(20) default NULL,
  `dtlaunched` int(11) default NULL,
  `dtterminated` int(11) default NULL,
  `uptime` int(11) default NULL,
  `instance_type` varchar(20) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `ipaccess` */

DROP TABLE IF EXISTS `ipaccess`;

CREATE TABLE `ipaccess` (
  `id` int(11) NOT NULL auto_increment,
  `ipaddress` varchar(255) default NULL,
  `comment` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `logentries` */

DROP TABLE IF EXISTS `logentries`;

CREATE TABLE `logentries` (
  `id` int(11) NOT NULL auto_increment,
  `serverid` varchar(36) NOT NULL,
  `message` text NOT NULL,
  `severity` tinyint(1) default '0',
  `time` int(11) NOT NULL,
  `source` varchar(255) default NULL,
  `farmid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`farmid`),
  KEY `NewIndex2` (`severity`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `messages` */

DROP TABLE IF EXISTS `messages`;

CREATE TABLE `messages` (
  `id` int(11) NOT NULL auto_increment,
  `messageid` varchar(75) default NULL,
  `instance_id` varchar(15) default NULL,
  `status` tinyint(1) default '0',
  `handle_attempts` int(2) default '1',
  `dtlasthandleattempt` datetime default NULL,
  `message` text,
  `server_id` varchar(36) default NULL,
  `type` enum('in','out') default NULL,
  `isszr` tinyint(1) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`messageid`(50)),
  KEY `NewIndex2` (`instance_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `nameservers` */

DROP TABLE IF EXISTS `nameservers`;

CREATE TABLE `nameservers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `host` varchar(100) default NULL,
  `port` int(10) unsigned default NULL,
  `username` varchar(100) default NULL,
  `password` text,
  `rndc_path` varchar(255) default NULL,
  `named_path` varchar(255) default NULL,
  `namedconf_path` varchar(255) default NULL,
  `isproxy` tinyint(1) default '0',
  `isbackup` tinyint(1) default '0',
  `ipaddress` varchar(15) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `payment_redirects` */

DROP TABLE IF EXISTS `payment_redirects`;

CREATE TABLE `payment_redirects` (
  `id` int(11) default NULL,
  `from_clientid` int(11) default NULL,
  `to_clientid` int(11) default NULL,
  `subscription_id` varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `payments` */

DROP TABLE IF EXISTS `payments`;

CREATE TABLE `payments` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `transactionid` varchar(255) default NULL,
  `subscriptionid` varchar(255) default NULL,
  `dtpaid` datetime default NULL,
  `amount` float(6,2) default NULL,
  `payer_email` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `rds_snaps_info` */

DROP TABLE IF EXISTS `rds_snaps_info`;

CREATE TABLE `rds_snaps_info` (
  `id` int(11) NOT NULL auto_increment,
  `snapid` varchar(50) default NULL,
  `comment` varchar(255) default NULL,
  `dtcreated` datetime default NULL,
  `region` varchar(255) default 'us-east-1',
  `autosnapshotid` int(11) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `real_servers` */

DROP TABLE IF EXISTS `real_servers`;

CREATE TABLE `real_servers` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `ami_id` varchar(255) default NULL,
  `ipaddress` varchar(15) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `rebundle_log` */

DROP TABLE IF EXISTS `rebundle_log`;

CREATE TABLE `rebundle_log` (
  `id` int(11) NOT NULL auto_increment,
  `roleid` int(11) default NULL,
  `dtadded` datetime default NULL,
  `message` text,
  `bundle_task_id` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `records` */

DROP TABLE IF EXISTS `records`;

CREATE TABLE `records` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zoneid` int(10) unsigned NOT NULL default '0',
  `rtype` varchar(6) default NULL,
  `ttl` int(10) unsigned default NULL,
  `rpriority` int(10) unsigned default NULL,
  `rvalue` varchar(255) default NULL,
  `rkey` varchar(255) default NULL,
  `issystem` tinyint(1) default NULL,
  `rweight` int(10) default NULL,
  `rport` int(10) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `zoneid` (`zoneid`,`rtype`(1),`rvalue`,`rkey`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `role_options` */

DROP TABLE IF EXISTS `role_options`;

CREATE TABLE `role_options` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `type` varchar(15) default NULL,
  `isrequired` tinyint(1) default '0',
  `defval` text,
  `allow_multiple_choice` tinyint(1) default '0',
  `options` text,
  `ami_id` varchar(50) default NULL,
  `hash` varchar(255) default NULL,
  `issystem` tinyint(1) default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name_role` (`name`,`ami_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `roles` */

DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `id` int(11) NOT NULL auto_increment,
  `ami_id` varchar(255) default NULL,
  `name` varchar(255) default NULL,
  `roletype` enum('SHARED','CUSTOM') default NULL,
  `clientid` int(11) default NULL,
  `comments` text,
  `dtbuilt` datetime default NULL,
  `description` text,
  `replace` varchar(255) default NULL,
  `default_minLA` int(5) default NULL,
  `default_maxLA` int(5) default NULL,
  `alias` varchar(255) default NULL,
  `instance_type` varchar(255) default 'm1.small',
  `architecture` varchar(10) default 'i386',
  `isstable` tinyint(1) default '1',
  `prototype_role` varchar(255) default NULL,
  `approval_state` varchar(255) default NULL,
  `region` varchar(255) default 'us-east-1',
  `default_ssh_port` int(5) default '22',
  `platform` varchar(20) default 'ec2',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`ami_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `scheduler_tasks` */

DROP TABLE IF EXISTS `scheduler_tasks`;

CREATE TABLE `scheduler_tasks` (
  `id` int(11) NOT NULL auto_increment,
  `task_name` varchar(255) default NULL,
  `task_type` varchar(255) default NULL,
  `target_id` varchar(255) default NULL COMMENT 'id of farm, farm_role or farm_role:index from other tables',
  `target_type` varchar(255) default NULL COMMENT 'farm, role or instance type',
  `start_time_date` datetime default NULL COMMENT 'start task''s time',
  `end_time_date` datetime default NULL COMMENT 'end task by this time',
  `last_start_time` datetime default NULL COMMENT 'the last time task was started',
  `restart_every` int(11) default '0' COMMENT 'restart task every N minutes',
  `task_config` text COMMENT 'arguments for script',
  `order_index` int(11) default NULL COMMENT 'task order',
  `client_id` int(11) default NULL COMMENT 'Task belongs to selected client',
  `status` varchar(11) default NULL COMMENT 'active, suspended, finished',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `script_revisions` */

DROP TABLE IF EXISTS `script_revisions`;

CREATE TABLE `script_revisions` (
  `id` int(11) NOT NULL auto_increment,
  `scriptid` int(11) default NULL,
  `revision` int(11) default NULL,
  `script` longtext,
  `dtcreated` datetime default NULL,
  `approval_state` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `scripting_log` */

DROP TABLE IF EXISTS `scripting_log`;

CREATE TABLE `scripting_log` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `event` varchar(255) default NULL,
  `server_id` varchar(36) default NULL,
  `dtadded` datetime default NULL,
  `message` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `scripts` */

DROP TABLE IF EXISTS `scripts`;

CREATE TABLE `scripts` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `origin` varchar(50) default NULL,
  `dtadded` datetime default NULL,
  `issync` tinyint(1) default '0',
  `clientid` int(11) default '0',
  `approval_state` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `security_rules` */

DROP TABLE IF EXISTS `security_rules`;

CREATE TABLE `security_rules` (
  `id` int(11) NOT NULL auto_increment,
  `roleid` int(11) default NULL,
  `rule` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `sensor_data` */

DROP TABLE IF EXISTS `sensor_data`;

CREATE TABLE `sensor_data` (
  `id` int(11) NOT NULL auto_increment,
  `farm_roleid` int(11) default NULL,
  `sensor_name` varchar(255) default NULL,
  `sensor_value` varchar(255) default NULL,
  `dtlastupdate` int(11) default NULL,
  `raw_sensor_data` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unique` (`farm_roleid`,`sensor_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `server_properties` */

DROP TABLE IF EXISTS `server_properties`;

CREATE TABLE `server_properties` (
  `id` int(11) NOT NULL auto_increment,
  `server_id` varchar(36) default NULL,
  `name` varchar(255) default NULL,
  `value` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `serverid_name` (`server_id`,`name`),
  KEY `serverid` (`server_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `servers` */

DROP TABLE IF EXISTS `servers`;

CREATE TABLE `servers` (
  `id` int(11) NOT NULL auto_increment,
  `server_id` varchar(36) default NULL,
  `farm_id` int(11) default NULL,
  `farm_roleid` int(11) default NULL,
  `client_id` int(11) default NULL,
  `role_id` int(11) default NULL,
  `platform` varchar(10) default NULL,
  `status` varchar(25) default NULL,
  `remote_ip` varchar(15) default NULL,
  `local_ip` varchar(15) default NULL,
  `dtadded` datetime default NULL,
  `index` int(11) default NULL,
  `dtshutdownscheduled` datetime default NULL,
  `dtrebootstart` datetime default NULL,
  `replace_server_id` varchar(36) default NULL,
  `dtlastsync` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `serverid` (`server_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `subscriptions` */

DROP TABLE IF EXISTS `subscriptions`;

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `subscriptionid` varchar(255) default NULL,
  `dtstart` datetime default NULL,
  `status` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `syslog` */

DROP TABLE IF EXISTS `syslog`;

CREATE TABLE `syslog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dtadded` datetime default NULL,
  `message` text,
  `severity` varchar(10) default NULL,
  `dtadded_time` bigint(20) default NULL,
  `transactionid` varchar(50) default NULL,
  `backtrace` text,
  `caller` varchar(255) default NULL,
  `path` varchar(255) default NULL,
  `sub_transactionid` varchar(50) default NULL,
  `farmid` varchar(20) default '0',
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`transactionid`),
  KEY `NewIndex2` (`sub_transactionid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `syslog_metadata` */

DROP TABLE IF EXISTS `syslog_metadata`;

CREATE TABLE `syslog_metadata` (
  `id` int(11) NOT NULL auto_increment,
  `transactionid` varchar(50) default NULL,
  `errors` int(5) default NULL,
  `warnings` int(5) default NULL,
  `message` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `transid` (`transactionid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `task_queue` */

DROP TABLE IF EXISTS `task_queue`;

CREATE TABLE `task_queue` (
  `id` int(11) NOT NULL auto_increment,
  `queue_name` varchar(255) default NULL,
  `data` text,
  `dtadded` datetime default NULL,
  `failed_attempts` int(3) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Table structure for table `vhosts` */

DROP TABLE IF EXISTS `vhosts`;

CREATE TABLE `vhosts` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) default NULL,
  `document_root_dir` varchar(100) default NULL,
  `server_admin` varchar(100) default NULL,
  `issslenabled` tinyint(1) default NULL,
  `farmid` int(11) default NULL,
  `logs_dir` varchar(100) default NULL,
  `ssl_cert` text,
  `ssl_pkey` text,
  `aliases` text,
  `role_name` varchar(255) default NULL,
  `farm_roleid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`name`,`farmid`),
  KEY `farm_roleid` (`farm_roleid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `wus_info` */

DROP TABLE IF EXISTS `wus_info`;

CREATE TABLE `wus_info` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `company` varchar(255) default NULL,
  `about` text,
  `scalrabout` text,
  `isapproved` tinyint(1) default '0',
  `url` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`clientid`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

/*Table structure for table `zones` */

DROP TABLE IF EXISTS `zones`;

CREATE TABLE `zones` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zone` varchar(255) default NULL,
  `soa_owner` varchar(100) default NULL,
  `soa_ttl` int(10) unsigned default NULL,
  `soa_parent` varchar(100) default NULL,
  `soa_serial` int(10) unsigned default NULL,
  `soa_refresh` int(10) unsigned default NULL,
  `soa_retry` int(10) unsigned default NULL,
  `soa_expire` int(10) unsigned default NULL,
  `min_ttl` int(10) unsigned default NULL,
  `dtupdated` datetime default NULL,
  `farmid` int(11) default NULL,
  `ami_id` varchar(255) default NULL,
  `clientid` int(11) default NULL,
  `status` tinyint(1) default '0',
  `role_name` varchar(255) default NULL,
  `islocked` tinyint(1) default '0',
  `dtlocked` int(11) default NULL,
  `lockedby` int(2) default NULL,
  `axfr_allowed_hosts` text,
  `hosts_list_updated` tinyint(1) default '0',
  `isobsoleted` tinyint(1) default NULL,
  `allow_manage_system_records` tinyint(1) default '0',
  `farm_roleid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `zones_index3945` (`zone`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

alter table `apache_vhosts` add column `httpd_conf_ssl` text NULL after `advanced_mode`

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
