-- MySQL dump 10.13  Distrib 5.7.20, for Linux (x86_64)
--
-- Host: localhost    Database: ona_default
-- ------------------------------------------------------
-- Server version	5.7.20-0ubuntu0.16.04.1

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
-- Dumping data for table `blocks`
--

LOCK TABLES `blocks` WRITE;
/*!40000 ALTER TABLE `blocks` DISABLE KEYS */;
INSERT INTO `blocks` (`id`, `ip_addr_start`, `ip_addr_end`, `name`, `notes`) VALUES (4,3232235520,3233480703,'ALL-CORP-LANS','Class B for Corp'),(5,3232235796,3232235806,'SERVER-RANGE','Put servers here'),(6,3232235781,3232235785,'LAN-EXAMPLE-RESERVED','example reserved space');
/*!40000 ALTER TABLE `blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `configuration_types`
--

LOCK TABLES `configuration_types` WRITE;
/*!40000 ALTER TABLE `configuration_types` DISABLE KEYS */;
INSERT INTO `configuration_types` (`id`, `name`) VALUES (1,'IOS_CONFIG'),(2,'IOS_VERSION'),(3,'NS_CONFIG'),(4,'NS_VERSION'),(5,'UCS_CONFIG'),(6,'UCS_VERSION');
/*!40000 ALTER TABLE `configuration_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `configurations`
--

LOCK TABLES `configurations` WRITE;
/*!40000 ALTER TABLE `configurations` DISABLE KEYS */;
INSERT INTO `configurations` (`id`, `configuration_type_id`, `host_id`, `md5_checksum`, `config_body`, `ctime`) VALUES (1,1,22,'0c01d6431707f18fa514786ee66c6ca1','service timestamps debug uptime\nservice timestamps log uptime\nno service password-encryption\n!\nhostname router\n!\n!\nip subnet-zero\nip cef\n!\n!\n!\nip ssh time-out 120\nip ssh authentication-retries 3\n!\n!\ninterface Ethernet0/0\n description LAN-EXAMPLE\n ip address 192.168.1.1 255.255.255.0\n!\ninterface Ethernet0/1\n description CORP-DMZ\n ip address 192.168.2.1 255.255.255.0\n!\ninterface Ethernet1/0\n no ip address\n shutdown\n!\ninterface Ethernet1/1\n no ip address\n shutdown\n!\ninterface Ethernet1/2\n no ip address\n shutdown\n!\nrouter eigrp 60\n network 192.168.0.0 0.0.255.255\n auto-summary\n no eigrp log-neighbor-changes\n!\nip classless\nip route 0.0.0.0 0.0.0.0 14.38.1.1\nip http server\n!\n!\nline con 0\n exec-timeout 0 0\nline aux 0\nline vty 0 4\n!\nend\n','2018-01-06 22:02:13'),(2,1,22,'71f65ba78cd70e6c46e04112f6acc584','service timestamps debug uptime\nservice timestamps log uptime\nno service password-encryption\n!\nhostname router\n!\n!\nip subnet-zero\nip cef\n!\n!\n!\nip ssh time-out 120\nip ssh authentication-retries 3\n!\n!\ninterface Ethernet0/0\n description LAN-EXAMPLE\n ip address 192.168.1.1 255.255.255.0\n!\ninterface Ethernet0/1\n description CORP-DMZ\n ip address 192.168.2.1 255.255.255.0\n!\ninterface Ethernet1/0\n no ip address\n shutdown\n!\ninterface Ethernet1/1\n no ip address\n!\ninterface Ethernet1/2\n description A-NEW-DESCRIPTION\n no ip address\n shutdown\n!\nrouter eigrp 60\n network 192.168.0.0 0.0.255.255\n auto-summary\n no eigrp log-neighbor-changes\n!\nip classless\nip route 0.0.0.0 0.0.0.0 14.38.1.1\nip http server\n!\n!\nline con 0\n exec-timeout 0 22\nline aux 0\nline vty 0 4\n!\nend\n','2018-01-06 22:03:55');
/*!40000 ALTER TABLE `configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `custom_attribute_types`
--

LOCK TABLES `custom_attribute_types` WRITE;
/*!40000 ALTER TABLE `custom_attribute_types` DISABLE KEYS */;
INSERT INTO `custom_attribute_types` (`id`, `name`, `notes`, `field_validation_rule`, `failed_rule_text`) VALUES (1,'nmap_scan','Used to determine if this subnet should be scanned by Nmap based tools.','/^[Y|N]$/','Must be either Y or N'),(3,'Asset Tag','Asset tracking tag of physical device','','');
/*!40000 ALTER TABLE `custom_attribute_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `custom_attributes`
--

LOCK TABLES `custom_attributes` WRITE;
/*!40000 ALTER TABLE `custom_attributes` DISABLE KEYS */;
INSERT INTO `custom_attributes` (`id`, `table_name_ref`, `table_id_ref`, `custom_attribute_type_id`, `value`) VALUES (1,'hosts',23,3,'QKRNS731');
/*!40000 ALTER TABLE `custom_attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `dcm_module_list`
--

LOCK TABLES `dcm_module_list` WRITE;
/*!40000 ALTER TABLE `dcm_module_list` DISABLE KEYS */;
INSERT INTO `dcm_module_list` (`name`, `description`, `file`) VALUES ('add_module','Register a new DCM module','get_module_list.inc.php'),('add_permission','Add new security permission','permissions.inc.php'),('block_add','Add an ip block range','ona/block.inc.php'),('block_del','Delete an ip block','ona/block.inc.php'),('block_modify','Modify ip blocks','ona/block.inc.php'),('config_add','Adds a configuration to the database','ona/configuration.inc.php'),('config_chksum','Displays the chksum of a config record from the database','ona/configuration.inc.php'),('config_diff','Display unix diff of configs','ona/configuration.inc.php'),('config_display','Displays a config record from the database','ona/configuration.inc.php'),('custom_attribute_add','Add a custom attribute','ona/custom_attribute.inc.php'),('custom_attribute_del','Delete a custom attribute','ona/custom_attribute.inc.php'),('custom_attribute_display','Display a custom attribute','ona/custom_attribute.inc.php'),('custom_attribute_modify','Modify a custom attribute','ona/custom_attribute.inc.php'),('custom_attribute_type_display','Display a custom attribute type','ona/custom_attribute.inc.php'),('dhcp_entry_add','Add a DHCP option entry','ona/dhcp_entry.inc.php'),('dhcp_entry_del','Delete a DHCP option entry','ona/dhcp_entry.inc.php'),('dhcp_entry_modify','Modify DHCP option entry','ona/dhcp_entry.inc.php'),('dhcp_failover_group_add','Add servers to a DHCP failover group','ona/dhcp_failover.inc.php'),('dhcp_failover_group_del','Delete a DHCP failover group','ona/dhcp_failover.inc.php'),('dhcp_failover_group_display','Display a DHCP failover group','ona/dhcp_failover.inc.php'),('dhcp_failover_group_modify','Modify a DHCP failover group','ona/dhcp_failover.inc.php'),('dhcp_pool_add','Add DHCP pools','ona/dhcp_pool.inc.php'),('dhcp_pool_del','Delete a DHCP pool','ona/dhcp_pool.inc.php'),('dhcp_pool_modify','Modify DHCP pools','ona/dhcp_pool.inc.php'),('dhcp_server_add','Add a DHCP server to subnet relationship','ona/dhcp_server.inc.php'),('dhcp_server_del','Delete a DHCP server to subnet relationship','ona/dhcp_server.inc.php'),('dns_record_add','Add a DNS record','ona/dns_record.inc.php'),('dns_record_del','Delete a DNS record','ona/dns_record.inc.php'),('dns_record_display','Display info about a DNS record','ona/dns_record.inc.php'),('dns_record_modify','Modify a DNS record','ona/dns_record.inc.php'),('domain_add','Adds a domain entry into the IP database','ona/domain.inc.php'),('domain_del','Deletes a domain from the IP database','ona/domain.inc.php'),('domain_display','Displays an existing domain','ona/domain.inc.php'),('domain_modify','Updates an domain record in the IP database','ona/domain.inc.php'),('domain_server_add','Add a DNS domain to a server','ona/domain_server.inc.php'),('domain_server_del','Delete a DNS domain from a server','ona/domain_server.inc.php'),('get_module_list','Returns the list of available modules','get_module_list.inc.php'),('host_add','Add a new host','ona/host.inc.php'),('host_del','Delete an existing host','ona/host.inc.php'),('host_display','Display an existing host','ona/host.inc.php'),('host_modify','Modify an existing host','ona/host.inc.php'),('interface_add','Add an interface to an existing host','ona/interface.inc.php'),('interface_del','Delete an existing interface','ona/interface.inc.php'),('interface_display','Displays details of an existing interface','ona/interface.inc.php'),('interface_modify','Modify an existing interface','ona/interface.inc.php'),('interface_move','Move an interface from one subnet to another','ona/interface.inc.php'),('interface_move_host','Moves an interface from one host to another','ona/interface.inc.php'),('interface_share','Share an existing interface with another host','ona/interface.inc.php'),('interface_share_del','Delete an interface share entry','ona/interface.inc.php'),('location_add','Add a location record','ona/location.inc.php'),('location_del','Delete a location','ona/location.inc.php'),('location_modify','Modify a location record','ona/location.inc.php'),('mangle_ip','Converts between various IP address representations','mangle.inc.php'),('message_add','Add a message to a subnet or host that will show on a display page','ona/message.inc.php'),('mysql_purge_logs','Purges unused replication logs on MySQL masters','mysql_purge_logs.inc.php'),('nat_add','Add external NAT IP to existing internal IP','ona/interface.inc.php'),('nat_del','Delete external NAT IP from existing internal IP','ona/interface.inc.php'),('ona_sql','Perform basic SQL operations on the database','sql.inc.php'),('report_run','Run a report','report_run.inc.php'),('subnet_add','Add a new subnet','ona/subnet.inc.php'),('subnet_del','Delete an existing subnet','ona/subnet.inc.php'),('subnet_display','Display an existing subnet','ona/subnet.inc.php'),('subnet_modify','Modify an existing subnet','ona/subnet.inc.php'),('subnet_nextip','Return the next available IP address on a subnet','ona/subnet.inc.php'),('tag_add','Add a tag to an object','ona/tag.inc.php'),('tag_del','Delete a tag from an object','ona/tag.inc.php'),('vlan_add','Add a VLAN','ona/vlan.inc.php'),('vlan_campus_add','Add a VLAN campus (VTP Domain)','ona/vlan_campus.inc.php'),('vlan_campus_del','Delete a VLAN campus','ona/vlan_campus.inc.php'),('vlan_campus_display','Display a VLAN campus record','ona/vlan_campus.inc.php'),('vlan_campus_modify','Modify a VLAN campus record','ona/vlan_campus.inc.php'),('vlan_del','Delete a VLAN','ona/vlan.inc.php'),('vlan_modify','Modify a VLAN','ona/vlan.inc.php');
/*!40000 ALTER TABLE `dcm_module_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `device_types`
--

LOCK TABLES `device_types` WRITE;
/*!40000 ALTER TABLE `device_types` DISABLE KEYS */;
INSERT INTO `device_types` (`id`, `model_id`, `role_id`) VALUES (1,1,1),(2,9,11),(3,2,13),(4,4,2),(5,5,3),(6,9,12),(7,11,4),(8,12,13);
/*!40000 ALTER TABLE `device_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `devices`
--

LOCK TABLES `devices` WRITE;
/*!40000 ALTER TABLE `devices` DISABLE KEYS */;
INSERT INTO `devices` (`id`, `device_type_id`, `location_id`, `primary_host_id`, `asset_tag`, `serial_number`) VALUES (14,1,1,22,NULL,NULL),(15,8,1,23,NULL,NULL),(16,7,1,24,NULL,NULL);
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `dhcp_failover_groups`
--

