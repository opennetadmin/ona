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
-- Dumping data for table `configuration_types`
--

LOCK TABLES `configuration_types` WRITE;
/*!40000 ALTER TABLE `configuration_types` DISABLE KEYS */;
INSERT INTO `configuration_types` VALUES (1,'IOS_CONFIG'),(2,'IOS_VERSION');
/*!40000 ALTER TABLE `configuration_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `dcm_module_list`
--

LOCK TABLES `dcm_module_list` WRITE;
/*!40000 ALTER TABLE `dcm_module_list` DISABLE KEYS */;
INSERT INTO `dcm_module_list` VALUES
(0,'domain_display','Displays an existing domain','ona/domain.inc.php'),
(1,'get_module_list','Returns the list of available modules','get_module_list.inc.php'),
(2,'mangle_ip','Converts between various IP address representations','mangle.inc.php'),
(3,'mysql_purge_logs','Purges unused replication logs on MySQL masters','mysql_purge_logs.inc.php'),
(4,'subnet_add','Add a new subnet','ona/subnet.inc.php'),
(5,'subnet_modify','Modify an existing subnet','ona/subnet.inc.php'),
(6,'subnet_del','Delete an existing subnet','ona/subnet.inc.php'),
(7,'subnet_display','Display an existing subnet','ona/subnet.inc.php'),
(8,'host_add','Add a new host','ona/host.inc.php'),
(9,'host_display','Display an existing host','ona/host.inc.php'),
(10,'host_modify','Modify an existing host','ona/host.inc.php'),
(11,'host_del','Delete an existing host','ona/host.inc.php'),
(12,'interface_add','Add an interface to an existing host','ona/interface.inc.php'),
(13,'interface_modify','Modify an existing interface','ona/interface.inc.php'),
(14,'interface_del','Delete an existing interface','ona/interface.inc.php'),
(15,'interface_display','Displays details of an existing interface','ona/interface.inc.php'),
(16,'interface_move','Move an interface from one subnet to another','ona/interface.inc.php'),
(17,'domain_add','Adds a domain entry into the IP database','ona/domain.inc.php'),
(18,'domain_del','Deletes a domain from the IP database','ona/domain.inc.php'),
(19,'domain_modify','Updates an domain record in the IP database','ona/domain.inc.php'),
(20,'dhcp_pool_add','Add DHCP pools','ona/dhcp_pool.inc.php'),
(21,'dhcp_pool_modify','Modify DHCP pools','ona/dhcp_pool.inc.php'),
(22,'dhcp_pool_del','Delete a DHCP pool','ona/dhcp_pool.inc.php'),
(23,'dhcp_entry_add','Add a DHCP option entry','ona/dhcp_entry.inc.php'),
(24,'dhcp_entry_del','Delete a DHCP option entry','ona/dhcp_entry.inc.php'),
(25,'dhcp_entry_modify','Modify DHCP option entry','ona/dhcp_entry.inc.php'),
(26,'message_add','Allows you to add a message to a subnet or host that will so in a display page','ona/message.inc.php'),
(27,'block_add','Add an ip block range','ona/block.inc.php'),
(28,'block_del','Delete an ip block','ona/block.inc.php'),
(29,'block_modify','Modify ip blocks','ona/block.inc.php'),
(30,'config_add','Adds a configuration to the database','ona/configuration.inc.php'),
(31,'config_chksum','Displays the chksum of a config record from the database','ona/configuration.inc.php'),
(32,'config_display','Displays a config record from the database','ona/configuration.inc.php'),
(33,'dhcp_server_add','Add a DHCP server to subnet relationship','ona/dhcp_server.inc.php'),
(34,'dhcp_server_del','Delete a DHCP server to subnet relationship','ona/dhcp_server.inc.php'),
(35,'dhcp_build_conf','Build an ISC dhcp config file','build/build_dhcp.inc.php'),
(36,'dns_record_add','Add a DNS record','ona/dns_record.inc.php'),
(37,'dns_record_display','Display info about a DNS record','ona/dns_record.inc.php'),
(38,'dns_record_del','Delete a DNS record','ona/dns_record.inc.php'),
(39,'dns_record_modify','Modify a DNS record','ona/dns_record.inc.php'),
(40,'build_zone','Build DNS zone file','build/build_dns.inc.php'),
(41,'domain_server_add','Add a DNS domain to a server','ona/domain_server.inc.php'),
(42,'domain_server_del','Delete a DNS domain from a server','ona/domain_server.inc.php'),
(43,'dhcp_failover_group_del','Delete a DHCP failover group','ona/dhcp_failover.inc.php'),
(44,'interface_move_host','Moves an interface from one host to another','ona/interface.inc.php'),
(45,'interface_share','Share an existing interface with another host','ona/interface.inc.php'),
(46,'interface_share_del','Delete an interface share entry','ona/interface.inc.php'),
(47,'vlan_campus_add','Add a VLAN campus (VTP Domain)','ona/vlan_campus.inc.php'),
(48,'vlan_campus_del','Delete a VLAN campus','ona/vlan_campus.inc.php'),
(49,'vlan_campus_modify','Modify a VLAN campus record','ona/vlan_campus.inc.php'),
(50,'vlan_add','Add a VLAN','ona/vlan.inc.php'),
(51,'vlan_del','Delete a VLAN','ona/vlan.inc.php'),
(52,'vlan_modify','Modify a VLAN','ona/vlan.inc.php'),
(53, 'dhcp_failover_group_add', 'Add servers to a DHCP failover group', 'ona/dhcp_failover.inc.php'),
(54, 'dhcp_failover_group_modify', 'Modify a DHCP failover group', 'ona/dhcp_failover.inc.php'),
(55, 'dhcp_failover_group_display', 'Display a DHCP failover group', 'ona/dhcp_failover.inc.php'),
(56, 'config_diff', 'Display unix diff of configs', 'ona/configuration.inc.php'),
(57, 'nat_add', 'Add external NAT IP to existing internal IP', 'ona/interface.inc.php'),
(58, 'nat_del', 'Delete external NAT IP from existing internal IP', 'ona/interface.inc.php');
/*!40000 ALTER TABLE `dcm_module_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `defaults`
--

LOCK TABLES `defaults` WRITE;
/*!40000 ALTER TABLE `defaults` DISABLE KEYS */;
/*!40000 ALTER TABLE `defaults` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `device_types`
--

LOCK TABLES `device_types` WRITE;
/*!40000 ALTER TABLE `device_types` DISABLE KEYS */;
INSERT INTO `device_types` VALUES (1,1,1),(2,9,11),(3,2,13),(4,4,2),(5,5,3),(6,9,12);
/*!40000 ALTER TABLE `device_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `dhcp_options`
--

LOCK TABLES `dhcp_options` WRITE;
/*!40000 ALTER TABLE `dhcp_options` DISABLE KEYS */;
INSERT INTO `dhcp_options` VALUES (1,'subnet-mask',1,'I','Subnet Mask',1),(2,'routers',3,'L','Default Gateway',1),(3,'domain-name-servers',6,'L','DNS Name Servers',1),(4,'domain-name',15,'S','Default domain',1),(5,'host-name',12,'S','Host Name',1),(6,'vendor-encapsulated-options',43,'S','Vendor Ecapsulated Options',1),(7,'netbios-name-servers',44,'L','Netbios Name Servers',1),(8,'netbios-node-type',46,'N','Netbios Node Type',1),(9,'netbios-scope',47,'S','Netbios Scope',1),(10,'vendor-class-identifier',60,'S','Vendor Class Identifier',1),(11,'tftp-server-name',66,'S','TFTP Server Name',1),(12,'bootfile-name',67,'S','Bootfile Name',1);
/*!40000 ALTER TABLE `dhcp_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `manufacturers`
--

LOCK TABLES `manufacturers` WRITE;
/*!40000 ALTER TABLE `manufacturers` DISABLE KEYS */;
INSERT INTO `manufacturers` VALUES (7,'Adtran'),(8,'Allied Telesyn'),(9,'Cabletron'),(1,'Cisco'),(5,'Dell'),(10,'Extreme Networks'),(4,'Hewlett Packard'),(6,'IBM'),(2,'Juniper'),(3,'Unknown');
/*!40000 ALTER TABLE `manufacturers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `models`
--

LOCK TABLES `models` WRITE;
/*!40000 ALTER TABLE `models` DISABLE KEYS */;
INSERT INTO `models` VALUES (1,1,'2821',''),(2,4,'dv9000t',''),(3,4,'4000m',''),(4,4,'8000m',''),(5,4,'LJ5000',''),(6,1,'2948G-L3',''),(7,5,'Optiplex GS560',''),(8,9,'24TXM-GLS',''),(9,3,'Unknown',''),(10,6,'Netfinity 2232','');
/*!40000 ALTER TABLE `models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (12,'Bulk loaded'),(13,'laptop'),(11,'Manually loaded'),(3,'printer'),(1,'router'),(4,'server'),(2,'switch'),(7,'wireless access point'),(5,'workstation');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sequences`
--

LOCK TABLES `sequences` WRITE;
/*!40000 ALTER TABLE `sequences` DISABLE KEYS */;
INSERT INTO `sequences` VALUES ('',3),('blocks',4),('configurations',1),('custom_attribute_types',3),('devices',14),('device_types',7),('dhcp_options',14),('dhcp_option_entries',13),('dhcp_pools',3),('dhcp_server_subnets',6),('dns',62),('dns_server_domains',8),('domains',5),('hosts',22),('interfaces',28),('manufacturers',47),('models',11),('roles',14),('subnets',22),('subnet_types',14),('vlans',1),('vlan_campuses',5);
/*!40000 ALTER TABLE `sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `subnet_types`
--

LOCK TABLES `subnet_types` WRITE;
/*!40000 ALTER TABLE `subnet_types` DISABLE KEYS */;
INSERT INTO `subnet_types` VALUES (1,'loopback','Loopback','Loopback Interfaces (mostly for routers)'),(2,'','WAN',''),(7,'','VLAN',''),(10,'p2p','Point-to-Point',''),(11,'','VPN',''),(12,'','Wireless LAN',''),(13,'lan','LAN','Simple LAN');
/*!40000 ALTER TABLE `subnet_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
INSERT INTO `groups` VALUES (5,'dcm.pl','CLI Utility',80),(17,'Default','Default user group',1),(18,'Admin','Admin group',99);
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `group_assignments`
--

LOCK TABLES `group_assignments` WRITE;
/*!40000 ALTER TABLE `group_assignments` DISABLE KEYS */;
INSERT INTO `group_assignments` VALUES (1,17,1),(2,18,2);
/*!40000 ALTER TABLE `group_assignments` ENABLE KEYS */;
UNLOCK TABLES;

