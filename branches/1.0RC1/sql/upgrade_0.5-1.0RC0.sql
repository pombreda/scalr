
create table `client_settings`(
	`id` int not null auto_increment,
	`clientid` int,
	`key` varchar(255),
	`value` text,
	primary key (`id`),
	unique index `NewIndex1` (`clientid`,`key`)
);

create table `countries`(
	`id` int not null auto_increment,
	`name` varchar(64) not null,
	`code` char(2) not null,
	primary key (`id`),
	index `IDX_COUNTRIES_NAME` (`name`)
);

create table `default_records`(
	`id` int not null auto_increment,
	`clientid` int default 0,
	`rtype` enum('NS','MX','CNAME','A'),
	`ttl` int default 14400,
	`rpriority` int,
	`rvalue` varchar(255),
	`rkey` varchar(255),
	primary key (`id`)
);

create table `real_servers`(
	`id` int not null auto_increment,
	`farmid` int,
	`ami_id` varchar(255),
	`ipaddress` varchar(15),
	primary key (`id`)
);


alter table `ami_roles` add column `instance_type` varchar(255) DEFAULT 'm1.small' NULL after `alias`, add column `architecture` varchar(10) DEFAULT 'i386' NULL after `instance_type`;

alter table `clients` add column `isactive` tinyint(1) DEFAULT '0' NULL after `farms_limit`, add column `fullname` varchar(60) NULL after `isactive`, add column `org` varchar(60) NULL after `fullname`, add column `country` varchar(60) NULL after `org`, add column `state` varchar(60) NULL after `country`, add column `city` varchar(60) NULL after `state`, add column `zipcode` varchar(60) NULL after `city`, add column `address1` varchar(60) NULL after `zipcode`, add column `address2` varchar(60) NULL after `address1`, add column `phone` varchar(60) NULL after `address2`, add column `fax` varchar(60) NULL after `phone`;

alter table `farm_amis` add column `avail_zone` varchar(255) NULL after `replace_to_ami`, add column `instance_type` varchar(255) DEFAULT 'm1.small' NULL after `avail_zone`;

alter table `farm_instances` add column `isactive` tinyint(1) DEFAULT '1' NULL after `isrebootlaunched`, add column `role_name` varchar(255) NULL after `isactive`;

alter table `logentries` add column `farmid` int NULL after `source`,change `time` `time` int(11) DEFAULT '0' NOT NULL ;

alter table `syslog` add column `transactionid` varchar(50) NULL after `dtadded_time`, add column `backtrace` text NULL after `transactionid`, add column `caller` varchar(255) NULL after `backtrace`, add column `path` varchar(255) NULL after `caller`,change `severity` `severity` varchar(10) NULL ;

alter table `syslog` add index `NewIndex1` (`transactionid`);

alter table `zones` add column `role_name` varchar(255) NULL after `isdeleted`;

delete from config WHERE `key` IN ('Submit','id','page','f');

