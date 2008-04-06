--
-- Table structure for table `sys_config`
--

CREATE TABLE `sys_config` (
  `name` varchar(128) NOT NULL,
  `value` varchar(256) NOT NULL,
  `description` varchar(512) NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `custom_attribute_types`
--

CREATE TABLE `custom_attribute_types` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(63) NOT NULL,
  `notes` varchar(127) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='';

--
-- Table structure for table `custom_attributes`
--

CREATE TABLE `custom_attributes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `table_name_ref` varchar(40) NOT NULL COMMENT 'the name of the table conaining the associated record',
  `table_id_ref` int(10) unsigned NOT NULL default '0' COMMENT 'the id within the table_name_ref table to associate with',
  `custom_attribute_type_id` int(10) NOT NULL,
  `attribute` longtext NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='';


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
('version', '', 'Tracks current installed version, used to detect when upgrades should be done.');
('upgrade_index', '1', 'Tracks current upgrade index, used to process database upgrades in order.');


INSERT INTO `dcm_module_list` VALUES (53, 'dhcp_failover_group_add', 'Add servers to a DHCP failover group', 'ona/dhcp_failover.inc.php'),(54, 'dhcp_failover_group_modify', 'Modify a DHCP failover group', 'ona/dhcp_failover.inc.php'),(55, 'dhcp_failover_group_display', 'Display a DHCP failover group', 'ona/dhcp_failover.inc.php');

