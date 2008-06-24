-- add primary_host_id to devices
ALTER TABLE `devices`
 ADD `primary_host_id` INT( 10 ) UNSIGNED NOT NULL AFTER `location_id` COMMENT 'Tracks the host that references this device by name';

ALTER TABLE `custom_attribute_types` 
 ADD `field_validation_rule` TEXT NOT NULL COMMENT 'Use a regular expression to validate the data associated with this type',
 ADD `failed_rule_text`      TEXT NOT NULL COMMENT 'The text that its presented when the field validation rule fails.';

ALTER TABLE `custom_attributes` CHANGE `attribute` `value` LONGTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

INSERT INTO `dcm_module_list` ( `id` , `name` , `description` , `file` ) VALUES
('59', 'location_add', 'Add a location record', 'ona/location.inc.php'),
('60', 'location_modify', 'Modify a location record', 'ona/location.inc.php'),
('61', 'location_del', 'Delete a location', 'ona/location.inc.php'),
('62', 'custom_attribute_add', 'Add a custom attribute', 'ona/custom_attribute.inc.php'),
('63', 'custom_attribute_del', 'Delete a custom attribute', 'ona/custom_attribute.inc.php'),
('64', 'custom_attribute_modify', 'Modify a custom attribute', 'ona/custom_attribute.inc.php');

INSERT INTO `permissions` ( `id` , `name` , `description` ) VALUES
( '19', 'location_del', 'Delete a location'),
( '20', 'location_add', 'Add a location');

INSERT INTO `permission_assignments` ( `id` , `perm_id` , `user_id` , `group_id` ) VALUES
('23', '19', '0', '18'),
('24', '20', '0', '18');


