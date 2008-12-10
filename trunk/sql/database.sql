SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `wwwscal_ec2farm`
--

-- --------------------------------------------------------

--
-- Table structure for table `ami_roles`
--

CREATE TABLE IF NOT EXISTS `ami_roles` (
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
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE IF NOT EXISTS `clients` (
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
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `client_settings`
--

CREATE TABLE IF NOT EXISTS `client_settings` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `key` varchar(255) default NULL,
  `value` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`clientid`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `key` varchar(255) default NULL,
  `value` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE IF NOT EXISTS `countries` (
  `id` int(5) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `code` char(2) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `IDX_COUNTRIES_NAME` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `default_records`
--

CREATE TABLE IF NOT EXISTS `default_records` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default '0',
  `rtype` enum('NS','MX','CNAME','A') default NULL,
  `ttl` int(11) default '14400',
  `rpriority` int(11) default NULL,
  `rvalue` varchar(255) default NULL,
  `rkey` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `elastic_ips`
--

CREATE TABLE IF NOT EXISTS `elastic_ips` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `role_name` varchar(100) default NULL,
  `ipaddress` varchar(15) default NULL,
  `state` tinyint(1) default '0',
  `instance_id` varchar(20) default NULL,
  `clientid` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `type` varchar(25) default NULL,
  `dtadded` datetime default NULL,
  `message` varchar(255) default NULL,
  `ishandled` tinyint(1) default '0',
  `short_message` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `farmid` (`farmid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farms`
--

CREATE TABLE IF NOT EXISTS `farms` (
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
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_amis`
--

CREATE TABLE IF NOT EXISTS `farm_amis` (
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
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_event_observers`
--

CREATE TABLE IF NOT EXISTS `farm_event_observers` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `event_observer_name` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`farmid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_event_observers_config`
--

CREATE TABLE IF NOT EXISTS `farm_event_observers_config` (
  `id` int(11) NOT NULL auto_increment,
  `observerid` int(11) default NULL,
  `key` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `NewIndex1` (`observerid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_instances`
--

CREATE TABLE IF NOT EXISTS `farm_instances` (
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
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_stats`
--

CREATE TABLE IF NOT EXISTS `farm_stats` (
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `garbage_queue`
--

CREATE TABLE IF NOT EXISTS `garbage_queue` (
  `id` int(11) NOT NULL auto_increment,
  `clientid` int(11) default NULL,
  `data` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `clientindex` (`clientid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ipaccess`
--

CREATE TABLE IF NOT EXISTS `ipaccess` (
  `id` int(11) NOT NULL auto_increment,
  `ipaddress` varchar(255) default NULL,
  `comment` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `logentries`
--

CREATE TABLE IF NOT EXISTS `logentries` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nameservers`
--

CREATE TABLE IF NOT EXISTS `nameservers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `host` varchar(100) default NULL,
  `port` int(10) unsigned default NULL,
  `username` varchar(100) default NULL,
  `password` text,
  `rndc_path` varchar(255) default NULL,
  `named_path` varchar(255) default NULL,
  `namedconf_path` varchar(255) default NULL,
  `isproxy` tinyint(1) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `payment_redirects`
--

CREATE TABLE IF NOT EXISTS `payment_redirects` (
  `id` int(11) NOT NULL auto_increment,
  `from_clientid` int(11) default NULL,
  `to_clientid` int(11) default NULL,
  `subscription_id` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `real_servers`
--

CREATE TABLE IF NOT EXISTS `real_servers` (
  `id` int(11) NOT NULL auto_increment,
  `farmid` int(11) default NULL,
  `ami_id` varchar(255) default NULL,
  `ipaddress` varchar(15) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `rebundle_log`
--

CREATE TABLE IF NOT EXISTS `rebundle_log` (
  `id` int(11) NOT NULL auto_increment,
  `roleid` int(11) default NULL,
  `dtadded` datetime default NULL,
  `message` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `records`
--

CREATE TABLE IF NOT EXISTS `records` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zoneid` int(10) unsigned NOT NULL default '0',
  `rtype` enum('A','MX','CNAME','NS','TXT') default NULL,
  `ttl` int(10) unsigned default NULL,
  `rpriority` int(10) unsigned default NULL,
  `rvalue` varchar(255) default NULL,
  `rkey` varchar(255) default NULL,
  `issystem` tinyint(1) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `zoneid` (`zoneid`,`rtype`,`rvalue`,`rkey`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `security_rules`
--

CREATE TABLE IF NOT EXISTS `security_rules` (
  `id` int(11) NOT NULL auto_increment,
  `roleid` int(11) default NULL,
  `rule` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `syslog`
--

CREATE TABLE IF NOT EXISTS `syslog` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `syslog_metadata`
--

CREATE TABLE IF NOT EXISTS `syslog_metadata` (
  `id` int(11) NOT NULL auto_increment,
  `transactionid` varchar(50) default NULL,
  `errors` int(5) default NULL,
  `warnings` int(5) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `transid` (`transactionid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `task_queue`
--

CREATE TABLE IF NOT EXISTS `task_queue` (
  `id` int(11) NOT NULL auto_increment,
  `queue_name` varchar(255) default NULL,
  `data` text,
  `dtadded` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `vhosts`
--

CREATE TABLE IF NOT EXISTS `vhosts` (
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
  PRIMARY KEY  (`id`),
  UNIQUE KEY `NewIndex1` (`name`,`farmid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `zones`
--

CREATE TABLE IF NOT EXISTS `zones` (
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


INSERT INTO `ami_roles` (`id`, `ami_id`, `name`, `roletype`, `clientid`, `prototype_iid`, `iscompleted`, `comments`, `dtbuilt`, `description`, `replace`, `default_minLA`, `default_maxLA`, `alias`, `instance_type`, `architecture`, `dtbuildstarted`, `rebundle_trap_received`, `fail_details`, `isstable`) VALUES
(1, 'ami-51f21638', 'base', 'SHARED', 0, NULL, 1, '', NULL, 'Bare AMI that doesn''t involved in web serving. Suitable for batch job workers like media encoders etc.', '', 1, 4, 'base', 'm1.small', 'i386', NULL, 1, NULL, 1),
(2, 'ami-2cf21645', 'mysql', 'SHARED', 0, NULL, 1, NULL, NULL, 'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing.', '', 1, 4, 'mysql', 'm1.small', 'i386', NULL, 1, NULL, 1),
(18, 'ami-72f2161b', 'www', 'SHARED', 0, NULL, 1, '', NULL, 'Frontend web server/load balancer, running nginx. Proxies all requests to all instances of app role.', '', 1, 5, 'www', 'm1.small', 'i386', NULL, 1, NULL, 1),
(20, 'ami-bac420d3', 'app', 'SHARED', 0, NULL, 1, NULL, NULL, 'Apache (LAMP) application server. Can act as a backend (if farm contains www role) or frontend web server.', '', 1, 5, 'app', 'm1.small', 'i386', NULL, 1, NULL, 1),
(47, 'ami-0ac62263', 'app64', 'SHARED', 0, NULL, 1, NULL, NULL, 'Apache (LAMP) application server. Can act as a backend (if farm contains www role) or frontend web server.', '', 1, 5, 'app', 'm1.large', 'x86_64', NULL, 1, NULL, 1),
(48, 'ami-03ca2e6a', 'base64', 'SHARED', 0, NULL, 1, NULL, NULL, 'Bare AMI that doesn''t involved in web serving. Suitable for batch job workers like media encoders etc.', '', 1, 5, 'base', 'm1.large', 'x86_64', NULL, 1, NULL, 1),
(49, 'ami-e8c62281', 'mysql64', 'SHARED', 0, NULL, 1, NULL, NULL, 'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing.', '', 1, 5, 'mysql', 'm1.large', 'x86_64', NULL, 1, NULL, 1),
(50, 'ami-01ca2e68', 'www64', 'SHARED', 0, NULL, 1, NULL, NULL, 'Frontend web server/load balancer, running nginx. Proxies all requests to all instances of app role.', '', 1, 5, 'www', 'm1.large', 'x86_64', NULL, 1, NULL, 1),
(892, 'ami-21cf2b48', 'mysqllvm64', 'SHARED', 0, NULL, 1, NULL, NULL, 'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing. Users LVM to quicker perform backup snapshots and support huge databases.', NULL, 1, 5, 'mysql', 'm1.large', 'x86_64', NULL, 0, NULL, 0),
(936, 'ami-c2d034ab', 'app-rails', 'SHARED', 0, '', 1, NULL, '2008-09-22 08:05:56', '<b>Apache2 + mod_rails + Rails 2.1.1</b><br/>\r\nCan act as a backend (if farm contains www role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\na_? <a target="blank" href=''http://www.modrails.com/documentation/Users guide.html''>Phusion Passenger</a><br/>\r\na_? <a target="blank" href="http://revolutiononrails.blogspot.com/2007/04/plugin-release-actsasreadonlyable.html">ActsAsReadonlyable</a>\r\n<br/><br/>\r\n<b>Essential paths:</b><br/>\r\nWebroot:  <code>/var/www</code> - symlinks to <code>/usr/rails/scalr-placeholder/public</code><br/>\r\nDefault virtual host config: <code>/etc/apache2/sites-enabled/000-default</code><br/>', '', 2, 5, 'app', 'm1.small', 'i386', '2008-09-22 07:58:46', 1, NULL, 0),
(959, 'ami-cfd034a6', 'memcached', 'SHARED', 0, NULL, 1, NULL, NULL, '<b>Memcached</b><br/><br/>\r\n\r\n<b>Notes</b><br/>\r\na_? Consumes up to 1.5GB of memory.<br/>\r\na_? By default only allows connections from all instances in the same farm. To add external IPs, add them into <code>/etc/aws/roles/memcached/allowed_ips.list</code> file, one per line.\r\n<br/><br/>\r\n<b>References:</b><br>\r\na_? <a target=_"blank" href=''http://www.danga.com/memcached/''>memcached: a distributed memory object caching system</a> ', NULL, 2, 5, 'memcached', 'm1.small', 'i386', NULL, 0, NULL, 1),
(977, 'ami-69d23600', 'app-rails64', 'SHARED', 0, NULL, 1, NULL, NULL, '<b>Apache2 + mod_rails + Rails 2.1.1</b><br/>\r\nCan act as a backend (if farm contains www role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\na_? <a target="blank" href=''http://www.modrails.com/documentation/Users guide.html''>Phusion Passenger</a><br/>\r\na_? <a target="blank" href="http://revolutiononrails.blogspot.com/2007/04/plugin-release-actsasreadonlyable.html">ActsAsReadonlyable</a>\r\n<br/><br/>\r\n<b>Essential paths:</b><br/>\r\nWebroot:  <code>/var/www</code> - symlinks to <code>/usr/rails/scalr-placeholder/public</code><br/>\r\nDefault virtual host config: <code>/etc/apache2/sites-enabled/000-default</code><br/>', NULL, 1, 5, 'app', 'm1.large', 'x86_64', NULL, 0, NULL, 0),
(704, 'ami-51fa1e38', 'mysqllvm', 'SHARED', 0, NULL, 1, NULL, NULL, 'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing. Users LVM to quicker perform backup snapshots and support huge databases.', NULL, 1, 5, 'mysql', 'm1.small', 'i386', NULL, 0, NULL, 0);

INSERT INTO `config` (`id`, `key`, `value`) VALUES
(3, 'cryptokey', 'QhSqDYX7K5N85W8E'),
(4, 'crypto_algo', 'SHA256'),
(18, 'paging_items', '20'),
(944, 'admin_password', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918'),
(979, 'http_vhost_template', ''),
(980, 'https_vhost_template', ''),
(981, 'apache_docroot_dir', ''),
(982, 'apache_logs_dir', ''),
(983, 'nginx_https_vhost_template', ''),
(985, 'admin_login', 'admin'),
(986, 'email_address', 'admin@server.net'),
(987, 'email_name', 'Support'),
(988, 'email_dsn', ''),
(989, 'log_days', '10'),
(990, 'dynamic_a_rec_ttl', '90'),
(991, 'def_soa_owner', 'admin.server.net'),
(992, 'def_soa_parent', 'ns1.server.net'),
(993, 'def_soa_ttl', '14400'),
(994, 'def_soa_refresh', '14400'),
(995, 'def_soa_retry', '7200'),
(996, 'def_soa_expire', '3600000'),
(997, 'def_soa_minttl', '300'),
(998, 'namedconftpl', 'zone "{zone}" {\r\n   type master;\r\n   file "{db_filename}";\r\n   allow-transfer { {allow_transfer}; };\r\n};'),
(999, 'aws_accountid', '000000000000'),
(1000, 'aws_keyname', 'XXXXXXXXXXXX'),
(1001, 'aws_accesskey', 'XXXXXXXXXXXXXXXXXXXXXXXXX'),
(1002, 'aws_accesskey_id', 'XXXXXXXXXXXXXXXXXXX'),
(1003, 'secgroup_prefix', 'myscalr.'),
(1004, 's3cfg_template', '[default]\r\naccess_key = [access_key]\r\nacl_public = False\r\nforce = False\r\nhost = s3.amazonaws.com\r\nhuman_readable_sizes = False\r\nrecv_chunk = 4096\r\nsecret_key = [secret_key]\r\nsend_chunk = 4096\r\nverbosity = WARNING'),
(1005, 'client_max_instances', '20'),
(1006, 'rrdtool_path', '/usr/local/bin/rrdtool'),
(1007, 'rrd_default_font_path', '/usr/share/rrdtool/fonts/DejaVuSansMono-Roman.ttf'),
(1008, 'rrd_db_dir', '/home/rrddata'),
(1009, 'rrd_stats_url', 'https://s3.amazonaws.com/scalr-stats/%fid%/%rn%_%wn%.'),
(1010, 'rrd_graph_storage_type', 'S3'),
(1011, 'rrd_graph_storage_path', 'scalr-stats'),
(1012, 'snmptrap_path', '/usr/bin/snmptrap'),
(1013, 'http_proto', 'http'),
(1014, 'eventhandler_url', 'server.net'),
(1015, 'reboot_timeout', '300'),
(1016, 'launch_timeout', '300'),
(1017, 'cron_processes_number', '5'),
(1018, 'app_sys_ipaddress', 'xxx.xxx.xxx.xxx');

INSERT INTO `countries` (`id`, `name`, `code`) VALUES
(1, 'Afghanistan', 'AF'),
(2, 'Albania', 'AL'),
(3, 'Algeria', 'DZ'),
(4, 'American Samoa', 'AS'),
(5, 'Andorra', 'AD'),
(6, 'Angola', 'AO'),
(7, 'Anguilla', 'AI'),
(8, 'Antarctica', 'AQ'),
(9, 'Antigua and Barbuda', 'AG'),
(10, 'Argentina', 'AR'),
(11, 'Armenia', 'AM'),
(12, 'Aruba', 'AW'),
(13, 'Australia', 'AU'),
(14, 'Austria', 'AT'),
(15, 'Azerbaijan', 'AZ'),
(16, 'Bahamas', 'BS'),
(17, 'Bahrain', 'BH'),
(18, 'Bangladesh', 'BD'),
(19, 'Barbados', 'BB'),
(20, 'Belarus', 'BY'),
(21, 'Belgium', 'BE'),
(22, 'Belize', 'BZ'),
(23, 'Benin', 'BJ'),
(24, 'Bermuda', 'BM'),
(25, 'Bhutan', 'BT'),
(26, 'Bolivia', 'BO'),
(27, 'Bosnia and Herzegowina', 'BA'),
(28, 'Botswana', 'BW'),
(29, 'Bouvet Island', 'BV'),
(30, 'Brazil', 'BR'),
(31, 'British Indian Ocean Territory', 'IO'),
(32, 'Brunei Darussalam', 'BN'),
(33, 'Bulgaria', 'BG'),
(34, 'Burkina Faso', 'BF'),
(35, 'Burundi', 'BI'),
(36, 'Cambodia', 'KH'),
(37, 'Cameroon', 'CM'),
(38, 'Canada', 'CA'),
(39, 'Cape Verde', 'CV'),
(40, 'Cayman Islands', 'KY'),
(41, 'Central African Republic', 'CF'),
(42, 'Chad', 'TD'),
(43, 'Chile', 'CL'),
(44, 'China', 'CN'),
(45, 'Christmas Island', 'CX'),
(46, 'Cocos (Keeling) Islands', 'CC'),
(47, 'Colombia', 'CO'),
(48, 'Comoros', 'KM'),
(49, 'Congo', 'CG'),
(50, 'Cook Islands', 'CK'),
(51, 'Costa Rica', 'CR'),
(52, 'Cote D''Ivoire', 'CI'),
(53, 'Croatia', 'HR'),
(54, 'Cuba', 'CU'),
(55, 'Cyprus', 'CY'),
(56, 'Czech Republic', 'CZ'),
(57, 'Denmark', 'DK'),
(58, 'Djibouti', 'DJ'),
(59, 'Dominica', 'DM'),
(60, 'Dominican Republic', 'DO'),
(61, 'East Timor', 'TP'),
(62, 'Ecuador', 'EC'),
(63, 'Egypt', 'EG'),
(64, 'El Salvador', 'SV'),
(65, 'Equatorial Guinea', 'GQ'),
(66, 'Eritrea', 'ER'),
(67, 'Estonia', 'EE'),
(68, 'Ethiopia', 'ET'),
(69, 'Falkland Islands (Malvinas)', 'FK'),
(70, 'Faroe Islands', 'FO'),
(71, 'Fiji', 'FJ'),
(72, 'Finland', 'FI'),
(73, 'France', 'FR'),
(74, 'France, MEtropolitan', 'FX'),
(75, 'French Guiana', 'GF'),
(76, 'French Polynesia', 'PF'),
(77, 'French Southern Territories', 'TF'),
(78, 'Gabon', 'GA'),
(79, 'Gambia', 'GM'),
(80, 'Georgia', 'GE'),
(81, 'Germany', 'DE'),
(82, 'Ghana', 'GH'),
(83, 'Gibraltar', 'GI'),
(84, 'Greece', 'GR'),
(85, 'Greenland', 'GL'),
(86, 'Grenada', 'GD'),
(87, 'Guadeloupe', 'GP'),
(88, 'Guam', 'GU'),
(89, 'Guatemala', 'GT'),
(90, 'Guinea', 'GN'),
(91, 'Guinea-bissau', 'GW'),
(92, 'Guyana', 'GY'),
(93, 'Haiti', 'HT'),
(94, 'Heard and Mc Donald Islands', 'HM'),
(95, 'Honduras', 'HN'),
(96, 'Hong Kong', 'HK'),
(97, 'Hungary', 'HU'),
(98, 'Iceland', 'IS'),
(99, 'India', 'IN'),
(100, 'Indonesia', 'ID'),
(101, 'Iran (Islamic Republic of)', 'IR'),
(102, 'Iraq', 'IQ'),
(103, 'Ireland', 'IE'),
(104, 'Israel', 'IL'),
(105, 'Italy', 'IT'),
(106, 'Jamaica', 'JM'),
(107, 'Japan', 'JP'),
(108, 'Jordan', 'JO'),
(109, 'Kazakhstan', 'KZ'),
(110, 'Kenya', 'KE'),
(111, 'Kiribati', 'KI'),
(112, 'Korea, Democratic People''s Republic of', 'KP'),
(113, 'Korea, Republic of', 'KR'),
(114, 'Kuwait', 'KW'),
(115, 'Kyrgyzstan', 'KG'),
(116, 'Lao People''s Democratic Republic', 'LA'),
(117, 'Latvia', 'LV'),
(118, 'Lebanon', 'LB'),
(119, 'Lesotho', 'LS'),
(120, 'Liberia', 'LR'),
(121, 'Libyan Arab Jamahiriya', 'LY'),
(122, 'Liechtenstein', 'LI'),
(123, 'Lithuania', 'LT'),
(124, 'Luxembourg', 'LU'),
(125, 'Macau', 'MO'),
(126, 'Macedonia, The Former Yugoslav Republic of', 'MK'),
(127, 'Madagascar', 'MG'),
(128, 'Malawi', 'MW'),
(129, 'Malaysia', 'MY'),
(130, 'Maldives', 'MV'),
(131, 'Mali', 'ML'),
(132, 'Malta', 'MT'),
(133, 'Marshall Islands', 'MH'),
(134, 'Martinique', 'MQ'),
(135, 'Mauritania', 'MR'),
(136, 'Mauritius', 'MU'),
(137, 'Mayotte', 'YT'),
(138, 'Mexico', 'MX'),
(139, 'Micronesia, Federated States of', 'FM'),
(140, 'Moldova, Republic of', 'MD'),
(141, 'Monaco', 'MC'),
(142, 'Mongolia', 'MN'),
(143, 'Montserrat', 'MS'),
(144, 'Morocco', 'MA'),
(145, 'Mozambique', 'MZ'),
(146, 'Myanmar', 'MM'),
(147, 'Namibia', 'NA'),
(148, 'Nauru', 'NR'),
(149, 'Nepal', 'NP'),
(150, 'Netherlands', 'NL'),
(151, 'Netherlands Antilles', 'AN'),
(152, 'New Caledonia', 'NC'),
(153, 'New Zealand', 'NZ'),
(154, 'Nicaragua', 'NI'),
(155, 'Niger', 'NE'),
(156, 'Nigeria', 'NG'),
(157, 'Niue', 'NU'),
(158, 'Norfolk Island', 'NF'),
(159, 'Northern Mariana Islands', 'MP'),
(160, 'Norway', 'NO'),
(161, 'Oman', 'OM'),
(162, 'Pakistan', 'PK'),
(163, 'Palau', 'PW'),
(164, 'Panama', 'PA'),
(165, 'Papua New Guinea', 'PG'),
(166, 'Paraguay', 'PY'),
(167, 'Peru', 'PE'),
(168, 'Philippines', 'PH'),
(169, 'Pitcairn', 'PN'),
(170, 'Poland', 'PL'),
(171, 'Portugal', 'PT'),
(172, 'Puerto Rico', 'PR'),
(173, 'Qatar', 'QA'),
(174, 'Reunion', 'RE'),
(175, 'Romania', 'RO'),
(176, 'Russian Federation', 'RU'),
(177, 'Rwanda', 'RW'),
(178, 'Saint Kitts and Nevis', 'KN'),
(179, 'Saint Lucia', 'LC'),
(180, 'Saint Vincent and the Grenadines', 'VC'),
(181, 'Samoa', 'WS'),
(182, 'San Marino', 'SM'),
(183, 'Sao Tome and Principe', 'ST'),
(184, 'Saudi Arabia', 'SA'),
(185, 'Senegal', 'SN'),
(186, 'Seychelles', 'SC'),
(187, 'Sierra Leone', 'SL'),
(188, 'Singapore', 'SG'),
(189, 'Slovakia (Slovak Republic)', 'SK'),
(190, 'Slovenia', 'SI'),
(191, 'Solomon Islands', 'SB'),
(192, 'Somalia', 'SO'),
(193, 'south Africa', 'ZA'),
(194, 'South Georgia and the South Sandwich Islands', 'GS'),
(195, 'Spain', 'ES'),
(196, 'Sri Lanka', 'LK'),
(197, 'St. Helena', 'SH'),
(198, 'St. Pierre and Miquelon', 'PM'),
(199, 'Sudan', 'SD'),
(200, 'Suriname', 'SR'),
(201, 'Svalbard and Jan Mayen Islands', 'SJ'),
(202, 'Swaziland', 'SZ'),
(203, 'Sweden', 'SE'),
(204, 'Switzerland', 'CH'),
(205, 'Syrian Arab Republic', 'SY'),
(206, 'Taiwan, Province of China', 'TW'),
(207, 'Tajikistan', 'TJ'),
(208, 'Tanzania, United Republic of', 'TZ'),
(209, 'Thailand', 'TH'),
(210, 'Togo', 'TG'),
(211, 'Tokelau', 'TK'),
(212, 'Tonga', 'TO'),
(213, 'Trinidad and Tobago', 'TT'),
(214, 'Tunisia', 'TN'),
(215, 'Turkey', 'TR'),
(216, 'Turkmenistan', 'TM'),
(217, 'Turks and Caicos Islands', 'TC'),
(218, 'Tuvalu', 'TV'),
(219, 'Uganda', 'UG'),
(220, 'Ukraine', 'UA'),
(221, 'United Arab Emirates', 'AE'),
(222, 'United Kingdom', 'GB'),
(223, 'United States', 'US'),
(224, 'United States Minor Outlying Islands', 'UM'),
(225, 'Uruguay', 'UY'),
(226, 'Uzbekistan', 'UZ'),
(227, 'Vanuatu', 'VU'),
(228, 'Vatican City State (Holy See)', 'VA'),
(229, 'Venezuela', 'VE'),
(230, 'Viet Nam', 'VN'),
(231, 'Virgin Islands (British)', 'VG'),
(232, 'Virgin Islands (U.S.)', 'VI'),
(233, 'Wallis and Futuna Islands', 'WF'),
(234, 'Western Sahara', 'EH'),
(235, 'Yemen', 'YE'),
(236, 'Yugoslavia', 'YU'),
(237, 'Zaire', 'ZR'),
(238, 'Zambia', 'ZM'),
(239, 'Zimbabwe', 'ZW');

--
-- Dumping data for table `default_records`
--

INSERT INTO `default_records` (`id`, `clientid`, `rtype`, `ttl`, `rpriority`, `rvalue`, `rkey`) VALUES
(1, 0, 'CNAME', 14400, 0, '%hostname%', 'www');

--
-- Dumping data for table `ipaccess`
--

INSERT INTO `ipaccess` (`id`, `ipaddress`, `comment`) VALUES
(1, '91.124.*.*', 'Urktelecom aDSL pool'),
(2, '*.*.*.*', 'Disable IP whitelist');

--
-- Dumping data for table `security_rules`
--

INSERT INTO `security_rules` (`id`, `roleid`, `rule`) VALUES
(45, 18, 'tcp:22:22:0.0.0.0/0'),
(46, 18, 'tcp:80:80:0.0.0.0/0'),
(47, 18, 'udp:161:162:0.0.0.0/0'),
(126, 44, 'udp:161:162:0.0.0.0/0'),
(127, 44, 'tcp:80:80:0.0.0.0/0'),
(128, 44, 'tcp:22:22:0.0.0.0/0'),
(132, 46, 'tcp:22:22:0.0.0.0/0'),
(133, 46, 'tcp:80:80:0.0.0.0/0'),
(134, 46, 'udp:161:162:0.0.0.0/0'),
(138, 48, 'tcp:22:22:0.0.0.0/0'),
(139, 48, 'udp:161:162:0.0.0.0/0'),
(140, 49, 'tcp:3306:3306:0.0.0.0/0'),
(141, 49, 'tcp:22:22:0.0.0.0/0'),
(142, 49, 'udp:161:162:0.0.0.0/0'),
(143, 50, 'tcp:22:22:0.0.0.0/0'),
(144, 50, 'tcp:80:80:0.0.0.0/0'),
(145, 50, 'udp:161:162:0.0.0.0/0'),
(303, 18, 'icmp:-1:-1:0.0.0.0/0'),
(305, 44, 'icmp:-1:-1:0.0.0.0/0'),
(307, 48, 'icmp:-1:-1:0.0.0.0/0'),
(308, 49, 'icmp:-1:-1:0.0.0.0/0'),
(309, 50, 'icmp:-1:-1:0.0.0.0/0'),
(493, 892, 'udp:161:162:0.0.0.0/0'),
(494, 892, 'tcp:22:22:0.0.0.0/0'),
(495, 892, 'icmp:-1:-1:0.0.0.0/0'),
(496, 892, 'tcp:3306:3306:0.0.0.0/0'),
(505, 1, 'tcp:22:22:0.0.0.0/0'),
(506, 1, 'udp:161:162:0.0.0.0/0'),
(507, 1, 'icmp:-1:-1:0.0.0.0/0'),
(508, 2, 'udp:161:162:0.0.0.0/0'),
(509, 2, 'tcp:3306:3306:0.0.0.0/0'),
(510, 2, 'tcp:22:22:0.0.0.0/0'),
(511, 2, 'icmp:-1:-1:0.0.0.0/0'),
(512, 47, 'tcp:22:22:0.0.0.0/0'),
(513, 47, 'tcp:80:80:0.0.0.0/0'),
(514, 47, 'udp:161:162:0.0.0.0/0'),
(515, 47, 'icmp:-1:-1:0.0.0.0/0'),
(523, 20, 'tcp:22:22:0.0.0.0/0'),
(524, 20, 'tcp:80:80:0.0.0.0/0'),
(525, 20, 'udp:161:162:0.0.0.0/0'),
(526, 20, 'icmp:-1:-1:0.0.0.0/0'),
(571, 936, 'tcp:22:22:0.0.0.0/0'),
(572, 936, 'tcp:80:80:0.0.0.0/0'),
(573, 936, 'udp:161:162:0.0.0.0/0'),
(574, 936, 'icmp:-1:-1:0.0.0.0/0'),
(575, 959, 'udp:161:162:0.0.0.0/0'),
(576, 959, 'tcp:22:22:0.0.0.0/0'),
(577, 959, 'icmp:-1:-1:0.0.0.0/0'),
(578, 959, 'tcp:11211:11211:0.0.0.0/0'),
(595, 955, 'udp:161:162:0.0.0.0/0'),
(596, 955, 'tcp:22:22:0.0.0.0/0'),
(597, 955, 'icmp:-1:-1:0.0.0.0/0'),
(598, 955, 'tcp:11211:11211:0.0.0.0/0'),
(603, 977, 'udp:161:162:0.0.0.0/0'),
(604, 977, 'tcp:22:22:0.0.0.0/0'),
(605, 977, 'icmp:-1:-1:0.0.0.0/0'),
(606, 977, 'tcp:80:80:0.0.0.0/0'),
(null, 704, 'udp:161:162:0.0.0.0/0'),
(null, 704, 'tcp:22:22:0.0.0.0/0'),
(null, 704, 'icmp:-1:-1:0.0.0.0/0'),
(null, 704, 'tcp:3306:3306:0.0.0.0/0');


CREATE TABLE `ebs_snaps_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `snapid` varchar(50) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `dtcreated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1;


CREATE TABLE `farm_ebs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `role_name` varchar(255) DEFAULT NULL,
  `volumeid` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `instance_id` varchar(255) DEFAULT NULL,
  `avail_zone` varchar(255) DEFAULT NULL,
  `device` varchar(50) DEFAULT NULL,
  `isfsexists` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1;


CREATE TABLE `farm_role_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `ami_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` text,
  `hash` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1;


CREATE TABLE `farm_role_scripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `templateid` int(11) DEFAULT NULL,
  `farmid` int(11) DEFAULT NULL,
  `ami_id` varchar(255) DEFAULT NULL,
  `params` text,
  `event_name` varchar(255) DEFAULT NULL,
  `target` varchar(50) DEFAULT NULL,
  `version` varchar(20) DEFAULT 'latest',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UniqueIndex` (`templateid`,`farmid`,`ami_id`,`event_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1;

CREATE TABLE `role_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(15) DEFAULT NULL,
  `isrequired` tinyint(1) DEFAULT '0',
  `defval` text,
  `allow_multiple_choice` tinyint(1) DEFAULT '0',
  `options` text,
  `ami_id` varchar(50) DEFAULT NULL,
  `hash` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_role` (`name`,`ami_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1;


CREATE TABLE `script_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `script` text,
  `issystem` tinyint(1) DEFAULT '0',
  `version` int(2) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `issync` tinyint(1) DEFAULT '0',
  `clientid` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1;


CREATE TABLE `scripting_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `instance` varchar(25) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `message` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clientid` int(11) DEFAULT NULL,
  `object_owner` int(11) DEFAULT NULL,
  `dtcreated` datetime DEFAULT NULL,
  `object_type` varchar(50) DEFAULT NULL,
  `comment` text,
  `objectid` int(11) DEFAULT NULL,
  `isprivate` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1;

CREATE TABLE `script_template_revisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `templateid` int(11) DEFAULT NULL,
  `revision` int(11) DEFAULT NULL,
  `script` longtext,
  `dtcreated` datetime DEFAULT NULL,
  `approval_state` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1;

ALTER TABLE `ami_roles` CHANGE `description` `description` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;
ALTER TABLE `farm_amis` ADD `use_ebs` TINYINT( 1 ) NULL ;

alter table `farm_amis` add column `ebs_size` int(11) DEFAULT '0' NULL after `use_ebs`, add column `ebs_snapid` varchar(50) NULL after `ebs_size`;
alter table `farm_instances` add column `avail_zone` varchar(255) NULL after `mysql_replication_status`;
alter table `farm_amis` add column `ebs_mountpoint` varchar(255) NULL after `ebs_snapid`, add column `ebs_mount` tinyint(1) DEFAULT '0' NULL after `ebs_mountpoint`;
alter table `farm_instances` add column `ishalted` tinyint(1) DEFAULT '0' NULL after `avail_zone`;
alter table `ami_roles` add column `prototype_role` varchar(255) NULL after `isstable`;


insert  into `script_templates`(`id`,`name`,`description`,`script`,`issystem`,`version`,`dtadded`,`issync`,`clientid`) values (1,'SVN update','Update a working copy from SVN repository','#!/bin/bash\n\nSVN_PATH=\"/usr/bin/svn\"\nSVN_USER=\"%svn_user%\"\nSVN_PASS=\"%svn_password%\"\nSVN_REV=\"%svn_revision%\"\nSVN_UP_DIR=\"%svn_co_dir%\"\n\n\nif [ -z \"$SVN_UP_DIR\" ]; then\n        echo \"Working copy directory was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ -z \"$SVN_PATH\" ] || [ ! -x \"$SVN_PATH\" ]; then\n        echo \"SVN binary is not executable\" >&2\n\n        exit 1\nfi\n\n[ \"$SVN_USER\" ] && SVN_USER_STR=\"--username $SVN_USER\"\n[ \"$SVN_PASS\" ] && SVN_PASS_STR=\"--password $SVN_PASS\"\n[ \"$SVN_REV\" ]  && SVN_REV_STR=\"-r $SVN_REV\"\n\n\nif $SVN_PATH --non-interactive $SVN_USER_STR $SVN_PASS_STR info \"$SVN_UP_DIR\" >/dev/null 2>&1; then\n        $SVN_PATH --non-interactive $SVN_USER_STR $SVN_PASS_STR $SVN_REV_STR \"$SVN_UP_DIR\"\nfi\n',1,1,'2008-10-23 19:41:07',1,0),(2,'SVN export','Export SVN repository to local directory','#!/bin/bash\n\nSVN_PATH=\"/usr/bin/svn\"\nSVN_REPO_URL=\"%svn_repo_url%\"\nSVN_USER=\"%svn_user%\"\nSVN_PASS=\"%svn_password%\"\nSVN_REV=\"%svn_revision%\"\nSVN_CO_DIR=\"%svn_co_dir%\"\n\n\nif [ -z \"$SVN_REPO_URL\" ]; then \n        echo \"SVN repository URL was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ -z \"$SVN_CO_DIR\" ]; then\n        echo \"Checkout directory was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ -z \"$SVN_PATH\" ] || [ ! -x \"$SVN_PATH\" ]; then\n        echo \"SVN binary is not executable\" >&2\n\n        exit 1\nfi\n\n[ \"$SVN_USER\" ] && SVN_USER_STR=\"--username $SVN_USER\"\n[ \"$SVN_PASS\" ] && SVN_PASS_STR=\"--password $SVN_PASS\"\n[ \"$SVN_REV\" ]  && SVN_REV_STR=\"-r $SVN_REV\"\n\n[ -d \"$SVN_CO_DIR\" ] || mkdir -p $SVN_CO_DIR\n\n$SVN_PATH --force --non-interactive $SVN_USER_STR $SVN_PASS_STR export $SVN_REV_STR \"$SVN_REPO_URL\" \"$SVN_CO_DIR\"\n',1,1,'2008-10-22 15:30:46',0,0),(3,'SVN checkout','Checkout from SVN repository','#!/bin/bash\n\nSVN_PATH=\"/usr/bin/svn\"\nSVN_REPO_URL=\"%svn_repo_url%\"\nSVN_USER=\"%svn_user%\"\nSVN_PASS=\"%svn_password%\"\nSVN_REV=\"%svn_revision%\"\nSVN_CO_DIR=\"%svn_co_dir%\"\n\n\nif [ -z \"$SVN_REPO_URL\" ]; then\n        echo \"SVN repository URL was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ -z \"$SVN_CO_DIR\" ]; then\n        echo \"Checkout directory was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ -z \"$SVN_PATH\" ] || [ ! -x \"$SVN_PATH\" ]; then\n        echo \"SVN binary is not executable\" >&2\n\n        exit 1\nfi\n\n[ \"$SVN_USER\" ] && SVN_USER_STR=\"--username $SVN_USER\"\n[ \"$SVN_PASS\" ] && SVN_PASS_STR=\"--password $SVN_PASS\"\n[ \"$SVN_REV\" ]  && SVN_REV_STR=\"-r $SVN_REV\"\n\n[ -d \"$SVN_CO_DIR\" ] || mkdir -p $SVN_CO_DIR\n\n$SVN_PATH --force --non-interactive $SVN_USER_STR $SVN_PASS_STR checkout $SVN_REV_STR \"$SVN_REPO_URL\" \"$SVN_CO_DIR\"\n',1,1,'2008-10-23 19:42:21',1,0),(4,'Git clone','Clone a git repository','#!/bin/bash\n\nGIT_PATH=\"/usr/bin/git\"\nGIT_REPO_URL=\"%git_repo_url%\"\nGIT_CL_DIR=\"%git_co_dir%\"\n\n\nif [ -z \"$GIT_REPO_URL\" ]; then\n        echo \"GIT repository URL was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ -z \"$GIT_CL_DIR\" ]; then\n        echo \"Destination directory was not specified.\" >&2\n\n        exit 1\nfi\n\nif [ ! -x \"$GIT_PATH\" ]; then\n        /usr/bin/apt-get -q -y install git-core\n\n        if [ ! -x \"$GIT_PATH\" ]; then\n                echo \"GIT binary is not executable\" >&2\n\n                exit 1\n        fi\nfi\n\n$GIT_PATH clone \"$GIT_REPO_URL\" \"$GIT_CL_DIR\"\n',1,1,'2008-10-24 15:04:41',1,0);

alter table `farms` add index `clientid` (`clientid`);
alter table `farm_instances` add index `farmid` (`farmid`);
alter table `ami_roles` add index `NewIndex1` (`ami_id`(255));
alter table `farm_amis` add index `NewIndex1` (`ami_id`(255));

alter table `clients` add column `dtadded` datetime NULL after `aws_certificate_enc`;
alter table `clients` add column `iswelcomemailsent` tinyint(1) DEFAULT '0' NULL after `dtadded`;
alter table `vhosts` add column `role_name` varchar(255) NULL after `aliases`;

alter table `farm_role_scripts` add column `timeout` int(5) DEFAULT '120' NULL after `version`;

alter table `task_queue` add column `failed_attempts` int(3) DEFAULT '0' NULL after `dtadded`;




alter table `script_templates` add column `approval_state` varchar(50) NULL after `clientid`,change `issystem` `origin` varchar(50) DEFAULT '0' NULL ;

alter table `farm_role_scripts` change `templateid` `scriptid` int(11) NULL;

alter table `script_template_revisions` change `templateid` `scriptid` int(11) NULL;

rename table `script_template_revisions` to `script_revisions`;
rename table `script_templates` to `scripts`;

alter table `ami_roles` add column `approval_state` varchar(255) NULL after `prototype_role`;

alter table `farm_role_scripts` add column `issync` tinyint(1) DEFAULT '0' NULL after `timeout`;

alter table `farm_role_scripts` add column `ismenuitem` tinyint(1) DEFAULT '0' NULL after `issync`;