insert  into `countries`(`id`,`name`,`code`) values (1,'Afghanistan','AF'),(2,'Albania','AL'),(3,'Algeria','DZ'),(4,'American Samoa','AS'),(5,'Andorra','AD'),(6,'Angola','AO'),(7,'Anguilla','AI'),(8,'Antarctica','AQ'),(9,'Antigua and Barbuda','AG'),(10,'Argentina','AR'),(11,'Armenia','AM'),(12,'Aruba','AW'),(13,'Australia','AU'),(14,'Austria','AT'),(15,'Azerbaijan','AZ'),(16,'Bahamas','BS'),(17,'Bahrain','BH'),(18,'Bangladesh','BD'),(19,'Barbados','BB'),(20,'Belarus','BY'),(21,'Belgium','BE'),(22,'Belize','BZ'),(23,'Benin','BJ'),(24,'Bermuda','BM'),(25,'Bhutan','BT'),(26,'Bolivia','BO'),(27,'Bosnia and Herzegowina','BA'),(28,'Botswana','BW'),(29,'Bouvet Island','BV'),(30,'Brazil','BR'),(31,'British Indian Ocean Territory','IO'),(32,'Brunei Darussalam','BN'),(33,'Bulgaria','BG'),(34,'Burkina Faso','BF'),(35,'Burundi','BI'),(36,'Cambodia','KH'),(37,'Cameroon','CM'),(38,'Canada','CA'),(39,'Cape Verde','CV'),(40,'Cayman Islands','KY'),(41,'Central African Republic','CF'),(42,'Chad','TD'),(43,'Chile','CL'),(44,'China','CN'),(45,'Christmas Island','CX'),(46,'Cocos (Keeling) Islands','CC'),(47,'Colombia','CO'),(48,'Comoros','KM'),(49,'Congo','CG'),(50,'Cook Islands','CK'),(51,'Costa Rica','CR'),(52,'Cote D\'Ivoire','CI'),(53,'Croatia','HR'),(54,'Cuba','CU'),(55,'Cyprus','CY'),(56,'Czech Republic','CZ'),(57,'Denmark','DK'),(58,'Djibouti','DJ'),(59,'Dominica','DM'),(60,'Dominican Republic','DO'),(61,'East Timor','TP'),(62,'Ecuador','EC'),(63,'Egypt','EG'),(64,'El Salvador','SV'),(65,'Equatorial Guinea','GQ'),(66,'Eritrea','ER'),(67,'Estonia','EE'),(68,'Ethiopia','ET'),(69,'Falkland Islands (Malvinas)','FK'),(70,'Faroe Islands','FO'),(71,'Fiji','FJ'),(72,'Finland','FI'),(73,'France','FR'),(74,'France, MEtropolitan','FX'),(75,'French Guiana','GF'),(76,'French Polynesia','PF'),(77,'French Southern Territories','TF'),(78,'Gabon','GA'),(79,'Gambia','GM'),(80,'Georgia','GE'),(81,'Germany','DE'),(82,'Ghana','GH'),(83,'Gibraltar','GI'),(84,'Greece','GR'),(85,'Greenland','GL'),(86,'Grenada','GD'),(87,'Guadeloupe','GP'),(88,'Guam','GU'),(89,'Guatemala','GT'),(90,'Guinea','GN'),(91,'Guinea-bissau','GW'),(92,'Guyana','GY'),(93,'Haiti','HT'),(94,'Heard and Mc Donald Islands','HM'),(95,'Honduras','HN'),(96,'Hong Kong','HK'),(97,'Hungary','HU'),(98,'Iceland','IS'),(99,'India','IN'),(100,'Indonesia','ID'),(101,'Iran (Islamic Republic of)','IR'),(102,'Iraq','IQ'),(103,'Ireland','IE'),(104,'Israel','IL'),(105,'Italy','IT'),(106,'Jamaica','JM'),(107,'Japan','JP'),(108,'Jordan','JO'),(109,'Kazakhstan','KZ'),(110,'Kenya','KE'),(111,'Kiribati','KI'),(112,'Korea, Democratic People\'s Republic of','KP'),(113,'Korea, Republic of','KR'),(114,'Kuwait','KW'),(115,'Kyrgyzstan','KG'),(116,'Lao People\'s Democratic Republic','LA'),(117,'Latvia','LV'),(118,'Lebanon','LB'),(119,'Lesotho','LS'),(120,'Liberia','LR'),(121,'Libyan Arab Jamahiriya','LY'),(122,'Liechtenstein','LI'),(123,'Lithuania','LT'),(124,'Luxembourg','LU'),(125,'Macau','MO'),(126,'Macedonia, The Former Yugoslav Republic of','MK'),(127,'Madagascar','MG'),(128,'Malawi','MW'),(129,'Malaysia','MY'),(130,'Maldives','MV'),(131,'Mali','ML'),(132,'Malta','MT'),(133,'Marshall Islands','MH'),(134,'Martinique','MQ'),(135,'Mauritania','MR'),(136,'Mauritius','MU'),(137,'Mayotte','YT'),(138,'Mexico','MX'),(139,'Micronesia, Federated States of','FM'),(140,'Moldova, Republic of','MD'),(141,'Monaco','MC'),(142,'Mongolia','MN'),(143,'Montserrat','MS'),(144,'Morocco','MA'),(145,'Mozambique','MZ'),(146,'Myanmar','MM'),(147,'Namibia','NA'),(148,'Nauru','NR'),(149,'Nepal','NP'),(150,'Netherlands','NL'),(151,'Netherlands Antilles','AN'),(152,'New Caledonia','NC'),(153,'New Zealand','NZ'),(154,'Nicaragua','NI'),(155,'Niger','NE'),(156,'Nigeria','NG'),(157,'Niue','NU'),(158,'Norfolk Island','NF'),(159,'Northern Mariana Islands','MP'),(160,'Norway','NO'),(161,'Oman','OM'),(162,'Pakistan','PK'),(163,'Palau','PW'),(164,'Panama','PA'),(165,'Papua New Guinea','PG'),(166,'Paraguay','PY'),(167,'Peru','PE'),(168,'Philippines','PH'),(169,'Pitcairn','PN'),(170,'Poland','PL'),(171,'Portugal','PT'),(172,'Puerto Rico','PR'),(173,'Qatar','QA'),(174,'Reunion','RE'),(175,'Romania','RO'),(176,'Russian Federation','RU'),(177,'Rwanda','RW'),(178,'Saint Kitts and Nevis','KN'),(179,'Saint Lucia','LC'),(180,'Saint Vincent and the Grenadines','VC'),(181,'Samoa','WS'),(182,'San Marino','SM'),(183,'Sao Tome and Principe','ST'),(184,'Saudi Arabia','SA'),(185,'Senegal','SN'),(186,'Seychelles','SC'),(187,'Sierra Leone','SL'),(188,'Singapore','SG'),(189,'Slovakia (Slovak Republic)','SK'),(190,'Slovenia','SI'),(191,'Solomon Islands','SB'),(192,'Somalia','SO'),(193,'south Africa','ZA'),(194,'South Georgia and the South Sandwich Islands','GS'),(195,'Spain','ES'),(196,'Sri Lanka','LK'),(197,'St. Helena','SH'),(198,'St. Pierre and Miquelon','PM'),(199,'Sudan','SD'),(200,'Suriname','SR'),(201,'Svalbard and Jan Mayen Islands','SJ'),(202,'Swaziland','SZ'),(203,'Sweden','SE'),(204,'Switzerland','CH'),(205,'Syrian Arab Republic','SY'),(206,'Taiwan, Province of China','TW'),(207,'Tajikistan','TJ'),(208,'Tanzania, United Republic of','TZ'),(209,'Thailand','TH'),(210,'Togo','TG'),(211,'Tokelau','TK'),(212,'Tonga','TO'),(213,'Trinidad and Tobago','TT'),(214,'Tunisia','TN'),(215,'Turkey','TR'),(216,'Turkmenistan','TM'),(217,'Turks and Caicos Islands','TC'),(218,'Tuvalu','TV'),(219,'Uganda','UG'),(220,'Ukraine','UA'),(221,'United Arab Emirates','AE'),(222,'United Kingdom','GB'),(223,'United States','US'),(224,'United States Minor Outlying Islands','UM'),(225,'Uruguay','UY'),(226,'Uzbekistan','UZ'),(227,'Vanuatu','VU'),(228,'Vatican City State (Holy See)','VA'),(229,'Venezuela','VE'),(230,'Viet Nam','VN'),(231,'Virgin Islands (British)','VG'),(232,'Virgin Islands (U.S.)','VI'),(233,'Wallis and Futuna Islands','WF'),(234,'Western Sahara','EH'),(235,'Yemen','YE'),(236,'Yugoslavia','YU'),(237,'Zaire','ZR'),(238,'Zambia','ZM'),(239,'Zimbabwe','ZW');


