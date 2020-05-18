# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.26)
# Database: stacks
# Generation Time: 2020-04-28 19:36:43 +0000
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
  `archived_order` enum('title-asc','title-desc','created-asc','created-desc','updated-asc','updated-desc','archived-asc','archived-desc') NOT NULL DEFAULT 'title-asc',
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_boards` WRITE;
/*!40000 ALTER TABLE `stk_boards` DISABLE KEYS */;

INSERT INTO `stk_boards` (`id`, `owner`, `title`, `archived_order`, `created`, `updated`, `deleted`)
VALUES
	('35436ec7-d32b-4f50-bba5-b5f276f289f9',1,'Welcome board','title-asc','2020-04-25 13:21:17','2020-04-28 12:44:39',NULL);

/*!40000 ALTER TABLE `stk_boards` ENABLE KEYS */;
UNLOCK TABLES;

DELIMITER ;;
/*!50003 SET SESSION SQL_MODE="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION" */;;
/*!50003 CREATE */ /*!50017 DEFINER=`root`@`localhost` */ /*!50003 TRIGGER `UUID` BEFORE INSERT ON `stk_boards` FOR EACH ROW SET new.id = UUID() */;;
DELIMITER ;
/*!50003 SET SESSION SQL_MODE=@OLD_SQL_MODE */;


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
	('7','Complete','#00E6B2','35436ec7-d32b-4f50-bba5-b5f276f289f9','2020-04-25 13:21:47',NULL);

/*!40000 ALTER TABLE `stk_boards_tags` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table stk_stacks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_stacks`;

CREATE TABLE `stk_stacks` (
  `id` varchar(37) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_tasks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_tasks`;

CREATE TABLE `stk_tasks` (
  `id` varchar(37) NOT NULL DEFAULT '',
  `title` text NOT NULL,
  `content` text NOT NULL,
  `tags` text,
  `info` text,
  `duedate` datetime DEFAULT NULL,
  `cover` tinyint(1) DEFAULT NULL,
  `done` tinyint(1) DEFAULT NULL,
  `altTags` tinyint(1) DEFAULT NULL,
  `estimate` varchar(100) DEFAULT NULL,
  `spent` varchar(100) DEFAULT NULL,
  `progress` tinyint(3) DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `archived` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table stk_users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `stk_users`;

CREATE TABLE `stk_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(320) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL DEFAULT '',
  `last_name` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `stk_users` WRITE;
/*!40000 ALTER TABLE `stk_users` DISABLE KEYS */;

INSERT INTO `stk_users` (`id`, `email`, `password`, `first_name`, `last_name`, `created`, `updated`)
VALUES
	(1,'admin@stacks.rocks','$2y$12$264650655ea6f3258cc5bukTLXMfwH3TxLERG5JtSHF0CkD7q9m2S','Admin','Stacks','2020-04-24 09:10:18','2020-04-24 09:10:18');

/*!40000 ALTER TABLE `stk_users` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
