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
  `description` text,
  `replace` varchar(255) default NULL,
  `default_minLA` int(5) default NULL,
  `default_maxLA` int(5) default NULL,
  `alias` varchar(255) default NULL,
  `instance_type` varchar(255) default 'm1.small',
  `architecture` varchar(10) default 'i386',
  `dtbuildstarted` datetime default NULL,
  `rebundle_trap_received` tinyint(1) default '0',
  `fail_details` text,
  `isstable` tinyint(1) default '1',
  `prototype_role` varchar(255) default NULL,
  `approval_state` varchar(255) default NULL,
  `ismasterbundle` tinyint(1) default '0',
  `region` varchar(255) default 'us-east-1',
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`ami_id`)
) ENGINE=InnoDB AUTO_INCREMENT=991 DEFAULT CHARSET=latin1;

/*Data for the table `ami_roles` */

insert  into `ami_roles`(`id`,`ami_id`,`name`,`roletype`,`clientid`,`prototype_iid`,`iscompleted`,`comments`,`dtbuilt`,`description`,`replace`,`default_minLA`,`default_maxLA`,`alias`,`instance_type`,`architecture`,`dtbuildstarted`,`rebundle_trap_received`,`fail_details`,`isstable`,`prototype_role`,`approval_state`,`ismasterbundle`,`region`) values (1,'ami-51f21638','base','SHARED',0,NULL,1,'',NULL,'Bare AMI that doesn\'t involved in web serving. Suitable for batch job workers like media encoders etc.','',1,4,'base','m1.small','i386',NULL,1,NULL,1,NULL,NULL,0,'us-east-1'),(2,'ami-2cf21645','mysql','SHARED',0,NULL,1,NULL,NULL,'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing.','',1,4,'mysql','m1.small','i386',NULL,1,NULL,1,NULL,NULL,0,'us-east-1'),(18,'ami-72f2161b','www','SHARED',0,NULL,1,'',NULL,'Frontend web server/load balancer, running nginx. Proxies all requests to all instances of app role.','',1,5,'www','m1.small','i386',NULL,1,NULL,1,NULL,NULL,0,'us-east-1'),(20,'ami-bac420d3','app','SHARED',0,NULL,1,NULL,NULL,'Apache (LAMP) application server. Can act as a backend (if farm contains www role) or frontend web server.','',1,5,'app','m1.small','i386',NULL,1,NULL,1,NULL,NULL,0,'us-east-1'),(47,'ami-0ac62263','app64','SHARED',0,NULL,1,NULL,NULL,'Apache (LAMP) application server. Can act as a backend (if farm contains www role) or frontend web server.','',1,5,'app','m1.large','x86_64',NULL,1,NULL,1,NULL,NULL,0,'us-east-1'),(48,'ami-03ca2e6a','base64','SHARED',0,NULL,1,NULL,NULL,'Bare AMI that doesn\'t involved in web serving. Suitable for batch job workers like media encoders etc.','',1,5,'base','m1.large','x86_64',NULL,1,NULL,1,NULL,NULL,0,'us-east-1'),(49,'ami-e8c62281','mysql64','SHARED',0,NULL,1,NULL,NULL,'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing.','',1,5,'mysql','m1.large','x86_64',NULL,1,NULL,1,NULL,NULL,0,'us-east-1'),(50,'ami-01ca2e68','www64','SHARED',0,NULL,1,NULL,NULL,'Frontend web server/load balancer, running nginx. Proxies all requests to all instances of app role.','',1,5,'www','m1.large','x86_64',NULL,1,NULL,1,NULL,NULL,0,'us-east-1'),(704,'ami-d09572b9','mysqllvm','SHARED',0,NULL,1,NULL,NULL,'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing. Users LVM to quicker perform backup snapshots and support huge databases.',NULL,1,5,'mysql','m1.small','i386',NULL,0,NULL,0,NULL,NULL,0,'us-east-1'),(892,'ami-21cf2b48','mysqllvm64','SHARED',0,NULL,1,NULL,NULL,'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing. Users LVM to quicker perform backup snapshots and support huge databases.',NULL,1,5,'mysql','m1.large','x86_64',NULL,0,NULL,0,NULL,NULL,0,'us-east-1'),(936,'ami-c2d034ab','app-rails','SHARED',0,'',1,NULL,'2008-09-22 08:05:56','<b>Apache2 + mod_rails + Rails 2.1.1</b><br/>\r\nCan act as a backend (if farm contains www role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\na_? <a target=\"blank\" href=\'http://www.modrails.com/documentation/Users guide.html\'>Phusion Passenger</a><br/>\r\na_? <a target=\"blank\" href=\"http://revolutiononrails.blogspot.com/2007/04/plugin-release-actsasreadonlyable.html\">ActsAsReadonlyable</a>\r\n<br/><br/>\r\n<b>Essential paths:</b><br/>\r\nWebroot:  <code>/var/www</code> - symlinks to <code>/usr/rails/scalr-placeholder/public</code><br/>\r\nDefault virtual host config: <code>/etc/apache2/sites-enabled/000-default</code><br/>','',2,5,'app','m1.small','i386','2008-09-22 07:58:46',1,NULL,0,NULL,NULL,0,'us-east-1'),(959,'ami-cfd034a6','memcached','SHARED',0,NULL,1,NULL,NULL,'<b>Memcached</b><br/><br/>\r\n\r\n<b>Notes</b><br/>\r\na_? Consumes up to 1.5GB of memory.<br/>\r\na_? By default only allows connections from all instances in the same farm. To add external IPs, add them into <code>/etc/aws/roles/memcached/allowed_ips.list</code> file, one per line.\r\n<br/><br/>\r\n<b>References:</b><br>\r\na_? <a target=_\"blank\" href=\'http://www.danga.com/memcached/\'>memcached: a distributed memory object caching system</a> ',NULL,2,5,'memcached','m1.small','i386',NULL,0,NULL,1,NULL,NULL,0,'us-east-1'),(977,'ami-69d23600','app-rails64','SHARED',0,NULL,1,NULL,NULL,'<b>Apache2 + mod_rails + Rails 2.1.1</b><br/>\r\nCan act as a backend (if farm contains www role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\na_? <a target=\"blank\" href=\'http://www.modrails.com/documentation/Users guide.html\'>Phusion Passenger</a><br/>\r\na_? <a target=\"blank\" href=\"http://revolutiononrails.blogspot.com/2007/04/plugin-release-actsasreadonlyable.html\">ActsAsReadonlyable</a>\r\n<br/><br/>\r\n<b>Essential paths:</b><br/>\r\nWebroot:  <code>/var/www</code> - symlinks to <code>/usr/rails/scalr-placeholder/public</code><br/>\r\nDefault virtual host config: <code>/etc/apache2/sites-enabled/000-default</code><br/>',NULL,1,5,'app','m1.large','x86_64',NULL,0,NULL,0,NULL,NULL,0,'us-east-1'),(978,'ami-221c3456','mysql','SHARED',0,NULL,1,NULL,NULL,'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing.',NULL,1,4,'mysql','m1.small','i386',NULL,0,NULL,1,NULL,NULL,0,'eu-west-1'),(979,'ami-201c3454','mysql64','SHARED',0,NULL,1,NULL,NULL,'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing.',NULL,1,5,'mysql','m1.large','x86_64',NULL,0,NULL,1,NULL,NULL,0,'eu-west-1'),(980,'ami-3c1c3448','app','SHARED',0,NULL,1,NULL,NULL,'Apache (LAMP) application server. Can act as a backend (if farm contains www role) or frontend web server.',NULL,1,5,'app','m1.small','i386',NULL,0,NULL,1,NULL,NULL,0,'eu-west-1'),(981,'ami-481c343c','app64','SHARED',0,NULL,1,NULL,NULL,'Apache (LAMP) application server. Can act as a backend (if farm contains www role) or frontend web server.',NULL,1,5,'app','m1.large','x86_64',NULL,0,NULL,1,NULL,NULL,0,'eu-west-1'),(982,'ami-441c3430','www','SHARED',0,NULL,1,NULL,NULL,'Frontend web server/load balancer, running nginx. Proxies all requests to all instances of app role.',NULL,1,5,'www','m1.small','i386',NULL,0,NULL,1,NULL,NULL,0,'eu-west-1'),(983,'ami-5a1c342e','www64','SHARED',0,NULL,1,NULL,NULL,'Frontend web server/load balancer, running nginx. Proxies all requests to all instances of app role.',NULL,1,5,'www','m1.large','x86_64',NULL,0,NULL,1,NULL,NULL,0,'eu-west-1'),(984,'ami-4c1c3438','base','SHARED',0,NULL,1,NULL,NULL,'Bare AMI that doesn\'t involved in web serving. Suitable for batch job workers like media encoders etc.',NULL,1,4,'base','m1.small','i386',NULL,0,NULL,1,NULL,NULL,0,'eu-west-1'),(985,'ami-401c3434','base64','SHARED',0,NULL,1,NULL,NULL,'Bare AMI that doesn\'t involved in web serving. Suitable for batch job workers like media encoders etc.',NULL,1,5,'base','m1.large','x86_64',NULL,0,NULL,1,NULL,NULL,0,'eu-west-1'),(986,'ami-161c3462','mysqllvm','SHARED',0,NULL,1,NULL,NULL,'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing. Users LVM to quicker perform backup snapshots and support huge databases.',NULL,1,5,'mysql','m1.small','i386',NULL,0,NULL,0,NULL,NULL,0,'eu-west-1'),(987,'ami-241c3450','mysqllvm64','SHARED',0,NULL,1,NULL,NULL,'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing. Users LVM to quicker perform backup snapshots and support huge databases.',NULL,1,5,'mysql','m1.large','x86_64',NULL,0,NULL,0,NULL,NULL,0,'eu-west-1'),(988,'ami-321c3446','app-rails','SHARED',0,NULL,1,NULL,NULL,'<b>Apache2 + mod_rails + Rails 2.1.1</b><br/>\r\nCan act as a backend (if farm contains www role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\na_? <a target=\"blank\" href=\'http://www.modrails.com/documentation/Users guide.html\'>Phusion Passenger</a><br/>\r\na_? <a target=\"blank\" href=\"http://revolutiononrails.blogspot.com/2007/04/plugin-release-actsasreadonlyable.html\">ActsAsReadonlyable</a>\r\n<br/><br/>\r\n<b>Essential paths:</b><br/>\r\nWebroot:  <code>/var/www</code> - symlinks to <code>/usr/rails/scalr-placeholder/public</code><br/>\r\nDefault virtual host config: <code>/etc/apache2/sites-enabled/000-default</code><br/>',NULL,2,5,'app','m1.small','i386',NULL,0,NULL,0,NULL,NULL,0,'eu-west-1'),(989,'ami-301c3444','app-rails64','SHARED',0,NULL,1,NULL,NULL,'<b>Apache2 + mod_rails + Rails 2.1.1</b><br/>\r\nCan act as a backend (if farm contains www role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\na_? <a target=\"blank\" href=\'http://www.modrails.com/documentation/Users guide.html\'>Phusion Passenger</a><br/>\r\na_? <a target=\"blank\" href=\"http://revolutiononrails.blogspot.com/2007/04/plugin-release-actsasreadonlyable.html\">ActsAsReadonlyable</a>\r\n<br/><br/>\r\n<b>Essential paths:</b><br/>\r\nWebroot:  <code>/var/www</code> - symlinks to <code>/usr/rails/scalr-placeholder/public</code><br/>\r\nDefault virtual host config: <code>/etc/apache2/sites-enabled/000-default</code><br/>',NULL,1,5,'app','m1.large','x86_64',NULL,0,NULL,0,NULL,NULL,0,'eu-west-1'),(990,'ami-421c3436','memcached','SHARED',0,NULL,1,NULL,NULL,'<b>Memcached</b><br/><br/>\r\n\r\n<b>Notes</b><br/>\r\na_? Consumes up to 1.5GB of memory.<br/>\r\na_? By default only allows connections from all instances in the same farm. To add external IPs, add them into <code>/etc/aws/roles/memcached/allowed_ips.list</code> file, one per line.\r\n<br/><br/>\r\n<b>References:</b><br>\r\na_? <a target=_\"blank\" href=\'http://www.danga.com/memcached/\'>memcached: a distributed memory object caching system</a> ',NULL,2,5,'memcached','m1.small','i386',NULL,0,NULL,1,NULL,NULL,0,'eu-west-1');

