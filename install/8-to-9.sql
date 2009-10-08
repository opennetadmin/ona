-- create new log table for storing logs from printmsg function.  Only level 0 messages will be logged
CREATE TABLE `ona`.`ona_logs` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'timestamp',
`username` VARCHAR( 25 ) NOT NULL COMMENT 'User who logged message',
`remote_addr` VARCHAR( 50 ) NOT NULL COMMENT 'IP or hostname user connected from',
`message` VARCHAR( 1023 ) NOT NULL COMMENT 'the message itself'
) ENGINE = InnoDB COMMENT = 'Stores log messages from printmsg function if enabled. Only level 0 messages.';

-- add the log_to_db option
INSERT INTO `sys_config` ( `name` , `value` , `description` , `field_validation_rule` , `editable` , `deleteable` , `failed_rule_text` )
VALUES ( 'log_to_db', '0', 'Log only level 0 messages to the database.', '', '1', '0', '');
