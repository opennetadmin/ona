alter table interfaces add `ip_addr_inet` varbinary(16) default NULL;
CREATE INDEX domains_name_index ON domains (name);
CREATE INDEX dns_name_index ON dns (name);
CREATE INDEX type_index ON dns (type);
INSERT INTO interfaces (id, subnet_id, host_id, nat_interface_id, ip_addr, mac_addr, name, description, last_response, ip_addr_inet) VALUES (0, 0, 0, 0, 0, '0', '0', '0', '2018-04-20 15:14:24', null);
