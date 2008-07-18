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