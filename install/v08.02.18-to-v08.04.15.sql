INSERT INTO `sys_config` (`name`, `value`, `description`) VALUES
('version', 'v08.04.15', 'Tracks current installed version, used to detect when upgrades should be done.'),
('upgrade_index', '1', 'Tracks current upgrade index, used to process database upgrades in order.');

INSERT INTO `permissions` (`id`, `name`, `description`) VALUES 
(16, 'vlan_add', 'Add VLANs and VLAN Campuses'),
(17, 'vlan_del', 'Delete VLANs and VLAN Campuses'),
(18, 'vlan_modify', 'Modify VLANs and VLAN Campuses');
