ALTER TABLE `dhcp_server_subnets` CHANGE `id` `id` INT( 10 ) UNSIGNED NOT NULL ,
CHANGE `host_id` `host_id` INT( 10 ) UNSIGNED NOT NULL ,
CHANGE `subnet_id` `subnet_id` INT( 10 ) UNSIGNED NOT NULL;

ALTER TABLE `dns_server_domains` CHANGE `id` `id` INT( 10 ) UNSIGNED NOT NULL ,
CHANGE `host_id` `host_id` INT( 10 ) UNSIGNED NOT NULL ,
CHANGE `domain_id` `domain_id` INT( 10 ) UNSIGNED NOT NULL;

ALTER TABLE `dcm_module_list` DROP `id`;

INSERT INTO `dcm_module_list` (`name` , `description` , `file`)
VALUES ('add_module', 'Register a new DCM module', 'get_module_list.inc.php');