-- 
-- Dumping data for table `sys_config`
-- 

INSERT INTO `sys_config` (`name`, `value`, `description`) VALUES 
('cookie_life', '172800', 'Sets the length of the session cookie.  It is in seconds'),
('date_format', 'M jS, g:ia', 'PHP text format for date values'),
('db', '1', 'Log to a sql log (not sure if this is working)'),
('debug', '0', 'Debug output level, the higher the number the more it logs'),
('dns_admin_email', 'hostmaster', 'per RFC 2412, defaults to hostmaster within the domain origin'),
('dns_defaultdomain', 'example.com', 'Default DNS domain name'),
('dns_default_ttl', '86400', 'this is the value of $TTL for the zone, used as the default value'),
('dns_expiry', '3600', 'DNS expire time used in SOA'),
('dns_minimum', '3600', 'DNS minimum TTL time, used as the negative caching value per RFC 2308'),
('dns_primary_master', '', 'The fqdn of your default primary master DNS server, leave blank if not required'),
('dns_refresh', '86400', 'DNS refresh time used in SOA'),
('dns_retry', '3600', 'DNS retry time used in SOA'),
('logfile', '/var/log/ona.log', 'Local filesystem path to log messages'),
('search_results_per_page', '10', 'Sets the amount of rows per page in list items'),
('stdout', '0', 'Flag to allow logging via STDOUT.. This is extreme debugging, not recomended.'),
('suggest_max_results', '10', 'Limits the amount of rows returned by queries. (test impact of changing this first)'),
('syslog', '0', 'Log via syslog, only works if debug is set to 0'),
('version', '', 'Tracks current installed version, used to detect when upgrades should be done.'),
('upgrade_index', '1', 'Tracks current upgrade index, used to perform database upgrades.');

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'guest','098f6bcd4621d373cade4e832627b4f6',0,'2007-10-30 02:55:37','2007-12-02 23:44:21'),(2,'admin','21232f297a57a5a743894a0e4a801fc3',0,'2007-10-30 03:00:17','2007-12-02 22:10:26');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES
(1,'dns_record_add','Add a DNS record'),
(2,'dns_record_modify','Modify a DNS record'),
(3,'dns_record_del','Delete a DNS record'),
(4,'host_add','Add hosts and interfaces'),
(5,'host_modify','Modify hosts (& host classifications)'),
(6,'host_del','Delete hosts'),
(7,'user_admin','User Administrator'),
(8,'subnet_add','Add subnets'),
(9,'subnet_modify','Modify subnets'),
(10,'subnet_del','Delete subnets'),
(11,'interface_modify','Modify interface records'),
(12,'interface_del','Delete interface records'),
(13,'advanced','Advanced Maintenance'),
(14,'host_config_admin','Host config archive admin'),
(15,'template_admin','Template system admin'),
(16,'vlan_add','Add VLANs and VLAN Campuses'),
(17,'vlan_del','Delete VLANs and VLAN Campuses'),
(18,'vlan_modify','Modify VLANs and VLAN Campuses');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `permission_assignments`
--

LOCK TABLES `permission_assignments` WRITE;
/*!40000 ALTER TABLE `permission_assignments` DISABLE KEYS */;
INSERT INTO `permission_assignments` VALUES (1,1,0,18),(2,2,0,18),(3,3,0,18),(4,4,0,18),(5,5,0,18),(6,6,0,18),(7,7,0,18),(8,8,0,18),(9,9,0,18),(10,10,0,18),(11,11,0,18),(12,12,0,18),(13,13,0,18),(14,14,0,18),(15,15,0,18),(16,16,0,18),(17,17,0,18),(18,18,0,18);
/*!40000 ALTER TABLE `permission_assignments` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2007-12-05  4:59:47