/*Table structure for table `autosnap_settings` */

DROP TABLE IF EXISTS `autosnap_settings`;

CREATE TABLE `autosnap_settings` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `volumeid` varchar(15) default '0',
  `period` int(5) default NULL,
  `dtlastsnapshot` datetime default NULL,
  `rotate` int(11) default NULL,
  `last_snapshotid` varchar(50) default NULL,
  `region` varchar(50) default 'us-east-1',
  `arrayid` int(11) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `autosnap_settings` */

/*Table structure for table `client_settings` */

DROP TABLE IF EXISTS `client_settings`;

CREATE TABLE `client_settings` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `key` varchar(255) default NULL,
  `value` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`clientid`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
  `login_attempts` int(2) default '0',
  `dtlastloginattempt` datetime default NULL,
  `scalr_api_keyid` varchar(16) default NULL,
  `scalr_api_key` varchar(250) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `clients` */

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `comments` */

/*Table structure for table `config` */

DROP TABLE IF EXISTS `config`;

CREATE TABLE `config` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `key` varchar(255) default NULL,
  `value` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=1019 DEFAULT CHARSET=latin1;

/*Data for the table `config` */

insert  into `config`(`id`,`key`,`value`) values (3,'cryptokey','QhSqDYX7K5N85W8E'),(4,'crypto_algo','SHA256'),(18,'paging_items','20'),(944,'admin_password','8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918'),(979,'http_vhost_template',''),(980,'https_vhost_template',''),(981,'apache_docroot_dir',''),(982,'apache_logs_dir',''),(983,'nginx_https_vhost_template',''),(985,'admin_login','admin'),(986,'email_address','admin@server.net'),(987,'email_name','Support'),(988,'email_dsn',''),(989,'log_days','10'),(990,'dynamic_a_rec_ttl','90'),(991,'def_soa_owner','admin.server.net'),(992,'def_soa_parent','ns1.server.net'),(993,'def_soa_ttl','14400'),(994,'def_soa_refresh','14400'),(995,'def_soa_retry','7200'),(996,'def_soa_expire','3600000'),(997,'def_soa_minttl','300'),(998,'namedconftpl','zone \"{zone}\" {\r\n   type master;\r\n   file \"{db_filename}\";\r\n   allow-transfer { {allow_transfer}; };\r\n};'),(999,'aws_accountid','000000000000'),(1000,'aws_keyname','XXXXXXXXXXXX'),(1001,'aws_accesskey','XXXXXXXXXXXXXXXXXXXXXXXXX'),(1002,'aws_accesskey_id','XXXXXXXXXXXXXXXXXXX'),(1003,'secgroup_prefix','myscalr.'),(1004,'s3cfg_template','[default]\r\naccess_key = [access_key]\r\nacl_public = False\r\nforce = False\r\nhost = s3.amazonaws.com\r\nhuman_readable_sizes = False\r\nrecv_chunk = 4096\r\nsecret_key = [secret_key]\r\nsend_chunk = 4096\r\nverbosity = WARNING'),(1005,'client_max_instances','20'),(1006,'rrdtool_path','/usr/local/bin/rrdtool'),(1007,'rrd_default_font_path','/usr/share/rrdtool/fonts/DejaVuSansMono-Roman.ttf'),(1008,'rrd_db_dir','/home/rrddata'),(1009,'rrd_stats_url','https://s3.amazonaws.com/scalr-stats/%fid%/%rn%_%wn%.'),(1010,'rrd_graph_storage_type','S3'),(1011,'rrd_graph_storage_path','scalr-stats'),(1012,'snmptrap_path','/usr/bin/snmptrap'),(1013,'http_proto','http'),(1014,'eventhandler_url','server.net'),(1015,'reboot_timeout','300'),(1016,'launch_timeout','300'),(1017,'cron_processes_number','5'),(1018,'app_sys_ipaddress','xxx.xxx.xxx.xxx');

