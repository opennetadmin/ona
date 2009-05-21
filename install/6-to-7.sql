-- Create the block table if it is not there.. fixes bug in v09.05.02
CREATE TABLE IF NOT EXISTS `blocks` (
  `id` int(10) unsigned NOT NULL,
  `ip_addr_start` int(10) unsigned NOT NULL,
  `ip_addr_end` int(10) unsigned NOT NULL,
  `name` varchar(63) NOT NULL,
  `notes` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='User Defined IP Address Ranges';

