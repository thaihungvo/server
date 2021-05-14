# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.26)
# Database: stacks
# Generation Time: 2021-05-14 08:03:43 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table stk_activities
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_activities`;

CREATE TABLE `stk_activities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(10) unsigned NOT NULL,
  `instance` char(36) NOT NULL DEFAULT '',
  `parent` char(36) NOT NULL DEFAULT '',
  `item` char(36) DEFAULT NULL,
  `action` enum('CREATE','UPDATE','DELETE','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
  `section` varchar(10) NOT NULL DEFAULT 'UNKNOWN',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_activities` WRITE;
/*!40000 ALTER TABLE `stk_activities` DISABLE KEYS */;

INSERT INTO `stk_activities` (`id`, `user`, `instance`, `parent`, `item`, `action`, `section`, `created`)
VALUES
	(1,1,'79b1c371-2772-4603-a27e-78ada82fdae1','5114bbde-0a1c-4a6c-8d10-d00631188104','d9da620c-d4e9-429a-9a6b-e3f6d8d1f9f7','DELETE','document','2021-05-13 15:11:43'),
	(2,1,'79b1c371-2772-4603-a27e-78ada82fdae1','c9da620c-d4e9-429a-9a6b-e3f6d8d1f9f7','','CREATE','folder','2021-05-13 15:23:22'),
	(3,1,'79b1c371-2772-4603-a27e-78ada82fdae1','d3da620c-d4e9-429a-9a6b-e3f6d8d1f9f7','','CREATE','folder','2021-05-13 15:23:53'),
	(4,1,'79b1c371-2772-4603-a27e-78ada82fdae1','c9da620c-d4e9-429a-9a6b-e3f6d8d1f9f7','','CREATE','folder','2021-05-13 15:24:15'),
	(5,1,'79b1c371-2772-4603-a27e-78ada82fdae1','d9da620c-d4e9-429a-9a6b-e3f6d8d1f9f7','1','CREATE','project','2021-05-13 15:26:19'),
	(6,1,'79b1c371-2772-4603-a27e-78ada82fdae1','d9da620c-d4e9-429a-9a6b-e3f6d8d1f9f7','1','CREATE','project','2021-05-13 15:27:40'),
	(7,1,'79b1c371-2772-4603-a27e-78ada82fdae1','d9da620c-d4e9-429a-9a6b-e3f6d8d1f9f7','1','CREATE','project','2021-05-13 15:28:43'),
	(8,1,'79b1c371-2772-4603-a27e-78ada82fdae1','w9da620c-d4e9-429a-9a6b-e3f6d8d1f9f7','1','CREATE','project','2021-05-13 15:29:10');

/*!40000 ALTER TABLE `stk_activities` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_attachments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_attachments`;

CREATE TABLE `stk_attachments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner` int(11) NOT NULL,
  `task` char(36) NOT NULL DEFAULT '',
  `title` text,
  `extension` varchar(10) NOT NULL DEFAULT '',
  `size` int(11) DEFAULT NULL,
  `content` text,
  `hash` varchar(20) DEFAULT '',
  `type` enum('file','link') NOT NULL DEFAULT 'file',
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_documents
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_documents`;

CREATE TABLE `stk_documents` (
  `id` char(36) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL,
  `type` enum('folder','project','notepad','people') NOT NULL DEFAULT 'project',
  `owner` int(11) NOT NULL,
  `everyone` tinyint(1) NOT NULL DEFAULT '1',
  `folder` char(36) NOT NULL,
  `order` smallint(6) NOT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_documents` WRITE;
/*!40000 ALTER TABLE `stk_documents` DISABLE KEYS */;

INSERT INTO `stk_documents` (`id`, `title`, `type`, `owner`, `everyone`, `folder`, `order`, `created`, `updated`, `deleted`)
VALUES
	('c9da620c-d4e9-429a-9a6b-e3f6d8d1f9f7','Test Folder','folder',1,1,'',1,'2021-05-13 15:24:15','2021-05-13 15:24:15',NULL),
	('d9da620c-d4e9-429a-9a6b-e3f6d8d1f9f7','Some project','project',1,1,'c9da620c-d4e9-429a-9a6b-e3f6d8d1f9f7',1,'2021-05-13 15:28:43','2021-05-13 15:28:43',NULL),
	('w9da620c-d4e9-429a-9a6b-e3f6d8d1f9f7','Some project 2','project',1,1,'c9da620c-d4e9-429a-9a6b-e3f6d8d1f9f7',2,'2021-05-13 15:29:10','2021-05-13 15:29:10',NULL);

/*!40000 ALTER TABLE `stk_documents` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_documents_members
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_documents_members`;

CREATE TABLE `stk_documents_members` (
  `document` char(36) NOT NULL DEFAULT '',
  `user` char(36) NOT NULL DEFAULT '',
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`user`),
  UNIQUE KEY `record` (`document`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_documents_members` WRITE;
/*!40000 ALTER TABLE `stk_documents_members` DISABLE KEYS */;

INSERT INTO `stk_documents_members` (`document`, `user`, `created`)
VALUES
	('5114bbde-0a1c-4a6c-8d10-d00631188104','1',NULL),
	('5114bbde-0a1c-4a6c-8d10-d00631188104','2',NULL),
	('5114bbde-0a1c-4a6c-8d10-d00631188104','3',NULL);

/*!40000 ALTER TABLE `stk_documents_members` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_files
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_files`;

CREATE TABLE `stk_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_projects_options
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_projects_options`;

CREATE TABLE `stk_projects_options` (
  `project` char(36) NOT NULL DEFAULT '',
  `hourlyFee` float DEFAULT NULL,
  `feeCurrency` varchar(10) DEFAULT NULL,
  `archived_order` enum('title-asc','title-desc','created-asc','created-desc','updated-asc','updated-desc','archived-asc','archived-desc') NOT NULL DEFAULT 'title-asc',
  PRIMARY KEY (`project`),
  UNIQUE KEY `board` (`project`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_projects_tags
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_projects_tags`;

CREATE TABLE `stk_projects_tags` (
  `id` char(36) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `color` varchar(7) NOT NULL DEFAULT '',
  `project` char(36) NOT NULL DEFAULT '',
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_stacks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_stacks`;

CREATE TABLE `stk_stacks` (
  `id` char(36) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `project` char(36) NOT NULL DEFAULT '',
  `tag` text NOT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_stacks` WRITE;
/*!40000 ALTER TABLE `stk_stacks` DISABLE KEYS */;

INSERT INTO `stk_stacks` (`id`, `title`, `project`, `tag`, `created`, `updated`, `deleted`)
VALUES
	('6a20c65c-8241-40d4-afe8-7c1c5fa9595b','To Do','5114bbde-0a1c-4a6c-8d10-d00631188104','',NULL,NULL,NULL);

/*!40000 ALTER TABLE `stk_stacks` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_stacks_collapsed
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_stacks_collapsed`;

CREATE TABLE `stk_stacks_collapsed` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `stack` char(36) NOT NULL DEFAULT '',
  `collapsed` tinyint(1) NOT NULL,
  `user` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_stacks_collapsed` WRITE;
/*!40000 ALTER TABLE `stk_stacks_collapsed` DISABLE KEYS */;

INSERT INTO `stk_stacks_collapsed` (`id`, `stack`, `collapsed`, `user`, `created`)
VALUES
	(1,'6a20c65c-8241-40d4-afe8-7c1c5fa9595b',1,1,'0000-00-00 00:00:00');

/*!40000 ALTER TABLE `stk_stacks_collapsed` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_stacks_order
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_stacks_order`;

CREATE TABLE `stk_stacks_order` (
  `board` char(36) NOT NULL DEFAULT '',
  `stack` char(36) NOT NULL DEFAULT '',
  `order` smallint(6) NOT NULL DEFAULT '1',
  PRIMARY KEY (`stack`,`order`),
  UNIQUE KEY `order` (`order`,`stack`),
  KEY `id` (`stack`,`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_tasks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_tasks`;

CREATE TABLE `stk_tasks` (
  `id` char(36) NOT NULL DEFAULT '',
  `title` text NOT NULL,
  `content` text NOT NULL,
  `tags` text,
  `startdate` datetime DEFAULT NULL,
  `duedate` datetime DEFAULT NULL,
  `cover` tinyint(1) DEFAULT NULL,
  `done` tinyint(1) DEFAULT NULL,
  `altTags` tinyint(1) DEFAULT NULL,
  `estimate` varchar(100) DEFAULT NULL,
  `spent` varchar(100) DEFAULT NULL,
  `progress` tinyint(4) DEFAULT NULL,
  `hourlyFee` float DEFAULT NULL,
  `owner` int(11) DEFAULT NULL,
  `board` char(36) DEFAULT NULL,
  `archived` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_tasks` WRITE;
/*!40000 ALTER TABLE `stk_tasks` DISABLE KEYS */;

INSERT INTO `stk_tasks` (`id`, `title`, `content`, `tags`, `startdate`, `duedate`, `cover`, `done`, `altTags`, `estimate`, `spent`, `progress`, `hourlyFee`, `owner`, `board`, `archived`, `created`, `updated`, `deleted`)
VALUES
	('6a20c65c-8241-40d4-afe8-7c1c5fa9595b','Hello world','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);

/*!40000 ALTER TABLE `stk_tasks` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_tasks_assignees
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_tasks_assignees`;

CREATE TABLE `stk_tasks_assignees` (
  `task` char(36) NOT NULL DEFAULT '',
  `user` int(11) NOT NULL,
  PRIMARY KEY (`task`,`user`),
  UNIQUE KEY `task` (`task`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_tasks_extensions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_tasks_extensions`;

CREATE TABLE `stk_tasks_extensions` (
  `id` char(36) NOT NULL DEFAULT '',
  `task` char(36) NOT NULL DEFAULT '',
  `type` enum('attachments','location','checklist','description') DEFAULT NULL,
  `title` varchar(255) DEFAULT '',
  `content` text,
  `options` text,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_tasks_order
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_tasks_order`;

CREATE TABLE `stk_tasks_order` (
  `project` char(36) NOT NULL DEFAULT '',
  `stack` char(36) NOT NULL DEFAULT '',
  `task` char(36) NOT NULL DEFAULT '',
  `order` smallint(6) NOT NULL DEFAULT '1',
  PRIMARY KEY (`task`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_tasks_order` WRITE;
/*!40000 ALTER TABLE `stk_tasks_order` DISABLE KEYS */;

INSERT INTO `stk_tasks_order` (`project`, `stack`, `task`, `order`)
VALUES
	('5114bbde-0a1c-4a6c-8d10-d00631188104','6a20c65c-8241-40d4-afe8-7c1c5fa9595b','6a20c65c-8241-40d4-afe8-7c1c5fa9595b',1);

/*!40000 ALTER TABLE `stk_tasks_order` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_tasks_watchers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_tasks_watchers`;

CREATE TABLE `stk_tasks_watchers` (
  `task` char(36) NOT NULL DEFAULT '',
  `user` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`task`,`user`),
  UNIQUE KEY `task` (`task`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_users`;

CREATE TABLE `stk_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(320) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nickname` varchar(255) NOT NULL,
  `firstName` varchar(255) NOT NULL DEFAULT '',
  `lastName` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_users` WRITE;
/*!40000 ALTER TABLE `stk_users` DISABLE KEYS */;

INSERT INTO `stk_users` (`id`, `email`, `password`, `nickname`, `firstName`, `lastName`, `created`, `updated`)
VALUES
	(1,'admin@stacks.server','$2y$12$264650655ea6f3258cc5bukTLXMfwH3TxLERG5JtSHF0CkD7q9m2S','admin','Admin','Stacks','2020-04-24 09:10:18','2020-04-24 09:10:18'),
	(2,'l.skywalker@resistance.com','$2y$12$264650655ea6f3258cc5bukTLXMfwH3TxLERG5JtSHF0CkD7q9m2S','skywalker','Luke','Skywalker','2020-09-18 08:20:13','2020-09-18 08:20:13'),
	(3,'d.vader@theempire.com','$2y$12$264650655ea6f3258cc5bukTLXMfwH3TxLERG5JtSHF0CkD7q9m2S','vader','Darth','Vader','2020-09-22 08:13:51','2020-09-22 08:13:51');

/*!40000 ALTER TABLE `stk_users` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
