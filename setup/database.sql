# ************************************************************
# Sequel Pro SQL dump
# Version 4004
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: 127.0.0.1 (MySQL 5.5.30)
# Database: 2012 09 16 xhprof
# Generation Time: 2013-04-14 14:23:23 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table calls
# ------------------------------------------------------------

DROP TABLE IF EXISTS `calls`;

CREATE TABLE `calls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` int(10) unsigned NOT NULL,
  `ct` int(10) unsigned DEFAULT NULL,
  `wt` int(10) unsigned DEFAULT NULL,
  `cpu` int(10) unsigned DEFAULT NULL,
  `mu` int(10) unsigned DEFAULT NULL,
  `pmu` int(10) unsigned DEFAULT NULL,
  `caller_id` int(10) unsigned DEFAULT NULL,
  `callee_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  CONSTRAINT `calls_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table players
# ------------------------------------------------------------

DROP TABLE IF EXISTS `players`;

CREATE TABLE `players` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table request_data
# ------------------------------------------------------------

DROP TABLE IF EXISTS `request_data`;

CREATE TABLE `request_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hash` varchar(40) NOT NULL,
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table request_hosts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `request_hosts`;

CREATE TABLE `request_hosts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `host` (`host`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table request_methods
# ------------------------------------------------------------

DROP TABLE IF EXISTS `request_methods`;

CREATE TABLE `request_methods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `method` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `method` (`method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table request_uris
# ------------------------------------------------------------

DROP TABLE IF EXISTS `request_uris`;

CREATE TABLE `request_uris` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uri` (`uri`),
  KEY `id` (`id`,`uri`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table requests
# ------------------------------------------------------------

DROP TABLE IF EXISTS `requests`;

CREATE TABLE `requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_host_id` int(10) unsigned NOT NULL,
  `request_uri_id` int(10) unsigned NOT NULL,
  `request_method_id` int(10) unsigned NOT NULL,
  `request_caller_id` int(10) unsigned NOT NULL,
  `https` tinyint(3) unsigned NOT NULL,
  `request_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `request_host_id` (`request_host_id`),
  KEY `request_method_id` (`request_method_id`),
  KEY `request_timestamp` (`request_timestamp`),
  KEY `request_uri_id` (`request_uri_id`,`request_caller_id`),
  KEY `temporary_request_data` (`request_host_id`,`request_uri_id`,`request_method_id`,`request_caller_id`),
  CONSTRAINT `requests_ibfk_3` FOREIGN KEY (`request_method_id`) REFERENCES `request_methods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `requests_ibfk_5` FOREIGN KEY (`request_uri_id`) REFERENCES `request_uris` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
