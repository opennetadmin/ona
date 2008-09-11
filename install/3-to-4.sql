ALTER TABLE `dns` ADD `srv_pri` SMALLINT UNSIGNED NOT NULL COMMENT 'SRV priority. RFC 2782',
  ADD `srv_weight` SMALLINT UNSIGNED NOT NULL COMMENT 'SRV weight. RFC 2782',
  ADD `srv_port` SMALLINT UNSIGNED NOT NULL COMMENT 'SRV port. RFC 2782';

-- increment location sequence for good measure to ensure no collisions du to prior bug #27
UPDATE sequences SET seq = seq +10 WHERE name LIKE 'locations'; 

ALTER TABLE `interfaces` ADD `last_response` TIMESTAMP NULL COMMENT 'Last time this IP was communicated with';

ALTER TABLE `dcm_module_list` DROP `id`;

INSERT INTO `dcm_module_list` (`name` , `description` , `file`)
VALUES ('add_module', 'Register a new DCM module', 'get_module_list.inc.php');
