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