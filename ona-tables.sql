-- MySQL dump 10.11
--
-- Host: localhost    Database: ona
-- ------------------------------------------------------
-- Server version	5.0.45-Dotdeb_0.dotdeb.1-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `blocks`
--

DROP TABLE IF EXISTS `blocks`;
CREATE TABLE `blocks` (
  `id` int(10) unsigned NOT NULL,
  `ip_addr_start` int(10) unsigned NOT NULL,
  `ip_addr_end` int(10) unsigned NOT NULL,
  `name` varchar(63) NOT NULL,
  `notes` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='User Defined IP Address Ranges';

--
-- Table structure for table `configuration_types`
--

DROP TABLE IF EXISTS `configuration_types`;
CREATE TABLE `configuration_types` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(63) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Configuration types';

--
-- Table structure for table `configurations`
--

DROP TABLE IF EXISTS `configurations`;
CREATE TABLE `configurations` (
  `id` int(10) unsigned NOT NULL,
  `configuration_type_id` int(10) unsigned NOT NULL,
  `host_id` int(10) unsigned NOT NULL,
  `md5_checksum` varchar(63) NOT NULL,
  `config_body` longtext NOT NULL,
  `ctime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `etime` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores various types of text configurations';

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
-- Table structure for table `device_types`
--

DROP TABLE IF EXISTS `device_types`;
CREATE TABLE `device_types` (
  `id` int(10) NOT NULL,
  `model_id` int(10) NOT NULL,
  `role_id` int(10) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Joins model (+ manufacturer) and role to create a unique dev';

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `id` int(10) NOT NULL,
  `device_type_id` int(10) NOT NULL,
  `location_id` int(10) NOT NULL,
  `asset_tag` varchar(255) default NULL,
  `serial_number` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `asset_tag` (`asset_tag`,`serial_number`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `dhcp_failover_groups`
--

DROP TABLE IF EXISTS `dhcp_failover_groups`;
CREATE TABLE `dhcp_failover_groups` (
  `id` int(10) unsigned NOT NULL,
  `primary_server_id` int(10) unsigned NOT NULL,
  `secondary_server_id` int(10) unsigned NOT NULL,
  `max_response_delay` int(10) unsigned NOT NULL,
  `max_unacked_updates` int(10) unsigned NOT NULL,
  `max_load_balance` int(10) unsigned NOT NULL,
  `primary_port` int(10) unsigned NOT NULL,
  `peer_port` int(10) unsigned NOT NULL,
  `mclt` int(10) unsigned NOT NULL,
  `split` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Data for the DHCP failover groups';

--
-- Table structure for table `dhcp_option_entries`
--

DROP TABLE IF EXISTS `dhcp_option_entries`;
CREATE TABLE `dhcp_option_entries` (
  `id` int(10) unsigned NOT NULL,
  `subnet_id` int(10) unsigned NOT NULL COMMENT 'only subnet_id or host_id can be populated, not both',
  `host_id` int(10) unsigned NOT NULL COMMENT 'if neither host or subnet id is populated then it is a global value',
  `dhcp_option_id` int(10) unsigned NOT NULL,
  `value` varchar(127) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Assigns DHCP options to a host or subnet and gives it a valu';

--
-- Table structure for table `dhcp_options`
--

DROP TABLE IF EXISTS `dhcp_options`;
CREATE TABLE `dhcp_options` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(31) NOT NULL COMMENT 'internal name used by ISC configuration',
  `number` int(3) unsigned NOT NULL COMMENT 'Also known as DHCP code. someting from 0 to 255 currently',
  `type` varchar(1) NOT NULL COMMENT 'defines the structure of the option, string, integer, ip-address etc',
  `display_name` varchar(31) NOT NULL COMMENT 'user friendly display name of the dhcp option',
  `sys_default` tinyint(1) unsigned NOT NULL COMMENT 'Used to lock this option as a system default',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores standard DHCP option information';

--
-- Table structure for table `dhcp_pools`
--

DROP TABLE IF EXISTS `dhcp_pools`;
CREATE TABLE `dhcp_pools` (
  `id` int(10) unsigned NOT NULL,
  `subnet_id` int(10) unsigned NOT NULL,
  `dhcp_failover_group_id` int(10) unsigned NOT NULL,
  `ip_addr_start` int(10) unsigned NOT NULL,
  `ip_addr_end` int(10) unsigned NOT NULL,
  `lease_length` int(10) unsigned NOT NULL,
  `lease_grace_period` int(10) unsigned NOT NULL,
  `lease_renewal_time` int(10) unsigned NOT NULL,
  `lease_rebind_time` int(10) unsigned NOT NULL,
  `allow_bootp_clients` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores info about DHCP pool ranges and lease values';

--
-- Table structure for table `dhcp_server_subnets`
--

DROP TABLE IF EXISTS `dhcp_server_subnets`;
CREATE TABLE `dhcp_server_subnets` (
  `id` tinyint(10) unsigned NOT NULL,
  `host_id` tinyint(10) unsigned NOT NULL,
  `subnet_id` tinyint(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='stores subnet to dhcp server relationships';

--
-- Table structure for table `dns`
--

DROP TABLE IF EXISTS `dns`;
CREATE TABLE `dns` (
  `id` int(10) unsigned NOT NULL,
  `domain_id` int(10) unsigned NOT NULL,
  `interface_id` int(10) unsigned NOT NULL,
  `dns_id` int(10) unsigned NOT NULL default '0' COMMENT 'associated record (cname, ptr, etc)',
  `type` varchar(15) NOT NULL,
  `ttl` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'verify/set length',
  `ebegin` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT 'effective begin time.  used to  build new records, and disable a record if needed by setting all zeros',
  `notes` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='IP addr comes from interface_id';

--
-- Table structure for table `dns_server_domains`
--

DROP TABLE IF EXISTS `dns_server_domains`;
CREATE TABLE `dns_server_domains` (
  `id` tinyint(10) unsigned NOT NULL,
  `host_id` tinyint(10) unsigned NOT NULL,
  `domain_id` tinyint(10) unsigned NOT NULL,
  `authoritative` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores domain to DNS server relationships';

--
-- Table structure for table `domains`
--

DROP TABLE IF EXISTS `domains`;
CREATE TABLE `domains` (
  `id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `ctime` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `mtime` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT 'Used to create a serial number',
  `serial` int(10) unsigned NOT NULL,
  `refresh` int(10) unsigned NOT NULL,
  `retry` int(10) unsigned NOT NULL,
  `expiry` int(10) unsigned NOT NULL,
  `minimum` int(10) unsigned NOT NULL COMMENT 'Used for negative cache value',
  `default_ttl` int(10) unsigned NOT NULL COMMENT 'default ttl for entire domain',
  `primary_master` varchar(255) NOT NULL COMMENT 'Since this is a text field, user interface needs to indicate when entered text is invalid.',
  `admin_email` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'The name of the DNS domain (text)',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Domain name definitions';

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
-- Table structure for table `hosts`
--

DROP TABLE IF EXISTS `hosts`;
CREATE TABLE `hosts` (
  `id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL COMMENT 'For Virtual Hosts',
  `primary_dns_id` int(10) unsigned NOT NULL COMMENT 'So we have a display name for the host',
  `device_id` int(10) unsigned NOT NULL,
  `notes` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Host / device definitions';

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
  `name` varchar(255) NOT NULL,
  `description` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='IP addresses and other host interface data';

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
-- Table structure for table `manufacturers`
--

DROP TABLE IF EXISTS `manufacturers`;
CREATE TABLE `manufacturers` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `table_name_ref` varchar(40) NOT NULL default '',
  `table_id_ref` int(10) unsigned NOT NULL default '0',
  `priority` varchar(20) NOT NULL default '',
  `username` varchar(40) NOT NULL default '',
  `mtime` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `expiration` timestamp NOT NULL default '0000-00-00 00:00:00',
  `message_text` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1 COMMENT='Stores general messages for ONA "display" pages';

--
-- Table structure for table `models`
--

DROP TABLE IF EXISTS `models`;
CREATE TABLE `models` (
  `id` int(10) unsigned NOT NULL,
  `manufacturer_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `snmp_sysobjectid` varchar(255) NOT NULL COMMENT 'This is a device-specific SNMP identification string, provided by the device.',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(127) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Host roles / functions / services / verb';

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
-- Table structure for table `vlan_campuses`
--

DROP TABLE IF EXISTS `vlan_campuses`;
CREATE TABLE `vlan_campuses` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(63) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores vlan campus information (groupings of vlans)';

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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2007-07-31 19:01:52
