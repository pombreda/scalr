CREATE TABLE `autosnap_settings` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `volumeid` varchar(15) default NULL,
  `period` int(5) default NULL,
  `dtlastsnapshot` datetime default NULL,
  `rotate` int(11) default NULL,
  `last_snapshotid` varchar(50) default NULL,
  `region` varchar(50) default 'us-east-1',
  `arrayid` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

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
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

CREATE TABLE `messages` (
  `id` int(11) NOT NULL auto_increment,
  `messageid` varchar(75) default NULL,
  `instance_id` varchar(15) default NULL,
  `isdelivered` tinyint(1) default '0',
  `delivery_attempts` int(2) default '1',
  `dtlastdeliveryattempt` datetime default NULL,
  `message` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`messageid`(50)),
  KEY `NewIndex2` (`instance_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;



alter table `farm_ebs` add column `instance_index` tinyint(2) DEFAULT '0' NULL after `isfsexists`, add column `ebsgroup` varchar(255) NULL after `instance_index`;
alter table `farm_ebs` add column `ismanual` tinyint(1) DEFAULT '0' NULL after `ebsgroup`;
alter table `farm_ebs` change `ebsgroup` `ebsstorageid` int(11) default '0' NULL;
alter table `farm_ebs` change `ebsstorageid` `ebs_arrayid` int(11) default '0' NULL;
alter table `farm_ebs` add column `ebs_array_part` int(11) DEFAULT '1' NULL after `ismanual`;
alter table `farm_ebs` add column `region` varchar(50) DEFAULT 'us-east-1' NULL after `ebs_array_part`;

alter table `ebs_snaps_info` add column `ebs_storageid` int(11) DEFAULT '0' NULL after `dtcreated`;
alter table `ebs_snaps_info` add column `partno` int(11) DEFAULT '1' NULL after `ebs_storageid`;
alter table `ebs_snaps_info` change `ebs_storageid` `ebs_arrayid` int(11) default '0' NULL , change `partno` `ebs_array_part` int(11) default '1' NULL;
alter table `ebs_snaps_info` drop column `ebs_array_part`,change `ebs_arrayid` `ebs_array_snapid` int(11) default '0' NULL;
alter table `ebs_snaps_info` add column `region` varchar(255) DEFAULT 'us-east-1' NULL after `ebs_array_snapid`;
alter table `ebs_snaps_info` add column `isautosnapshot` tinyint(1) DEFAULT '0' NULL after `region`;
alter table `ebs_snaps_info` change `isautosnapshot` `autosnapshotid` int(11) default '0' NULL;


alter table `farms` add column `region` varchar(255) DEFAULT 'us-east-1' NULL after `mysql_bundle`;

ALTER TABLE `farm_instances` ADD `index` INT( 11 ) NULL;
alter table `farm_instances` add column `region` varchar(50) DEFAULT 'us-east-1' NULL after `index`;
alter table `farm_instances` add column `dtlaststatusupdate` int(11) NULL after `region`;
alter table `farm_instances` add column `scalarizr_pkg_version` varchar(20) NULL after `dtlaststatusupdate`;


alter table `records` add column `rweight` int(10) NULL after `issystem`, add column `rport` int(10) NULL after `rweight`;
alter table `records` change `rtype` `rtype` varchar(6) NULL;

alter table `farm_role_scripts` add column `order_index` int(5) DEFAULT '0' NULL after `ismenuitem`;

alter table `autosnap_settings` change `volumeid` `volumeid` varchar(15) character set latin1 collate latin1_swedish_ci default '0' NULL , change `arrayid` `arrayid` int(11) default '0' NULL;

alter table `nameservers` add column `isbackup` tinyint(1) DEFAULT '0' NULL after `isproxy`, add column `ipaddress` varchar(15) NULL after `isbackup`;

alter table `clients` add column `login_attempts` int(2) DEFAULT '0' NULL after `iswelcomemailsent`, add column `dtlastloginattempt` datetime NULL after `login_attempts`;

alter table `ami_roles` add column `ismasterbundle` tinyint(1) DEFAULT '0' NULL after `approval_state`;
alter table `ami_roles` add column `region` varchar(255) DEFAULT 'us-east-1' NULL after `ismasterbundle`;

alter table `farm_amis` add column `status_timeout` int(10) DEFAULT '20' NULL after `ebs_mount`;
alter table `farm_amis` add column `aki_id` varchar(25) NULL after `status_timeout`, add column `ari_id` varchar(25) NULL after `aki_id`;