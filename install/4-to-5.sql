ALTER TABLE `dhcp_server_subnets` CHANGE `id` `id` INT( 10 ) UNSIGNED NOT NULL ,
CHANGE `host_id` `host_id` INT( 10 ) UNSIGNED NOT NULL ,
CHANGE `subnet_id` `subnet_id` INT( 10 ) UNSIGNED NOT NULL;

ALTER TABLE `dns_server_domains` CHANGE `id` `id` INT( 10 ) UNSIGNED NOT NULL ,
CHANGE `host_id` `host_id` INT( 10 ) UNSIGNED NOT NULL ,
CHANGE `domain_id` `domain_id` INT( 10 ) UNSIGNED NOT NULL;


