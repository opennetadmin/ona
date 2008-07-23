-- This is to upgrade the database info to version XXX

CREATE TABLE `contexts` (
`id` INT( 10 ) NOT NULL ,
`name` VARCHAR( 63 ) NOT NULL ,
`description` VARCHAR( 127 ) NOT NULL ,
`color` VARCHAR( 10 ) NOT NULL COMMENT 'define a color to visualy represent this context',
PRIMARY KEY ( `id` )
) ENGINE = innodb COMMENT = 'Allows for two sets of data with similar values.';

-- add primary_host_id to devices
ALTER TABLE `devices`
 ADD `primary_host_id` INT( 10 ) NOT NULL COMMENT 'Tracks the host that references this device by name' AFTER `location_id` ;

ALTER TABLE `custom_attribute_types` 
 ADD `field_validation_rule` TEXT NOT NULL COMMENT 'Use a regular expression to validate the data associated with this type',
 ADD `failed_rule_text`      TEXT NOT NULL COMMENT 'The text that its presented when the field validation rule fails.';

ALTER TABLE `custom_attributes` CHANGE `attribute` `value` LONGTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

ALTER TABLE `dns_server_domains`
 CHANGE `authoritative` `role` VARCHAR(10) NOT NULL COMMENT 'What role does this server play for this domain? master, slave, forward?';

ALTER TABLE `locations`  COMMENT = 'Stores basic location information for devices.';

INSERT INTO `dcm_module_list` ( `id` , `name` , `description` , `file` ) VALUES
('59', 'location_add', 'Add a location record', 'ona/location.inc.php'),
('60', 'location_modify', 'Modify a location record', 'ona/location.inc.php'),
('61', 'location_del', 'Delete a location', 'ona/location.inc.php'),
('62', 'custom_attribute_add', 'Add a custom attribute', 'ona/custom_attribute.inc.php'),
('63', 'custom_attribute_del', 'Delete a custom attribute', 'ona/custom_attribute.inc.php'),
('64', 'custom_attribute_modify', 'Modify a custom attribute', 'ona/custom_attribute.inc.php'),
('65', 'ona_sql', 'Perform basic SQL operations on the database', 'sql.inc.php');

INSERT INTO `permissions` ( `id` , `name` , `description` ) VALUES
( '100019', 'location_del', 'Delete a location'),
( '100020', 'location_add', 'Add a location'),
( '100021', 'ona_sql', 'Perform SQL operations on the ONA tables'),
( '100022', 'custom_attribute_add', 'Add custom attribute'),
( '100023', 'custom_attribute_del', 'Delete custom attribute'),
( '100024', 'custom_attribute_modify', 'Modify custom attribute');

INSERT INTO `permission_assignments` ( `id` , `perm_id` , `user_id` , `group_id` ) 
SELECT '100001', '100019', '0', id FROM `groups` WHERE name LIKE 'Admin';

INSERT INTO `permission_assignments` ( `id` , `perm_id` , `user_id` , `group_id` ) 
SELECT '100002', '100020', '0', id FROM `groups` WHERE name LIKE 'Admin';

INSERT INTO `permission_assignments` ( `id` , `perm_id` , `user_id` , `group_id` ) 
SELECT '100003', '100021', '0', id FROM `groups` WHERE name LIKE 'Admin';

INSERT INTO `permission_assignments` ( `id` , `perm_id` , `user_id` , `group_id` ) 
SELECT '100004', '100022', '0', id FROM `groups` WHERE name LIKE 'Admin';

INSERT INTO `permission_assignments` ( `id` , `perm_id` , `user_id` , `group_id` ) 
SELECT '100005', '100023', '0', id FROM `groups` WHERE name LIKE 'Admin';

INSERT INTO `permission_assignments` ( `id` , `perm_id` , `user_id` , `group_id` ) 
SELECT '100006', '100024', '0', id FROM `groups` WHERE name LIKE 'Admin';

DELETE FROM `groups` WHERE `groups`.`id` = 5 LIMIT 1;

UPDATE `dcm_module_list` SET `description` = 'Add a message to a subnet or host that will show on a display page' WHERE `dcm_module_list`.`name` like 'message_add' LIMIT 1 ;
