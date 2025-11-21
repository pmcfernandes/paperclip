-- Initial schema migration extracted from file.sql
SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- forms
DROP TABLE IF EXISTS `forms`;
CREATE TABLE `forms` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `slug` varchar(50) DEFAULT NULL,
  `name` varchar(256) NOT NULL,
  `emailOnSubmit` tinyint(1) DEFAULT 1,
  `sendOnSubmit` varchar(256) DEFAULT NULL,
  `urlOnOk` varchar(1024) DEFAULT NULL,
  `urlOnError` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_UNIQUE` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- form_data
DROP TABLE IF EXISTS `form_data`;
CREATE TABLE `form_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `name` varchar(256) NOT NULL,
  `value` TEXT DEFAULT NULL,
  `when` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- sites
DROP TABLE IF EXISTS `sites`;
CREATE TABLE `sites` (
  `id` int(11) NOT NULL,
  `slug` varchar(50) DEFAULT NULL,
  `name` varchar(256) NOT NULL,
  `domain` varchar(256) NOT NULL,
  `key` varchar(50) NOT NULL,
  `webhook_url` varchar(1024) DEFAULT NULL,
  `webhook_token` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_UNIQUE` (`key`),
  UNIQUE KEY `slug_UNIQUE` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

SET foreign_key_checks = 1;
