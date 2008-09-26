ALTER TABLE `dhcp_server_subnets` CHANGE `id` `id` INT( 10 ) UNSIGNED NOT NULL ,
CHANGE `host_id` `host_id` INT( 10 ) UNSIGNED NOT NULL ,
CHANGE `subnet_id` `subnet_id` INT( 10 ) UNSIGNED NOT NULL;

ALTER TABLE `dns_server_domains` CHANGE `id` `id` INT( 10 ) UNSIGNED NOT NULL ,
CHANGE `host_id` `host_id` INT( 10 ) UNSIGNED NOT NULL ,
CHANGE `domain_id` `domain_id` INT( 10 ) UNSIGNED NOT NULL;

ALTER TABLE `dcm_module_list` DROP `id`;

INSERT INTO `dcm_module_list` (`name` , `description` , `file`)
VALUES ('add_module', 'Register a new DCM module', 'get_module_list.inc.php');

-- this is subject to change.. may not want to deploy this part yet
ALTER TABLE `sys_config` ADD `field_validation_rule` TEXT NOT NULL COMMENT 'Regular expression to validate content of the value column';
ALTER TABLE `sys_config` ADD `editable` TINYINT( 1 ) NOT NULL COMMENT 'Can this record be edited?';
ALTER TABLE `sys_config` ADD `deleteable` TINYINT( 1 ) NOT NULL COMMENT 'Can this record be deleted?';

-- set up the  new sys_config user
