-- Add server_id column
ALTER TABLE `dhcp_option_entries` ADD `server_id` INT( 10 ) UNSIGNED NOT NULL COMMENT 'DHCP entries for a specific server' AFTER `host_id`; 

-- Fixup the default primary host id value in devices table
update devices set primary_host_id = (select hosts.id from hosts where device_id like devices.id);

-- add a rebuild flag for domain servers
ALTER TABLE `dns_server_domains` ADD `rebuild_flag` INT( 1 ) UNSIGNED NOT NULL COMMENT 'Track if this domain needs to be rebuilt on this server';
