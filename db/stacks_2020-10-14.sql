# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.26)
# Database: stacks
# Generation Time: 2020-10-14 13:57:32 +0000
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
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) unsigned NOT NULL,
  `instance` varchar(37) NOT NULL,
  `board` varchar(37) NOT NULL,
  `item` varchar(37) DEFAULT NULL,
  `action` enum('CREATE','UPDATE','DELETE','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
  `section` enum('BOARDS','BOARD','TASK','STACK','WATCHER','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_activities` WRITE;
/*!40000 ALTER TABLE `stk_activities` DISABLE KEYS */;

INSERT INTO `stk_activities` (`id`, `user`, `instance`, `board`, `item`, `action`, `section`, `created`)
VALUES
	(1,1,'a301bc43-52fe-4cbd-a1fe-eadb73744980','bb1a87e5-3c98-4af7-aed1-f50087adc583','bb1a87e5-3c98-4af7-aed1-f50087adc583','CREATE','BOARDS','2020-10-14 15:57:02'),
	(2,1,'a301bc43-52fe-4cbd-a1fe-eadb73744980','bb1a87e5-3c98-4af7-aed1-f50087adc583','d7b43954-0b08-45ca-9e36-50d30c6447ce','CREATE','TASK','2020-10-14 15:57:13');

/*!40000 ALTER TABLE `stk_activities` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_boards
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_boards`;

CREATE TABLE `stk_boards` (
  `id` varchar(37) NOT NULL DEFAULT '',
  `everyone` tinyint(1) NOT NULL DEFAULT '1',
  `owner` int(11) NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  `hourlyFee` float DEFAULT NULL,
  `feeCurrency` varchar(10) DEFAULT NULL,
  `archived_order` enum('title-asc','title-desc','created-asc','created-desc','updated-asc','updated-desc','archived-asc','archived-desc') NOT NULL DEFAULT 'title-asc',
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_boards` WRITE;
/*!40000 ALTER TABLE `stk_boards` DISABLE KEYS */;

INSERT INTO `stk_boards` (`id`, `everyone`, `owner`, `title`, `hourlyFee`, `feeCurrency`, `archived_order`, `created`, `updated`, `deleted`)
VALUES
	('bb1a87e5-3c98-4af7-aed1-f50087adc583',1,1,'The Board',0,'USD','title-asc','2020-10-14 15:57:02','2020-10-14 15:57:02',NULL);

/*!40000 ALTER TABLE `stk_boards` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_boards_members
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_boards_members`;

CREATE TABLE `stk_boards_members` (
  `board` varchar(37) NOT NULL DEFAULT '',
  `user` varchar(37) NOT NULL DEFAULT '',
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`board`,`user`),
  UNIQUE KEY `board` (`board`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_boards_tags
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_boards_tags`;

CREATE TABLE `stk_boards_tags` (
  `id` varchar(37) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `color` varchar(7) NOT NULL DEFAULT '',
  `board` varchar(37) NOT NULL DEFAULT '',
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_stacks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_stacks`;

CREATE TABLE `stk_stacks` (
  `id` varchar(37) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `board` varchar(37) NOT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_stacks` WRITE;
/*!40000 ALTER TABLE `stk_stacks` DISABLE KEYS */;

INSERT INTO `stk_stacks` (`id`, `title`, `board`, `created`, `updated`, `deleted`)
VALUES
	('ede27188-b400-448e-914a-2d9dca576ab8','To Do','bb1a87e5-3c98-4af7-aed1-f50087adc583','2020-10-14 15:57:07','2020-10-14 15:57:07',NULL);

/*!40000 ALTER TABLE `stk_stacks` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_stacks_order
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_stacks_order`;

CREATE TABLE `stk_stacks_order` (
  `board` varchar(37) NOT NULL,
  `stack` varchar(37) NOT NULL DEFAULT '',
  `order` smallint(5) NOT NULL DEFAULT '1',
  PRIMARY KEY (`stack`,`order`),
  UNIQUE KEY `order` (`order`,`stack`),
  KEY `id` (`stack`,`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_stacks_order` WRITE;
/*!40000 ALTER TABLE `stk_stacks_order` DISABLE KEYS */;

INSERT INTO `stk_stacks_order` (`board`, `stack`, `order`)
VALUES
	('bb1a87e5-3c98-4af7-aed1-f50087adc583','ede27188-b400-448e-914a-2d9dca576ab8',1);

/*!40000 ALTER TABLE `stk_stacks_order` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_tasks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_tasks`;

CREATE TABLE `stk_tasks` (
  `id` varchar(37) NOT NULL DEFAULT '',
  `title` text NOT NULL,
  `content` text NOT NULL,
  `tags` text,
  `info` text,
  `startdate` datetime DEFAULT NULL,
  `duedate` datetime DEFAULT NULL,
  `cover` tinyint(1) DEFAULT NULL,
  `done` tinyint(1) DEFAULT NULL,
  `altTags` tinyint(1) DEFAULT NULL,
  `estimate` varchar(100) DEFAULT NULL,
  `spent` varchar(100) DEFAULT NULL,
  `progress` tinyint(3) DEFAULT NULL,
  `hourlyFee` float DEFAULT NULL,
  `owner` int(11) DEFAULT NULL,
  `board` varchar(37) DEFAULT NULL,
  `archived` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_tasks` WRITE;
/*!40000 ALTER TABLE `stk_tasks` DISABLE KEYS */;

INSERT INTO `stk_tasks` (`id`, `title`, `content`, `tags`, `info`, `startdate`, `duedate`, `cover`, `done`, `altTags`, `estimate`, `spent`, `progress`, `hourlyFee`, `owner`, `board`, `archived`, `created`, `updated`, `deleted`)
VALUES
	('d7b43954-0b08-45ca-9e36-50d30c6447ce','New task','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'bb1a87e5-3c98-4af7-aed1-f50087adc583',NULL,'2020-10-14 15:57:13',NULL,NULL);

/*!40000 ALTER TABLE `stk_tasks` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_tasks_assignees
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_tasks_assignees`;

CREATE TABLE `stk_tasks_assignees` (
  `task` varchar(37) NOT NULL DEFAULT '',
  `user` int(11) NOT NULL,
  PRIMARY KEY (`task`,`user`),
  UNIQUE KEY `task` (`task`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_tasks_order
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_tasks_order`;

CREATE TABLE `stk_tasks_order` (
  `board` varchar(37) NOT NULL,
  `stack` varchar(37) NOT NULL,
  `task` varchar(37) NOT NULL DEFAULT '',
  `order` smallint(5) NOT NULL DEFAULT '1',
  PRIMARY KEY (`task`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_tasks_order` WRITE;
/*!40000 ALTER TABLE `stk_tasks_order` DISABLE KEYS */;

INSERT INTO `stk_tasks_order` (`board`, `stack`, `task`, `order`)
VALUES
	('bb1a87e5-3c98-4af7-aed1-f50087adc583','ede27188-b400-448e-914a-2d9dca576ab8','d7b43954-0b08-45ca-9e36-50d30c6447ce',1);

/*!40000 ALTER TABLE `stk_tasks_order` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_tasks_watchers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_tasks_watchers`;

CREATE TABLE `stk_tasks_watchers` (
  `task` varchar(37) NOT NULL DEFAULT '',
  `user` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`task`,`user`),
  UNIQUE KEY `task` (`task`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_tasks_widgets
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_tasks_widgets`;

CREATE TABLE `stk_tasks_widgets` (
  `id` varchar(37) NOT NULL DEFAULT '',
  `type` enum('attachments','location','checklist') DEFAULT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` text,
  PRIMARY KEY (`id`)
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
