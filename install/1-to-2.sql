-- This is to upgrade the database info to version 08.05.14

DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `id` int(10) unsigned NOT NULL,
  `reference` varchar(10) NOT NULL,
  `name` varchar(63) NOT NULL,
  `address` varchar(63) NOT NULL,
  `city` varchar(63) NOT NULL,
  `state` varchar(31) NOT NULL,
  `zip_code` int(5) unsigned NOT NULL,
  `latitude` varchar(20) NOT NULL,
  `longitude` varchar(20) NOT NULL,
  `misc` varchar(191) NOT NULL COMMENT 'Misc info, site contacts, phone numbers etc.',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table needs re-worked';


INSERT INTO `dcm_module_list` VALUES (56, 'config_diff', 'Display unix diff of configs', 'ona/configuration.inc.php');
INSERT INTO `dcm_module_list` VALUES (57, 'nat_add', 'Add external NAT IP to existing internal IP', 'ona/interface.inc.php');
INSERT INTO `dcm_module_list` VALUES (58, 'nat_del', 'Delete external NAT IP from existing internal IP', 'ona/interface.inc.php');

ALTER TABLE `interfaces` ADD `nat_interface_id` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID of external interface that this interface is NATed to' AFTER `host_id` ;

UPDATE `devices` SET `location_id` = 0;
