-- MySQL dump 10.10
--
-- Host: blade2    Database: ona
-- ------------------------------------------------------
-- Server version	5.0.24a-log

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


/*!40000 ALTER TABLE `blocks` DISABLE KEYS */;
LOCK TABLES `blocks` WRITE;
INSERT INTO `blocks` VALUES (1,33686016,4294967040,'',''),(2,167866880,4294967040,'','');
UNLOCK TABLES;
/*!40000 ALTER TABLE `blocks` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `dcm_module_list` DISABLE KEYS */;
LOCK TABLES `dcm_module_list` WRITE;
INSERT INTO `dcm_module_list` VALUES (0,'domain_display','Displays an existing domain','ona/domain.inc.php'),(1,'get_module_list','Returns the list of available modules','get_module_list.inc.php'),(2,'mangle_ip','Converts between various IP address representations','mangle.inc.php'),(3,'mysql_purge_logs','Purges unused replication logs on MySQL masters','mysql_purge_logs.inc.php'),(4,'subnet_add','Add a new subnet','ona/subnet.inc.php'),(5,'subnet_modify','Modify an existing subnet','ona/subnet.inc.php'),(6,'subnet_del','Delete an existing subnet','ona/subnet.inc.php'),(7,'subnet_display','Display an existing subnet','ona/subnet.inc.php'),(8,'host_add','Add a new host','ona/host.inc.php'),(9,'host_display','Display an existing host','ona/host.inc.php'),(10,'host_modify','Modify an existing host','ona/host.inc.php'),(11,'host_del','Delete an existing host','ona/host.inc.php'),(12,'interface_add','Add an interface to an existing host','ona/interface.inc.php'),(13,'interface_modify','Modify an existing interface','ona/interface.inc.php'),(14,'interface_del','Delete an existing interface','ona/interface.inc.php'),(15,'interface_display','Displays details of an existing interface','ona/interface.inc.php'),(16,'interface_move','Move an interface from one subnet to another','ona/interface.inc.php'),(17,'domain_add','Adds a domain entry into the IP database','ona/domain.inc.php'),(18,'domain_del','Deletes a domain from the IP database','ona/domain.inc.php'),(19,'domain_modify','Updates an domain record in the IP database','ona/domain.inc.php');
UNLOCK TABLES;
/*!40000 ALTER TABLE `dcm_module_list` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `defaults` DISABLE KEYS */;
LOCK TABLES `defaults` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `defaults` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `device_types` DISABLE KEYS */;
LOCK TABLES `device_types` WRITE;
INSERT INTO `device_types` VALUES (1,1,1),(2,9,6),(3,3,2),(4,4,2),(5,5,3);
UNLOCK TABLES;
/*!40000 ALTER TABLE `device_types` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `devices` DISABLE KEYS */;
LOCK TABLES `devices` WRITE;
INSERT INTO `devices` VALUES (1,1,1,'FQGHX','123456'),(2,1,1,'UYEJK','54321'),(3,2,1,'PTRML','000001'),(4,4,1,'EJCSA','561345'),(5,5,1,'ITRHC','99822'),(6,3,1,'YTMIR','876543');
UNLOCK TABLES;
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `dns` DISABLE KEYS */;
LOCK TABLES `dns` WRITE;
INSERT INTO `dns` VALUES (1,1,1,0,'A',3600,'hostname1'),(2,1,3,0,'A',3600,'hostname2'),(10,2,0,0,'A',3600,'hostname3'),(11,1,0,0,'A',3600,'hostname4'),(12,2,0,0,'A',3600,'polyglot'),(13,1,0,0,'A',3600,'purple.people.eater'),(14,1,0,0,'A',3600,'hostname1'),(15,1,0,1,'CNAME',28800,'hostnamex'),(16,2,0,13,'CNAME',14400,'another.alias');
UNLOCK TABLES;
/*!40000 ALTER TABLE `dns` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `domains` DISABLE KEYS */;
LOCK TABLES `domains` WRITE;
INSERT INTO `domains` VALUES (1,0,'2007-04-02 22:10:46','0000-00-00 00:00:00','                                ',3600,3600,3600,3600,'ns1.test.com','','opennetadmin.com'),(2,0,'2007-04-25 22:46:33','0000-00-00 00:00:00','                                ',5400,3600,3600,3600,'purple.people.eater.opennetadmin.com','admin@albertsons.com','albertsons.com');
UNLOCK TABLES;
/*!40000 ALTER TABLE `domains` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `host_roles` DISABLE KEYS */;
LOCK TABLES `host_roles` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `host_roles` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `hosts` DISABLE KEYS */;
LOCK TABLES `hosts` WRITE;
INSERT INTO `hosts` VALUES (1,0,1,1,'testing again'),(2,0,2,2,'more notes'),(10,0,10,3,''),(11,0,11,4,'hostname 4 test'),(12,0,12,5,'This is the primary polyglot database server'),(13,0,13,6,'This one is yellow-bellied');
UNLOCK TABLES;
/*!40000 ALTER TABLE `hosts` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `interfaces` DISABLE KEYS */;
LOCK TABLES `interfaces` WRITE;
INSERT INTO `interfaces` VALUES (1,1,1,33686025,'001122334455','test',NULL),(2,8,1,151587081,'','',NULL),(4,18,2,3232235791,'0000DEADBEEF','Gi0/0','This is a test interface description field'),(5,4,2,16909058,'','FE1/12.2','WAN link to somewhere'),(6,1,2,33686019,'AABBCCDDEEFF','testing',''),(7,1,10,33686020,'003862F8EFDA','eth0',''),(8,18,10,3232235790,'','',''),(9,1,11,33686021,'80FE009F3B8C','',''),(10,1,12,33686023,'000EFE80A03D','sit0',''),(12,8,13,151587090,'8000FE2217ED','',''),(13,4,1,16909059,'00005F4380BB','ath0',''),(14,4,1,16909156,'','',''),(15,18,2,3232235792,'','virtual0','');
UNLOCK TABLES;
/*!40000 ALTER TABLE `interfaces` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
LOCK TABLES `locations` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `manufacturers` DISABLE KEYS */;
LOCK TABLES `manufacturers` WRITE;
INSERT INTO `manufacturers` VALUES (7,'Adtran'),(8,'Allied Telesyn'),(9,'Cabletron'),(1,'Cisco'),(5,'Dell'),(10,'Extreme Networks'),(4,'Hewlett Packard'),(6,'IBM'),(2,'Juniper'),(3,'Unknown');
UNLOCK TABLES;
/*!40000 ALTER TABLE `manufacturers` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `models` DISABLE KEYS */;
LOCK TABLES `models` WRITE;
INSERT INTO `models` VALUES (1,1,'2821',''),(2,4,'dv9000t',''),(3,4,'4000m',''),(4,4,'8000m',''),(5,4,'LJ5000',''),(6,1,'2948G-L3',''),(7,5,'Optiplex GS560',''),(8,9,'24TXM-GLS',''),(9,3,'GreaseMaster 1Billion',''),(10,6,'Netfinity 2232','');
UNLOCK TABLES;
/*!40000 ALTER TABLE `models` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
LOCK TABLES `roles` WRITE;
INSERT INTO `roles` VALUES (6,'fryer oil sensor'),(3,'printer'),(1,'router'),(4,'server'),(2,'switch'),(7,'wireless access point'),(5,'workstation');
UNLOCK TABLES;
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `sequences` DISABLE KEYS */;
LOCK TABLES `sequences` WRITE;
INSERT INTO `sequences` VALUES ('devices',7),('device_types',6),('dns',14),('hosts',14),('interfaces',16),('manufacturers',47),('models',11),('roles',11),('subnets',20),('subnet_types',13);
UNLOCK TABLES;
/*!40000 ALTER TABLE `sequences` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
LOCK TABLES `sessions` WRITE;
INSERT INTO `sessions` VALUES ('0bb42672848abc317cc077a5f3efe164',1178896607,'redirect|s:18:\"http://blade1/ona/\";ona|a:6:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:1:\"h\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:7:{i:0;a:3:{s:5:\"title\";s:9:\"hostname2\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>2\', \'display\')\";}i:1;a:3:{s:5:\"title\";s:9:\"hostname1\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>1\', \'display\')\";}i:2;a:3:{s:5:\"title\";s:9:\"hostname3\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:61:\"xajax_window_submit(\'display_host\', \'host_id=>10\', \'display\')\";}i:3;a:3:{s:5:\"title\";s:8:\"polyglot\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:61:\"xajax_window_submit(\'display_host\', \'host_id=>12\', \'display\')\";}i:4;a:3:{s:5:\"title\";s:19:\"purple.people.eater\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:61:\"xajax_window_submit(\'display_host\', \'host_id=>13\', \'display\')\";}i:5;a:3:{s:5:\"title\";s:14:\"albertsons.com\";s:4:\"type\";s:14:\"display_domain\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_domain\', \'domain_id=>2\', \'display\')\";}i:6;a:3:{s:5:\"title\";s:16:\"opennetadmin.com\";s:4:\"type\";s:14:\"display_domain\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_domain\', \'domain_id=>1\', \'display\')\";}}}s:22:\"list_hosts_filter_form\";a:2:{s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:24:\"list_records_filter_form\";a:2:{s:3:\"tab\";s:7:\"records\";s:7:\"records\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:27:\"list_interfaces_filter_form\";a:2:{s:3:\"tab\";s:10:\"interfaces\";s:10:\"interfaces\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}}tz|i:0;'),('6ae402b7bde6acee9e8b1269a1a4df39',1178455595,'redirect|s:18:\"http://blade1/ona/\";ona|a:7:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:11:\"vlan_campus\";s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:0:\"\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:7:{i:0;a:3:{s:5:\"title\";s:4:\"TEST\";s:4:\"type\";s:14:\"display_subnet\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_subnet\', \'subnet_id=>9\', \'display\')\";}i:1;a:3:{s:5:\"title\";s:9:\"hostname1\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>1\', \'display\')\";}i:2;a:3:{s:5:\"title\";s:14:\"albertsons.com\";s:4:\"type\";s:14:\"display_domain\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_domain\', \'domain_id=>2\', \'display\')\";}i:3;a:3:{s:5:\"title\";s:8:\"CALDWELL\";s:4:\"type\";s:19:\"display_vlan_campus\";s:3:\"url\";s:74:\"xajax_window_submit(\'display_vlan_campus\', \'vlan_campus_id=>4\', \'display\')\";}i:4;a:3:{s:5:\"title\";s:9:\"hostname3\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:61:\"xajax_window_submit(\'display_host\', \'host_id=>10\', \'display\')\";}i:5;a:3:{s:5:\"title\";s:5:\"BOISE\";s:4:\"type\";s:19:\"display_vlan_campus\";s:3:\"url\";s:74:\"xajax_window_submit(\'display_vlan_campus\', \'vlan_campus_id=>1\', \'display\')\";}i:6;a:3:{s:5:\"title\";s:8:\"DESKTOPS\";s:4:\"type\";s:12:\"display_vlan\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_vlan\', \'vlan_id=>2\', \'display\')\";}}}s:22:\"list_hosts_filter_form\";a:2:{s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:27:\"list_interfaces_filter_form\";a:2:{s:3:\"tab\";s:10:\"interfaces\";s:10:\"interfaces\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:22:\"list_vlans_filter_form\";a:2:{s:3:\"tab\";s:5:\"vlans\";s:5:\"vlans\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:24:\"list_subnets_filter_form\";a:2:{s:3:\"tab\";s:7:\"subnets\";s:7:\"subnets\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}}tz|i:0;window_position|a:8:{s:17:\"app_admin_tools_x\";s:3:\"781\";s:17:\"app_admin_tools_y\";s:3:\"505\";s:22:\"app_device_role_list_x\";s:3:\"969\";s:22:\"app_device_role_list_y\";s:3:\"505\";s:18:\"edit_vlan_campus_x\";s:3:\"114\";s:18:\"edit_vlan_campus_y\";s:3:\"215\";s:16:\"search_results_x\";s:3:\"276\";s:16:\"search_results_y\";s:3:\"159\";}'),('80834c25e8a3144e1e5a5d661054d353',1178119255,'redirect|s:18:\"http://blade1/ona/\";ona|a:4:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:9:\"hostname1\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:1:{i:0;a:3:{s:5:\"title\";s:9:\"hostname1\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:60:\"xajax_window_submit(\'display_host\', \'host_id=>1\', \'display\')\";}}}s:27:\"list_interfaces_filter_form\";a:2:{s:3:\"tab\";s:10:\"interfaces\";s:10:\"interfaces\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}}tz|i:0;'),('85e7492bc1f153dfb3a4f1c84f9c3f22',1178450532,'redirect|s:18:\"http://blade1/ona/\";ona|a:2:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:11:\"vlan_campus\";s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:0:\"\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}}tz|i:0;'),('d6406a3cae2da051a91017b0754ec46d',1178185086,'redirect|s:5:\"/ona/\";ona|a:5:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:9:\"hostname4\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:4:{i:0;a:3:{s:5:\"title\";s:16:\"opennetadmin.com\";s:4:\"type\";s:14:\"display_domain\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_domain\', \'domain_id=>1\', \'display\')\";}i:1;a:3:{s:5:\"title\";s:8:\"WOW-COOL\";s:4:\"type\";s:14:\"display_subnet\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_subnet\', \'subnet_id=>1\', \'display\')\";}i:2;a:3:{s:5:\"title\";s:14:\"albertsons.com\";s:4:\"type\";s:14:\"display_domain\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_domain\', \'domain_id=>2\', \'display\')\";}i:3;a:3:{s:5:\"title\";s:9:\"hostname4\";s:4:\"type\";s:12:\"display_host\";s:3:\"url\";s:61:\"xajax_window_submit(\'display_host\', \'host_id=>11\', \'display\')\";}}}s:27:\"list_interfaces_filter_form\";a:2:{s:3:\"tab\";s:10:\"interfaces\";s:10:\"interfaces\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:22:\"list_hosts_filter_form\";a:2:{s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}}tz|i:0;window_position|a:4:{s:13:\"edit_domain_x\";s:3:\"641\";s:13:\"edit_domain_y\";s:2:\"63\";s:17:\"app_admin_tools_x\";s:3:\"824\";s:17:\"app_admin_tools_y\";s:3:\"138\";}'),('f465883fd258e884b104d83feef2442d',1178101823,'redirect|s:18:\"http://blade1/ona/\";ona|a:5:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"tab\";s:7:\"subnets\";s:5:\"hosts\";a:3:{s:1:\"q\";a:1:{s:8:\"hostname\";s:14:\"albertsons.com\";}s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:10:\"work_space\";a:1:{s:7:\"history\";a:3:{i:0;a:3:{s:5:\"title\";s:8:\"WOW-COOL\";s:4:\"type\";s:14:\"display_subnet\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_subnet\', \'subnet_id=>1\', \'display\')\";}i:1;a:3:{s:5:\"title\";s:14:\"albertsons.com\";s:4:\"type\";s:14:\"display_domain\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_domain\', \'domain_id=>2\', \'display\')\";}i:2;a:3:{s:5:\"title\";s:16:\"opennetadmin.com\";s:4:\"type\";s:14:\"display_domain\";s:3:\"url\";s:64:\"xajax_window_submit(\'display_domain\', \'domain_id=>1\', \'display\')\";}}}s:22:\"list_hosts_filter_form\";a:2:{s:3:\"tab\";s:5:\"hosts\";s:5:\"hosts\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}s:30:\"list_hosts_aliases_filter_form\";a:2:{s:3:\"tab\";s:7:\"aliases\";s:7:\"aliases\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}}}tz|i:0;');
UNLOCK TABLES;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `subnet_types` DISABLE KEYS */;
LOCK TABLES `subnet_types` WRITE;
INSERT INTO `subnet_types` VALUES (1,'loopback','Loopback','Loopback Interfaces (mostly for routers)'),(2,'','WAN',''),(7,'','VLAN',''),(8,'man','MAN','Not sure what this is..'),(9,'','VSAT',''),(10,'p2p','Point-to-Point',''),(11,'','VPN',''),(12,'','Wireless LAN','');
UNLOCK TABLES;
/*!40000 ALTER TABLE `subnet_types` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `subnets` DISABLE KEYS */;
LOCK TABLES `subnets` WRITE;
INSERT INTO `subnets` VALUES (1,0,8,1,33686016,4294967040,'WOW-COOL'),(2,0,8,0,50463232,4294967040,'DUH'),(3,0,2,0,67372032,4294967040,'MORE'),(4,0,1,0,16909056,4294967040,'DUDE'),(5,0,1,0,16908800,4294967040,'YEAH-RIGHT'),(6,0,7,0,167837696,4294967040,'SOME-NAME'),(7,0,7,0,167866880,4294967040,'VLAN-110'),(8,0,8,2,151584768,4294950912,'TEST9DOT'),(9,0,12,0,16845312,4294967040,'TEST'),(17,0,2,0,3232236032,4294967040,'PAULK-TEST'),(18,0,11,0,3232235776,4294967040,'BZTEST'),(19,0,11,0,3232236288,4294967168,'BZTEST2');
UNLOCK TABLES;
/*!40000 ALTER TABLE `subnets` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `vlan_campuses` DISABLE KEYS */;
LOCK TABLES `vlan_campuses` WRITE;
INSERT INTO `vlan_campuses` VALUES (1,'BOISE'),(2,'NAMPA'),(3,'MERIDIAN'),(4,'CALDWELL');
UNLOCK TABLES;
/*!40000 ALTER TABLE `vlan_campuses` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `vlans` DISABLE KEYS */;
LOCK TABLES `vlans` WRITE;
INSERT INTO `vlans` VALUES (1,1,'DEFAULT',1),(2,1,'DESKTOPS',4);
UNLOCK TABLES;
/*!40000 ALTER TABLE `vlans` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

