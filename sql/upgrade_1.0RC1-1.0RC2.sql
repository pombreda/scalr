CREATE TABLE `elastic_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `role_name` varchar(100) DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  `state` tinyint(1) DEFAULT '0',
  `instance_id` varchar(20) DEFAULT NULL,
  `clientid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `farm_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `bw_in` bigint(20) DEFAULT '0',
  `bw_out` bigint(20) DEFAULT '0',
  `bw_in_last` int(11) DEFAULT '0',
  `bw_out_last` int(11) DEFAULT '0',
  `month` int(2) DEFAULT NULL,
  `year` int(4) DEFAULT NULL,
  `dtlastupdate` int(11) DEFAULT NULL,
  `m1_small` int(11) DEFAULT '0',
  `m1_large` int(11) DEFAULT '0',
  `m1_xlarge` int(11) DEFAULT '0',
  `c1_medium` int(11) DEFAULT '0',
  `c1_xlarge` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `NewIndex1` (`month`,`year`),
  KEY `NewIndex2` (`farmid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `task_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_name` varchar(255) DEFAULT NULL,
  `data` text,
  `dtadded` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `vhosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `document_root_dir` varchar(100) DEFAULT NULL,
  `server_admin` varchar(100) DEFAULT NULL,
  `issslenabled` tinyint(1) DEFAULT NULL,
  `farmid` int(11) DEFAULT NULL,
  `logs_dir` varchar(100) DEFAULT NULL,
  `ssl_cert` text,
  `ssl_pkey` text,
  `aliases` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `NewIndex1` (`name`,`farmid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

alter table `farm_amis` add column `use_elastic_ips` tinyint(1) DEFAULT '0' NULL after `instance_type`;
alter table `farm_amis` add column `dtlastsync` datetime NULL after `use_elastic_ips`;
alter table `farm_amis` add column `reboot_timeout` int(10) DEFAULT '300' NULL after `dtlastsync`, add column `launch_timeout` int(10) DEFAULT '300' NULL after `reboot_timeout`,change `dtlastsync` `dtlastsync` datetime NULL ;


alter table `farm_instances` add column `isipchanged` tinyint(1) NULL after `dtlastsync`;

alter table `records` change `rtype` `rtype` enum('A','MX','CNAME','NS','TXT') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL ;

alter table `zones` add column `isobsoleted` tinyint(1) NULL after `hosts_list_updated`;

alter table `farm_instances` add column `bwusage_in` bigint(20) NULL after `isipchanged`, add column `bwusage_out` bigint(20) NULL after `bwusage_in`;
alter table `farm_instances` add column `uptime` int(11) NULL after `bwusage_out`,change `isipchanged` `isipchanged` tinyint(1) NULL ;
alter table `farm_instances` add column `status` varchar(50) NULL after `uptime`;
alter table `farm_instances` add column `custom_elastic_ip` varchar(15) NULL after `status`;
alter table `farm_instances` change `state` `state` varchar(50) DEFAULT 'Pending' NULL ;
alter table `farm_instances` add column `mysql_stat_password` varchar(255) NULL after `custom_elastic_ip`;
alter table `farm_instances` add column `mysql_replication_status` tinyint(1) DEFAULT '1' NULL after `mysql_stat_password`;

alter table `ami_roles` add column `isstable` tinyint(1) DEFAULT '1' NULL after `fail_details`;

alter table `farms` add column `dtlaunched` datetime NULL after `bcp_instance_id`;
alter table `farms` add column `term_on_sync_fail` tinyint(1) DEFAULT '1' NULL after `dtlaunched`;
alter table `farms` add column `bucket_name` varchar(255) NULL after `term_on_sync_fail`;
alter table `farms` add column `isbundlerunning` tinyint(1) DEFAULT '0' NULL after `bucket_name`;


UPDATE ami_roles SET ami_id='ami-72f2161b' WHERE name='www';
UPDATE ami_roles SET ami_id='ami-01ca2e68' WHERE name='www64';
UPDATE ami_roles SET ami_id='ami-bac420d3' WHERE name='app';
UPDATE ami_roles SET ami_id='ami-0ac62263' WHERE name='app64';
UPDATE ami_roles SET ami_id='ami-2cf21645' WHERE name='mysql';
UPDATE ami_roles SET ami_id='ami-e8c62281' WHERE name='mysql64';
UPDATE ami_roles SET ami_id='ami-51f21638' WHERE name='base';
UPDATE ami_roles SET ami_id='ami-03ca2e6a' WHERE name='base64';

