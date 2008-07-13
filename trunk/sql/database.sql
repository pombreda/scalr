/*!40101 SET NAMES utf8 */;
/*!40101 SET SQL_MODE=''*/;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

/*Table structure for table `ami_roles` */

DROP TABLE IF EXISTS `ami_roles`;

CREATE TABLE `ami_roles` (
  `id` int(11) NOT NULL auto_increment,
  `ami_id` varchar(255) default NULL,
  `name` varchar(255) default NULL,
  `roletype` enum('SHARED','CUSTOM') default NULL,
  `clientid` int(11) default NULL,
  `prototype_iid` varchar(255) default NULL,
  `iscompleted` tinyint(1) default '1',
  `comments` text,
  `dtbuilt` datetime default NULL,
  `description` varchar(255) default NULL,
  `replace` varchar(255) default NULL,
  `default_minLA` int(5) default NULL,
  `default_maxLA` int(5) default NULL,
  `alias` varchar(255) default NULL,
  `instance_type` varchar(255) default 'm1.small',
  `architecture` varchar(10) default 'i386',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `ami_roles` */

insert  into `ami_roles`(`id`,`ami_id`,`name`,`roletype`,`clientid`,`prototype_iid`,`iscompleted`,`comments`,`dtbuilt`,`description`,`replace`,`default_minLA`,`default_maxLA`,`alias`,`instance_type`,`architecture`) values (1,'ami-e2aa4e8b','base','SHARED',0,NULL,1,NULL,NULL,NULL,NULL,5,10,'base','m1.small','i386'),(2,'ami-bd8763d4','mysql','SHARED',0,NULL,1,NULL,NULL,NULL,NULL,5,10,'mysql','m1.small','i386'),(3,'ami-e6ad498f','www','SHARED',0,NULL,1,NULL,NULL,NULL,NULL,5,10,'www','m1.small','i386'),(7,'ami-bfbd59d6','app','SHARED',0,NULL,1,NULL,NULL,NULL,NULL,5,10,'app','m1.small','i386'),(16,'ami-0aad4963','base64','SHARED',0,NULL,1,NULL,NULL,NULL,NULL,5,10,'base','m1.large','x86_64'),(17,'ami-16ac487f','mysql64','SHARED',0,NULL,1,NULL,NULL,NULL,NULL,5,10,'mysql','m1.large','x86_64'),(18,'ami-e3a2468a','www64','SHARED',0,NULL,1,NULL,NULL,NULL,NULL,5,10,'www','m1.large','x86_64'),(19,'ami-e5a2468c','app64','SHARED',0,NULL,1,NULL,NULL,NULL,NULL,5,10,'app','m1.large','x86_64');

/*Table structure for table `client_settings` */

DROP TABLE IF EXISTS `client_settings`;

CREATE TABLE `client_settings` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `key` varchar(255) default NULL,
  `value` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`clientid`,`key`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `client_settings` */

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
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `clients` */

/*Table structure for table `config` */

DROP TABLE IF EXISTS `config`;

CREATE TABLE `config` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `key` varchar(255) default NULL,
  `value` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `config` */

insert  into `config`(`id`,`key`,`value`) values (96,'admin_login','admin'),(77,'admin_password','8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918'),(3,'cryptokey','QhSqDYX7K5N85W8E'),(4,'crypto_algo','SHA256'),(18,'paging_items','20'),(97,'email_address','admin@scalr.net'),(98,'email_name','Support'),(105,'aws_accountid','123456789012'),(106,'aws_keyname','UIXXXXXXXXXXXMN3'),(102,'def_soa_owner','admin.scalr.net'),(110,'eventhandler_url','67.19.29.18'),(107,'secgroup_prefix','scalr.'),(99,'email_dsn',''),(111,'reboot_timeout','160'),(103,'def_soa_parent','ns1.scalr.net'),(104,'namedconftpl','zone \"{zone}\" {\r\n   type master;\r\n   file \"{db_filename}\";\r\n};'),(101,'dynamic_a_rec_ttl','90'),(108,'s3cfg_template','[default]\r\naccess_key = [access_key]\r\nacl_public = False\r\nforce = False\r\nhost = s3.amazonaws.com\r\nhuman_readable_sizes = False\r\nrecv_chunk = 4096\r\nsecret_key = [secret_key]\r\nsend_chunk = 4096\r\nverbosity = WARNING'),(109,'snmptrap_path','/usr/bin/snmptrap'),(100,'log_days','10'),(112,'launch_timeout','160'),(117,'client_max_instances','20');

/*Table structure for table `countries` */

DROP TABLE IF EXISTS `countries`;

CREATE TABLE `countries` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL,
  `code` char(2) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `IDX_COUNTRIES_NAME` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `countries` */

insert  into `countries`(`id`,`name`,`code`) values (1,'Afghanistan','AF'),(2,'Albania','AL'),(3,'Algeria','DZ'),(4,'American Samoa','AS'),(5,'Andorra','AD'),(6,'Angola','AO'),(7,'Anguilla','AI'),(8,'Antarctica','AQ'),(9,'Antigua and Barbuda','AG'),(10,'Argentina','AR'),(11,'Armenia','AM'),(12,'Aruba','AW'),(13,'Australia','AU'),(14,'Austria','AT'),(15,'Azerbaijan','AZ'),(16,'Bahamas','BS'),(17,'Bahrain','BH'),(18,'Bangladesh','BD'),(19,'Barbados','BB'),(20,'Belarus','BY'),(21,'Belgium','BE'),(22,'Belize','BZ'),(23,'Benin','BJ'),(24,'Bermuda','BM'),(25,'Bhutan','BT'),(26,'Bolivia','BO'),(27,'Bosnia and Herzegowina','BA'),(28,'Botswana','BW'),(29,'Bouvet Island','BV'),(30,'Brazil','BR'),(31,'British Indian Ocean Territory','IO'),(32,'Brunei Darussalam','BN'),(33,'Bulgaria','BG'),(34,'Burkina Faso','BF'),(35,'Burundi','BI'),(36,'Cambodia','KH'),(37,'Cameroon','CM'),(38,'Canada','CA'),(39,'Cape Verde','CV'),(40,'Cayman Islands','KY'),(41,'Central African Republic','CF'),(42,'Chad','TD'),(43,'Chile','CL'),(44,'China','CN'),(45,'Christmas Island','CX'),(46,'Cocos (Keeling) Islands','CC'),(47,'Colombia','CO'),(48,'Comoros','KM'),(49,'Congo','CG'),(50,'Cook Islands','CK'),(51,'Costa Rica','CR'),(52,'Cote D\'Ivoire','CI'),(53,'Croatia','HR'),(54,'Cuba','CU'),(55,'Cyprus','CY'),(56,'Czech Republic','CZ'),(57,'Denmark','DK'),(58,'Djibouti','DJ'),(59,'Dominica','DM'),(60,'Dominican Republic','DO'),(61,'East Timor','TP'),(62,'Ecuador','EC'),(63,'Egypt','EG'),(64,'El Salvador','SV'),(65,'Equatorial Guinea','GQ'),(66,'Eritrea','ER'),(67,'Estonia','EE'),(68,'Ethiopia','ET'),(69,'Falkland Islands (Malvinas)','FK'),(70,'Faroe Islands','FO'),(71,'Fiji','FJ'),(72,'Finland','FI'),(73,'France','FR'),(74,'France, MEtropolitan','FX'),(75,'French Guiana','GF'),(76,'French Polynesia','PF'),(77,'French Southern Territories','TF'),(78,'Gabon','GA'),(79,'Gambia','GM'),(80,'Georgia','GE'),(81,'Germany','DE'),(82,'Ghana','GH'),(83,'Gibraltar','GI'),(84,'Greece','GR'),(85,'Greenland','GL'),(86,'Grenada','GD'),(87,'Guadeloupe','GP'),(88,'Guam','GU'),(89,'Guatemala','GT'),(90,'Guinea','GN'),(91,'Guinea-bissau','GW'),(92,'Guyana','GY'),(93,'Haiti','HT'),(94,'Heard and Mc Donald Islands','HM'),(95,'Honduras','HN'),(96,'Hong Kong','HK'),(97,'Hungary','HU'),(98,'Iceland','IS'),(99,'India','IN'),(100,'Indonesia','ID'),(101,'Iran (Islamic Republic of)','IR'),(102,'Iraq','IQ'),(103,'Ireland','IE'),(104,'Israel','IL'),(105,'Italy','IT'),(106,'Jamaica','JM'),(107,'Japan','JP'),(108,'Jordan','JO'),(109,'Kazakhstan','KZ'),(110,'Kenya','KE'),(111,'Kiribati','KI'),(112,'Korea, Democratic People\'s Republic of','KP'),(113,'Korea, Republic of','KR'),(114,'Kuwait','KW'),(115,'Kyrgyzstan','KG'),(116,'Lao People\'s Democratic Republic','LA'),(117,'Latvia','LV'),(118,'Lebanon','LB'),(119,'Lesotho','LS'),(120,'Liberia','LR'),(121,'Libyan Arab Jamahiriya','LY'),(122,'Liechtenstein','LI'),(123,'Lithuania','LT'),(124,'Luxembourg','LU'),(125,'Macau','MO'),(126,'Macedonia, The Former Yugoslav Republic of','MK'),(127,'Madagascar','MG'),(128,'Malawi','MW'),(129,'Malaysia','MY'),(130,'Maldives','MV'),(131,'Mali','ML'),(132,'Malta','MT'),(133,'Marshall Islands','MH'),(134,'Martinique','MQ'),(135,'Mauritania','MR'),(136,'Mauritius','MU'),(137,'Mayotte','YT'),(138,'Mexico','MX'),(139,'Micronesia, Federated States of','FM'),(140,'Moldova, Republic of','MD'),(141,'Monaco','MC'),(142,'Mongolia','MN'),(143,'Montserrat','MS'),(144,'Morocco','MA'),(145,'Mozambique','MZ'),(146,'Myanmar','MM'),(147,'Namibia','NA'),(148,'Nauru','NR'),(149,'Nepal','NP'),(150,'Netherlands','NL'),(151,'Netherlands Antilles','AN'),(152,'New Caledonia','NC'),(153,'New Zealand','NZ'),(154,'Nicaragua','NI'),(155,'Niger','NE'),(156,'Nigeria','NG'),(157,'Niue','NU'),(158,'Norfolk Island','NF'),(159,'Northern Mariana Islands','MP'),(160,'Norway','NO'),(161,'Oman','OM'),(162,'Pakistan','PK'),(163,'Palau','PW'),(164,'Panama','PA'),(165,'Papua New Guinea','PG'),(166,'Paraguay','PY'),(167,'Peru','PE'),(168,'Philippines','PH'),(169,'Pitcairn','PN'),(170,'Poland','PL'),(171,'Portugal','PT'),(172,'Puerto Rico','PR'),(173,'Qatar','QA'),(174,'Reunion','RE'),(175,'Romania','RO'),(176,'Russian Federation','RU'),(177,'Rwanda','RW'),(178,'Saint Kitts and Nevis','KN'),(179,'Saint Lucia','LC'),(180,'Saint Vincent and the Grenadines','VC'),(181,'Samoa','WS'),(182,'San Marino','SM'),(183,'Sao Tome and Principe','ST'),(184,'Saudi Arabia','SA'),(185,'Senegal','SN'),(186,'Seychelles','SC'),(187,'Sierra Leone','SL'),(188,'Singapore','SG'),(189,'Slovakia (Slovak Republic)','SK'),(190,'Slovenia','SI'),(191,'Solomon Islands','SB'),(192,'Somalia','SO'),(193,'south Africa','ZA'),(194,'South Georgia and the South Sandwich Islands','GS'),(195,'Spain','ES'),(196,'Sri Lanka','LK'),(197,'St. Helena','SH'),(198,'St. Pierre and Miquelon','PM'),(199,'Sudan','SD'),(200,'Suriname','SR'),(201,'Svalbard and Jan Mayen Islands','SJ'),(202,'Swaziland','SZ'),(203,'Sweden','SE'),(204,'Switzerland','CH'),(205,'Syrian Arab Republic','SY'),(206,'Taiwan, Province of China','TW'),(207,'Tajikistan','TJ'),(208,'Tanzania, United Republic of','TZ'),(209,'Thailand','TH'),(210,'Togo','TG'),(211,'Tokelau','TK'),(212,'Tonga','TO'),(213,'Trinidad and Tobago','TT'),(214,'Tunisia','TN'),(215,'Turkey','TR'),(216,'Turkmenistan','TM'),(217,'Turks and Caicos Islands','TC'),(218,'Tuvalu','TV'),(219,'Uganda','UG'),(220,'Ukraine','UA'),(221,'United Arab Emirates','AE'),(222,'United Kingdom','GB'),(223,'United States','US'),(224,'United States Minor Outlying Islands','UM'),(225,'Uruguay','UY'),(226,'Uzbekistan','UZ'),(227,'Vanuatu','VU'),(228,'Vatican City State (Holy See)','VA'),(229,'Venezuela','VE'),(230,'Viet Nam','VN'),(231,'Virgin Islands (British)','VG'),(232,'Virgin Islands (U.S.)','VI'),(233,'Wallis and Futuna Islands','WF'),(234,'Western Sahara','EH'),(235,'Yemen','YE'),(236,'Yugoslavia','YU'),(237,'Zaire','ZR'),(238,'Zambia','ZM'),(239,'Zimbabwe','ZW');

/*Table structure for table `default_records` */

DROP TABLE IF EXISTS `default_records`;

CREATE TABLE `default_records` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default '0',
  `rtype` enum('NS','MX','CNAME','A') default NULL,
  `ttl` int(11) default '14400',
  `rpriority` int(11) default NULL,
  `rvalue` varchar(255) default NULL,
  `rkey` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `default_records` */
insert into `default_records`(`id`,`clientid`,`rtype`,`ttl`,`rpriority`,`rvalue`,`rkey`) values ( NULL,'0','CNAME','14400',NULL,'%hostname%','www');

/*Table structure for table `farm_amis` */

DROP TABLE IF EXISTS `farm_amis`;

CREATE TABLE `farm_amis` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `ami_id` varchar(255) default NULL,
  `min_count` int(3) default '1',
  `max_count` int(3) default '2',
  `min_LA` float(5,2) default NULL,
  `max_LA` float(5,2) default NULL,
  `replace_to_ami` varchar(255) default NULL,
  `avail_zone` varchar(255) default NULL,
  `instance_type` varchar(255) default 'm1.small',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `farm_amis` */

/*Table structure for table `farm_instances` */

DROP TABLE IF EXISTS `farm_instances`;

CREATE TABLE `farm_instances` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `instance_id` varchar(255) default NULL,
  `state` enum('Pending','Running') default 'Pending',
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
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `farm_instances` */

/*Table structure for table `farms` */

DROP TABLE IF EXISTS `farms`;

CREATE TABLE `farms` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `name` varchar(255) default NULL,
  `iscompleted` tinyint(1) default '0',
  `hash` varchar(25) default NULL,
  `dtadded` datetime default NULL,
  `private_key` text,
  `private_key_name` varchar(255) default NULL,
  `public_key` text,
  `status` tinyint(1) default '1',
  `mysql_bcp` tinyint(1) default '0',
  `mysql_bcp_every` int(5) default NULL,
  `mysql_rebundle_every` int(5) default NULL,
  `dtlastbcp` int(11) default NULL,
  `dtlastrebundle` int(11) default NULL,
  `isbcprunning` tinyint(1) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `farms` */

/*Table structure for table `logentries` */

DROP TABLE IF EXISTS `logentries`;

CREATE TABLE `logentries` (
  `id` int(11) NOT NULL auto_increment,
  `serverid` varchar(25) NOT NULL default '',
  `message` text NOT NULL,
  `severity` tinyint(1) default '0',
  `time` int(11) NOT NULL default '0',
  `source` varchar(255) default NULL,
  `farmid` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `logentries` */

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
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `nameservers` */

/*Table structure for table `real_servers` */

DROP TABLE IF EXISTS `real_servers`;

CREATE TABLE `real_servers` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `ami_id` varchar(255) default NULL,
  `ipaddress` varchar(15) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `real_servers` */

/*Table structure for table `records` */

DROP TABLE IF EXISTS `records`;

CREATE TABLE `records` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zoneid` int(10) unsigned NOT NULL default '0',
  `rtype` enum('A','MX','CNAME','NS') default NULL,
  `ttl` int(10) unsigned default NULL,
  `rpriority` int(10) unsigned default NULL,
  `rvalue` varchar(255) default NULL,
  `rkey` varchar(255) default NULL,
  `issystem` tinyint(1) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `zoneid` (`zoneid`,`rtype`,`rvalue`,`rkey`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `records` */

/*Table structure for table `security_rules` */

DROP TABLE IF EXISTS `security_rules`;

CREATE TABLE `security_rules` (
  `id` int(11) NOT NULL auto_increment,
  `roleid` int(11) default NULL,
  `rule` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `security_rules` */

insert  into `security_rules`(`id`,`roleid`,`rule`) values (1,1,'tcp:22:22:0.0.0.0/0'),(2,1,'udp:161:162:0.0.0.0/0'),(3,2,'tcp:22:22:0.0.0.0/0'),(4,2,'tcp:3306:3306:0.0.0.0/0'),(5,2,'udp:161:162:0.0.0.0/0'),(35,3,'tcp:80:80:0.0.0.0/0'),(34,3,'tcp:22:22:0.0.0.0/0'),(33,3,'udp:161:162:0.0.0.0/0'),(32,7,'udp:161:162:0.0.0.0/0'),(31,7,'tcp:22:22:0.0.0.0/0'),(30,7,'tcp:80:80:0.0.0.0/0'),(15,9,'tcp:80:80:0.0.0.0/0'),(16,9,'tcp:22:22:0.0.0.0/0'),(17,9,'udp:161:162:0.0.0.0/0'),(27,10,'tcp:80:80:0.0.0.0/0'),(28,10,'tcp:22:22:0.0.0.0/0'),(29,10,'udp:161:162:0.0.0.0/0'),(36,15,'udp:161:162:0.0.0.0/0'),(37,15,'tcp:22:22:0.0.0.0/0'),(38,15,'tcp:80:80:0.0.0.0/0'),(39,16,'tcp:22:22:0.0.0.0/0'),(40,16,'udp:161:162:0.0.0.0/0'),(41,17,'tcp:22:22:0.0.0.0/0'),(42,17,'tcp:3306:3306:0.0.0.0/0'),(43,17,'udp:161:162:0.0.0.0/0'),(44,18,'tcp:80:80:0.0.0.0/0'),(45,18,'tcp:22:22:0.0.0.0/0'),(46,18,'udp:161:162:0.0.0.0/0'),(47,19,'udp:161:162:0.0.0.0/0'),(48,19,'tcp:22:22:0.0.0.0/0'),(49,19,'tcp:80:80:0.0.0.0/0');

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
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`transactionid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `syslog` */

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
  `isdeleted` tinyint(1) default '0',
  `role_name` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `zones_index3945` (`zone`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `zones` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;

alter table `syslog` add column `sub_transactionid` varchar(50) NULL after `path`;
alter table `syslog` add index `NewIndex2` (`sub_transactionid`(50));

alter table `zones` add column `islocked` tinyint(1) DEFAULT '0' NULL after `role_name`;
alter table `zones` add column `dtlocked` int(11) NULL after `islocked`;
alter table `zones` change `isdeleted` `status` tinyint(1) DEFAULT '0' NULL ;
alter table `zones` add column `lockedby` int(2) NULL after `dtlocked`;

alter table `farm_instances` add column `dtlastsync` int(11) DEFAULT '0' NULL after `role_name`;

alter table `syslog` add column `farmid` int(11) DEFAULT '0' NULL after `sub_transactionid`;

alter table `ami_roles` add column `dtbuildstarted` datetime NULL after `architecture`, add column `rebundle_trap_received` tinyint(1) DEFAULT '0' NULL after `dtbuildstarted`;
alter table `ami_roles` add column `fail_details` text NULL after `rebundle_trap_received`;
update ami_roles SET rebundle_trap_received='1';
update farms SET mysql_bcp_every = mysql_bcp_every*60;

create table `rebundle_log`( `id` int(11) NOT NULL AUTO_INCREMENT , `roleid` int(11) , `dtadded` datetime , `message` text , PRIMARY KEY (`id`))  ;