insert into `ami_roles`(`id`,`ami_id`,`name`,`roletype`,`clientid`,`prototype_iid`,`iscompleted`,`comments`,`dtbuilt`,`description`,`replace`,`default_minLA`,`default_maxLA`,`alias`,`instance_type`,`architecture`) values ( NULL,'ami-0aad4963','base64','SHARED','0',NULL,'1',NULL,NULL,NULL,NULL,'5','10','base','m1.large','x86_64');
insert into security_rules (id, roleid, rule) SELECT null, (SELECT id FROM ami_roles WHERE name='base64'), rule FROM security_rules WHERE roleid = (SELECT id FROM ami_roles WHERE name='base');

insert into `ami_roles`(`id`,`ami_id`,`name`,`roletype`,`clientid`,`prototype_iid`,`iscompleted`,`comments`,`dtbuilt`,`description`,`replace`,`default_minLA`,`default_maxLA`,`alias`,`instance_type`,`architecture`) values ( NULL,'ami-16ac487f','mysql64','SHARED','0',NULL,'1',NULL,NULL,NULL,NULL,'5','10','mysql','m1.large','x86_64');
insert into security_rules (id, roleid, rule) SELECT null, (SELECT id FROM ami_roles WHERE name='mysql64'), rule FROM security_rules WHERE roleid = (SELECT id FROM ami_roles WHERE name='mysql');

