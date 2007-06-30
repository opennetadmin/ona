-- MySQL dump 10.11
--
-- Host: localhost    Database: ona
-- ------------------------------------------------------
-- Server version	5.0.41-Dotdeb_1.dotdeb.2-log

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
-- Dumping data for table `blocks`
--

LOCK TABLES `blocks` WRITE;
/*!40000 ALTER TABLE `blocks` DISABLE KEYS */;
INSERT INTO `blocks` VALUES (2,167866624,167866879,'NEW_BLOCK',''),(3,16843008,50529279,'BLOCK2','some notes');
/*!40000 ALTER TABLE `blocks` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `configuration_types`
--

LOCK TABLES `configuration_types` WRITE;
/*!40000 ALTER TABLE `configuration_types` DISABLE KEYS */;
INSERT INTO `configuration_types` VALUES (1,'IOS_CONFIG'),(2,'IOS_VERSION');
/*!40000 ALTER TABLE `configuration_types` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `configurations`
--

LOCK TABLES `configurations` WRITE;
/*!40000 ALTER TABLE `configurations` DISABLE KEYS */;
INSERT INTO `configurations` VALUES (1,1,1,'2345234q2gwefg345','this is soem\r\nlong\r\ntexxt','2007-01-10 10:02:46','0000-00-00 00:00:00'),(2,1,1,'320q9485q2l3i4','this soem\r\nlong\r\ntext','2007-01-10 10:55:46','0000-00-00 00:00:00');
/*!40000 ALTER TABLE `configurations` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `dcm_module_list` WRITE;
/*!40000 ALTER TABLE `dcm_module_list` DISABLE KEYS */;
INSERT INTO `dcm_module_list` VALUES (0,'domain_display','Displays an existing domain','ona/domain.inc.php'),(1,'get_module_list','Returns the list of available modules','get_module_list.inc.php'),(2,'mangle_ip','Converts between various IP address representations','mangle.inc.php'),(3,'mysql_purge_logs','Purges unused replication logs on MySQL masters','mysql_purge_logs.inc.php'),(4,'subnet_add','Add a new subnet','ona/subnet.inc.php'),(5,'subnet_modify','Modify an existing subnet','ona/subnet.inc.php'),(6,'subnet_del','Delete an existing subnet','ona/subnet.inc.php'),(7,'subnet_display','Display an existing subnet','ona/subnet.inc.php'),(8,'host_add','Add a new host','ona/host.inc.php'),(9,'host_display','Display an existing host','ona/host.inc.php'),(10,'host_modify','Modify an existing host','ona/host.inc.php'),(11,'host_del','Delete an existing host','ona/host.inc.php'),(12,'interface_add','Add an interface to an existing host','ona/interface.inc.php'),(13,'interface_modify','Modify an existing interface','ona/interface.inc.php'),(14,'interface_del','Delete an existing interface','ona/interface.inc.php'),(15,'interface_display','Displays details of an existing interface','ona/interface.inc.php'),(16,'interface_move','Move an interface from one subnet to another','ona/interface.inc.php'),(17,'domain_add','Adds a domain entry into the IP database','ona/domain.inc.php'),(18,'domain_del','Deletes a domain from the IP database','ona/domain.inc.php'),(19,'domain_modify','Updates an domain record in the IP database','ona/domain.inc.php'),(20,'dhcp_pool_add','Add DHCP pools','ona/dhcp_pool.inc.php'),(21,'dhcp_pool_modify','Modify DHCP pools','ona/dhcp_pool.inc.php'),(22,'dhcp_pool_del','Delete a DHCP pool','ona/dhcp_pool.inc.php'),(23,'dhcp_entry_add','Add a DHCP option entry','ona/dhcp_entry.inc.php'),(24,'dhcp_entry_del','Delete a DHCP option entry','ona/dhcp_entry.inc.php'),(25,'dhcp_entry_modify','Modify DHCP option entry','ona/dhcp_entry.inc.php'),(26,'message_add','Allows you to add a message to a subnet or host that will so in a display page','ona/message.inc.php'),(27,'block_add','Add an ip block range','ona/block.inc.php'),(28,'block_del','Delete an ip block','ona/block.inc.php'),(29,'block_modify','Modify ip blocks','ona/block.inc.php'),(30,'dhcp_server_add','Add a dhcp server to subnet relationship','ona/dhcp_server.inc.php'),(31,'dhcp_server_del','Delete a dhcp server to subnet relationship','ona/dhcp_server.inc.php');
/*!40000 ALTER TABLE `dcm_module_list` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `defaults` WRITE;
/*!40000 ALTER TABLE `defaults` DISABLE KEYS */;
/*!40000 ALTER TABLE `defaults` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `device_types`
--

LOCK TABLES `device_types` WRITE;
/*!40000 ALTER TABLE `device_types` DISABLE KEYS */;
INSERT INTO `device_types` VALUES (1,1,1),(2,9,6),(3,3,2),(4,4,2),(5,5,3);
/*!40000 ALTER TABLE `device_types` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `devices`
--

LOCK TABLES `devices` WRITE;
/*!40000 ALTER TABLE `devices` DISABLE KEYS */;
INSERT INTO `devices` VALUES (1,1,1,'FQGHX','123456'),(2,1,1,'UYEJK','54321'),(3,2,1,'PTRML','000001'),(4,4,1,'EJCSA','561345'),(5,5,1,'ITRHC','99822'),(6,3,1,'YTMIR','876543');
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `dhcp_failover_groups`
--

