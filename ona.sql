-- phpMyAdmin SQL Dump
-- version 2.10.0-rc1
-- http://www.phpmyadmin.net
-- 
-- Host: blade2
-- Generation Time: Mar 26, 2007 at 06:36 PM
-- Server version: 5.0.24
-- PHP Version: 4.4.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- 
-- Database: `ona`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `blocks`
-- 

DROP TABLE IF EXISTS `blocks`;
CREATE TABLE `blocks` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(63) NOT NULL,
  `ip_addr` int(10) unsigned NOT NULL,
  `ip_mask` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='User Defined IP Address Ranges';

-- 
-- Dumping data for table `blocks`
-- 

INSERT INTO `blocks` (`id`, `name`, `ip_addr`, `ip_mask`) VALUES 
(1, 'TEST_BLOCK', 33686016, 4294967040),
(2, 'BLOCK2', 167866880, 4294967040);

-- --------------------------------------------------------

-- 
-- Table structure for table `dcm_module_list`
-- 

DROP TABLE IF EXISTS `dcm_module_list`;
CREATE TABLE `dcm_module_list` (
  `id` int(10) unsigned NOT NULL default '0',
  `name` varchar(128) NOT NULL default '',
  `description` text NOT NULL,
  `file` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `dcm_module_list`
-- 

INSERT INTO `dcm_module_list` (`id`, `name`, `description`, `file`) VALUES 
(1, 'get_module_list', 'Returns the list of available modules', 'get_module_list.inc.php'),
(2, 'mangle_ip', 'Converts between various IP address representations', 'mangle.inc.php'),
(3, 'mysql_purge_logs', 'Purges unused replication logs on MySQL masters', 'mysql_purge_logs.inc.php'),
(4, 'subnet_add', 'Add a new subnet', 'ona/subnet.inc.php'),
(5, 'subnet_modify', 'Modify an existing subnet', 'ona/subnet.inc.php'),
(6, 'subnet_del', 'Delete an existing subnet', 'ona/subnet.inc.php'),
(7, 'subnet_display', 'Display an existing subnet', 'ona/subnet.inc.php'),
(8, 'host_add', 'Add a new host', 'ona/host.inc.php'),
(9, 'host_display', 'Display an existing host', 'ona/host.inc.php'),
(10, 'host_modify', 'Modify an existing host', 'ona/host.inc.php'),
(11, 'host_del', 'Delete an existing host', 'ona/host.inc.php');

-- --------------------------------------------------------

-- 
-- Table structure for table `defaults`
-- 

DROP TABLE IF EXISTS `defaults`;
CREATE TABLE `defaults` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `value` text NOT NULL,
  `default_value` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `defaults`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `dns_a`
-- 

DROP TABLE IF EXISTS `dns_a`;
CREATE TABLE `dns_a` (
  `id` int(10) unsigned NOT NULL,
  `domain_id` int(10) unsigned NOT NULL,
  `interface_id` int(10) unsigned NOT NULL,
  `ttl` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'verify/set length',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='No description needed.  Interface_ID is where it gets the IP';

-- 
-- Dumping data for table `dns_a`
-- 

INSERT INTO `dns_a` (`id`, `domain_id`, `interface_id`, `ttl`, `name`) VALUES 
(1, 1, 1, 3600, 'hostname1');

-- --------------------------------------------------------

-- 
-- Table structure for table `domains`
-- 

DROP TABLE IF EXISTS `domains`;
CREATE TABLE `domains` (
  `id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `ctime` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `mtime` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT 'Used to create a serial number',
  `refresh` int(10) unsigned NOT NULL,
  `retry` int(10) unsigned NOT NULL,
  `expire` int(10) unsigned NOT NULL,
  `minimum` int(10) unsigned NOT NULL,
  `ns_fqdn` varchar(255) NOT NULL COMMENT 'Since this is a text field, user interface needs to indicate when entered text is invalid.',
  `admin_email` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Domain name definitions';

-- 
-- Dumping data for table `domains`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `hosts`
-- 

DROP TABLE IF EXISTS `hosts`;
CREATE TABLE `hosts` (
  `id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL COMMENT 'For Virtual Hosts',
  `primary_dns_a_id` int(10) unsigned NOT NULL,
  `model_id` int(10) unsigned NOT NULL,
  `location_id` int(10) unsigned NOT NULL,
  `notes` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Host / device definitions';

-- 
-- Dumping data for table `hosts`
-- 

INSERT INTO `hosts` (`id`, `parent_id`, `primary_dns_a_id`, `model_id`, `location_id`, `notes`) VALUES 
(1, 0, 0, 1, 0, 'testing'),
(2, 0, 0, 1, 1, 'more notes');

-- --------------------------------------------------------

-- 
-- Table structure for table `host_roles`
-- 

DROP TABLE IF EXISTS `host_roles`;
CREATE TABLE `host_roles` (
  `id` int(10) unsigned NOT NULL,
  `host_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `host_roles`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `interfaces`
-- 

DROP TABLE IF EXISTS `interfaces`;
CREATE TABLE `interfaces` (
  `id` int(10) unsigned NOT NULL,
  `subnet_id` int(10) unsigned NOT NULL,
  `host_id` int(10) unsigned NOT NULL,
  `ip_addr` int(10) unsigned NOT NULL,
  `mac_addr` varchar(12) NOT NULL,
  `name` varchar(127) NOT NULL,
  `description` varchar(64) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='IP addresses and other host interface data';

-- 
-- Dumping data for table `interfaces`
-- 

INSERT INTO `interfaces` (`id`, `subnet_id`, `host_id`, `ip_addr`, `mac_addr`, `name`, `description`) VALUES 
(1, 1, 1, 33686018, '001122334455', 'test', NULL),
(2, 8, 1, 151587081, '', '', NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table `locations`
-- 

DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `id` int(10) unsigned NOT NULL,
  `city_id` int(10) unsigned NOT NULL,
  `state_id` int(10) unsigned NOT NULL,
  `country_id` int(10) unsigned NOT NULL,
  `lattitude` varchar(20) NOT NULL,
  `longitude` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table needs re-worked';

-- 
-- Dumping data for table `locations`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `manufacturers`
-- 

DROP TABLE IF EXISTS `manufacturers`;
CREATE TABLE `manufacturers` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `manufacturers`
-- 

INSERT INTO `manufacturers` (`id`, `name`) VALUES 
(1, 'Cisco'),
(2, 'Juniper'),
(3, 'Unknown'),
(4, 'Hewlet Packard');

-- --------------------------------------------------------

-- 
-- Table structure for table `models`
-- 

DROP TABLE IF EXISTS `models`;
CREATE TABLE `models` (
  `id` int(10) unsigned NOT NULL,
  `manufacturer_id` int(10) unsigned NOT NULL,
  `model` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `models`
-- 

INSERT INTO `models` (`id`, `manufacturer_id`, `model`) VALUES 
(1, 1, '2821'),
(2, 4, 'dv9000t');

-- --------------------------------------------------------

-- 
-- Table structure for table `roles`
-- 

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(127) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Host roles / functions / services / verb';

-- 
-- Dumping data for table `roles`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `sequences`
-- 

DROP TABLE IF EXISTS `sequences`;
CREATE TABLE `sequences` (
  `name` varchar(31) NOT NULL,
  `seq` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='All sequences stored here';

-- 
-- Dumping data for table `sequences`
-- 

INSERT INTO `sequences` (`name`, `seq`) VALUES 
('contexts', 1),
('subnets', 18),
('subnet_types', 13);

-- --------------------------------------------------------

-- 
-- Table structure for table `sessions`
-- 

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `sesskey` char(32) NOT NULL,
  `expiry` int(11) unsigned NOT NULL,
  `sessvalue` text NOT NULL,
  PRIMARY KEY  (`sesskey`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `sessions`
-- 

INSERT INTO `sessions` (`sesskey`, `expiry`, `sessvalue`) VALUES 
('4a53cd17f61b7c6d5d186a0abeaffa11', 1174865141, 'redirect|s:18:"http://blade1/ona/";ona|a:4:{s:4:"auth";a:1:{s:4:"user";a:2:{s:8:"username";s:5:"guest";s:5:"level";s:1:"0";}}s:26:"search_results_filter_form";a:4:{s:10:"content_id";s:19:"search_results_list";s:3:"tab";s:5:"hosts";s:7:"subnets";a:3:{s:1:"q";a:1:{s:9:"subnet_id";s:1:"9";}s:4:"page";s:1:"1";s:6:"filter";s:0:"";}s:5:"hosts";a:3:{s:1:"q";a:1:{s:8:"hostname";s:0:"";}s:4:"page";s:1:"1";s:6:"filter";s:0:"";}}s:10:"work_space";a:1:{s:7:"history";a:1:{i:0;a:3:{s:5:"title";s:8:"WOW-COOL";s:4:"type";s:14:"display_subnet";s:3:"url";s:64:"xajax_window_submit(''display_subnet'', ''subnet_id=>1'', ''display'')";}}}s:22:"list_hosts_filter_form";a:2:{s:3:"tab";s:5:"hosts";s:5:"hosts";a:2:{s:4:"page";s:1:"1";s:6:"filter";s:0:"";}}}tz|i:0;window_position|a:10:{s:15:"app_user_info_x";s:3:"853";s:15:"app_user_info_y";s:3:"173";s:13:"edit_subnet_x";s:3:"199";s:13:"edit_subnet_y";s:3:"205";s:15:"app_user_list_x";s:3:"697";s:15:"app_user_list_y";s:3:"498";s:16:"search_results_x";s:3:"194";s:16:"search_results_y";s:3:"132";s:11:"edit_host_x";s:3:"181";s:11:"edit_host_y";s:3:"268";}'),
('80834c25e8a3144e1e5a5d661054d353', 1175128480, 'redirect|s:18:"http://blade1/ona/";ona|a:4:{s:4:"auth";a:1:{s:4:"user";a:2:{s:8:"username";s:5:"guest";s:5:"level";s:1:"0";}}s:26:"search_results_filter_form";a:3:{s:10:"content_id";s:19:"search_results_list";s:3:"tab";s:7:"subnets";s:5:"hosts";a:3:{s:1:"q";a:1:{s:8:"hostname";s:0:"";}s:4:"page";s:1:"1";s:6:"filter";s:0:"";}}s:10:"work_space";a:1:{s:7:"history";a:2:{i:0;a:3:{s:5:"title";s:12:"Map: 2.2.2.0";s:4:"type";s:17:"display_block_map";s:3:"url";s:109:"xajax_window_submit(''display_block_map'', ''ip_block_start=>2.2.2.0,ip_block_end=>2.2.2.255,id=>1'', ''display'');";}i:1;a:3:{s:5:"title";s:8:"TEST9DOT";s:4:"type";s:14:"display_subnet";s:3:"url";s:64:"xajax_window_submit(''display_subnet'', ''subnet_id=>8'', ''display'')";}}}s:22:"list_hosts_filter_form";a:2:{s:3:"tab";s:5:"hosts";s:5:"hosts";a:2:{s:4:"page";s:1:"1";s:6:"filter";s:0:"";}}}tz|i:0;window_position|a:10:{s:11:"edit_host_x";s:3:"759";s:11:"edit_host_y";s:3:"371";s:16:"search_results_x";s:3:"148";s:16:"search_results_y";s:2:"63";s:17:"app_admin_tools_x";s:4:"1040";s:17:"app_admin_tools_y";s:3:"388";s:22:"app_subnet_type_list_x";s:3:"259";s:22:"app_subnet_type_list_y";s:3:"401";s:22:"app_subnet_type_edit_x";s:3:"514";s:22:"app_subnet_type_edit_y";s:3:"325";}'),
('f3fd1f497ab42ab0ae2c914f01202a03', 1175105030, 'redirect|s:18:"http://blade1/ona/";ona|a:1:{s:4:"auth";a:1:{s:4:"user";a:2:{s:8:"username";s:5:"guest";s:5:"level";s:1:"0";}}}tz|i:0;');

-- --------------------------------------------------------

-- 
-- Table structure for table `subnets`
-- 

DROP TABLE IF EXISTS `subnets`;
CREATE TABLE `subnets` (
  `id` int(10) unsigned NOT NULL,
  `network_role_id` int(10) unsigned NOT NULL,
  `subnet_type_id` int(10) unsigned NOT NULL,
  `vlan_id` int(10) unsigned NOT NULL,
  `ip_addr` int(10) unsigned NOT NULL,
  `ip_mask` int(10) unsigned NOT NULL,
  `name` varchar(127) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='IP subnet definitions';

-- 
-- Dumping data for table `subnets`
-- 

INSERT INTO `subnets` (`id`, `network_role_id`, `subnet_type_id`, `vlan_id`, `ip_addr`, `ip_mask`, `name`) VALUES 
(1, 0, 8, 1, 33686016, 4294967040, 'WOW-COOL'),
(2, 0, 8, 0, 50463232, 4294967040, 'DUH'),
(3, 0, 2, 0, 67372032, 4294967040, 'MORE'),
(4, 0, 1, 0, 16909056, 4294967040, 'DUDE'),
(5, 0, 1, 0, 16908800, 4294967040, 'YEAH-RIGHT'),
(6, 0, 7, 0, 167837696, 4294967040, 'SOME-NAME'),
(7, 0, 7, 0, 167866880, 4294967040, 'VLAN-110'),
(8, 0, 8, 2, 151584768, 4294950912, 'TEST9DOT'),
(9, 0, 0, 0, 16843008, 4294967040, 'TEST'),
(17, 0, 2, 0, 3232236032, 4294967040, 'PAULK-TEST');

-- --------------------------------------------------------

-- 
-- Table structure for table `subnet_types`
-- 

DROP TABLE IF EXISTS `subnet_types`;
CREATE TABLE `subnet_types` (
  `id` int(10) unsigned NOT NULL,
  `short_name` varchar(31) NOT NULL COMMENT 'Lower case name for use with console / scripts',
  `display_name` varchar(63) NOT NULL COMMENT 'Name displayed in GUI',
  `notes` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `subnet_types`
-- 

INSERT INTO `subnet_types` (`id`, `short_name`, `display_name`, `notes`) VALUES 
(1, 'loopback', 'Loopback', 'Loopback Interfaces (mostly for routers)'),
(2, '', 'WAN', ''),
(7, '', 'VLAN', ''),
(8, '', 'MAN', ''),
(9, '', 'VSAT', ''),
(10, '', 'Point-to-Point', ''),
(11, '', 'VPN', ''),
(12, '', 'Wireless LAN', '');

-- --------------------------------------------------------

-- 
-- Table structure for table `vlans`
-- 

DROP TABLE IF EXISTS `vlans`;
CREATE TABLE `vlans` (
  `id` int(10) unsigned NOT NULL,
  `vlan_campus_id` int(10) unsigned NOT NULL,
  `name` varchar(63) NOT NULL,
  `number` int(5) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores vlan information (groupings of subnets)';

-- 
-- Dumping data for table `vlans`
-- 

INSERT INTO `vlans` (`id`, `vlan_campus_id`, `name`, `number`) VALUES 
(1, 1, 'DEFAULT', 1),
(2, 1, 'DESKTOPS', 4);

-- --------------------------------------------------------

-- 
-- Table structure for table `vlan_campuses`
-- 

DROP TABLE IF EXISTS `vlan_campuses`;
CREATE TABLE `vlan_campuses` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(63) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores vlan campus information (groupings of vlans)';

-- 
-- Dumping data for table `vlan_campuses`
-- 

INSERT INTO `vlan_campuses` (`id`, `name`) VALUES 
(1, 'BOISE'),
(2, 'NAMPA');