insert into `ami_roles`(`id`,`ami_id`,`name`,`roletype`,`clientid`,`prototype_iid`,`iscompleted`,`comments`,`dtbuilt`,`description`,`replace`,`default_minLA`,`default_maxLA`,`alias`,`instance_type`,`architecture`) values ( NULL,'ami-e3a2468a','www64','SHARED','0',NULL,'1',NULL,NULL,NULL,NULL,'5','10','www','m1.large','x86_64');
insert into security_rules (id, roleid, rule) SELECT null, (SELECT id FROM ami_roles WHERE name='www64'), rule FROM security_rules WHERE roleid = (SELECT id FROM ami_roles WHERE name='www');

insert into `ami_roles`(`id`,`ami_id`,`name`,`roletype`,`clientid`,`prototype_iid`,`iscompleted`,`comments`,`dtbuilt`,`description`,`replace`,`default_minLA`,`default_maxLA`,`alias`,`instance_type`,`architecture`) values ( NULL,'ami-e5a2468c','app64','SHARED','0',NULL,'1',NULL,NULL,NULL,NULL,'5','10','app','m1.large','x86_64');
insert into security_rules (id, roleid, rule) SELECT null, (SELECT id FROM ami_roles WHERE name='app64'), rule FROM security_rules WHERE roleid = (SELECT id FROM ami_roles WHERE name='app');

insert into `default_records`(`id`,`clientid`,`rtype`,`ttl`,`rpriority`,`rvalue`,`rkey`) values ( NULL,'0','CNAME','14400',NULL,'%hostname%','www');

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

update ami_roles set ami_id = 'ami-bd8763d4' where name = 'mysql';
update ami_roles set ami_id = 'ami-bfbd59d6' where name = 'app';
update ami_roles set ami_id = 'ami-e6ad498f' where name = 'www';
update ami_roles set ami_id = 'ami-e2aa4e8b' where name = 'base';
update ami_roles set ami_id = 'ami-a68266cf' where name = 'mysql64';
update ami_roles set ami_id = 'ami-e5a2468c' where name = 'app64';
update ami_roles set ami_id = 'ami-e3a2468a' where name = 'www64';
update ami_roles set ami_id = 'ami-0aad4963' where name = 'base64';