INSERT INTO `ami_roles` (`id`, `ami_id`, `name`, `roletype`, `clientid`, `prototype_iid`, `iscompleted`, `comments`, `dtbuilt`, `description`, `replace`, `default_minLA`, `default_maxLA`, `alias`, `instance_type`, `architecture`, `dtbuildstarted`, `rebundle_trap_received`, `fail_details`, `isstable`) VALUES
(892, 'ami-21cf2b48', 'mysqllvm64', 'SHARED', 0, NULL, 1, NULL, NULL, 'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing. Users LVM to quicker perform backup snapshots and support huge databases.', NULL, 1, 5, 'mysql', 'm1.large', 'x86_64', NULL, 0, NULL, 0),
(936, 'ami-c2d034ab', 'app-rails', 'SHARED', 0, '', 1, NULL, '2008-09-22 08:05:56', '<b>Apache2 + mod_rails + Rails 2.1.1</b><br/>\r\nCan act as a backend (if farm contains www role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\na_? <a target="blank" href=''http://www.modrails.com/documentation/Users guide.html''>Phusion Passenger</a><br/>\r\na_? <a target="blank" href="http://revolutiononrails.blogspot.com/2007/04/plugin-release-actsasreadonlyable.html">ActsAsReadonlyable</a>\r\n<br/><br/>\r\n<b>Essential paths:</b><br/>\r\nWebroot:  <code>/var/www</code> - symlinks to <code>/usr/rails/scalr-placeholder/public</code><br/>\r\nDefault virtual host config: <code>/etc/apache2/sites-enabled/000-default</code><br/>', '', 2, 5, 'app', 'm1.small', 'i386', '2008-09-22 07:58:46', 1, NULL, 0),
(959, 'ami-cfd034a6', 'memcached', 'SHARED', 0, NULL, 1, NULL, NULL, '<b>Memcached</b><br/><br/>\r\n\r\n<b>Notes</b><br/>\r\na_? Consumes up to 1.5GB of memory.<br/>\r\na_? By default only allows connections from all instances in the same farm. To add external IPs, add them into <code>/etc/aws/roles/memcached/allowed_ips.list</code> file, one per line.\r\n<br/><br/>\r\n<b>References:</b><br>\r\na_? <a target=_"blank" href=''http://www.danga.com/memcached/''>memcached: a distributed memory object caching system</a> ', NULL, 2, 5, 'memcached', 'm1.small', 'i386', NULL, 0, NULL, 1),
(977, 'ami-69d23600', 'app-rails64', 'SHARED', 0, NULL, 1, NULL, NULL, '<b>Apache2 + mod_rails + Rails 2.1.1</b><br/>\r\nCan act as a backend (if farm contains www role) or frontend web server.\r\n<br/><br/>\r\n<b>References:</b><br/>\r\na_? <a target="blank" href=''http://www.modrails.com/documentation/Users guide.html''>Phusion Passenger</a><br/>\r\na_? <a target="blank" href="http://revolutiononrails.blogspot.com/2007/04/plugin-release-actsasreadonlyable.html">ActsAsReadonlyable</a>\r\n<br/><br/>\r\n<b>Essential paths:</b><br/>\r\nWebroot:  <code>/var/www</code> - symlinks to <code>/usr/rails/scalr-placeholder/public</code><br/>\r\nDefault virtual host config: <code>/etc/apache2/sites-enabled/000-default</code><br/>', NULL, 1, 5, 'app', 'm1.large', 'x86_64', NULL, 0, NULL, 0);
(704, 'ami-51fa1e38', 'mysqllvm', 'SHARED', 0, NULL, 1, NULL, NULL, 'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing. Users LVM to quicker perform backup snapshots and support huge databases.', NULL, 1, 5, 'mysql', 'm1.small', 'i386', NULL, 0, NULL, 0);

INSERT INTO `security_rules` (`id`, `roleid`, `rule`) VALUES
(493, 892, 'udp:161:162:0.0.0.0/0'),
(494, 892, 'tcp:22:22:0.0.0.0/0'),
(495, 892, 'icmp:-1:-1:0.0.0.0/0'),
(496, 892, 'tcp:3306:3306:0.0.0.0/0'),
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
(493, 704, 'udp:161:162:0.0.0.0/0'),
(494, 704, 'tcp:22:22:0.0.0.0/0'),
(495, 704, 'icmp:-1:-1:0.0.0.0/0'),
(496, 704, 'tcp:3306:3306:0.0.0.0/0');