/*Table structure for table `countries` */

DROP TABLE IF EXISTS `countries`;

CREATE TABLE `countries` (
  `id` int(5) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `code` char(2) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `IDX_COUNTRIES_NAME` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=240 DEFAULT CHARSET=latin1;

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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

/*Data for the table `default_records` */

insert  into `default_records`(`id`,`clientid`,`rtype`,`ttl`,`rpriority`,`rvalue`,`rkey`) values (1,0,'CNAME',14400,0,'%hostname%','www');

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `ebs_array_snaps` */

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
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `ebs_arrays` */

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
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `ebs_snaps_info` */

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
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `elastic_ips` */

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

/*Data for the table `events` */

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
  `use_elastic_ips` tinyint(1) default '0',
  `dtlastsync` datetime default NULL,
  `reboot_timeout` int(10) default '300',
  `launch_timeout` int(10) default '300',
  `use_ebs` tinyint(1) default NULL,
  `ebs_size` int(11) default '0',
  `ebs_snapid` varchar(50) default NULL,
  `ebs_mountpoint` varchar(255) default NULL,
  `ebs_mount` tinyint(1) default '0',
  `status_timeout` int(10) default '20',
  `aki_id` varchar(25) default NULL,
  `ari_id` varchar(25) default NULL,
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`ami_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `farm_amis` */

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
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `farm_ebs` */

/*Table structure for table `farm_event_observers` */

DROP TABLE IF EXISTS `farm_event_observers`;

CREATE TABLE `farm_event_observers` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `event_observer_name` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`farmid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `farm_event_observers` */

/*Table structure for table `farm_event_observers_config` */

DROP TABLE IF EXISTS `farm_event_observers_config`;

CREATE TABLE `farm_event_observers_config` (
  `id` int(11) NOT NULL auto_increment,
  `observerid` int(11) default NULL,
  `key` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`observerid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `farm_event_observers_config` */

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
  `bwusage_in` bigint(20) default NULL,
  `bwusage_out` bigint(20) default NULL,
  `uptime` int(11) default NULL,
  `status` varchar(50) default NULL,
  `custom_elastic_ip` varchar(15) default NULL,
  `mysql_stat_password` varchar(255) default NULL,
  `mysql_replication_status` tinyint(1) default '1',
  `avail_zone` varchar(255) default NULL,
  `ishalted` tinyint(1) default '0',
  `index` int(11) default NULL,
  `region` varchar(50) default 'us-east-1',
  `dtlaststatusupdate` int(11) default NULL,
  `scalarizr_pkg_version` varchar(20) default NULL,
  `dtshutdownscheduled` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `farmid` (`farmid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `farm_instances` */

/*Table structure for table `farm_role_options` */

DROP TABLE IF EXISTS `farm_role_options`;

CREATE TABLE `farm_role_options` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `ami_id` varchar(255) default NULL,
  `name` varchar(255) default NULL,
  `value` text,
  `hash` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `farm_role_options` */

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
  PRIMARY KEY  (`id`),
  UNIQUE KEY `UniqueIndex` (`scriptid`,`farmid`,`ami_id`,`event_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `farm_role_scripts` */

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `farm_stats` */

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
  `bcp_instance_id` varchar(20) default NULL,
  `dtlaunched` datetime default NULL,
  `term_on_sync_fail` tinyint(1) default '1',
  `bucket_name` varchar(255) default NULL,
  `isbundlerunning` tinyint(1) default '0',
  `mysql_bundle` tinyint(1) default '1',
  `region` varchar(255) default 'us-east-1',
  `scalarizr_pkey` text,
  `scalarizr_cert` text,
  `mysql_master_ebs_volume_id` varchar(255) default NULL,
  `mysql_data_storage_engine` varchar(5) default 'lvm',
  `mysql_ebs_size` int(7) default '100',
  PRIMARY KEY  (`id`),
  KEY `clientid` (`clientid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `farms` */

/*Table structure for table `garbage_queue` */

DROP TABLE IF EXISTS `garbage_queue`;

CREATE TABLE `garbage_queue` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `data` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `clientindex` (`clientid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `garbage_queue` */

/*Table structure for table `ipaccess` */

DROP TABLE IF EXISTS `ipaccess`;

CREATE TABLE `ipaccess` (
  `id` int(11) NOT NULL auto_increment,
  `ipaddress` varchar(255) default NULL,
  `comment` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

/*Data for the table `ipaccess` */

insert  into `ipaccess`(`id`,`ipaddress`,`comment`) values (1,'91.124.*.*','Urktelecom aDSL pool'),(2,'*.*.*.*','Disable IP whitelist');

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
  PRIMARY KEY  (`id`),
  KEY `farmidindex` (`farmid`),
  KEY `severityindex` (`severity`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `logentries` */

/*Table structure for table `messages` */

DROP TABLE IF EXISTS `messages`;

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
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `messages` */

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

/*Data for the table `nameservers` */

/*Table structure for table `payment_redirects` */

DROP TABLE IF EXISTS `payment_redirects`;

CREATE TABLE `payment_redirects` (
  `id` int(11) NOT NULL auto_increment,
  `from_clientid` int(11) default NULL,
  `to_clientid` int(11) default NULL,
  `subscription_id` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `payment_redirects` */

/*Table structure for table `real_servers` */

DROP TABLE IF EXISTS `real_servers`;

CREATE TABLE `real_servers` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `ami_id` varchar(255) default NULL,
  `ipaddress` varchar(15) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `real_servers` */

/*Table structure for table `rebundle_log` */

DROP TABLE IF EXISTS `rebundle_log`;

CREATE TABLE `rebundle_log` (
  `id` int(11) NOT NULL auto_increment,
  `roleid` int(11) default NULL,
  `dtadded` datetime default NULL,
  `message` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `rebundle_log` */

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `records` */

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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=latin1;

/*Data for the table `role_options` */

insert  into `role_options`(`id`,`name`,`type`,`isrequired`,`defval`,`allow_multiple_choice`,`options`,`ami_id`,`hash`,`issystem`) values (1,'Apache HTTP Vhost Template','textarea',1,'<VirtualHost *:80>\r\n	ServerAlias www.{$host} {$host} {$server_alias}\r\n	ServerAdmin {$server_admin}\r\n	DocumentRoot {$document_root}\r\n	ServerName www.{$host}\r\n	CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n	ScriptAlias /cgi-bin/ {$document_root}/cgi-bin/\r\n</VirtualHost>',0,'','ami-bac420d3','apache_http_vhost_template',1),(2,'Apache HTTPS Vhost Template','textarea',1,'<IfModule mod_ssl.c>\r\n        <VirtualHost *:443>\r\n			ServerName {$host}\r\n			ServerAlias www.{$host} {$host} {$server_alias}\r\n			ServerAdmin {$server_admin}\r\n			DocumentRoot {$document_root}\r\n			CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n\r\n			ErrorLog {$logs_dir}/http-{$host}-error.log\r\n			LogLevel warn\r\n\r\n			SSLEngine on\r\n\r\n			SSLProtocol all -SSLv2\r\n			SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:MEDIUM:+LOW\r\n			SSLCertificateFile /etc/aws/keys/ssl/https.crt\r\n			SSLCertificateKeyFile /etc/aws/keys/ssl/https.key\r\n\r\n			ScriptAlias /cgi-bin/ /var/www/cgi-bin/\r\n			SetEnvIf User-Agent \".*MSIE.*\" nokeepalive ssl-unclean-shutdown\r\n        </VirtualHost>\r\n</IfModule>',0,'','ami-bac420d3','apache_https_vhost_template',1),(3,'Apache HTTP Vhost Template','textarea',1,'<VirtualHost *:80>\r\n	ServerAlias www.{$host} {$host} {$server_alias}\r\n	ServerAdmin {$server_admin}\r\n	DocumentRoot {$document_root}\r\n	ServerName www.{$host}\r\n	CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n	ScriptAlias /cgi-bin/ {$document_root}/cgi-bin/\r\n</VirtualHost>',0,'','ami-0ac62263','apache_http_vhost_template',1),(4,'Apache HTTPS Vhost Template','textarea',1,'<IfModule mod_ssl.c>\r\n        <VirtualHost *:443>\r\n			ServerName {$host}\r\n			ServerAlias www.{$host} {$host} {$server_alias}\r\n			ServerAdmin {$server_admin}\r\n			DocumentRoot {$document_root}\r\n			CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n\r\n			ErrorLog {$logs_dir}/http-{$host}-error.log\r\n			LogLevel warn\r\n\r\n			SSLEngine on\r\n\r\n			SSLProtocol all -SSLv2\r\n			SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:MEDIUM:+LOW\r\n			SSLCertificateFile /etc/aws/keys/ssl/https.crt\r\n			SSLCertificateKeyFile /etc/aws/keys/ssl/https.key\r\n\r\n			ScriptAlias /cgi-bin/ /var/www/cgi-bin/\r\n			SetEnvIf User-Agent \".*MSIE.*\" nokeepalive ssl-unclean-shutdown\r\n        </VirtualHost>\r\n</IfModule>',0,'','ami-0ac62263','apache_https_vhost_template',1),(5,'Apache HTTP Vhost Template','textarea',1,'<VirtualHost *:80>\r\n	ServerAlias www.{$host} {$host} {$server_alias}\r\n	ServerAdmin {$server_admin}\r\n	DocumentRoot {$document_root}\r\n	ServerName www.{$host}\r\n	CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n	ScriptAlias /cgi-bin/ {$document_root}/cgi-bin/\r\n</VirtualHost>',0,'','ami-c2d034ab','apache_http_vhost_template',1),(6,'Apache HTTPS Vhost Template','textarea',1,'<IfModule mod_ssl.c>\r\n        <VirtualHost *:443>\r\n			ServerName {$host}\r\n			ServerAlias www.{$host} {$host} {$server_alias}\r\n			ServerAdmin {$server_admin}\r\n			DocumentRoot {$document_root}\r\n			CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n\r\n			ErrorLog {$logs_dir}/http-{$host}-error.log\r\n			LogLevel warn\r\n\r\n			SSLEngine on\r\n\r\n			SSLProtocol all -SSLv2\r\n			SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:MEDIUM:+LOW\r\n			SSLCertificateFile /etc/aws/keys/ssl/https.crt\r\n			SSLCertificateKeyFile /etc/aws/keys/ssl/https.key\r\n\r\n			ScriptAlias /cgi-bin/ /var/www/cgi-bin/\r\n			SetEnvIf User-Agent \".*MSIE.*\" nokeepalive ssl-unclean-shutdown\r\n        </VirtualHost>\r\n</IfModule>',0,'','ami-c2d034ab','apache_https_vhost_template',1),(7,'Apache HTTP Vhost Template','textarea',1,'<VirtualHost *:80>\r\n	ServerAlias www.{$host} {$host} {$server_alias}\r\n	ServerAdmin {$server_admin}\r\n	DocumentRoot {$document_root}\r\n	ServerName www.{$host}\r\n	CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n	ScriptAlias /cgi-bin/ {$document_root}/cgi-bin/\r\n</VirtualHost>',0,'','ami-69d23600','apache_http_vhost_template',1),(8,'Apache HTTPS Vhost Template','textarea',1,'<IfModule mod_ssl.c>\r\n        <VirtualHost *:443>\r\n			ServerName {$host}\r\n			ServerAlias www.{$host} {$host} {$server_alias}\r\n			ServerAdmin {$server_admin}\r\n			DocumentRoot {$document_root}\r\n			CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n\r\n			ErrorLog {$logs_dir}/http-{$host}-error.log\r\n			LogLevel warn\r\n\r\n			SSLEngine on\r\n\r\n			SSLProtocol all -SSLv2\r\n			SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:MEDIUM:+LOW\r\n			SSLCertificateFile /etc/aws/keys/ssl/https.crt\r\n			SSLCertificateKeyFile /etc/aws/keys/ssl/https.key\r\n\r\n			ScriptAlias /cgi-bin/ /var/www/cgi-bin/\r\n			SetEnvIf User-Agent \".*MSIE.*\" nokeepalive ssl-unclean-shutdown\r\n        </VirtualHost>\r\n</IfModule>',0,'','ami-69d23600','apache_https_vhost_template',1),(9,'Apache HTTP Vhost Template','textarea',1,'<VirtualHost *:80>\r\n	ServerAlias www.{$host} {$host} {$server_alias}\r\n	ServerAdmin {$server_admin}\r\n	DocumentRoot {$document_root}\r\n	ServerName www.{$host}\r\n	CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n	ScriptAlias /cgi-bin/ {$document_root}/cgi-bin/\r\n</VirtualHost>',0,'','ami-3c1c3448','apache_http_vhost_template',1),(10,'Apache HTTPS Vhost Template','textarea',1,'<IfModule mod_ssl.c>\r\n        <VirtualHost *:443>\r\n			ServerName {$host}\r\n			ServerAlias www.{$host} {$host} {$server_alias}\r\n			ServerAdmin {$server_admin}\r\n			DocumentRoot {$document_root}\r\n			CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n\r\n			ErrorLog {$logs_dir}/http-{$host}-error.log\r\n			LogLevel warn\r\n\r\n			SSLEngine on\r\n\r\n			SSLProtocol all -SSLv2\r\n			SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:MEDIUM:+LOW\r\n			SSLCertificateFile /etc/aws/keys/ssl/https.crt\r\n			SSLCertificateKeyFile /etc/aws/keys/ssl/https.key\r\n\r\n			ScriptAlias /cgi-bin/ /var/www/cgi-bin/\r\n			SetEnvIf User-Agent \".*MSIE.*\" nokeepalive ssl-unclean-shutdown\r\n        </VirtualHost>\r\n</IfModule>',0,'','ami-3c1c3448','apache_https_vhost_template',1),(11,'Apache HTTP Vhost Template','textarea',1,'<VirtualHost *:80>\r\n	ServerAlias www.{$host} {$host} {$server_alias}\r\n	ServerAdmin {$server_admin}\r\n	DocumentRoot {$document_root}\r\n	ServerName www.{$host}\r\n	CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n	ScriptAlias /cgi-bin/ {$document_root}/cgi-bin/\r\n</VirtualHost>',0,'','ami-481c343c','apache_http_vhost_template',1),(12,'Apache HTTPS Vhost Template','textarea',1,'<IfModule mod_ssl.c>\r\n        <VirtualHost *:443>\r\n			ServerName {$host}\r\n			ServerAlias www.{$host} {$host} {$server_alias}\r\n			ServerAdmin {$server_admin}\r\n			DocumentRoot {$document_root}\r\n			CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n\r\n			ErrorLog {$logs_dir}/http-{$host}-error.log\r\n			LogLevel warn\r\n\r\n			SSLEngine on\r\n\r\n			SSLProtocol all -SSLv2\r\n			SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:MEDIUM:+LOW\r\n			SSLCertificateFile /etc/aws/keys/ssl/https.crt\r\n			SSLCertificateKeyFile /etc/aws/keys/ssl/https.key\r\n\r\n			ScriptAlias /cgi-bin/ /var/www/cgi-bin/\r\n			SetEnvIf User-Agent \".*MSIE.*\" nokeepalive ssl-unclean-shutdown\r\n        </VirtualHost>\r\n</IfModule>',0,'','ami-481c343c','apache_https_vhost_template',1),(13,'Apache HTTP Vhost Template','textarea',1,'<VirtualHost *:80>\r\n	ServerAlias www.{$host} {$host} {$server_alias}\r\n	ServerAdmin {$server_admin}\r\n	DocumentRoot {$document_root}\r\n	ServerName www.{$host}\r\n	CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n	ScriptAlias /cgi-bin/ {$document_root}/cgi-bin/\r\n</VirtualHost>',0,'','ami-321c3446','apache_http_vhost_template',1),(14,'Apache HTTPS Vhost Template','textarea',1,'<IfModule mod_ssl.c>\r\n        <VirtualHost *:443>\r\n			ServerName {$host}\r\n			ServerAlias www.{$host} {$host} {$server_alias}\r\n			ServerAdmin {$server_admin}\r\n			DocumentRoot {$document_root}\r\n			CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n\r\n			ErrorLog {$logs_dir}/http-{$host}-error.log\r\n			LogLevel warn\r\n\r\n			SSLEngine on\r\n\r\n			SSLProtocol all -SSLv2\r\n			SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:MEDIUM:+LOW\r\n			SSLCertificateFile /etc/aws/keys/ssl/https.crt\r\n			SSLCertificateKeyFile /etc/aws/keys/ssl/https.key\r\n\r\n			ScriptAlias /cgi-bin/ /var/www/cgi-bin/\r\n			SetEnvIf User-Agent \".*MSIE.*\" nokeepalive ssl-unclean-shutdown\r\n        </VirtualHost>\r\n</IfModule>',0,'','ami-321c3446','apache_https_vhost_template',1),(15,'Apache HTTP Vhost Template','textarea',1,'<VirtualHost *:80>\r\n	ServerAlias www.{$host} {$host} {$server_alias}\r\n	ServerAdmin {$server_admin}\r\n	DocumentRoot {$document_root}\r\n	ServerName www.{$host}\r\n	CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n	ScriptAlias /cgi-bin/ {$document_root}/cgi-bin/\r\n</VirtualHost>',0,'','ami-301c3444','apache_http_vhost_template',1),(16,'Apache HTTPS Vhost Template','textarea',1,'<IfModule mod_ssl.c>\r\n        <VirtualHost *:443>\r\n			ServerName {$host}\r\n			ServerAlias www.{$host} {$host} {$server_alias}\r\n			ServerAdmin {$server_admin}\r\n			DocumentRoot {$document_root}\r\n			CustomLog {$logs_dir}/http-{$host}-access.log combined\r\n\r\n			ErrorLog {$logs_dir}/http-{$host}-error.log\r\n			LogLevel warn\r\n\r\n			SSLEngine on\r\n\r\n			SSLProtocol all -SSLv2\r\n			SSLCipherSuite ALL:!ADH:!EXPORT:!SSLv2:RC4+RSA:+HIGH:MEDIUM:+LOW\r\n			SSLCertificateFile /etc/aws/keys/ssl/https.crt\r\n			SSLCertificateKeyFile /etc/aws/keys/ssl/https.key\r\n\r\n			ScriptAlias /cgi-bin/ /var/www/cgi-bin/\r\n			SetEnvIf User-Agent \".*MSIE.*\" nokeepalive ssl-unclean-shutdown\r\n        </VirtualHost>\r\n</IfModule>',0,'','ami-301c3444','apache_https_vhost_template',1),(17,'Nginx HTTPS Vhost Template','textarea',1,'{literal}server { {/literal}\r\n	  listen       443;\r\n        server_name  {$host} www.{$host} {$server_alias};\r\n\r\n        ssl                  on;\r\n        ssl_certificate      /etc/aws/keys/ssl/https.crt;\r\n        ssl_certificate_key  /etc/aws/keys/ssl/https.key;\r\n\r\n        ssl_session_timeout  10m;\r\n        ssl_session_cache    shared:SSL:10m;\r\n\r\n        ssl_protocols  SSLv2 SSLv3 TLSv1;\r\n        ssl_ciphers  ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP;\r\n        ssl_prefer_server_ciphers   on;\r\n{literal}\r\n        location / {\r\n            proxy_pass         http://backend;\r\n            proxy_set_header   Host             $host;\r\n            proxy_set_header   X-Real-IP        $remote_addr;\r\n            proxy_set_header   X-Forwarded-For  $proxy_add_x_forwarded_for;\r\n\r\n            client_max_body_size       10m;\r\n            client_body_buffer_size    128k;\r\n\r\n          \r\n            proxy_buffering on;\r\n            proxy_connect_timeout 15;\r\n            proxy_intercept_errors on;  \r\n        }\r\n    } {/literal}',0,'','ami-72f2161b','nginx_https_host_template',1),(18,'Nginx HTTPS Vhost Template','textarea',1,'{literal}server { {/literal}\r\n	  listen       443;\r\n        server_name  {$host} www.{$host} {$server_alias};\r\n\r\n        ssl                  on;\r\n        ssl_certificate      /etc/aws/keys/ssl/https.crt;\r\n        ssl_certificate_key  /etc/aws/keys/ssl/https.key;\r\n\r\n        ssl_session_timeout  10m;\r\n        ssl_session_cache    shared:SSL:10m;\r\n\r\n        ssl_protocols  SSLv2 SSLv3 TLSv1;\r\n        ssl_ciphers  ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP;\r\n        ssl_prefer_server_ciphers   on;\r\n{literal}\r\n        location / {\r\n            proxy_pass         http://backend;\r\n            proxy_set_header   Host             $host;\r\n            proxy_set_header   X-Real-IP        $remote_addr;\r\n            proxy_set_header   X-Forwarded-For  $proxy_add_x_forwarded_for;\r\n\r\n            client_max_body_size       10m;\r\n            client_body_buffer_size    128k;\r\n\r\n          \r\n            proxy_buffering on;\r\n            proxy_connect_timeout 15;\r\n            proxy_intercept_errors on;  \r\n        }\r\n    } {/literal}',0,'','ami-01ca2e68','nginx_https_host_template',1),(19,'Nginx HTTPS Vhost Template','textarea',1,'{literal}server { {/literal}\r\n	  listen       443;\r\n        server_name  {$host} www.{$host} {$server_alias};\r\n\r\n        ssl                  on;\r\n        ssl_certificate      /etc/aws/keys/ssl/https.crt;\r\n        ssl_certificate_key  /etc/aws/keys/ssl/https.key;\r\n\r\n        ssl_session_timeout  10m;\r\n        ssl_session_cache    shared:SSL:10m;\r\n\r\n        ssl_protocols  SSLv2 SSLv3 TLSv1;\r\n        ssl_ciphers  ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP;\r\n        ssl_prefer_server_ciphers   on;\r\n{literal}\r\n        location / {\r\n            proxy_pass         http://backend;\r\n            proxy_set_header   Host             $host;\r\n            proxy_set_header   X-Real-IP        $remote_addr;\r\n            proxy_set_header   X-Forwarded-For  $proxy_add_x_forwarded_for;\r\n\r\n            client_max_body_size       10m;\r\n            client_body_buffer_size    128k;\r\n\r\n          \r\n            proxy_buffering on;\r\n            proxy_connect_timeout 15;\r\n            proxy_intercept_errors on;  \r\n        }\r\n    } {/literal}',0,'','ami-441c3430','nginx_https_host_template',1),(20,'Nginx HTTPS Vhost Template','textarea',1,'{literal}server { {/literal}\r\n	  listen       443;\r\n        server_name  {$host} www.{$host} {$server_alias};\r\n\r\n        ssl                  on;\r\n        ssl_certificate      /etc/aws/keys/ssl/https.crt;\r\n        ssl_certificate_key  /etc/aws/keys/ssl/https.key;\r\n\r\n        ssl_session_timeout  10m;\r\n        ssl_session_cache    shared:SSL:10m;\r\n\r\n        ssl_protocols  SSLv2 SSLv3 TLSv1;\r\n        ssl_ciphers  ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP;\r\n        ssl_prefer_server_ciphers   on;\r\n{literal}\r\n        location / {\r\n            proxy_pass         http://backend;\r\n            proxy_set_header   Host             $host;\r\n            proxy_set_header   X-Real-IP        $remote_addr;\r\n            proxy_set_header   X-Forwarded-For  $proxy_add_x_forwarded_for;\r\n\r\n            client_max_body_size       10m;\r\n            client_body_buffer_size    128k;\r\n\r\n          \r\n            proxy_buffering on;\r\n            proxy_connect_timeout 15;\r\n            proxy_intercept_errors on;  \r\n        }\r\n    } {/literal}',0,'','ami-5a1c342e','nginx_https_host_template',1);

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

/*Data for the table `script_revisions` */

insert  into `script_revisions`(`id`,`scriptid`,`revision`,`script`,`dtcreated`,`approval_state`) values (1,1,1,'#!/bin/bash\n\nSVN_PATH=\"/usr/bin/svn\"\nSVN_USER=\"%svn_user%\"\nSVN_PASS=\"%svn_password%\"\nSVN_REV=\"%svn_revision%\"\nSVN_UP_DIR=\"%svn_co_dir%\"\n\n\nif [ -z \"$SVN_UP_DIR\" ]; then\n        echo \"Working copy directory was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ -z \"$SVN_PATH\" ] || [ ! -x \"$SVN_PATH\" ]; then\n        echo \"SVN binary is not executable\" >&2\n\n        exit 1\nfi\n\n[ \"$SVN_USER\" ] && SVN_USER_STR=\"--username $SVN_USER\"\n[ \"$SVN_PASS\" ] && SVN_PASS_STR=\"--password $SVN_PASS\"\n[ \"$SVN_REV\" ]  && SVN_REV_STR=\"-r $SVN_REV\"\n\n\nif $SVN_PATH --non-interactive $SVN_USER_STR $SVN_PASS_STR info \"$SVN_UP_DIR\" >/dev/null 2>&1; then\n        $SVN_PATH --non-interactive $SVN_USER_STR $SVN_PASS_STR $SVN_REV_STR \"$SVN_UP_DIR\"\nfi\n','2009-05-05 15:45:10','Approved'),(2,2,1,'#!/bin/bash\n\nSVN_PATH=\"/usr/bin/svn\"\nSVN_REPO_URL=\"%svn_repo_url%\"\nSVN_USER=\"%svn_user%\"\nSVN_PASS=\"%svn_password%\"\nSVN_REV=\"%svn_revision%\"\nSVN_CO_DIR=\"%svn_co_dir%\"\n\n\nif [ -z \"$SVN_REPO_URL\" ]; then \n        echo \"SVN repository URL was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ -z \"$SVN_CO_DIR\" ]; then\n        echo \"Checkout directory was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ -z \"$SVN_PATH\" ] || [ ! -x \"$SVN_PATH\" ]; then\n        echo \"SVN binary is not executable\" >&2\n\n        exit 1\nfi\n\n[ \"$SVN_USER\" ] && SVN_USER_STR=\"--username $SVN_USER\"\n[ \"$SVN_PASS\" ] && SVN_PASS_STR=\"--password $SVN_PASS\"\n[ \"$SVN_REV\" ]  && SVN_REV_STR=\"-r $SVN_REV\"\n\n[ -d \"$SVN_CO_DIR\" ] || mkdir -p $SVN_CO_DIR\n\n$SVN_PATH --force --non-interactive $SVN_USER_STR $SVN_PASS_STR export $SVN_REV_STR \"$SVN_REPO_URL\" \"$SVN_CO_DIR\"\n','2009-05-05 15:45:10','Approved'),(3,3,1,'#!/bin/bash\n\nSVN_PATH=\"/usr/bin/svn\"\nSVN_REPO_URL=\"%svn_repo_url%\"\nSVN_USER=\"%svn_user%\"\nSVN_PASS=\"%svn_password%\"\nSVN_REV=\"%svn_revision%\"\nSVN_CO_DIR=\"%svn_co_dir%\"\n\n\nif [ -z \"$SVN_REPO_URL\" ]; then\n        echo \"SVN repository URL was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ -z \"$SVN_CO_DIR\" ]; then\n        echo \"Checkout directory was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ -z \"$SVN_PATH\" ] || [ ! -x \"$SVN_PATH\" ]; then\n        echo \"SVN binary is not executable\" >&2\n\n        exit 1\nfi\n\n[ \"$SVN_USER\" ] && SVN_USER_STR=\"--username $SVN_USER\"\n[ \"$SVN_PASS\" ] && SVN_PASS_STR=\"--password $SVN_PASS\"\n[ \"$SVN_REV\" ]  && SVN_REV_STR=\"-r $SVN_REV\"\n\n[ -d \"$SVN_CO_DIR\" ] || mkdir -p $SVN_CO_DIR\n\n$SVN_PATH --force --non-interactive $SVN_USER_STR $SVN_PASS_STR checkout $SVN_REV_STR \"$SVN_REPO_URL\" \"$SVN_CO_DIR\"\n','2009-05-05 15:45:10','Approved'),(4,4,1,'#!/bin/bash\n\nGIT_PATH=\"/usr/bin/git\"\nGIT_REPO_URL=\"%git_repo_url%\"\nGIT_CL_DIR=\"%git_co_dir%\"\n\n\nif [ -z \"$GIT_REPO_URL\" ]; then\n        echo \"GIT repository URL was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ -z \"$GIT_CL_DIR\" ]; then\n        echo \"Destination directory was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ ! -x \"$GIT_PATH\" ]; then\n        /usr/bin/apt-get -q -y install git-core\n\n        if [ ! -x \"$GIT_PATH\" ]; then\n                echo \"GIT binary is not executable\" >&2\n\n                exit 1\n        fi\nfi\n\n$GIT_PATH clone \"$GIT_REPO_URL\" \"$GIT_CL_DIR\"\n','2009-05-05 15:45:10','Approved');

/*Table structure for table `scripting_log` */

DROP TABLE IF EXISTS `scripting_log`;

CREATE TABLE `scripting_log` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `event` varchar(255) default NULL,
  `instance` varchar(25) default NULL,
  `dtadded` datetime default NULL,
  `message` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `scripting_log` */

/*Table structure for table `scripts` */

DROP TABLE IF EXISTS `scripts`;

CREATE TABLE `scripts` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `origin` varchar(50) default '0',
  `dtadded` datetime default NULL,
  `issync` tinyint(1) default '0',
  `clientid` int(11) default '0',
  `approval_state` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

/*Data for the table `scripts` */

insert  into `scripts`(`id`,`name`,`description`,`origin`,`dtadded`,`issync`,`clientid`,`approval_state`) values (1,'SVN update','Update a working copy from SVN repository','1','2008-10-23 19:41:07',1,0,'Approved'),(2,'SVN export','Export SVN repository to local directory','1','2008-10-22 15:30:46',0,0,'Approved'),(3,'SVN checkout','Checkout from SVN repository','1','2008-10-23 19:42:21',1,0,'Approved'),(4,'Git clone','Clone a git repository','1','2008-10-24 15:04:41',1,0,'Approved');

/*Table structure for table `security_rules` */

DROP TABLE IF EXISTS `security_rules`;

CREATE TABLE `security_rules` (
  `id` int(11) NOT NULL auto_increment,
  `roleid` int(11) default NULL,
  `rule` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=661 DEFAULT CHARSET=latin1;

/*Data for the table `security_rules` */

insert  into `security_rules`(`id`,`roleid`,`rule`) values (45,18,'tcp:22:22:0.0.0.0/0'),(46,18,'tcp:80:80:0.0.0.0/0'),(47,18,'udp:161:162:0.0.0.0/0'),(126,44,'udp:161:162:0.0.0.0/0'),(127,44,'tcp:80:80:0.0.0.0/0'),(128,44,'tcp:22:22:0.0.0.0/0'),(132,46,'tcp:22:22:0.0.0.0/0'),(133,46,'tcp:80:80:0.0.0.0/0'),(134,46,'udp:161:162:0.0.0.0/0'),(138,48,'tcp:22:22:0.0.0.0/0'),(139,48,'udp:161:162:0.0.0.0/0'),(140,49,'tcp:3306:3306:0.0.0.0/0'),(141,49,'tcp:22:22:0.0.0.0/0'),(142,49,'udp:161:162:0.0.0.0/0'),(143,50,'tcp:22:22:0.0.0.0/0'),(144,50,'tcp:80:80:0.0.0.0/0'),(145,50,'udp:161:162:0.0.0.0/0'),(303,18,'icmp:-1:-1:0.0.0.0/0'),(305,44,'icmp:-1:-1:0.0.0.0/0'),(307,48,'icmp:-1:-1:0.0.0.0/0'),(308,49,'icmp:-1:-1:0.0.0.0/0'),(309,50,'icmp:-1:-1:0.0.0.0/0'),(493,892,'udp:161:162:0.0.0.0/0'),(494,892,'tcp:22:22:0.0.0.0/0'),(495,892,'icmp:-1:-1:0.0.0.0/0'),(496,892,'tcp:3306:3306:0.0.0.0/0'),(505,1,'tcp:22:22:0.0.0.0/0'),(506,1,'udp:161:162:0.0.0.0/0'),(507,1,'icmp:-1:-1:0.0.0.0/0'),(508,2,'udp:161:162:0.0.0.0/0'),(509,2,'tcp:3306:3306:0.0.0.0/0'),(510,2,'tcp:22:22:0.0.0.0/0'),(511,2,'icmp:-1:-1:0.0.0.0/0'),(512,47,'tcp:22:22:0.0.0.0/0'),(513,47,'tcp:80:80:0.0.0.0/0'),(514,47,'udp:161:162:0.0.0.0/0'),(515,47,'icmp:-1:-1:0.0.0.0/0'),(523,20,'tcp:22:22:0.0.0.0/0'),(524,20,'tcp:80:80:0.0.0.0/0'),(525,20,'udp:161:162:0.0.0.0/0'),(526,20,'icmp:-1:-1:0.0.0.0/0'),(571,936,'tcp:22:22:0.0.0.0/0'),(572,936,'tcp:80:80:0.0.0.0/0'),(573,936,'udp:161:162:0.0.0.0/0'),(574,936,'icmp:-1:-1:0.0.0.0/0'),(575,959,'udp:161:162:0.0.0.0/0'),(576,959,'tcp:22:22:0.0.0.0/0'),(577,959,'icmp:-1:-1:0.0.0.0/0'),(578,959,'tcp:11211:11211:0.0.0.0/0'),(595,955,'udp:161:162:0.0.0.0/0'),(596,955,'tcp:22:22:0.0.0.0/0'),(597,955,'icmp:-1:-1:0.0.0.0/0'),(598,955,'tcp:11211:11211:0.0.0.0/0'),(603,977,'udp:161:162:0.0.0.0/0'),(604,977,'tcp:22:22:0.0.0.0/0'),(605,977,'icmp:-1:-1:0.0.0.0/0'),(606,977,'tcp:80:80:0.0.0.0/0'),(607,704,'udp:161:162:0.0.0.0/0'),(608,704,'tcp:22:22:0.0.0.0/0'),(609,704,'icmp:-1:-1:0.0.0.0/0'),(610,704,'tcp:3306:3306:0.0.0.0/0'),(611,978,'udp:161:162:0.0.0.0/0'),(612,978,'tcp:3306:3306:0.0.0.0/0'),(613,978,'tcp:22:22:0.0.0.0/0'),(614,978,'icmp:-1:-1:0.0.0.0/0'),(615,979,'tcp:3306:3306:0.0.0.0/0'),(616,979,'tcp:22:22:0.0.0.0/0'),(617,979,'udp:161:162:0.0.0.0/0'),(618,979,'icmp:-1:-1:0.0.0.0/0'),(619,980,'tcp:22:22:0.0.0.0/0'),(620,980,'tcp:80:80:0.0.0.0/0'),(621,980,'udp:161:162:0.0.0.0/0'),(622,980,'icmp:-1:-1:0.0.0.0/0'),(623,981,'tcp:22:22:0.0.0.0/0'),(624,981,'tcp:80:80:0.0.0.0/0'),(625,981,'udp:161:162:0.0.0.0/0'),(626,981,'icmp:-1:-1:0.0.0.0/0'),(627,982,'tcp:22:22:0.0.0.0/0'),(628,982,'tcp:80:80:0.0.0.0/0'),(629,982,'udp:161:162:0.0.0.0/0'),(630,982,'icmp:-1:-1:0.0.0.0/0'),(631,983,'tcp:22:22:0.0.0.0/0'),(632,983,'tcp:80:80:0.0.0.0/0'),(633,983,'udp:161:162:0.0.0.0/0'),(634,983,'icmp:-1:-1:0.0.0.0/0'),(635,984,'tcp:22:22:0.0.0.0/0'),(636,984,'udp:161:162:0.0.0.0/0'),(637,984,'icmp:-1:-1:0.0.0.0/0'),(638,985,'tcp:22:22:0.0.0.0/0'),(639,985,'udp:161:162:0.0.0.0/0'),(640,985,'icmp:-1:-1:0.0.0.0/0'),(641,986,'udp:161:162:0.0.0.0/0'),(642,986,'tcp:22:22:0.0.0.0/0'),(643,986,'icmp:-1:-1:0.0.0.0/0'),(644,986,'tcp:3306:3306:0.0.0.0/0'),(645,987,'udp:161:162:0.0.0.0/0'),(646,987,'tcp:22:22:0.0.0.0/0'),(647,987,'icmp:-1:-1:0.0.0.0/0'),(648,987,'tcp:3306:3306:0.0.0.0/0'),(649,988,'tcp:22:22:0.0.0.0/0'),(650,988,'tcp:80:80:0.0.0.0/0'),(651,988,'udp:161:162:0.0.0.0/0'),(652,988,'icmp:-1:-1:0.0.0.0/0'),(653,989,'udp:161:162:0.0.0.0/0'),(654,989,'tcp:22:22:0.0.0.0/0'),(655,989,'icmp:-1:-1:0.0.0.0/0'),(656,989,'tcp:80:80:0.0.0.0/0'),(657,990,'udp:161:162:0.0.0.0/0'),(658,990,'tcp:22:22:0.0.0.0/0'),(659,990,'icmp:-1:-1:0.0.0.0/0'),(660,990,'tcp:11211:11211:0.0.0.0/0');

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
  `farmid` int(11) default '0',
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`transactionid`),
  KEY `NewIndex2` (`sub_transactionid`),
  KEY `TimeIndex1` (`dtadded_time`),
  KEY `severity` (`severity`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `syslog` */

/*Table structure for table `syslog_metadata` */

DROP TABLE IF EXISTS `syslog_metadata`;

CREATE TABLE `syslog_metadata` (
  `id` int(11) NOT NULL auto_increment,
  `transactionid` varchar(50) default NULL,
  `errors` int(5) default NULL,
  `warnings` int(5) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `transid` (`transactionid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Data for the table `syslog_metadata` */

/*Table structure for table `task_queue` */

DROP TABLE IF EXISTS `task_queue`;

CREATE TABLE `task_queue` (
  `id` int(11) NOT NULL auto_increment,
  `queue_name` varchar(255) default NULL,
  `data` text,
  `dtadded` datetime default NULL,
  `failed_attempts` int(3) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `task_queue` */

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
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`name`,`farmid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `vhosts` */

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
  PRIMARY KEY  (`id`),
  UNIQUE KEY `zones_index3945` (`zone`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `zones` */

create table `farm_role_settings`( `id` int(11) NOT NULL AUTO_INCREMENT , `farm_roleid` int(11) , `name` varchar(255) , `value` varchar(255) , PRIMARY KEY (`id`));

create table `sensor_data`( `id` int(11) , `farm_roleid` int(11) , `sensor_name` varchar(255) , `sensor_value` varchar(255) , `dtlastupdate` int , `raw_sensor_data` varchar(255) );

alter table `farms` add column `farm_roles_launch_order` tinyint(1) DEFAULT '0' NULL after `mysql_ebs_size`;

alter table `farm_amis` add column `launch_index` int(5) DEFAULT '0' NULL after `ari_id`;

alter table `default_records` change `rtype` `rtype` enum('NS','MX','CNAME','A','TXT') character set latin1 collate latin1_swedish_ci NULL;

alter table `clients` add column `comments` text NULL after `scalr_api_key`;

CREATE TABLE `init_tokens` (
  `id` int(11) NOT NULL auto_increment,
  `instance_id` varchar(255) default NULL,
  `token` varchar(255) default NULL,
  `dtadded` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

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


CREATE TABLE `instances_history` (
  `id` int(11) NOT NULL auto_increment,
  `instance_id` varchar(20) default NULL,
  `dtlaunched` int(11) default NULL,
  `dtterminated` int(11) default NULL,
  `uptime` int(11) default NULL,
  `instance_type` varchar(20) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `scheduler_tasks` (
  `id` int(11) NOT NULL auto_increment,
  `task_name` varchar(255) default NULL,
  `task_type` varchar(255) default NULL,
  `target_id` varchar(255) default NULL,
  `target_type` varchar(255) default NULL,
  `start_time_date` datetime default NULL,
  `end_time_date` datetime default NULL,
  `last_start_time` datetime default NULL,
  `restart_every` int(11) default '0',
  `task_config` text,
  `order_index` int(11) default NULL,
  `client_id` int(11) default NULL,
  `status` varchar(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=INNODB AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `rds_snaps_info` (
   `id` INT(11) NOT NULL AUTO_INCREMENT,
   `snapid` VARCHAR(50) DEFAULT NULL,
   `comment` VARCHAR(255) DEFAULT NULL,
   `dtcreated` DATETIME DEFAULT NULL,   
   `region` VARCHAR(255) DEFAULT 'us-east-1',
   `autosnapshotid` INT(11) DEFAULT '0',
   PRIMARY KEY  (`id`)
) ENGINE=INNODB AUTO_INCREMENT=1;

alter table `autosnap_settings` add column `objectid` varchar(20) NULL after `arrayid`, add column `object_type` varchar(20) NULL after `objectid`;
alter table `autosnap_settings` drop column `volumeid`, drop column `arrayid`;
