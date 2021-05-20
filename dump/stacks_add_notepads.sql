-- Adminer 4.8.0 MySQL 8.0.25 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `stk_activities`;
CREATE TABLE `stk_activities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user` int unsigned NOT NULL,
  `instance` char(36) NOT NULL DEFAULT '',
  `parent` char(36) NOT NULL DEFAULT '',
  `item` char(36) DEFAULT NULL,
  `action` enum('CREATE','UPDATE','DELETE','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
  `section` varchar(10) NOT NULL DEFAULT 'UNKNOWN',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `stk_attachments`;
CREATE TABLE `stk_attachments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `owner` int NOT NULL,
  `task` char(36) NOT NULL DEFAULT '',
  `title` text,
  `extension` varchar(10) NOT NULL DEFAULT '',
  `size` int DEFAULT NULL,
  `content` text,
  `hash` varchar(20) DEFAULT '',
  `type` enum('file','link') NOT NULL DEFAULT 'file',
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `stk_documents`;
CREATE TABLE `stk_documents` (
  `id` char(36) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL,
  `type` enum('folder','project','notepad','people') NOT NULL DEFAULT 'project',
  `owner` int NOT NULL,
  `everyone` tinyint(1) NOT NULL DEFAULT '1',
  `folder` char(36) NOT NULL,
  `position` smallint NOT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `stk_documents_members`;
CREATE TABLE `stk_documents_members` (
  `document` char(36) NOT NULL DEFAULT '',
  `user` char(36) NOT NULL DEFAULT '',
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`user`),
  UNIQUE KEY `record` (`document`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `stk_files`;
CREATE TABLE `stk_files` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `stk_notepads`;
CREATE TABLE `stk_notepads` (
  `document` char(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `stk_projects_options`;
CREATE TABLE `stk_projects_options` (
  `project` char(36) NOT NULL DEFAULT '',
  `hourlyFee` float DEFAULT NULL,
  `feeCurrency` varchar(10) DEFAULT NULL,
  `archived_order` enum('title-asc','title-desc','created-asc','created-desc','updated-asc','updated-desc','archived-asc','archived-desc') NOT NULL DEFAULT 'title-asc',
  PRIMARY KEY (`project`),
  UNIQUE KEY `board` (`project`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `stk_projects_tags`;
CREATE TABLE `stk_projects_tags` (
  `id` char(36) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `color` varchar(7) NOT NULL DEFAULT '',
  `project` char(36) NOT NULL DEFAULT '',
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `stk_stacks`;
CREATE TABLE `stk_stacks` (
  `id` char(36) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `project` char(36) NOT NULL DEFAULT '',
  `tag` text NOT NULL,
  `position` smallint NOT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `stk_stacks_collapsed`;
CREATE TABLE `stk_stacks_collapsed` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `stack` char(36) NOT NULL DEFAULT '',
  `collapsed` tinyint(1) NOT NULL,
  `user` int NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `stk_tasks`;
CREATE TABLE `stk_tasks` (
  `id` char(36) NOT NULL DEFAULT '',
  `title` text NOT NULL,
  `description` text NOT NULL,
  `tags` text,
  `startdate` datetime DEFAULT NULL,
  `duedate` datetime DEFAULT NULL,
  `cover` tinyint(1) DEFAULT NULL,
  `done` tinyint(1) DEFAULT NULL,
  `altTags` tinyint(1) DEFAULT NULL,
  `estimate` varchar(100) DEFAULT NULL,
  `spent` varchar(100) DEFAULT NULL,
  `progress` tinyint DEFAULT NULL,
  `hourlyFee` float DEFAULT NULL,
  `archived` datetime DEFAULT NULL,
  `project` char(36) NOT NULL DEFAULT '',
  `stack` char(36) NOT NULL,
  `position` smallint NOT NULL,
  `owner` int NOT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `stk_tasks_assignees`;
CREATE TABLE `stk_tasks_assignees` (
  `task` char(36) NOT NULL DEFAULT '',
  `user` int NOT NULL,
  PRIMARY KEY (`task`,`user`),
  UNIQUE KEY `task` (`task`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `stk_tasks_watchers`;
CREATE TABLE `stk_tasks_watchers` (
  `task` char(36) NOT NULL DEFAULT '',
  `user` int NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`task`,`user`),
  UNIQUE KEY `task` (`task`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `stk_users`;
CREATE TABLE `stk_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(320) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nickname` varchar(255) NOT NULL,
  `firstName` varchar(255) NOT NULL DEFAULT '',
  `lastName` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


-- 2021-05-20 15:04:33