LOCK TABLES `dhcp_failover_groups` WRITE;
/*!40000 ALTER TABLE `dhcp_failover_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `dhcp_failover_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `dhcp_option_entries`
--

LOCK TABLES `dhcp_option_entries` WRITE;
/*!40000 ALTER TABLE `dhcp_option_entries` DISABLE KEYS */;
INSERT INTO `dhcp_option_entries` (`id`, `subnet_id`, `host_id`, `server_id`, `dhcp_option_id`, `value`) VALUES (13,22,0,0,2,'192.168.1.1');
/*!40000 ALTER TABLE `dhcp_option_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `dhcp_options`
--

LOCK TABLES `dhcp_options` WRITE;
/*!40000 ALTER TABLE `dhcp_options` DISABLE KEYS */;
INSERT INTO `dhcp_options` (`id`, `name`, `number`, `type`, `display_name`, `sys_default`) VALUES (1,'subnet-mask',1,'I','Subnet Mask',1),(2,'routers',3,'L','Default Gateway',1),(3,'domain-name-servers',6,'L','DNS Name Servers',1),(4,'domain-name',15,'S','Default domain',1),(5,'host-name',12,'S','Host Name',1),(6,'vendor-encapsulated-options',43,'S','Vendor Ecapsulated Options',1),(7,'netbios-name-servers',44,'L','Netbios Name Servers',1),(8,'netbios-node-type',46,'N','Netbios Node Type',1),(9,'netbios-scope',47,'S','Netbios Scope',1),(10,'vendor-class-identifier',60,'S','Vendor Class Identifier',1),(11,'tftp-server-name',66,'S','TFTP Server Name',1),(12,'bootfile-name',67,'S','Bootfile Name',1);
/*!40000 ALTER TABLE `dhcp_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `dhcp_pools`
--

LOCK TABLES `dhcp_pools` WRITE;
/*!40000 ALTER TABLE `dhcp_pools` DISABLE KEYS */;
INSERT INTO `dhcp_pools` (`id`, `subnet_id`, `dhcp_failover_group_id`, `ip_addr_start`, `ip_addr_end`, `lease_length`, `lease_grace_period`, `lease_renewal_time`, `lease_rebind_time`, `allow_bootp_clients`) VALUES (3,22,0,3232235876,3232236030,604800,0,0,0,0);
/*!40000 ALTER TABLE `dhcp_pools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `dhcp_server_subnets`
--

LOCK TABLES `dhcp_server_subnets` WRITE;
/*!40000 ALTER TABLE `dhcp_server_subnets` DISABLE KEYS */;
INSERT INTO `dhcp_server_subnets` (`id`, `host_id`, `subnet_id`) VALUES (6,24,22);
/*!40000 ALTER TABLE `dhcp_server_subnets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `dns`
--

LOCK TABLES `dns` WRITE;
/*!40000 ALTER TABLE `dns` DISABLE KEYS */;
INSERT INTO `dns` (`id`, `domain_id`, `interface_id`, `dns_id`, `type`, `ttl`, `name`, `ebegin`, `notes`, `mx_preference`, `txt`, `srv_pri`, `srv_weight`, `srv_port`, `dns_view_id`) VALUES (62,1,28,0,'A',0,'router','2018-01-05 05:24:37','',0,'',0,0,0,0),(63,5,28,62,'PTR',0,'','2018-01-05 05:24:37','',0,'',0,0,0,0),(64,1,29,0,'A',0,'laptop01','2018-01-05 05:27:16','',0,'',0,0,0,0),(65,5,29,64,'PTR',0,'','2018-01-05 05:27:16','',0,'',0,0,0,0),(66,1,30,0,'A',0,'server','2018-01-06 22:06:57','Primary name for this server used for searching in ONA',0,'',0,0,0,0),(67,5,30,66,'PTR',0,'','2018-01-05 05:34:23','',0,'',0,0,0,0),(68,1,30,66,'NS',0,'','2018-01-06 22:06:27','NS record since this is a DNS server',0,'',0,0,0,0),(69,5,31,62,'PTR',0,'','2018-01-05 05:48:31','',0,'',0,0,0,0),(70,5,32,66,'PTR',0,'','2018-01-05 05:49:14','',0,'',0,0,0,0),(71,6,33,66,'PTR',0,'','2018-01-05 06:10:59','',0,'',0,0,0,0),(72,1,32,0,'A',0,'www','2018-01-05 06:13:29','internal dns record for the website',0,'',0,0,0,0),(73,1,30,66,'MX',0,'mail','2018-01-05 06:14:43','MX record for mail services',10,'',0,0,0,0);
/*!40000 ALTER TABLE `dns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `dns_server_domains`
--

LOCK TABLES `dns_server_domains` WRITE;
/*!40000 ALTER TABLE `dns_server_domains` DISABLE KEYS */;
INSERT INTO `dns_server_domains` (`id`, `host_id`, `domain_id`, `role`, `rebuild_flag`) VALUES (8,24,1,'master',1);
/*!40000 ALTER TABLE `dns_server_domains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `dns_views`
--

LOCK TABLES `dns_views` WRITE;
/*!40000 ALTER TABLE `dns_views` DISABLE KEYS */;
INSERT INTO `dns_views` (`id`, `name`, `description`) VALUES (0,'DEFAULT','Default view for dns records');
/*!40000 ALTER TABLE `dns_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `domains`
--

LOCK TABLES `domains` WRITE;
/*!40000 ALTER TABLE `domains` DISABLE KEYS */;
INSERT INTO `domains` (`id`, `parent_id`, `ctime`, `serial`, `refresh`, `retry`, `expiry`, `minimum`, `default_ttl`, `primary_master`, `admin_email`, `name`) VALUES (1,0,'2018-01-07 02:10:17',0,86400,3600,3600,3600,86400,'server.example.com','hostmaster','example.com'),(5,0,'2018-01-05 05:24:35',15052435,86400,3600,3600,3600,86400,'','hostmaster','192.in-addr.arpa'),(6,0,'2018-01-05 06:10:49',15061049,86400,3600,3600,3600,86400,'','hostmaster','250.in-addr.arpa');
/*!40000 ALTER TABLE `domains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `group_assignments`
--

LOCK TABLES `group_assignments` WRITE;
/*!40000 ALTER TABLE `group_assignments` DISABLE KEYS */;
INSERT INTO `group_assignments` (`id`, `group_id`, `user_id`) VALUES (1,17,1),(2,18,2);
/*!40000 ALTER TABLE `group_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `groups`
--

LOCK TABLES `auth_groups` WRITE;
/*!40000 ALTER TABLE `auth_groups` DISABLE KEYS */;
INSERT INTO `auth_groups` (`id`, `name`, `description`, `level`) VALUES (17,'Default','Default user group',1),(18,'Admin','Admin group',99);
/*!40000 ALTER TABLE `auth_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `hosts`
--

LOCK TABLES `hosts` WRITE;
/*!40000 ALTER TABLE `hosts` DISABLE KEYS */;
INSERT INTO `hosts` (`id`, `parent_id`, `primary_dns_id`, `device_id`, `notes`) VALUES (22,0,62,14,'This is the router for the office'),(23,0,64,15,'Example desktop/laptop device'),(24,0,66,16,'Example DNS and DHCP server');
/*!40000 ALTER TABLE `hosts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `interface_clusters`
--

LOCK TABLES `interface_clusters` WRITE;
/*!40000 ALTER TABLE `interface_clusters` DISABLE KEYS */;
/*!40000 ALTER TABLE `interface_clusters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `interfaces`
--

LOCK TABLES `interfaces` WRITE;
/*!40000 ALTER TABLE `interfaces` DISABLE KEYS */;
INSERT INTO `interfaces` (`id`, `subnet_id`, `host_id`, `nat_interface_id`, `ip_addr`, `mac_addr`, `name`, `description`, `last_response`) VALUES (28,22,22,0,3232235777,'','Ethernet0/0','',NULL),(29,22,23,0,3232235786,'784F43679B70','','Static MAC based IP address, Assigned by DHCP',NULL),(30,22,24,0,3232235796,'','eth0','Interface for DNS and DHCP services',NULL),(31,24,22,0,3232236033,'','Ethernet0/1','',NULL),(32,24,24,33,3232236042,'','eth0:websvc','Interface for public web site, with NAT',NULL),(33,23,24,0,4194370123,'','','EXT NAT',NULL);
/*!40000 ALTER TABLE `interfaces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
INSERT INTO `locations` (`id`, `reference`, `name`, `address`, `city`, `state`, `zip_code`, `latitude`, `longitude`, `misc`) VALUES (1,'CORP','Primary Office','123 Main St','Boise','ID',83706,'','','Main Phone: 208-123-4456\nContact: John Doe');
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `manufacturers`
--

LOCK TABLES `manufacturers` WRITE;
/*!40000 ALTER TABLE `manufacturers` DISABLE KEYS */;
INSERT INTO `manufacturers` (`id`, `name`) VALUES (7,'Adtran'),(8,'Allied Telesyn'),(48,'Apple'),(9,'Cabletron'),(1,'Cisco'),(5,'Dell'),(10,'Extreme Networks'),(4,'Hewlett Packard'),(6,'IBM'),(2,'Juniper'),(3,'Unknown'),(47,'VMware');
/*!40000 ALTER TABLE `manufacturers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `models`
--

LOCK TABLES `models` WRITE;
/*!40000 ALTER TABLE `models` DISABLE KEYS */;
INSERT INTO `models` (`id`, `manufacturer_id`, `name`, `snmp_sysobjectid`) VALUES (1,1,'2821',''),(2,4,'dv9000t',''),(3,4,'4000m',''),(4,4,'8000m',''),(5,4,'LJ5000',''),(6,1,'2948G-L3',''),(7,5,'Optiplex GS560',''),(8,9,'24TXM-GLS',''),(9,3,'Unknown',''),(10,6,'Netfinity 2232',''),(11,47,'Virtual',''),(12,48,'MacBook Pro','');
/*!40000 ALTER TABLE `models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ona_logs`
--

LOCK TABLES `ona_logs` WRITE;
/*!40000 ALTER TABLE `ona_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ona_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `permission_assignments`
--

LOCK TABLES `permission_assignments` WRITE;
/*!40000 ALTER TABLE `permission_assignments` DISABLE KEYS */;
INSERT INTO `permission_assignments` (`id`, `perm_id`, `user_id`, `group_id`) VALUES (1,1,2,18),(2,2,2,18),(3,3,2,18),(4,4,2,18),(5,5,2,18),(6,6,2,18),(7,7,2,18),(8,8,2,18),(9,9,2,18),(10,10,2,18),(11,11,2,18),(12,12,2,18),(13,13,2,18),(14,14,2,18),(15,15,2,18),(16,16,2,18),(17,17,2,18),(18,18,2,18),(100001,100019,2,18),(100002,100020,2,18),(100003,100021,2,18),(100004,100022,2,18),(100005,100023,2,18),(100006,100024,2,18);
/*!40000 ALTER TABLE `permission_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` (`id`, `name`, `description`) VALUES (1,'dns_record_add','Add a DNS record'),(2,'dns_record_modify','Modify a DNS record'),(3,'dns_record_del','Delete a DNS record'),(4,'host_add','Add hosts and interfaces'),(5,'host_modify','Modify hosts'),(6,'host_del','Delete hosts'),(7,'user_admin','User Administrator'),(8,'subnet_add','Add subnets'),(9,'subnet_modify','Modify subnets'),(10,'subnet_del','Delete subnets'),(11,'interface_modify','Modify interface records'),(12,'interface_del','Delete interface records'),(13,'advanced','Advanced Maintenance'),(14,'host_config_admin','Host config archive admin'),(15,'template_admin','Template system admin'),(16,'vlan_add','Add VLANs and VLAN Campuses'),(17,'vlan_del','Delete VLANs and VLAN Campuses'),(18,'vlan_modify','Modify VLANs and VLAN Campuses'),(100019,'location_del','Delete a location'),(100020,'location_add','Add a location'),(100021,'ona_sql','Perform SQL operations on the ONA tables'),(100022,'custom_attribute_add','Add custom attribute'),(100023,'custom_attribute_del','Delete custom attribute'),(100024,'custom_attribute_modify','Modify custom attribute');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` (`id`, `name`) VALUES (12,'Bulk loaded'),(13,'laptop'),(11,'Manually loaded'),(3,'printer'),(1,'router'),(4,'server'),(2,'switch'),(7,'wireless access point'),(5,'workstation');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sequences`
--

LOCK TABLES `sequences` WRITE;
/*!40000 ALTER TABLE `sequences` DISABLE KEYS */;
INSERT INTO `sequences` (`name`, `seq`) VALUES ('blocks',7),('configurations',3),('configuration_types',5),('custom_attribute_types',4),('devices',17),('device_types',9),('dhcp_options',14),('dhcp_option_entries',14),('dhcp_pools',4),('dhcp_server_subnets',7),('dns',74),('dns_server_domains',9),('domains',7),('hosts',25),('interfaces',34),('locations',2),('manufacturers',49),('models',13),('permissions',100),('roles',14),('subnets',25),('subnet_types',15),('tags',3),('vlans',2),('vlan_campuses',6);
/*!40000 ALTER TABLE `sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `subnet_types`
--

LOCK TABLES `subnet_types` WRITE;
/*!40000 ALTER TABLE `subnet_types` DISABLE KEYS */;
INSERT INTO `subnet_types` (`id`, `short_name`, `display_name`, `notes`) VALUES (1,'loopback','Loopback','Loopback Interfaces (mostly for routers)'),(2,'','WAN',''),(7,'','VLAN',''),(10,'p2p','Point-to-Point',''),(11,'','VPN',''),(12,'','Wireless LAN',''),(13,'lan','LAN','Simple LAN'),(14,'nat','NAT','Subnet used for NAT translation mapping');
/*!40000 ALTER TABLE `subnet_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `subnets`
--

LOCK TABLES `subnets` WRITE;
/*!40000 ALTER TABLE `subnets` DISABLE KEYS */;
INSERT INTO `subnets` (`id`, `subnet_type_id`, `vlan_id`, `ip_addr`, `ip_mask`, `name`) VALUES (22,7,1,3232235776,4294967040,'LAN-EXAMPLE'),(23,14,0,4194370048,4294967040,'PUBLIC-NAT'),(24,7,0,3232236032,4294967040,'CORP-DMZ');
/*!40000 ALTER TABLE `subnets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_config`
--

LOCK TABLES `sys_config` WRITE;
/*!40000 ALTER TABLE `sys_config` DISABLE KEYS */;
INSERT INTO `sys_config` (`name`, `value`, `description`, `field_validation_rule`, `editable`, `deleteable`, `failed_rule_text`) VALUES ('authtype','local','Define standard authentication module to use','',1,0,''),('build_dhcp_type','isc','DHCP build type',NULL,1,1,NULL),('build_dns_type','bind','DNS build type',NULL,1,1,NULL),('cookie_life','172800','Sets the length of the session cookie.  It is in seconds','',1,0,''),('date_format','M jS, g:ia','PHP text format for date values','',1,0,''),('debug','0','Debug output level, the higher the number the more it logs','',1,0,''),('disable_guest','0','Disable the autologin of the guest user.','',1,0,''),('dns_admin_email','hostmaster','per RFC 2412, defaults to hostmaster within the domain origin','',1,0,''),('dns_defaultdomain','example.com','Default DNS domain name','',1,0,''),('dns_default_ttl','86400','this is the value of $TTL for the zone, used as the default value','',1,0,''),('dns_expiry','3600','DNS expire time used in SOA','',1,0,''),('dns_minimum','3600','DNS minimum TTL time, used as the negative caching value per RFC 2308','',1,0,''),('dns_primary_master','server.example.com','The fqdn of your default primary master DNS server, leave blank if not required','',1,0,''),('dns_refresh','86400','DNS refresh time used in SOA','',1,0,''),('dns_retry','3600','DNS retry time used in SOA','',1,0,''),('dns_views','0','Enable support for DNS views.','',0,0,''),('logfile','/var/log/ona.log','Local filesystem path to log messages','',1,0,''),('log_to_db','0','Log only level 0 messages to the database.','',1,0,''),('search_results_per_page','10','Sets the amount of rows per page in list items','',1,0,''),('stdout','0','Flag to allow logging via STDOUT.. This is extreme debugging, not recomended.','',0,0,''),('suggest_max_results','10','Limits the amount of rows returned by queries. (test impact of changing this first)','',1,0,''),('syslog','0','Log via syslog, only works if debug is set to 0','',0,0,''),('upgrade_index','13','Tracks current upgrade index, used to perform database upgrades.','',0,0,''),('version','v18.1.1','Tracks current installed version, used to detect when upgrades should be done.','',0,0,'');
/*!40000 ALTER TABLE `sys_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` (`id`, `name`, `type`, `reference`) VALUES (1,'Webserver','host',24),(2,'Prod','host',24);
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `username`, `password`, `level`, `ctime`, `atime`) VALUES (1,'guest','098f6bcd4621d373cade4e832627b4f6',0,'2018-01-06 04:50:42','2018-01-06 04:50:42'),(2,'admin','21232f297a57a5a743894a0e4a801fc3',0,'2018-01-05 06:10:17','2018-01-05 06:10:17');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `vlan_campuses`
--

LOCK TABLES `vlan_campuses` WRITE;
/*!40000 ALTER TABLE `vlan_campuses` DISABLE KEYS */;
INSERT INTO `vlan_campuses` (`id`, `name`) VALUES (5,'CORPORATE');
/*!40000 ALTER TABLE `vlan_campuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `vlans`
--

LOCK TABLES `vlans` WRITE;
/*!40000 ALTER TABLE `vlans` DISABLE KEYS */;
INSERT INTO `vlans` (`id`, `vlan_campus_id`, `name`, `number`) VALUES (1,5,'EXAMPLE-VLAN',23);
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

-- Dump completed on 2018-01-07  2:19:49
