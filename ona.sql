-- MySQL dump 10.10
--
-- Host: localhost    Database: ona
-- ------------------------------------------------------
-- Server version	5.0.24a-Debian_9-log

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
  `name` varchar(63) NOT NULL,
  `ip_addr` int(10) unsigned NOT NULL,
  `ip_mask` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores CIDR block information';

--
-- Dumping data for table `blocks`
--


/*!40000 ALTER TABLE `blocks` DISABLE KEYS */;
LOCK TABLES `blocks` WRITE;
INSERT INTO `blocks` VALUES (1,'TEST_BLOCK',33686016,4294967040),(2,'BLOCK2',167866880,4294967040);
UNLOCK TABLES;
/*!40000 ALTER TABLE `blocks` ENABLE KEYS */;

--
-- Table structure for table `contexts`
--

DROP TABLE IF EXISTS `contexts`;
CREATE TABLE `contexts` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(63) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Universe containers';

--
-- Dumping data for table `contexts`
--


/*!40000 ALTER TABLE `contexts` DISABLE KEYS */;
LOCK TABLES `contexts` WRITE;
INSERT INTO `contexts` VALUES (1,'DEFAULT','Default');
UNLOCK TABLES;
/*!40000 ALTER TABLE `contexts` ENABLE KEYS */;

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
INSERT INTO `dcm_module_list` VALUES (1,'get_file','Returns contents of a file','inc_get_file.php'),(2,'get_module_list','Returns the list of available modules','inc_get_module_list.php'),(5,'alias_add','Add a new alias','inc_ona_alias.php'),(6,'alias_del','Delete an existing alias','inc_ona_alias.php'),(7,'alias_modify','Modify an existing alias','inc_ona_alias.php'),(8,'alias_move','Move an alias pointer','inc_ona_alias.php'),(9,'host_add','Add a new host/device','inc_ona_host.php'),(10,'host_del','Delete a host/device','inc_ona_host.php'),(11,'host_display','Display a host record','inc_ona_host.php'),(14,'alias_display','Display an alias record','inc_ona_alias.php'),(15,'interface_display','Display an interface record','inc_ona_interface.php'),(16,'interface_del','Delete an interface record','inc_ona_interface.php'),(19,'config_display','Display a config text record','inc_ona_config.php'),(20,'config_chksum','Display a config text\'s chksum','inc_ona_config.php'),(21,'config_add','Add a config text record','inc_ona_config.php'),(22,'db_template_add','Add a template to the DB','inc_db_template.php'),(23,'db_template_display','Display a template record','inc_db_template.php'),(24,'db_template_del','Delete a template record','inc_db_template.php'),(25,'interface_add','Add an interface record','inc_ona_interface.php'),(26,'named_build_conf','Build a bind conf file','inc_ona_dns.php'),(27,'dhcp_build_conf','Build configuration for dhcpd','inc_ona_dhcp.php'),(28,'ona_sql','Run a SQL query','inc_ona_sql.php'),(29,'ona_context_display','Display valid ONA contexts','inc_ona_context.php'),(30,'interface_modify','Modify an interface record','inc_ona_interface.php'),(31,'subnet_display','Display a subnet record','inc_ona_subnet.php'),(32,'host_modify','Modify a host record','inc_ona_host.php'),(33,'subnet_add','Add a subnet record','inc_ona_subnet.php'),(34,'subnet_modify','Modify a subnet record','inc_ona_subnet.php'),(35,'interface_move','Move interfaces to a new subnet','inc_ona_interface.php'),(36,'get_retail_ios_config_parm','Builds retail ios key=value parm file for config generation','inc_get_retail_ios_config_parm.php'),(37,'db_template_list','Finds and lists name and description of templates in the database','inc_db_template.php'),(38,'switchport_display','Display CIM related switchport details','inc_ipdb_switchport_admin.php'),(39,'host_infobit_add','Adds a classification (infobit) to a host','inc_ipdb_host_infobit.php'),(40,'host_infobit_display','Display infobits on a host','inc_ipdb_host_infobit.php'),(41,'host_infobit_modify','Modify an existing host infobit','inc_ipdb_host_infobit.php'),(42,'host_infobit_del','Deletes a host infobit from a host','inc_ipdb_host_infobit.php'),(43,'dhcp_entry_display','Display DHCP entries','inc_ipdb_dhcp_entry.php'),(44,'dhcp_entry_add','Adds a DHCP entry to specified host, server, or network','inc_ipdb_dhcp_entry.php'),(45,'dhcp_entry_del','Deletes a dhcp entry from the specified host, network, or server','inc_ipdb_dhcp_entry.php'),(46,'dhcp_entry_modify','Updates a dhcp entry','inc_ipdb_dhcp_entry.php'),(47,'zone_add','Adds a new DNS zone','inc_ipdb_zone.php'),(48,'zone_display','Display DNS zone information','inc_ipdb_zone.php'),(49,'zone_del','Delete a DNS zone','inc_ipdb_zone.php'),(50,'zone_modify','Modify an existing zone entry','inc_ipdb_zone.php'),(51,'zone_server_add','Adds an association of a zone to a server','inc_ipdb_zone.php'),(52,'zone_server_del','Removes association of a zone from a server','inc_ipdb_zone.php'),(53,'subnet_del','Delete a subnet (network) record','inc_ona_subnet.php'),(56,'dhcp_failover_group_add','Adds a DHCP failover group to the database','inc_ipdb_dhcp_failover.php'),(57,'dhcp_failover_group_del','Deletes a DHCP failover group from the database','inc_ipdb_dhcp_failover.php'),(58,'dhcp_failover_group_modify','Modify a DHCP failover group','inc_ipdb_dhcp_failover.php'),(59,'dhcp_failover_group_display','Display DHCP failover group details','inc_ipdb_dhcp_failover.php'),(60,'dhcp_pool_add','Add a DHCP pool','inc_ipdb_dhcp_pool.php'),(61,'dhcp_pool_del','Delete a DHCP pool','inc_ipdb_dhcp_pool.php'),(62,'dhcp_pool_modify','Modify a DHCP pool','inc_ipdb_dhcp_pool.php'),(63,'dhcp_lease_add','Adds a lease entry to our own lease tracking table','inc_ipdb_dhcp_pool.php'),(64,'dhcp_lease_del','Removes a lease entry from our own tracking table','inc_ipdb_dhcp_pool.php'),(65,'dhcp_server_add','Add an existing network to a DHCP server','inc_ipdb_dhcp_server.php'),(66,'dhcp_server_del','Remove an existing network from a DHCP server','inc_ipdb_dhcp_server.php'),(67,'vlan_add','adds a new vlan','inc_ipdb_vlan.php'),(68,'vlan_del','Removes a VLAN from the database','inc_ipdb_vlan.php'),(69,'vlan_modify','Make changes to VLAN entries','inc_ipdb_vlan.php'),(70,'vlan_campus_add','Add a new VLAN campus','inc_ipdb_vlan_campus.php'),(71,'vlan_campus_del','Delete a VLAN campus','inc_ipdb_vlan_campus.php'),(72,'vlan_campus_modify','Change a VLAN campus','inc_ipdb_vlan_campus.php'),(73,'block_add','Add an address block','inc_ipdb_block.php'),(74,'block_del','Delete an address block','inc_ipdb_block.php'),(75,'block_modify','Modify an address block','inc_ipdb_block.php');
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
-- Table structure for table `dns_a`
--

