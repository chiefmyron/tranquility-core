-- phpMyAdmin SQL Dump
-- version 4.2.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 26, 2014 at 10:13 AM
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

-- --------------------------------------------------------

--
-- Table structure for table `tql_cd_locales`
--

CREATE TABLE IF NOT EXISTS `tql_cd_locales` (
  `locale` varchar(30) NOT NULL,
  `description` varchar(100) NOT NULL,
  `ordering` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_cd_timezones`
--

CREATE TABLE IF NOT EXISTS `tql_cd_timezones` (
  `timezone` varchar(30) NOT NULL,
  `description` varchar(100) NOT NULL,
  `daylightSavings` tinyint(1) NOT NULL,
  `ordering` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_entity`
--

CREATE TABLE IF NOT EXISTS `tql_entity` (
`id` bigint(20) NOT NULL,
  `type` varchar(25) NOT NULL,
  `subType` varchar(25) DEFAULT NULL,
  `version` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'If 1, then the record has been logically deleted',
  `locked` tinyint(1) NOT NULL COMMENT 'If 1 the record has been locked for editing by a user',
  `lockedBy` bigint(20) NOT NULL,
  `lockedDatetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tql_entity_addresses_electronic`
--

CREATE TABLE IF NOT EXISTS `tql_entity_addresses_electronic` (
  `id` int(11) NOT NULL,
  `addressType` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `addressText` varchar(255) NOT NULL,
  `primaryContact` tinyint(1) NOT NULL DEFAULT '0',
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_entity_addresses_phone`
--

CREATE TABLE IF NOT EXISTS `tql_entity_addresses_phone` (
  `id` int(11) NOT NULL,
  `addressType` varchar(50) NOT NULL,
  `addressText` varchar(255) NOT NULL,
  `primaryContact` tinyint(4) NOT NULL,
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_entity_addresses_physical`
--

CREATE TABLE IF NOT EXISTS `tql_entity_addresses_physical` (
  `id` int(11) NOT NULL,
  `addressType` varchar(50) NOT NULL,
  `addressLine1` varchar(255) NOT NULL,
  `addressLine2` varchar(255) DEFAULT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `postcode` varchar(50) NOT NULL,
  `country` varchar(255) NOT NULL,
  `latitude` float DEFAULT '0',
  `longitude` float DEFAULT '0',
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_entity_content`
--

CREATE TABLE IF NOT EXISTS `tql_entity_content` (
  `id` int(11) NOT NULL,
  `contentType` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `publishState` varchar(20) NOT NULL,
  `publishStartDatetime` datetime NOT NULL,
  `publishFinishDatetime` datetime NOT NULL,
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_entity_content_page`
--

CREATE TABLE IF NOT EXISTS `tql_entity_content_page` (
  `id` int(11) NOT NULL,
  `excerpt` mediumtext NOT NULL,
  `mainText` longtext NOT NULL,
  `authorId` int(11) NOT NULL,
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_entity_people`
--

CREATE TABLE IF NOT EXISTS `tql_entity_people` (
  `id` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_entity_users`
--

CREATE TABLE IF NOT EXISTS `tql_entity_users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `timezone` varchar(255) NOT NULL,
  `locale` varchar(6) NOT NULL,
  `active` tinyint(4) NOT NULL,
  `aclGroup` int(11) NOT NULL,
  `registeredDate` datetime NOT NULL,
  `lastVisitDate` datetime NOT NULL,
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_entity_xref`
--

CREATE TABLE IF NOT EXISTS `tql_entity_xref` (
  `parentId` int(11) NOT NULL,
  `childId` int(11) NOT NULL,
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_history_addresses_electronic`
--

CREATE TABLE IF NOT EXISTS `tql_history_addresses_electronic` (
  `id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `addressType` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `addressText` varchar(255) NOT NULL,
  `primaryContact` tinyint(1) NOT NULL DEFAULT '0',
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_history_addresses_phone`
--

CREATE TABLE IF NOT EXISTS `tql_history_addresses_phone` (
  `id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `addressType` varchar(50) NOT NULL,
  `addressText` varchar(255) NOT NULL,
  `primaryContact` tinyint(4) NOT NULL,
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_history_addresses_physical`
--

CREATE TABLE IF NOT EXISTS `tql_history_addresses_physical` (
  `id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `addressType` varchar(50) NOT NULL,
  `addressLine1` varchar(255) NOT NULL,
  `addressLine2` varchar(255) DEFAULT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `postcode` varchar(50) NOT NULL,
  `country` varchar(255) NOT NULL,
  `latitude` float DEFAULT '0',
  `longitude` float DEFAULT '0',
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_history_content`
--

CREATE TABLE IF NOT EXISTS `tql_history_content` (
  `id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `contentType` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `publishState` varchar(20) NOT NULL,
  `publishStartDatetime` datetime NOT NULL,
  `publishFinishDatetime` datetime NOT NULL,
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_history_content_page`
--

CREATE TABLE IF NOT EXISTS `tql_history_content_page` (
  `id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `excerpt` mediumtext NOT NULL,
  `mainText` longtext NOT NULL,
  `authorId` int(11) NOT NULL,
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_history_people`
--

CREATE TABLE IF NOT EXISTS `tql_history_people` (
  `id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `transactionId` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_sys_acl_privileges`
--

CREATE TABLE IF NOT EXISTS `tql_sys_acl_privileges` (
`id` int(11) NOT NULL,
  `roleId` int(11) NOT NULL,
  `resourceType` varchar(255) NOT NULL,
  `resourceId` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `access` varchar(255) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `tql_sys_acl_roles`
--

CREATE TABLE IF NOT EXISTS `tql_sys_acl_roles` (
`id` int(11) NOT NULL,
  `roleName` varchar(50) NOT NULL,
  `parentRoleId` int(11) DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `tql_sys_acl_roles_users_xref`
--

CREATE TABLE IF NOT EXISTS `tql_sys_acl_roles_users_xref` (
  `userId` int(11) NOT NULL,
  `roleId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tql_sys_trans_audit`
--

CREATE TABLE IF NOT EXISTS `tql_sys_trans_audit` (
`transactionId` bigint(20) NOT NULL,
  `transactionSource` varchar(100) NOT NULL,
  `updateBy` int(11) NOT NULL,
  `updateDatetime` datetime NOT NULL,
  `updateReason` varchar(100) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10000000000 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tql_cd_locales`
--
ALTER TABLE `tql_cd_locales`
 ADD PRIMARY KEY (`locale`);

--
-- Indexes for table `tql_cd_timezones`
--
ALTER TABLE `tql_cd_timezones`
 ADD PRIMARY KEY (`timezone`);

--
-- Indexes for table `tql_entity`
--
ALTER TABLE `tql_entity`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tql_entity_addresses_electronic`
--
ALTER TABLE `tql_entity_addresses_electronic`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tql_entity_addresses_phone`
--
ALTER TABLE `tql_entity_addresses_phone`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tql_entity_addresses_physical`
--
ALTER TABLE `tql_entity_addresses_physical`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tql_entity_content`
--
ALTER TABLE `tql_entity_content`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tql_entity_content_page`
--
ALTER TABLE `tql_entity_content_page`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tql_entity_people`
--
ALTER TABLE `tql_entity_people`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tql_entity_users`
--
ALTER TABLE `tql_entity_users`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tql_entity_xref`
--
ALTER TABLE `tql_entity_xref`
 ADD PRIMARY KEY (`parentId`,`childId`);

--
-- Indexes for table `tql_history_addresses_electronic`
--
ALTER TABLE `tql_history_addresses_electronic`
 ADD PRIMARY KEY (`id`,`version`);

--
-- Indexes for table `tql_history_addresses_phone`
--
ALTER TABLE `tql_history_addresses_phone`
 ADD PRIMARY KEY (`id`,`version`);

--
-- Indexes for table `tql_history_addresses_physical`
--
ALTER TABLE `tql_history_addresses_physical`
 ADD PRIMARY KEY (`id`,`version`);

--
-- Indexes for table `tql_history_content`
--
ALTER TABLE `tql_history_content`
 ADD PRIMARY KEY (`id`,`version`);

--
-- Indexes for table `tql_history_content_page`
--
ALTER TABLE `tql_history_content_page`
 ADD PRIMARY KEY (`id`,`version`);

--
-- Indexes for table `tql_history_people`
--
ALTER TABLE `tql_history_people`
 ADD PRIMARY KEY (`id`,`version`);

--
-- Indexes for table `tql_sys_acl_privileges`
--
ALTER TABLE `tql_sys_acl_privileges`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tql_sys_acl_roles`
--
ALTER TABLE `tql_sys_acl_roles`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tql_sys_acl_roles_users_xref`
--
ALTER TABLE `tql_sys_acl_roles_users_xref`
 ADD PRIMARY KEY (`userId`,`roleId`);

--
-- Indexes for table `tql_sys_trans_audit`
--
ALTER TABLE `tql_sys_trans_audit`
 ADD PRIMARY KEY (`transactionId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tql_entity`
--
ALTER TABLE `tql_entity`
MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `tql_sys_acl_privileges`
--
ALTER TABLE `tql_sys_acl_privileges`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `tql_sys_acl_roles`
--
ALTER TABLE `tql_sys_acl_roles`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `tql_sys_trans_audit`
--
ALTER TABLE `tql_sys_trans_audit`
MODIFY `transactionId` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10000000000;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
