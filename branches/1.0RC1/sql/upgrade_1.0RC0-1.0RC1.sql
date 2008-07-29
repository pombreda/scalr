CREATE TABLE `ipaccess` (
  `id` int(11) NOT NULL auto_increment,
  `ipaddress` varchar(255) default NULL,
  `comment` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `events` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `type` varchar(25) default NULL,
  `dtadded` datetime default NULL,
  `message` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `farmid` (`farmid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `farm_event_observers` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `event_observer_name` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`farmid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `farm_event_observers_config` (
  `id` int(11) NOT NULL auto_increment,
  `observerid` int(11) default NULL,
  `key` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`observerid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

alter table `events` add column `ishandled` tinyint(1) DEFAULT '0' NULL after `message`;

alter table `logentries` add index `farmidindex` (`farmid`);
alter table `logentries` add index `severityindex` (`severity`);

alter table `clients` add column `aws_private_key_enc` text NULL after `fax`, add column `aws_certificate_enc` text NULL after `aws_private_key_enc`;

alter table `zones` add column `axfr_allowed_hosts` text NULL after `lockedby`;
alter table `zones` add column `hosts_list_updated` tinyint(1) DEFAULT '0' NULL after `axfr_allowed_hosts`;

create table `garbage_queue`( `id` int(11) NOT NULL AUTO_INCREMENT , `clientid` int(11) , `data` text , PRIMARY KEY (`id`))  Engine=InnoDB;

alter table `garbage_queue` add unique `clientindex` (`clientid`);

insert into `ipaccess`(`id`,`ipaddress`,`comment`) values ( NULL,'*.*.*.*','Allow access from all IPs');

UPDATE ami_roles SET ami_id='ami-8c8f6be5' WHERE name='www';
UPDATE ami_roles SET ami_id='ami-b48f6bdd' WHERE name='www64';
UPDATE ami_roles SET ami_id='ami-bfbd59d6' WHERE name='app';
UPDATE ami_roles SET ami_id='ami-e5a2468c' WHERE name='app64';
UPDATE ami_roles SET ami_id='ami-bd8763d4' WHERE name='mysql';
UPDATE ami_roles SET ami_id='ami-a68266cf' WHERE name='mysql64';
UPDATE ami_roles SET ami_id='ami-e2aa4e8b' WHERE name='base';
UPDATE ami_roles SET ami_id='ami-0aad4963' WHERE name='base64';

alter table `events` add column `short_message` varchar(255) NULL after `ishandled`;

alter table `farms` add column `bcp_instance_id` varchar(20) NULL after `isbcprunning`;

alter table `nameservers` add column `isproxy` tinyint(1) DEFAULT '0' NULL after `namedconf_path`;

create table `syslog_metadata`( `id` int(11) NOT NULL AUTO_INCREMENT , `transactionid` varchar(50) , `errors` int(5) , `warnings` int(5) , PRIMARY KEY (`id`))  ;
alter table `syslog_metadata` add unique `transid` (`transactionid`);