DROP TABLE IF EXISTS `dns_a`;
CREATE TABLE `dns_a` (
  `id` int(10) unsigned NOT NULL,
  `context_id` int(10) unsigned NOT NULL,
  `domain_id` int(10) unsigned NOT NULL,
  `interface_id` int(10) unsigned NOT NULL,
  `ttl` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'verify/set length',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='No description needed.  Interface_ID is where it gets the IP';

--
-- Dumping data for table `dns_a`
--


/*!40000 ALTER TABLE `dns_a` DISABLE KEYS */;
LOCK TABLES `dns_a` WRITE;
INSERT INTO `dns_a` VALUES (1,1,1,1,3600,'hostname1');
UNLOCK TABLES;
/*!40000 ALTER TABLE `dns_a` ENABLE KEYS */;

--
-- Table structure for table `domains`
--

DROP TABLE IF EXISTS `domains`;
CREATE TABLE `domains` (
  `id` int(10) unsigned NOT NULL,
  `context_id` int(10) unsigned NOT NULL,
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


/*!40000 ALTER TABLE `domains` DISABLE KEYS */;
LOCK TABLES `domains` WRITE;
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
  `parent_id` int(10) unsigned NOT NULL,
  `primary_dns_a_id` int(10) unsigned NOT NULL,
  `model_id` int(10) unsigned NOT NULL,
  `location_id` int(10) unsigned NOT NULL,
  `notes` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Host / device definitions';

--
-- Dumping data for table `hosts`
--


/*!40000 ALTER TABLE `hosts` DISABLE KEYS */;
LOCK TABLES `hosts` WRITE;
INSERT INTO `hosts` VALUES (1,0,0,1,0,'testing'),(2,0,0,1,1,'more notes');
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
  `name` varchar(127) NOT NULL,
  `description` varchar(64) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='IP addresses and other host interface data';

--
-- Dumping data for table `interfaces`
--


/*!40000 ALTER TABLE `interfaces` DISABLE KEYS */;
LOCK TABLES `interfaces` WRITE;
INSERT INTO `interfaces` VALUES (1,1,1,33686018,'001122334455','test',NULL),(2,8,1,151587081,'','',NULL);
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
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `manufacturers`
--


/*!40000 ALTER TABLE `manufacturers` DISABLE KEYS */;
LOCK TABLES `manufacturers` WRITE;
INSERT INTO `manufacturers` VALUES (1,'Cisco'),(2,'Juniper'),(3,'Unknown'),(4,'Hewlet Packard');
UNLOCK TABLES;
/*!40000 ALTER TABLE `manufacturers` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `models` DISABLE KEYS */;
LOCK TABLES `models` WRITE;
INSERT INTO `models` VALUES (1,1,'2821'),(2,4,'dv9000t');
UNLOCK TABLES;
/*!40000 ALTER TABLE `models` ENABLE KEYS */;

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


/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
LOCK TABLES `roles` WRITE;
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
INSERT INTO `sequences` VALUES ('contexts',1),('subnets',10),('subnet_types',13);
UNLOCK TABLES;
/*!40000 ALTER TABLE `sequences` ENABLE KEYS */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `sesskey` varchar(70) NOT NULL,
  `expiry` int(15) unsigned NOT NULL,
  `sessvalue` varchar(100) NOT NULL,
  PRIMARY KEY  (`sesskey`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='session tracking';

--
-- Dumping data for table `sessions`
--


/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
LOCK TABLES `sessions` WRITE;
INSERT INTO `sessions` VALUES ('00684fa5140732ea9ef672a27a5a1692',1164829731,'ona|a:1:{s:26:\"search_results_filter_form\";a:1:{s:3:\"tab\";s:7:\"subnets\";}}window_position|a:2:{s:16:'),('00e8bf9f877e8ca59982c9151569f4d3',1163219625,'ona|a:1:{s:26:\"search_results_filter_form\";a:1:{s:3:\"tab\";s:5:\"hosts\";}}window_position|N;'),('01fced1a2856f4b00a1e85e59f19f86f',1159838647,''),('065ecb88dae29a3b2f846ed8c9ba67a5',1162361878,'window_position|a:2:{s:16:\"search_results_x\";s:3:\"618\";s:16:\"search_results_y\";s:2:\"38\";}'),('0730429c82ca9deee07751e0ec3ba12e',1159838715,'ona|a:1:{s:26:\"search_results_filter_form\";a:1:{s:3:\"tab\";s:7:\"subnets\";}}'),('0824864f4786a1733b0cc6d10451f129',1163919797,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('0b23c4bf7256f91b19d1b7abc7e2d2c2',1159838724,''),('0c4551af88ecf9237c790ccfac425a34',1163544476,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('0ed1046f7699fac6fe801beabfbca565',1163733047,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('12ced32e4fe9b3ae3ea52416b3f88344',1164373761,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('150fc483105ffa7dca8ec3f4e8d8e96d',1165941867,'ona|N;'),('15cf5a0159e4076a9bb08ed9c30f08d2',1163440390,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('186226424ef66b3bf35895c55c7a087a',1159838753,'window_position|a:2:{s:16:\"search_results_x\";s:3:\"311\";s:16:\"search_results_y\";s:3:\"198\";}'),('1890be8757a2097a0de2521e7d5da83a',1166576986,''),('1a3abf5f89d14d20daffbf3ab460d967',1165296846,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('1ef4e3ed65c65fcbec9169e573fa1492',1162244632,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('2222aa5beb71ae0f71488addea909a1a',1159838634,'ona|a:2:{s:10:\"work_space\";a:1:{s:7:\"history\";N;}s:22:\"list_hosts_filter_form\";a:1:{s:3:\"tab\";s:5:\"h'),('23a0163befe6970b025e2edec0a40670',1166499584,''),('2e5a4c9c078f76712058343e7788469d',1159838719,''),('2fd1f690614c78527eaaf95b89fabde4',1159838588,'ona|a:1:{s:26:\"search_results_filter_form\";a:1:{s:0:\"\";a:2:{s:4:\"page\";s:1:\"1\";s:6:\"filter\";s:0:\"\";}'),('341e02d2fe9761546e6fec50af4c8db8',1163218548,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";N;}}'),('35a40d58a3612d3b2bf36cb12adef24b',1162422706,'ona|N;window_position|a:2:{s:16:\"search_results_x\";s:3:\"484\";s:16:\"search_results_y\";s:2:\"90\";}'),('36099fb50b1c0228aab242f91fb5f72f',1165443805,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('3bea96c5b2c3491d74703c0424c92b3b',1164017933,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('3d194c32a09f0808886c1e65851b3a32',1162359053,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('3d322e2d365fa5506ccaa56e0dd886e8',1159838763,''),('3d514c66bb5d861bd8659579fb811f1d',1159838629,''),('428ef6082c46fa9baf9371967ccb1823',1164628785,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('44295cc3fa3308f12963bd61ddca2c07',1159838605,''),('44dd2affbd2fd7e9ab2526efca1f5d9e',1161206544,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('45214e466582a8d286c2ed535e2b0f3b',1164675217,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}window_po'),('45f2b33d9ba6d34be475c4af6eb85c1d',1166576902,''),('464551a610b3d2dc985faa0c2d5d608f',1161316141,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}window_po'),('4722b3490cac05c600cb1f73287f44bd',1164756281,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('474d8baa8c6b5f1932c28306e2ba64f2',1159838722,'ona|a:1:{s:26:\"search_results_filter_form\";a:1:{s:3:\"tab\";s:5:\"hosts\";}}'),('475a16bd2a08633b9462b00739623ace',1163574198,'window_position|a:2:{s:21:\"app_advanced_search_x\";s:3:\"407\";s:21:\"app_advanced_search_y\";s:2:\"26\";}'),('486bff059239b25f3e011d3e801f2020',1159838767,''),('4cba83e02494027f6f69054bfb0f425c',1166577149,''),('4cbf9eb0ea97c4c448fe17af12d252c7',1165738968,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('4de9a9eaa930805edb52f550b6337b1c',1159838722,''),('5375df7e9282bae1b457a9934097d6ee',1159838594,'ona|a:2:{s:10:\"work_space\";a:1:{s:7:\"history\";N;}s:22:\"list_hosts_filter_form\";a:1:{s:3:\"tab\";s:5:\"h'),('57b707ccdd1e2d9e45461193e4462e49',1163570034,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('57c4cbe12237351e12adedfba07b6218',1159838703,''),('5927abec7b7eab8bc1c49c35a9f58d31',1163336878,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('5a51ca55cfcec01dadc9dea142b18bfe',1160063041,'window_position|N;ona|N;'),('5d973443e516f018b6c50f6837db3b99',1166499744,''),('5e3d314b3820abf13ad965731cab31ed',1164501245,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('6001856f921078909dad9a20fedabe5e',1165016829,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('616351b6c67a837a003b2ee868f68f1f',1165593488,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('61bbe28be1a655a9db4985364ec907ec',1159838574,''),('63ee14d5f724d32851faf2c70249b148',1161923464,'ona|N;'),('651400f765c289f46fdfa037f9c8b14e',1165864278,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";N;}}'),('655bf9abe80e1d69838022327f7c1a65',1163232185,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('6609a81eeb3e65492b35f3e405628776',1165865640,'ona|a:2:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:10:\"work'),('67c7ab5971428dfb0501c7956412a670',1161747074,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('6a2e3f2ad87d0605dc68d3bfd96baa7d',1165468500,'ona|N;window_position|N;'),('6ca0493f77d006b6d564640a7ae3e36b',1164675151,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";N;}}window_position|a:2:{s:21:\"app_advanced_search_x\";'),('6d1c1cbf1cdb23eb6175e11fd0fa6aa3',1165153283,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('6e5717358b1348ca9d8d79449bb1edd9',1159838658,''),('6f01265f4cb1db9c1d17a42ee79fdbff',1163622787,'ona|N;window_position|a:2:{s:16:\"search_results_x\";s:3:\"194\";s:16:\"search_results_y\";s:2:\"86\";}'),('75ec42814c91366ce86764756ea57d62',1159838568,''),('764444c6cfca2bbf5114b89e1b255feb',1168457369,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";a:0:{}}}window_position|a:2:{s:16:\"search_results_x\";s'),('776822ce22fa82b9dcad8050c50eac8e',1164244234,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('77ebb904d41dbaa491f2da207781b9ec',1164777352,'window_position|a:4:{s:16:\"search_results_x\";s:3:\"238\";s:16:\"search_results_y\";s:2:\"91\";s:21:\"app_ad'),('78378ffb5c9b38aeb70a7e373fbd08df',1159838620,'auth|a:0:{}'),('79420b80b2cadc8f966320b5b9ddca56',1159838719,'ona|a:1:{s:26:\"search_results_filter_form\";a:1:{s:3:\"tab\";s:7:\"subnets\";}}'),('8214d95dd5a71b2e708a579d11a6d9e0',1162406436,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";N;}}'),('884eb3e8bd89d4d8b896fc1ae5dd0756',1163824700,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('8b11b8edc17bce820e34ee8a3f7f90c4',1159838608,''),('8ddbb0568a2d7ccb7f5ea22bf2fa9a2e',1163638409,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('913431c1639a40c948ceb8cdac480ae1',1166576855,''),('91c1ba6e84733017009580e392812063',1165882595,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";a:0:{}}}'),('91fdf303aa519ee2cff6454299db71ec',1163660547,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('93814f34f92535c8f82c786195239589',1163725441,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('97fe5511d2f68113a84fa338484c18f9',1161745632,'ona|a:1:{s:4:\"auth\";a:3:{s:4:\"user\";a:0:{}s:6:\"groups\";a:0:{}s:5:\"perms\";a:0:{}}}window_position|N;'),('9ddf9c18ddc6c90a5d12d2afe6093916',1164782518,'window_position|a:2:{s:16:\"search_results_x\";s:3:\"498\";s:16:\"search_results_y\";s:3:\"230\";}ona|N;auth'),('a11a627cddc8bd6e6ac988c500c88d9a',1163820846,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('a366a335c3d3a24fae75c5258f7bc842',1159838741,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";a:1:{i:0;a:3:{s:5:\"title\";s:17:\"display_block_map\";s:4'),('a45f84c92253d8cf5eb6bfd7bb2bec66',1159838568,'ona|a:2:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}s:10:\"work'),('a5e5b6f6ae4cc2be3b08dc7fbf9f8701',1159838633,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";a:1:{i:0;a:3:{s:5:\"title\";s:14:\"display_subnet\";s:4:\"t'),('a89c68eb2e455dff79a1999d8e7c6f75',1159838610,''),('a9e3ff1bf98b04a42af852c2cd70d5fb',1159838715,''),('aa4d6afad44330ce458effb4d0fb8ead',1163605734,'ona|a:1:{s:26:\"search_results_filter_form\";a:1:{s:3:\"tab\";s:7:\"subnets\";}}window_position|N;auth|a:0'),('adbf8070d60ca2b29806a615d081e46f',1160697071,'ona|N;window_position|a:2:{s:16:\"search_results_x\";s:3:\"255\";s:16:\"search_results_y\";s:2:\"44\";}'),('af1619158369c0dcf235b87669e1d468',1159838739,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";a:1:{i:0;a:3:{s:5:\"title\";s:17:\"display_block_map\";s:4'),('baba91e5bfff098e4c45a7e91895f705',1163538648,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";N;}}auth|a:0:{}'),('bced05b1b360d014942c2b77084aef64',1159838605,''),('c0346574cfd104b4abe94692a3717e75',1165819479,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('c07e7a340d6321389c2350d63baa18ab',1159838724,'ona|a:1:{s:26:\"search_results_filter_form\";a:1:{s:3:\"tab\";s:7:\"subnets\";}}'),('c1f00cd341574dddfdb27cc8ab4eb6ae',1162423200,'ona|N;'),('c5a7944200eb5f14e2cc71e13ce6dc7d',1164888336,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('c74cdf3eb4c92a979e16bab28b9e9689',1164738683,'ona|N;window_position|a:2:{s:16:\"search_results_x\";s:3:\"278\";s:16:\"search_results_y\";s:3:\"125\";}'),('c7cc63ceb8931448d57f20c296663dd1',1159838741,''),('c7dabe7f10473a8917d853f57351a66b',1164411108,'ona|N;window_position|N;'),('c9c77bdada80850a57f0246221eddfc0',1166331367,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";a:0:{}}}'),('c9fef441f3235d4bf40a14dbc412c05f',1164952210,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";N;}}'),('cba42453f9aab5cdc3d773470cc54d68',1161210163,'ona|N;'),('ce059e672ae49657ea3c93ad99971bb2',1159838603,''),('cec08883cd7a3666a6fd14f04a2c0696',1166499832,''),('d0878d4f6010844352b9cf5940897908',1159838734,''),('d543013bdb7cf1882312163d345f1b5f',1159838594,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";a:1:{i:0;a:3:{s:5:\"title\";s:14:\"display_subnet\";s:4:\"t'),('daf12bf30960390fc332fe54217a243b',1159991826,'ona|a:1:{s:10:\"work_space\";a:1:{s:7:\"history\";N;}}'),('deb2f6aff6313afb229af361c4a5781c',1166576924,''),('e3124a0a50ac576e7b7f364d22da3549',1159838588,'ona|a:1:{s:26:\"search_results_filter_form\";a:3:{s:10:\"content_id\";s:19:\"search_results_list\";s:3:\"ta'),('e6139d8b0437f8f194f8509bbef2958e',1159838731,''),('e73dfeb48ba9762e7ec74a0c7658040b',1159628679,'ona|N;window_position|N;'),('e7c749d5896187c0180d4c8601f0fe23',1164119995,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('f19d419478066aec84581f4c6399c4dc',1160711751,'ona|N;auth|a:0:{}'),('f47e10e13efc461d06cc932616fbf21a',1165950784,'ona|N;'),('fa31b08d9f42b554a95a71060124e09a',1159838739,''),('fad5f78b27cc3e11067eb2e4a4b37301',1159838651,''),('fb5982ff5ac6a0b016f5e4ea423bd2d8',1160802617,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}'),('fcfca7db3d02cf88f7729c0510334458',1159838643,''),('fe4bc39ccdbed9a4e099ba855b62571c',1159838776,'window_position|a:2:{s:16:\"search_results_x\";s:3:\"302\";s:16:\"search_results_y\";s:3:\"191\";}'),('fe93f249920a4715b1523a9d8c040fae',1161923359,'ona|a:1:{s:4:\"auth\";a:1:{s:4:\"user\";a:2:{s:8:\"username\";s:5:\"guest\";s:5:\"level\";s:1:\"0\";}}}');
UNLOCK TABLES;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;

--
-- Table structure for table `subnet_types`
--

DROP TABLE IF EXISTS `subnet_types`;
CREATE TABLE `subnet_types` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(127) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `subnet_types`
--


/*!40000 ALTER TABLE `subnet_types` DISABLE KEYS */;
LOCK TABLES `subnet_types` WRITE;
INSERT INTO `subnet_types` VALUES (1,'Loopback'),(2,'WAN'),(7,'VLAN'),(8,'MAN'),(9,'VSAT'),(10,'Point-to-Point'),(11,'VPN'),(12,'Wireless LAN');
UNLOCK TABLES;
/*!40000 ALTER TABLE `subnet_types` ENABLE KEYS */;

--
-- Table structure for table `subnets`
--

DROP TABLE IF EXISTS `subnets`;
CREATE TABLE `subnets` (
  `id` int(10) unsigned NOT NULL,
  `context_id` int(10) unsigned NOT NULL,
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
INSERT INTO `subnets` VALUES (0,0,0,0,0,16843008,4294967040,'TEST'),(1,0,0,8,1,33686016,4294967040,'WOW-COOL'),(2,0,0,8,0,50463232,4294967040,'DUH'),(3,0,0,2,0,67372032,4294967040,'MORE'),(4,0,0,1,0,16909056,4294967040,'DUDE'),(5,0,0,1,0,16908800,4294967040,'WLEJRWER'),(6,0,0,7,0,167837696,4294967040,'SOME-NAME'),(7,0,0,7,0,167866880,4294967040,'VLAN-110'),(8,0,0,1,0,151587080,4294967292,'TEST9DOT');
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
INSERT INTO `vlan_campuses` VALUES (1,'BOISE'),(2,'NAMPA');
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

