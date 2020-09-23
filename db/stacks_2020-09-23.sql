# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.26)
# Database: stacks
# Generation Time: 2020-09-23 12:38:42 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table stk_boards
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_boards`;

CREATE TABLE `stk_boards` (
  `id` varchar(37) NOT NULL DEFAULT '',
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

INSERT INTO `stk_boards` (`id`, `owner`, `title`, `hourlyFee`, `feeCurrency`, `archived_order`, `created`, `updated`, `deleted`)
VALUES
	('35436ec7-d32b-4f50-bba5-b5f276f289f9',1,'Server board',15,'EUR','title-asc','2020-04-25 13:21:17','2020-09-23 07:03:45',NULL),
	('7dbabff3-b4d0-46da-8862-d95db6c803f6',1,'Another server board',NULL,NULL,'title-asc','2020-05-19 01:49:45','2020-05-19 01:49:45',NULL);

/*!40000 ALTER TABLE `stk_boards` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_boards_members
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_boards_members`;

CREATE TABLE `stk_boards_members` (
  `board` varchar(37) NOT NULL DEFAULT '',
  `user` varchar(37) NOT NULL DEFAULT '',
  PRIMARY KEY (`board`,`user`),
  UNIQUE KEY `board` (`board`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_boards_members` WRITE;
/*!40000 ALTER TABLE `stk_boards_members` DISABLE KEYS */;

INSERT INTO `stk_boards_members` (`board`, `user`)
VALUES
	('35436ec7-d32b-4f50-bba5-b5f276f289f9','1');

/*!40000 ALTER TABLE `stk_boards_members` ENABLE KEYS */;
UNLOCK TABLES;


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

LOCK TABLES `stk_boards_tags` WRITE;
/*!40000 ALTER TABLE `stk_boards_tags` DISABLE KEYS */;

INSERT INTO `stk_boards_tags` (`id`, `title`, `color`, `board`, `created`, `updated`)
VALUES
	('1','Bugs','#FF6C35','35436ec7-d32b-4f50-bba5-b5f276f289f9','2020-04-25 13:21:47',NULL),
	('2','Doing','#92D632','35436ec7-d32b-4f50-bba5-b5f276f289f9','2020-04-25 13:21:47',NULL),
	('3','High Priority','#FFDC00','35436ec7-d32b-4f50-bba5-b5f276f289f9','2020-04-25 13:21:47',NULL),
	('4','Missing Info','#FF9C00','35436ec7-d32b-4f50-bba5-b5f276f289f9','2020-04-25 13:21:47',NULL),
	('5','Idle','#B7C3C8','35436ec7-d32b-4f50-bba5-b5f276f289f9','2020-04-25 13:21:47',NULL),
	('6','On Hold','#32BAF5','35436ec7-d32b-4f50-bba5-b5f276f289f9','2020-04-25 13:21:47',NULL),
	('7','Complete','#00E6B2','35436ec7-d32b-4f50-bba5-b5f276f289f9','2020-04-25 13:21:47',NULL),
	('8decf5f4-91c7-11ea-b366-0d4c57d00ccc','Update New Tag','#ffffff','35436ec7-d32b-4f50-bba5-b5f276f289f9','2020-05-09 02:34:45','2020-05-09 02:35:11');

/*!40000 ALTER TABLE `stk_boards_tags` ENABLE KEYS */;
UNLOCK TABLES;


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
	('471a06f9-28f0-4050-acac-76f52662efcc','Test 2','35436ec7-d32b-4f50-bba5-b5f276f289f9','2020-09-23 06:42:10','2020-09-23 06:42:10',NULL),
	('60166fa5-918c-485c-9bea-4ce4ffefea47','test','35436ec7-d32b-4f50-bba5-b5f276f289f9','2020-05-16 02:47:55','2020-05-18 10:38:24',NULL),
	('61d68535-4302-4484-b518-2ca00df3b7d8','Untitled stack','35436ec7-d32b-4f50-bba5-b5f276f289f9','2020-09-22 08:22:24','2020-09-22 08:22:24',NULL),
	('9b8004ea-89e8-11ea-be55-2474dae7f827','Updated Stack 2','35436ec7-d32b-4f50-bba5-b5f276f289f9','2020-05-18 21:00:00','2020-05-18 22:00:00',NULL);

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
	('35436ec7-d32b-4f50-bba5-b5f276f289f9','471a06f9-28f0-4050-acac-76f52662efcc',4),
	('35436ec7-d32b-4f50-bba5-b5f276f289f9','60166fa5-918c-485c-9bea-4ce4ffefea47',1),
	('35436ec7-d32b-4f50-bba5-b5f276f289f9','61d68535-4302-4484-b518-2ca00df3b7d8',3),
	('35436ec7-d32b-4f50-bba5-b5f276f289f9','9b8004ea-89e8-11ea-be55-2474dae7f827',2);

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
  `user` int(11) DEFAULT NULL,
  `hourlyFee` float DEFAULT NULL,
  `assignee` int(11) DEFAULT NULL,
  `archived` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_tasks` WRITE;
/*!40000 ALTER TABLE `stk_tasks` DISABLE KEYS */;

INSERT INTO `stk_tasks` (`id`, `title`, `content`, `tags`, `info`, `startdate`, `duedate`, `cover`, `done`, `altTags`, `estimate`, `spent`, `progress`, `user`, `hourlyFee`, `assignee`, `archived`, `created`, `updated`, `deleted`)
VALUES
	('ce8d16f6-9222-11ea-8c1c-870eb04b6723','New task 3','hello world','[\"1\",\"2\",\"3\",\"4\",\"5\"]',NULL,'2020-05-06 20:00:00','2020-06-06 20:00:00',0,0,0,'2d',NULL,0,NULL,NULL,NULL,NULL,'2020-05-09 13:27:58','2020-09-22 13:14:43',NULL),
	('eab828fb-32d2-440e-825c-d5e9a69e79a6','test','',NULL,NULL,NULL,NULL,0,0,0,'1d',NULL,0,NULL,NULL,NULL,NULL,'2020-09-22 13:23:54','2020-09-23 11:37:45',NULL),
	('f15ce382-8a11-11ea-b94d-a87f862112f6','Hello world','The content here',NULL,'[{\"id\":\"9c1b5d69-a4a5-466d-a8d5-0770a6d4f391\",\"items\":[],\"type\":\"attachments\"}]','2020-09-30 11:53:36','2020-10-25 12:53:36',0,0,0,'3d',NULL,0,NULL,2,NULL,NULL,NULL,'2020-09-23 12:03:44',NULL),
	('fcdb8844-9222-11ea-8c1c-870eb04b6723','Updated title','',NULL,NULL,NULL,'2020-05-21 07:04:15',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2020-05-21 23:12:14','2020-05-09 13:29:15','2020-05-21 02:11:49',NULL);

/*!40000 ALTER TABLE `stk_tasks` ENABLE KEYS */;
UNLOCK TABLES;


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
	('35436ec7-d32b-4f50-bba5-b5f276f289f9','61d68535-4302-4484-b518-2ca00df3b7d8','ce8d16f6-9222-11ea-8c1c-870eb04b6723',1),
	('35436ec7-d32b-4f50-bba5-b5f276f289f9','471a06f9-28f0-4050-acac-76f52662efcc','eab828fb-32d2-440e-825c-d5e9a69e79a6',1),
	('35436ec7-d32b-4f50-bba5-b5f276f289f9','60166fa5-918c-485c-9bea-4ce4ffefea47','f15ce382-8a11-11ea-b94d-a87f862112f6',1);

/*!40000 ALTER TABLE `stk_tasks_order` ENABLE KEYS */;
UNLOCK TABLES;


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
  `first_name` varchar(255) NOT NULL DEFAULT '',
  `last_name` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_users` WRITE;
/*!40000 ALTER TABLE `stk_users` DISABLE KEYS */;

INSERT INTO `stk_users` (`id`, `email`, `password`, `nickname`, `first_name`, `last_name`, `created`, `updated`)
VALUES
	(1,'admin@stacks.rocks','$2y$12$264650655ea6f3258cc5bukTLXMfwH3TxLERG5JtSHF0CkD7q9m2S','admin','Admin','Stacks','2020-04-24 09:10:18','2020-04-24 09:10:18'),
	(2,'d.vader@theempire.com','','','',NULL,'2020-09-18 08:20:13','2020-09-18 08:20:13'),
	(3,'d.vader@theempire.com','','','',NULL,'2020-09-22 08:13:51','2020-09-22 08:13:51');

/*!40000 ALTER TABLE `stk_users` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
