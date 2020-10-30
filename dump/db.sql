-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Oct 30, 2020 at 01:53 PM
-- Server version: 8.0.22
-- PHP Version: 7.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stacks`
--

-- --------------------------------------------------------

--
-- Table structure for table `stk_activities`
--

CREATE TABLE `stk_activities` (
  `id` int UNSIGNED NOT NULL,
  `user` int UNSIGNED NOT NULL,
  `instance` varchar(37) NOT NULL,
  `board` varchar(37) NOT NULL,
  `item` varchar(37) DEFAULT NULL,
  `action` enum('CREATE','UPDATE','DELETE','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
  `section` enum('BOARDS','BOARD','TASK','STACK','WATCHER','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stk_attachments`
--

CREATE TABLE `stk_attachments` (
  `id` int UNSIGNED NOT NULL,
  `owner` int NOT NULL,
  `task` varchar(37) NOT NULL DEFAULT '',
  `title` text,
  `extension` varchar(10) NOT NULL DEFAULT '',
  `size` int DEFAULT NULL,
  `content` text,
  `hash` varchar(20) DEFAULT '',
  `type` enum('file','link') NOT NULL DEFAULT 'file',
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stk_boards`
--

CREATE TABLE `stk_boards` (
  `id` varchar(37) NOT NULL DEFAULT '',
  `everyone` tinyint(1) NOT NULL DEFAULT '1',
  `owner` int NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  `hourlyFee` float DEFAULT NULL,
  `feeCurrency` varchar(10) DEFAULT NULL,
  `archived_order` enum('title-asc','title-desc','created-asc','created-desc','updated-asc','updated-desc','archived-asc','archived-desc') NOT NULL DEFAULT 'title-asc',
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stk_boards_members`
--

CREATE TABLE `stk_boards_members` (
  `board` varchar(37) NOT NULL DEFAULT '',
  `user` varchar(37) NOT NULL DEFAULT '',
  `created` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stk_boards_tags`
--

CREATE TABLE `stk_boards_tags` (
  `id` varchar(37) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `color` varchar(7) NOT NULL DEFAULT '',
  `board` varchar(37) NOT NULL DEFAULT '',
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stk_files`
--

CREATE TABLE `stk_files` (
  `id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stk_stacks`
--

CREATE TABLE `stk_stacks` (
  `id` varchar(37) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `board` varchar(37) NOT NULL,
  `tag` text NOT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stk_stacks_order`
--

CREATE TABLE `stk_stacks_order` (
  `board` varchar(37) NOT NULL,
  `stack` varchar(37) NOT NULL DEFAULT '',
  `order` smallint NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stk_tasks`
--

CREATE TABLE `stk_tasks` (
  `id` varchar(37) NOT NULL DEFAULT '',
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
  `progress` tinyint DEFAULT NULL,
  `hourlyFee` float DEFAULT NULL,
  `owner` int DEFAULT NULL,
  `board` varchar(37) DEFAULT NULL,
  `archived` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stk_tasks_assignees`
--

CREATE TABLE `stk_tasks_assignees` (
  `task` varchar(37) NOT NULL DEFAULT '',
  `user` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stk_tasks_extensions`
--

CREATE TABLE `stk_tasks_extensions` (
  `id` varchar(37) NOT NULL DEFAULT '',
  `task` varchar(37) NOT NULL,
  `type` enum('attachments','location','checklist','description') DEFAULT NULL,
  `title` varchar(255) DEFAULT '',
  `content` text,
  `options` text,
  `created` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stk_tasks_order`
--

CREATE TABLE `stk_tasks_order` (
  `board` varchar(37) NOT NULL,
  `stack` varchar(37) NOT NULL,
  `task` varchar(37) NOT NULL DEFAULT '',
  `order` smallint NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stk_tasks_watchers`
--

CREATE TABLE `stk_tasks_watchers` (
  `task` varchar(37) NOT NULL DEFAULT '',
  `user` int NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stk_users`
--

CREATE TABLE `stk_users` (
  `id` int NOT NULL,
  `email` varchar(320) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nickname` varchar(255) NOT NULL,
  `firstName` varchar(255) NOT NULL DEFAULT '',
  `lastName` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `stk_users`
--

INSERT INTO `stk_users` (`id`, `email`, `password`, `nickname`, `firstName`, `lastName`, `created`, `updated`) VALUES
(1, 'admin@stacks.server', '$2y$12$264650655ea6f3258cc5bukTLXMfwH3TxLERG5JtSHF0CkD7q9m2S', 'admin', 'Admin', 'Stacks', '2020-04-24 09:10:18', '2020-04-24 09:10:18'),
(2, 'l.skywalker@resistance.com', '$2y$12$264650655ea6f3258cc5bukTLXMfwH3TxLERG5JtSHF0CkD7q9m2S', 'skywalker', 'Luke', 'Skywalker', '2020-09-18 08:20:13', '2020-09-18 08:20:13'),
(3, 'd.vader@theempire.com', '$2y$12$264650655ea6f3258cc5bukTLXMfwH3TxLERG5JtSHF0CkD7q9m2S', 'vader', 'Darth', 'Vader', '2020-09-22 08:13:51', '2020-09-22 08:13:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `stk_activities`
--
ALTER TABLE `stk_activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stk_attachments`
--
ALTER TABLE `stk_attachments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stk_boards`
--
ALTER TABLE `stk_boards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stk_boards_members`
--
ALTER TABLE `stk_boards_members`
  ADD PRIMARY KEY (`board`,`user`),
  ADD UNIQUE KEY `board` (`board`,`user`);

--
-- Indexes for table `stk_boards_tags`
--
ALTER TABLE `stk_boards_tags`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stk_files`
--
ALTER TABLE `stk_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stk_stacks`
--
ALTER TABLE `stk_stacks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stk_stacks_order`
--
ALTER TABLE `stk_stacks_order`
  ADD PRIMARY KEY (`stack`,`order`),
  ADD UNIQUE KEY `order` (`order`,`stack`),
  ADD KEY `id` (`stack`,`order`);

--
-- Indexes for table `stk_tasks`
--
ALTER TABLE `stk_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stk_tasks_assignees`
--
ALTER TABLE `stk_tasks_assignees`
  ADD PRIMARY KEY (`task`,`user`),
  ADD UNIQUE KEY `task` (`task`,`user`);

--
-- Indexes for table `stk_tasks_extensions`
--
ALTER TABLE `stk_tasks_extensions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stk_tasks_order`
--
ALTER TABLE `stk_tasks_order`
  ADD PRIMARY KEY (`task`);

--
-- Indexes for table `stk_tasks_watchers`
--
ALTER TABLE `stk_tasks_watchers`
  ADD PRIMARY KEY (`task`,`user`),
  ADD UNIQUE KEY `task` (`task`,`user`);

--
-- Indexes for table `stk_users`
--
ALTER TABLE `stk_users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `stk_activities`
--
ALTER TABLE `stk_activities`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stk_attachments`
--
ALTER TABLE `stk_attachments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stk_files`
--
ALTER TABLE `stk_files`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stk_users`
--
ALTER TABLE `stk_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