LOCK TABLES `dhcp_failover_groups` WRITE;
/*!40000 ALTER TABLE `dhcp_failover_groups` DISABLE KEYS */;
INSERT INTO `dhcp_failover_groups` VALUES (1,1,2,1111,11,11,1,11,111,255);
/*!40000 ALTER TABLE `dhcp_failover_groups` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `dhcp_option_entries`
--

LOCK TABLES `dhcp_option_entries` WRITE;
/*!40000 ALTER TABLE `dhcp_option_entries` DISABLE KEYS */;
INSERT INTO `dhcp_option_entries` VALUES (1,1,0,3,'2.2.2.1'),(4,0,1,12,'cool.file'),(5,4,0,13,'asdf');
/*!40000 ALTER TABLE `dhcp_option_entries` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `dhcp_options`
--

LOCK TABLES `dhcp_options` WRITE;
/*!40000 ALTER TABLE `dhcp_options` DISABLE KEYS */;
INSERT INTO `dhcp_options` VALUES (1,'subnet-mask',1,'I','Subnet Mask',1),(2,'routers',3,'L','Default Gateway',1),(3,'domain-name-servers',6,'L','DNS Name Servers',1),(4,'domain-name',15,'S','Default domain',1),(5,'host-name',12,'S','Host Name',1),(6,'vendor-encapsulated-options',43,'S','Vendor Ecapsulated Options',1),(7,'netbios-name-servers',44,'L','Netbios Name Servers',1),(8,'netbios-node-type',46,'N','Netbios Node Type',1),(9,'netbios-scope',47,'S','Netbios Scope',1),(10,'vendor-class-identifier',60,'S','Vendor Class Identifier',1),(11,'tftp-server-name',66,'S','TFTP Server Name',1),(12,'bootfile-name',67,'S','Bootfile Name',1),(13,'wer',2,'S','asd',0);
/*!40000 ALTER TABLE `dhcp_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dhcp_pools`
--

DROP TABLE IF EXISTS `dhcp_pools`;
CREATE TABLE `dhcp_pools` (
  `id` int(10) unsigned NOT NULL auto_increment,
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
-- Dumping data for table `dhcp_pools`
--

LOCK TABLES `dhcp_pools` WRITE;
/*!40000 ALTER TABLE `dhcp_pools` DISABLE KEYS */;
INSERT INTO `dhcp_pools` VALUES (3,1,1,33686026,33686116,604800,0,0,0,1);
/*!40000 ALTER TABLE `dhcp_pools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dhcp_server_subnets`
--

DROP TABLE IF EXISTS `dhcp_server_subnets`;
CREATE TABLE `dhcp_server_subnets` (
  `id` tinyint(10) unsigned NOT NULL auto_increment,
  `host_id` tinyint(10) unsigned NOT NULL,
  `subnet_id` tinyint(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='stores subnet to dhcp server relationships';

--
-- Dumping data for table `dhcp_server_subnets`
--

LOCK TABLES `dhcp_server_subnets` WRITE;
/*!40000 ALTER TABLE `dhcp_server_subnets` DISABLE KEYS */;
INSERT INTO `dhcp_server_subnets` VALUES (1,2,1),(2,2,4);
/*!40000 ALTER TABLE `dhcp_server_subnets` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='IP addr comes from interface_id';

--
-- Dumping data for table `dns`
--

LOCK TABLES `dns` WRITE;
/*!40000 ALTER TABLE `dns` DISABLE KEYS */;
INSERT INTO `dns` VALUES (1,1,1,0,'A',3600,'hostname1'),(2,1,3,0,'A',3600,'hostname2'),(5,1,2,0,'A',3600,'anotherarecord'),(6,1,2,0,'A',3600,'morerecord'),(10,2,0,0,'A',3600,'hostname3'),(11,1,0,0,'A',3600,'hostname4'),(12,2,0,0,'A',3600,'polyglot'),(13,1,0,0,'A',3600,'purple.people.eater'),(14,1,2,0,'A',3600,'hostname1'),(15,1,0,1,'CNAME',28800,'hostnamex'),(16,2,0,13,'CNAME',14400,'another.alias');
/*!40000 ALTER TABLE `dns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dns_records`
--

DROP TABLE IF EXISTS `dns_records`;
CREATE TABLE `dns_records` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(64) NOT NULL,
  `domain_id` int(10) NOT NULL,
  `type` varchar(10) NOT NULL,
  `ttl` int(10) NOT NULL,
  `value` varchar(128) NOT NULL COMMENT 'the value is usualy an interface_id or a dns_record_id to another entry.',
  `ebegin` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT 'effective begin date',
  `eend` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT 'effective end date',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='New idea on dns records';

--
-- Dumping data for table `dns_records`
--

LOCK TABLES `dns_records` WRITE;
/*!40000 ALTER TABLE `dns_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `dns_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `domains`
--

DROP TABLE IF EXISTS `domains`;
CREATE TABLE `domains` (
  `id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `ctime` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `mtime` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT 'Used to create a serial number',
  `serial` binary(32) NOT NULL,
  `refresh` int(10) unsigned NOT NULL,
  `retry` int(10) unsigned NOT NULL,
  `expire` int(10) unsigned NOT NULL,
  `minimum` int(10) unsigned NOT NULL,
  `ns_fqdn` varchar(255) NOT NULL COMMENT 'Since this is a text field, user interface needs to indicate when entered text is invalid.',
  `admin_email` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'The name of the DNS domain (text)',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Domain name definitions';

--
-- Dumping data for table `domains`
--

LOCK TABLES `domains` WRITE;
/*!40000 ALTER TABLE `domains` DISABLE KEYS */;
INSERT INTO `domains` VALUES (1,0,'2007-04-02 22:10:46','0000-00-00 00:00:00','                                ',3600,3600,3600,3600,'ns1.test.com','','opennetadmin.com'),(2,0,'2007-05-15 15:26:14','0000-00-00 00:00:00','                                ',5400,3600,3600,3600,'purple.people.eater.opennetadmin.com','admin@albertsons.com','something.com');
/*!40000 ALTER TABLE `domains` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `host_roles` WRITE;
/*!40000 ALTER TABLE `host_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `host_roles` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `hosts`
--

LOCK TABLES `hosts` WRITE;
/*!40000 ALTER TABLE `hosts` DISABLE KEYS */;
INSERT INTO `hosts` VALUES (1,0,1,1,'testing again'),(2,0,2,2,'more notes'),(10,0,10,3,''),(11,0,11,4,'hostname 4 test'),(12,0,12,5,'This is the primary polyglot database server'),(13,0,13,6,'This one is yellow-bellied'),(14,0,14,4,'');
/*!40000 ALTER TABLE `hosts` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `interfaces`
--

LOCK TABLES `interfaces` WRITE;
/*!40000 ALTER TABLE `interfaces` DISABLE KEYS */;
INSERT INTO `interfaces` VALUES (1,1,1,33686025,'001122334455','test',NULL),(2,8,1,151587081,'','',NULL),(4,18,2,3232235791,'0000DEADBEEF','Gi0/0','This is a test interface description field'),(5,4,2,16909058,'','FE1/12.2','WAN link to somewhere'),(6,1,2,33686019,'AABBCCDDEEFF','testing',''),(7,1,10,33686020,'003862F8EFDA','eth0',''),(8,18,10,3232235790,'','',''),(9,1,11,33686021,'80FE009F3B8C','',''),(10,1,12,33686023,'000EFE80A03D','sit0',''),(12,8,13,151587090,'8000FE2217ED','',''),(13,4,1,16909059,'00005F4380BB','ath0',''),(14,1,14,33686033,'','','');
/*!40000 ALTER TABLE `interfaces` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `manufacturers`
--

LOCK TABLES `manufacturers` WRITE;
/*!40000 ALTER TABLE `manufacturers` DISABLE KEYS */;
INSERT INTO `manufacturers` VALUES (7,'Adtran'),(8,'Allied Telesyn'),(9,'Cabletron'),(1,'Cisco'),(5,'Dell'),(10,'Extreme Networks'),(4,'Hewlett Packard'),(6,'IBM'),(2,'Juniper'),(3,'Unknown');
/*!40000 ALTER TABLE `manufacturers` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores general messages for ONA "display" pages';

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,'hosts',1,'2','matt','2007-05-20 05:48:58','2029-05-30 06:00:00','this is a test message'),(2,'hosts',1,'3','matt','2007-05-20 05:54:05','0000-00-00 00:00:00','this is an expired message'),(3,'hosts',1,'1','matt','2007-05-20 05:54:05','2020-05-20 06:00:00','this is a priority 1 message. Do not pass go, go directly to jail!'),(4,'subnets',1,'0','anonymous','2007-05-20 06:06:30','2007-07-01 06:06:30','This is an informational level message'),(8,'SYS_ALERT',1,'0','Matt','2007-05-20 06:32:19','2029-05-20 06:00:00','This is an example system level message'),(9,'subnets',1,'3','anonymous','2007-05-21 02:46:08','2010-01-01 07:00:00','This is a priority 3 message');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `models`
--

LOCK TABLES `models` WRITE;
/*!40000 ALTER TABLE `models` DISABLE KEYS */;
INSERT INTO `models` VALUES (1,1,'2821',''),(2,4,'dv9000t',''),(3,4,'4000m',''),(4,4,'8000m',''),(5,4,'LJ5000',''),(6,1,'2948G-L3',''),(7,5,'Optiplex GS560',''),(8,9,'24TXM-GLS',''),(9,3,'GreaseMaster 1Billion',''),(10,6,'Netfinity 2232','');
/*!40000 ALTER TABLE `models` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (6,'fryer oil sensor'),(3,'printer'),(1,'router'),(4,'server'),(2,'switch'),(7,'wireless access point'),(5,'workstation');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `sequences` WRITE;
/*!40000 ALTER TABLE `sequences` DISABLE KEYS */;
INSERT INTO `sequences` VALUES ('blocks',4),('devices',7),('device_types',6),('dhcp_options',14),('dhcp_option_entries',6),('dhcp_pools',3),('dhcp_server_subnets',4),('dns',15),('hosts',15),('interfaces',15),('manufacturers',47),('models',11),('roles',11),('subnets',20),('subnet_types',13);
/*!40000 ALTER TABLE `sequences` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('1027053e998965a26df224ff9f3c5901',1179415686,'redirect|s:36:\"häž5//hive.homeip.net:8800/ona.new/\";ona|a:6:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:0:\"\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:3:{i:0;a:3:{s:5:\"title\";s:9:\"hostname1\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>1\', \'display\')\";}i:1;a:3:{s:5:\"title\";s:8:\"WOW-COOL\";s:4:\"type\";s:14:\"display_subnet\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_subnet\', \'subnet_id=>1\', \'display\')\";}i:2;a:3:{s:5:\"title\";s:13:\"something.com\";s:4:\"type\";s:14:\"display_domain\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_domain\', \'domain_id=>2\', \'display\')\";}}}s:27:\"list_interfaces_filter_form\";a:1:{s:3:\"tab\";s:10:\"interfaces\";}s:22:\"list_hosts_filter_form\";a:2:{s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:24:\"list_records_filter_form\";a:2:{s:3:\"tab\";s:7:\"records\";s:7:\"records\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}}tz|i:0;window_position|a:14:{s:24:\"edit_dhcp_option_entry_x\";s:3:\"218\";s:24:\"edit_dhcp_option_entry_y\";s:3:\"148\";s:24:\"app_dhcp_failover_list_x\";s:3:\"411\";s:24:\"app_dhcp_failover_list_y\";s:3:\"268\";s:22:\"app_dhcp_option_list_x\";s:3:\"355\";s:22:\"app_dhcp_option_list_y\";s:3:\"176\";s:22:\"app_device_type_list_x\";s:3:\"355\";s:22:\"app_device_type_list_y\";s:3:\"403\";s:22:\"app_device_type_edit_x\";s:3:\"688\";s:22:\"app_device_type_edit_y\";s:3:\"153\";s:17:\"app_admin_tools_x\";s:3:\"168\";s:17:\"app_admin_tools_y\";s:3:\"403\";s:17:\"app_domain_list_x\";s:3:\"360\";s:17:\"app_domain_list_y\";s:2:\"70\";}'),('185469f99c0fe94146ec226756eff21e',1182631192,'redirect|s:36:\"h|¹5//hive.homeip.net:8800/ona.new/\";ona|a:5:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:3:{s:1:\"q\";a:10:{s:14:\"search_form_id\";s:16:\"host_search_form\";s:8:\"hostname\";s:0:\"\";s:6:\"domain\";s:0:\"\";s:3:\"mac\";s:0:\"\";s:2:\"ip\";s:0:\"\";s:7:\"ip_thru\";s:0:\"\";s:5:\"notes\";s:0:\"\";s:4:\"unit\";s:0:\"\";s:5:\"reset\";s:5:\"Clear\";s:6:\"search\";s:6:\"Search\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:2:{i:0;a:3:{s:5:\"title\";s:9:\"hostname2\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>2\', \'display\')\";}i:1;a:3:{s:5:\"title\";s:7:\"1.2.3.2\";s:4:\"type\";s:17:\"display_interface\";s:3:\"url\";s:69:\"xajax_window_submit(\'display_interface\',\'interface_id=>5\', \'display\')\";}}}s:27:\"list_interfaces_filter_form\";a:2:{s:3:\"tab\";s:10:\"interfaces\";s:10:\"interfaces\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:24:\"list_records_filter_form\";a:2:{s:3:\"tab\";s:7:\"records\";s:7:\"records\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}}tz|i:0;window_position|a:4:{s:21:\"app_advanced_search_x\";s:3:\"393\";s:21:\"app_advanced_search_y\";s:3:\"159\";s:16:\"search_results_x\";s:2:\"51\";s:16:\"search_results_y\";s:3:\"159\";}'),('3724e9ae2143fc234bdee98771bd20fc',1182665590,'redirect|s:36:\"hDk\'//hive.homeip.net:8800/ona.new/\";ona|a:6:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:0:\"\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:2:{i:0;a:3:{s:5:\"title\";s:8:\"WOW-COOL\";s:4:\"type\";s:14:\"display_subnet\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_subnet\', \'subnet_id=>1\', \'display\')\";}i:1;a:3:{s:5:\"title\";s:9:\"hostname1\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>1\', \'display\')\";}}}s:22:\"list_hosts_filter_form\";a:2:{s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:27:\"list_interfaces_filter_form\";a:2:{s:3:\"tab\";s:10:\"interfaces\";s:10:\"interfaces\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:24:\"list_records_filter_form\";a:2:{s:3:\"tab\";s:7:\"records\";s:7:\"records\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}}tz|i:0;window_position|a:4:{s:16:\"search_results_x\";s:2:\"33\";s:16:\"search_results_y\";s:2:\"57\";s:18:\"edit_dhcp_server_x\";s:3:\"149\";s:18:\"edit_dhcp_server_y\";s:2:\"88\";}'),('3826cd4d34b7810cdf8517bc0f6d0082',1182305334,'redirect|s:36:\"h¼–://hive.homeip.net:8800/ona.new/\";ona|a:5:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:0:\"\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:1:{i:0;a:3:{s:5:\"title\";s:9:\"hostname1\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>1\', \'display\')\";}}}s:27:\"list_interfaces_filter_form\";a:1:{s:3:\"tab\";s:10:\"interfaces\";}s:24:\"list_records_filter_form\";a:2:{s:3:\"tab\";s:7:\"records\";s:7:\"records\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}}tz|i:0;window_position|a:4:{s:16:\"search_results_x\";s:2:\"25\";s:16:\"search_results_y\";s:2:\"77\";s:18:\"edit_dhcp_server_x\";s:3:\"115\";s:18:\"edit_dhcp_server_y\";s:3:\"133\";}'),('3d7558cc56ec99c0684d4d03ce73a3c8',1183343618,'redirect|s:25:\"http://172.22.22.223/ona/\";ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}tz|i:0;'),('55390f8cbb822ae06adfaf8f31ccdbf4',1182511254,'redirect|s:22:\"http://server/ona.new/\";ona|a:11:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:5:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:7:\"subnets\";a:3:{s:1:\"q\";a:1:{s:9:\"subnet_id\";s:1:\"1\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:0:\"\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}s:6:\"blocks\";a:3:{s:1:\"q\";a:2:{s:14:\"search_form_id\";s:17:\"block_search_form\";s:8:\"all_flag\";s:1:\"1\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:7:{i:0;a:3:{s:5:\"title\";s:8:\"WOW-COOL\";s:4:\"type\";s:14:\"display_subnet\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_subnet\', \'subnet_id=>1\', \'display\')\";}i:1;a:3:{s:5:\"title\";s:15:\"display_startup\";s:4:\"type\";s:15:\"display_startup\";s:3:\"url\";s:53:\"xajax_window_submit(\'display_startup\', \'\', \'display\')\";}i:2;a:3:{s:5:\"title\";s:7:\"9.9.9.9\";s:4:\"type\";s:17:\"display_interface\";s:3:\"url\";s:69:\"xajax_window_submit(\'display_interface\',\'interface_id=>2\', \'display\')\";}i:3;a:3:{s:5:\"title\";s:7:\"2.2.2.9\";s:4:\"type\";s:17:\"display_interface\";s:3:\"url\";s:69:\"xajax_window_submit(\'display_interface\',\'interface_id=>1\', \'display\')\";}i:4;a:3:{s:5:\"title\";s:7:\"Startup\";s:4:\"type\";s:15:\"display_startup\";s:3:\"url\";s:65:\"xajax_window_submit(\'display_startup\', \'domain_id=>1\', \'display\')\";}i:5;a:3:{s:5:\"title\";s:9:\"hostname1\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>1\', \'display\')\";}i:6;a:3:{s:5:\"title\";s:16:\"opennetadmin.com\";s:4:\"type\";s:14:\"display_domain\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_domain\', \'domain_id=>1\', \'display\')\";}}}s:22:\"list_hosts_filter_form\";a:2:{s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:27:\"list_interfaces_filter_form\";a:2:{s:3:\"tab\";s:10:\"interfaces\";s:10:\"interfaces\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:24:\"list_subnets_filter_form\";a:2:{s:3:\"tab\";s:7:\"subnets\";s:7:\"subnets\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:28:\"list_hosts_by_ip_filter_form\";a:1:{s:3:\"tab\";s:11:\"hosts_by_ip\";}s:24:\"list_records_filter_form\";a:2:{s:3:\"tab\";s:7:\"records\";s:7:\"records\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:28:\"list_dhcp_server_filter_form\";a:2:{s:3:\"tab\";s:11:\"dhcp_server\";s:11:\"dhcp_server\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:23:\"list_subnet_filter_form\";a:1:{s:3:\"tab\";s:6:\"subnet\";}s:0:\"\";a:1:{s:11:\"dhcp_server\";a:2:{s:4:\"page\";N;s:6:\"filter\";N;}}}tz|i:0;window_position|a:14:{s:24:\"edit_dhcp_option_entry_x\";s:3:\"319\";s:24:\"edit_dhcp_option_entry_y\";s:3:\"195\";s:16:\"search_results_x\";s:3:\"313\";s:16:\"search_results_y\";s:3:\"102\";s:12:\"edit_block_x\";s:3:\"350\";s:12:\"edit_block_y\";s:3:\"341\";s:16:\"edit_interface_x\";s:3:\"312\";s:16:\"edit_interface_y\";s:2:\"99\";s:18:\"edit_dhcp_server_x\";s:3:\"149\";s:18:\"edit_dhcp_server_y\";s:3:\"310\";s:26:\"edit_dhcp_failover_group_x\";s:3:\"153\";s:26:\"edit_dhcp_failover_group_y\";s:3:\"358\";s:13:\"edit_record_x\";s:3:\"497\";s:13:\"edit_record_y\";s:3:\"116\";}'),('5c9022547b91bade8c377be65cca1e0c',1182882263,'redirect|s:22:\"http://server/ona.new/\";ona|a:5:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:0:\"\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:1:{i:0;a:3:{s:5:\"title\";s:9:\"hostname1\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>1\', \'display\')\";}}}s:27:\"list_interfaces_filter_form\";a:2:{s:3:\"tab\";s:10:\"interfaces\";s:10:\"interfaces\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:24:\"list_records_filter_form\";a:1:{s:3:\"tab\";s:7:\"records\";}}tz|i:0;window_position|a:2:{s:13:\"edit_record_x\";s:3:\"260\";s:13:\"edit_record_y\";s:3:\"187\";}'),('64bab55d94cb762c4c367ba507607444',1178942923,'redirect|s:36:\"h|ô*//hive.homeip.net:8800/ona.new/\";ona|a:5:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:3:{s:1:\"q\";a:10:{s:14:\"search_form_id\";s:16:\"host_search_form\";s:8:\"hostname\";s:0:\"\";s:6:\"domain\";s:0:\"\";s:3:\"mac\";s:0:\"\";s:2:\"ip\";s:0:\"\";s:7:\"ip_thru\";s:0:\"\";s:5:\"notes\";s:0:\"\";s:4:\"unit\";s:0:\"\";s:5:\"reset\";s:5:\"Clear\";s:6:\"search\";s:6:\"Search\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:1:{i:0;a:3:{s:5:\"title\";s:9:\"hostname1\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>1\', \'display\')\";}}}s:27:\"list_interfaces_filter_form\";a:1:{s:3:\"tab\";s:10:\"interfaces\";}s:0:\"\";a:1:{s:10:\"interfaces\";a:2:{s:4:\"page\";N;s:6:\"filter\";N;}}}tz|i:0;window_position|a:6:{s:16:\"search_results_x\";s:2:\"39\";s:16:\"search_results_y\";s:2:\"93\";s:17:\"app_admin_tools_x\";s:2:\"30\";s:17:\"app_admin_tools_y\";s:3:\"292\";s:13:\"edit_subnet_x\";s:3:\"436\";s:13:\"edit_subnet_y\";s:3:\"162\";}'),('6c2d8735efe8c0ecb449cdd3ba224bf1',1177111934,'redirect|s:18:\"http://blade1/ona/\";ona|a:7:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:0:\"\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:6:{i:0;a:3:{s:5:\"title\";s:9:\"hostname2\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>2\', \'display\')\";}i:1;a:3:{s:5:\"title\";s:9:\"hostname4\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:61:\"xajax_window_submit(\'display_host\', \'host_id=>11\', \'display\')\";}i:2;a:3:{s:5:\"title\";s:8:\"WOW-COOL\";s:4:\"type\";s:14:\"display_subnet\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_subnet\', \'subnet_id=>1\', \'display\')\";}i:3;a:3:{s:5:\"title\";s:16:\"opennetadmin.com\";s:4:\"type\";s:12:\"display_zone\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_zone\', \'zone_id=>1\', \'display\')\";}i:4;a:3:{s:5:\"title\";s:9:\"hostname1\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>1\', \'display\')\";}i:5;a:3:{s:5:\"title\";s:12:\"Map: 2.2.2.0\";s:4:\"type\";s:17:\"display_block_map\";s:3:\"url\";s:79:\"xajax_window_submit(\'display_block_map\', \'ip_block_start=>2.2.2.0\', \'display\');\";}}}s:22:\"list_hosts_filter_form\";a:2:{s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:27:\"list_interfaces_filter_form\";a:2:{s:3:\"tab\";s:10:\"interfaces\";s:10:\"interfaces\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:30:\"list_hosts_aliases_filter_form\";a:2:{s:3:\"tab\";s:7:\"aliases\";s:7:\"aliases\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:28:\"list_hosts_by_ip_filter_form\";a:1:{s:3:\"tab\";s:11:\"hosts_by_ip\";}}tz|i:0;ipdb|a:1:{s:22:\"list_hosts_filter_form\";a:1:{s:3:\"tab\";s:5:\"hosts\";}}window_position|a:2:{s:11:\"edit_host_x\";s:2:\"91\";s:11:\"edit_host_y\";s:2:\"93\";}'),('ae8a45e96301bdb7721dcf0340428d5c',1182900849,'redirect|s:4:\"ona/\";'),('af2c8d492173c9d0c7d0787044cebc4a',1182305699,'redirect|s:36:\"htÑ,//hive.homeip.net:8800/ona.new/\";ona|a:5:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:0:\"\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:1:{i:0;a:3:{s:5:\"title\";s:9:\"hostname1\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>1\', \'display\')\";}}}s:27:\"list_interfaces_filter_form\";a:1:{s:3:\"tab\";s:10:\"interfaces\";}s:24:\"list_records_filter_form\";a:2:{s:3:\"tab\";s:7:\"records\";s:7:\"records\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}}tz|i:0;window_position|a:2:{s:16:\"search_results_x\";s:1:\"4\";s:16:\"search_results_y\";s:3:\"132\";}'),('b24f573920d60e186b4b525c436ad54a',1176500023,'redirect|s:18:\"http://blade1/ona/\";ona|a:5:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:4:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:17:\"\\the cracked eggs\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}s:7:\"subnets\";a:3:{s:1:\"q\";a:1:{s:9:\"subnet_id\";s:1:\"1\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:7:{i:0;a:3:{s:5:\"title\";s:4:\"TEST\";s:4:\"type\";s:14:\"display_subnet\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_subnet\', \'subnet_id=>9\', \'display\')\";}i:1;a:3:{s:5:\"title\";s:8:\"polyglot\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:61:\"xajax_window_submit(\'display_host\', \'host_id=>12\', \'display\')\";}i:2;a:3:{s:5:\"title\";s:9:\"SOME-NAME\";s:4:\"type\";s:14:\"display_subnet\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_subnet\', \'subnet_id=>6\', \'display\')\";}i:3;a:3:{s:5:\"title\";s:9:\"hostname2\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>2\', \'display\')\";}i:4;a:3:{s:5:\"title\";s:8:\"VLAN-110\";s:4:\"type\";s:14:\"display_subnet\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_subnet\', \'subnet_id=>7\', \'display\')\";}i:5;a:3:{s:5:\"title\";s:9:\"hostname5\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:61:\"xajax_window_submit(\'display_host\', \'host_id=>11\', \'display\')\";}i:6;a:3:{s:5:\"title\";s:8:\"WOW-COOL\";s:4:\"type\";s:14:\"display_subnet\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_subnet\', \'subnet_id=>1\', \'display\')\";}}}s:27:\"list_interfaces_filter_form\";a:2:{s:3:\"tab\";s:10:\"interfaces\";s:10:\"interfaces\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:22:\"list_hosts_filter_form\";a:2:{s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}}tz|i:0;window_position|a:4:{s:11:\"edit_host_x\";s:3:\"733\";s:11:\"edit_host_y\";s:2:\"61\";s:16:\"search_results_x\";s:3:\"661\";s:16:\"search_results_y\";s:2:\"99\";}'),('ce4524ad743eb8e7d442aa1fb09b38de',1182698865,'redirect|s:36:\"ht¿L//hive.homeip.net:8800/ona.new/\";'),('ea96aaed94d7f6e77bcdcf22970daa3b',1183344292,'redirect|s:4:\"ona/\";ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}tz|i:0;'),('f465883fd258e884b104d83feef2442d',1177714047,'redirect|s:18:\"http://blade1/ona/\";ona|a:6:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:5:{i:0;a:3:{s:5:\"title\";s:14:\"albertsons.com\";s:4:\"type\";s:12:\"display_zone\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_zone\', \'zone_id=>2\', \'display\')\";}i:1;a:3:{s:5:\"title\";s:6:\"BZTEST\";s:4:\"type\";s:14:\"display_subnet\";s:3:\"url\";s:65:\"xajax_window_submit(\'display_subnet\', \'subnet_id=>18\', \'display\')\";}i:2;a:3:{s:5:\"title\";s:16:\"opennetadmin.com\";s:4:\"type\";s:14:\"display_domain\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_domain\', \'domain_id=>1\', \'display\')\";}i:3;a:3:{s:5:\"title\";s:19:\"purple.people.eater\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:61:\"xajax_window_submit(\'display_host\', \'host_id=>13\', \'display\')\";}i:4;a:3:{s:5:\"title\";s:14:\"albertsons.com\";s:4:\"type\";s:14:\"display_domain\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_domain\', \'domain_id=>2\', \'display\')\";}}}s:22:\"list_hosts_filter_form\";a:2:{s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:30:\"list_hosts_aliases_filter_form\";a:2:{s:3:\"tab\";s:7:\"aliases\";s:7:\"aliases\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:26:\"search_results_filter_form\";a:4:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:7:\"subnets\";a:3:{s:1:\"q\";a:1:{s:9:\"subnet_id\";s:2:\"18\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:19:\"purple.people.eater\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:27:\"list_interfaces_filter_form\";a:2:{s:3:\"tab\";s:10:\"interfaces\";s:10:\"interfaces\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}}tz|i:0;window_position|a:14:{s:22:\"app_device_type_list_x\";s:3:\"418\";s:22:\"app_device_type_list_y\";s:2:\"57\";s:23:\"app_device_model_list_x\";s:3:\"409\";s:23:\"app_device_model_list_y\";s:2:\"44\";s:22:\"app_device_role_list_x\";s:3:\"415\";s:22:\"app_device_role_list_y\";s:2:\"63\";s:22:\"app_device_role_edit_x\";s:3:\"556\";s:22:\"app_device_role_edit_y\";s:3:\"202\";s:15:\"app_zone_list_x\";s:3:\"348\";s:15:\"app_zone_list_y\";s:3:\"143\";s:11:\"edit_zone_x\";s:3:\"706\";s:11:\"edit_zone_y\";s:3:\"119\";s:13:\"edit_domain_x\";s:3:\"503\";s:13:\"edit_domain_y\";s:2:\"86\";}');
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `subnet_types` WRITE;
/*!40000 ALTER TABLE `subnet_types` DISABLE KEYS */;
INSERT INTO `subnet_types` VALUES (1,'loopback','Loopback','Loopback Interfaces (mostly for routers)'),(2,'','WAN',''),(7,'','VLAN',''),(8,'man','MAN','Not sure what this is..'),(9,'','VSAT',''),(10,'p2p','Point-to-Point',''),(11,'','VPN',''),(12,'','Wireless LAN','');
/*!40000 ALTER TABLE `subnet_types` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `subnets` WRITE;
/*!40000 ALTER TABLE `subnets` DISABLE KEYS */;
INSERT INTO `subnets` VALUES (1,0,8,1,33686016,4294967040,'WOW-COOL'),(2,0,8,0,50463232,4294967040,'DUH'),(3,0,2,0,67372032,4294967040,'MORE'),(4,0,1,0,16909056,4294967040,'DUDE'),(5,0,1,0,16908800,4294967040,'YEAH-RIGHT'),(6,0,7,0,167837696,4294967040,'SOME-NAME'),(7,0,7,0,167866880,4294967040,'VLAN-110'),(8,0,8,2,151584768,4294950912,'TEST9DOT'),(9,0,12,0,16845312,4294967040,'TEST'),(17,0,2,0,3232236032,4294967040,'PAULK-TEST'),(18,0,11,0,3232235776,4294967040,'BZTEST'),(19,0,11,0,3232236288,4294967168,'BZTEST2');
/*!40000 ALTER TABLE `subnets` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `vlan_campuses` WRITE;
/*!40000 ALTER TABLE `vlan_campuses` DISABLE KEYS */;
INSERT INTO `vlan_campuses` VALUES (1,'BOISE'),(2,'NAMPA');
/*!40000 ALTER TABLE `vlan_campuses` ENABLE KEYS */;
UNLOCK TABLES;

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

LOCK TABLES `vlans` WRITE;
/*!40000 ALTER TABLE `vlans` DISABLE KEYS */;
INSERT INTO `vlans` VALUES (1,1,'DEFAULT',1),(2,1,'DESKTOPS',4);
/*!40000 ALTER TABLE `vlans` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2007-06-30  3:02:18
