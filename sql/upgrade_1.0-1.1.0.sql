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
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

CREATE TABLE `farm_role_scaling_times` (
  `id` int(11) NOT NULL auto_increment,
  `farm_roleid` int(11) default NULL,
  `start_time` int(11) default NULL,
  `end_time` int(11) default NULL,
  `days_of_week` varchar(75) default NULL,
  `instances_count` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

alter table `ebs_snaps_info` add column `is_autoebs_master_snap` tinyint(1) DEFAULT '0' NULL after `autosnapshotid`;

alter table `ebs_snaps_info` add column `farm_roleid` int(11) NULL after `is_autoebs_master_snap`;

alter table `clients` add column `comments` text NULL after `scalr_api_key`;