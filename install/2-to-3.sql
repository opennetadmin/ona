-- add primary_host_id to devices
ALTER TABLE `devices` ADD `primary_host_id` INT( 10 ) UNSIGNED NOT NULL AFTER `location_id` COMMENT 'Tracks the host that references this device by name'

INSERT INTO `dcm_module_list` ( `id` , `name` , `description` , `file` ) VALUES
('58', 'location_add', 'Add a location record', 'ona/location.inc.php'),
('59', 'location_modify', 'Modify a location record', 'ona/location.inc.php'),
('60', 'location_del', 'Delete a location', 'ona/location.inc.php');

INSERT INTO `permissions` ( `id` , `name` , `description` ) VALUES
( '19', 'location_del', 'Delete a location'),
( '20', 'location_add', 'Add a location');

INSERT INTO `permission_assignments` ( `id` , `perm_id` , `user_id` , `group_id` ) VALUES
('23', '19', '0', '18'),
('24', '20', '0', '18');
