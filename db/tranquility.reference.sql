-- phpMyAdmin SQL Dump
-- version 4.2.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 26, 2014 at 10:16 AM
-- Server version: 5.6.19-log
-- PHP Version: 5.5.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `tranquility`
--

--
-- Truncate table before insert `tql_cd_locales`
--

TRUNCATE TABLE `tql_cd_locales`;
--
-- Dumping data for table `tql_cd_locales`
--

INSERT INTO `tql_cd_locales` (`locale`, `description`, `ordering`) VALUES
('en-AU', 'locale_en-AU', 1);

--
-- Truncate table before insert `tql_cd_timezones`
--

TRUNCATE TABLE `tql_cd_timezones`;
--
-- Dumping data for table `tql_cd_timezones`
--

INSERT INTO `tql_cd_timezones` (`timezone`, `description`, `daylightSavings`, `ordering`) VALUES
('Africa/Cairo', 'timezone_Africa/Cairo', 1, 37),
('Africa/Casablanca', 'timezone_Africa/Casablanca', 0, 26),
('Africa/Johannesburg', 'timezone_Africa/Johannesburg', 0, 33),
('Africa/Lagos', 'timezone_Africa/Lagos', 0, 28),
('Africa/Nairobi', 'timezone_Africa/Nairobi', 0, 39),
('America/Anchorage', 'timezone_America/Anchorage', 1, 4),
('America/Bogota', 'timezone_America/Bogota', 0, 14),
('America/Buenos_Aires', 'timezone_America/Buenos_Aires', 0, 20),
('America/Caracas', 'timezone_America/Caracas', 0, 16),
('America/Chicago', 'timezone_America/Chicago', 1, 12),
('America/Chihuahua', 'timezone_America/Chihuahua', 1, 8),
('America/Denver', 'timezone_America/Denver', 1, 7),
('America/Godthab', 'timezone_America/Godthab', 1, 21),
('America/Halifax', 'timezone_America/Halifax', 1, 18),
('America/Indianapolis', 'timezone_America/Indianapolis', 0, 13),
('America/Los_Angeles', 'timezone_America/Los_Angeles', 1, 5),
('America/Managua', 'timezone_America/Managua', 0, 9),
('America/Mexico_City', 'timezone_America/Mexico_City', 1, 11),
('America/New_York', 'timezone_America/New_York', 1, 15),
('America/Noronha', 'timezone_America/Noronha', 1, 23),
('America/Phoenix', 'timezone_America/Phoenix', 0, 6),
('America/Regina', 'timezone_America/Regina', 0, 10),
('America/Santiago', 'timezone_America/Santiago', 1, 17),
('America/Sao_Paulo', 'timezone_America/Sao_Paulo', 1, 22),
('America/St_Johns', 'timezone_America/St_Johns', 1, 19),
('Asia/Baghdad', 'timezone_Asia/Baghdad', 1, 42),
('Asia/Bangkok', 'timezone_Asia/Bangkok', 0, 55),
('Asia/Calcutta', 'timezone_Asia/Calcutta', 0, 49),
('Asia/Colombo', 'timezone_Asia/Colombo', 0, 51),
('Asia/Dhaka', 'timezone_Asia/Dhaka', 0, 52),
('Asia/Hong_Kong', 'timezone_Asia/Hong_Kong', 0, 60),
('Asia/Irkutsk', 'timezone_Asia/Irkutsk', 1, 61),
('Asia/Jerusalem', 'timezone_Asia/Jerusalem', 0, 34),
('Asia/Kabul', 'timezone_Asia/Kabul', 0, 46),
('Asia/Karachi', 'timezone_Asia/Karachi', 0, 47),
('Asia/Katmandu', 'timezone_Asia/Katmandu', 0, 50),
('Asia/Krasnoyarsk', 'timezone_Asia/Krasnoyarsk', 1, 56),
('Asia/Magadan', 'timezone_Asia/Magadan', 0, 72),
('Asia/Muscat', 'timezone_Asia/Muscat', 0, 44),
('Asia/Novosibirsk', 'timezone_Asia/Novosibirsk', 1, 53),
('Asia/Rangoon', 'timezone_Asia/Rangoon', 0, 54),
('Asia/Riyadh', 'timezone_Asia/Riyadh', 0, 40),
('Asia/Seoul', 'timezone_Asia/Seoul', 0, 63),
('Asia/Singapore', 'timezone_Asia/Singapore', 0, 59),
('Asia/Taipei', 'timezone_Asia/Taipei', 0, 58),
('Asia/Tbilisi', 'timezone_Asia/Tbilisi', 1, 45),
('Asia/Tehran', 'timezone_Asia/Tehran', 1, 43),
('Asia/Tokyo', 'timezone_Asia/Tokyo', 0, 62),
('Asia/Vladivostok', 'timezone_Asia/Vladivostok', 1, 69),
('Asia/Yakutsk', 'timezone_Asia/Yakutsk', 1, 64),
('Asia/Yekaterinburg', 'timezone_Asia/Yekaterinburg', 1, 48),
('Atlantic/Azores', 'timezone_Atlantic/Azores', 1, 25),
('Atlantic/Cape_Verde', 'timezone_Atlantic/Cape_Verde', 0, 24),
('Australia/Adelaide', 'timezone_Australia/Adelaide', 1, 66),
('Australia/Brisbane', 'timezone_Australia/Brisbane', 0, 68),
('Australia/Darwin', 'timezone_Australia/Darwin', 0, 65),
('Australia/Hobart', 'timezone_Australia/Hobart', 1, 70),
('Australia/Perth', 'timezone_Australia/Perth', 0, 57),
('Australia/Sydney', 'timezone_Australia/Sydney', 1, 71),
('Etc/GMT+12', 'timezone_Etc/GMT+12', 0, 1),
('Europe/Belgrade', 'timezone_Europe/Belgrade', 1, 32),
('Europe/Berlin', 'timezone_Europe/Berlin', 1, 29),
('Europe/Bucharest', 'timezone_Europe/Bucharest', 1, 38),
('Europe/Helsinki', 'timezone_Europe/Helsinki', 1, 36),
('Europe/Istanbul', 'timezone_Europe/Istanbul', 1, 35),
('Europe/London', 'timezone_Europe/London', 1, 27),
('Europe/Moscow', 'timezone_Europe/Moscow', 1, 41),
('Europe/Paris', 'timezone_Europe/Paris', 1, 30),
('Europe/Sarajevo', 'timezone_Europe/Sarajevo', 1, 31),
('Pacific/Apia', 'timezone_Pacific/Apia', 0, 2),
('Pacific/Auckland', 'timezone_Pacific/Auckland', 1, 74),
('Pacific/Fiji', 'timezone_Pacific/Fiji', 0, 73),
('Pacific/Guam', 'timezone_Pacific/Guam', 0, 67),
('Pacific/Honolulu', 'timezone_Pacific/Honolulu', 0, 3),
('Pacific/Tongatapu', 'timezone_Pacific/Tongatapu', 0, 75);

--
-- Truncate table before insert `tql_sys_acl_privileges`
--

TRUNCATE TABLE `tql_sys_acl_privileges`;
--
-- Dumping data for table `tql_sys_acl_privileges`
--

INSERT INTO `tql_sys_acl_privileges` (`id`, `roleId`, `resourceType`, `resourceId`, `action`, `access`) VALUES
(1, 1, 'controller', NULL, NULL, 'deny'),
(2, 1, 'controller', 'backoffice-auth', NULL, 'allow'),
(3, 2, 'controller', NULL, NULL, 'allow'),
(4, 1, 'controller', 'backoffice-error', NULL, 'allow');

--
-- Truncate table before insert `tql_sys_acl_roles`
--

TRUNCATE TABLE `tql_sys_acl_roles`;
--
-- Dumping data for table `tql_sys_acl_roles`
--

INSERT INTO `tql_sys_acl_roles` (`id`, `roleName`, `parentRoleId`) VALUES
(1, 'Guest', NULL),
(2, 'Administrator', 1),
(3, 'Super Administrator', 2);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
